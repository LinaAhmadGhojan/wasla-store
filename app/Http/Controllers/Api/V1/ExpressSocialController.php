<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpressFavorite;
use App\Models\ExpressMenuItem;
use App\Models\ExpressQuestion;
use App\Models\ExpressReview;
use App\Models\ExpressStore;
use App\Models\ExpressStoreFollow;
use App\Services\Express\ExpressBrowseService;
use App\Services\Express\ExpressCache;
use App\Services\StoreSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class ExpressSocialController extends Controller
{
    public function __construct(private ExpressBrowseService $browse)
    {
    }

    public function reviews(ExpressMenuItem $item, Request $request)
    {
        $sort = $request->query('sort', 'newest');
        $query = ExpressReview::query()
            ->with('user:id,name,email')
            ->where('express_menu_item_id', $item->id)
            ->where('status', ExpressReview::STATUS_APPROVED);

        if ($sort === 'highest') {
            $query->orderByDesc('rating')->orderByDesc('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        $reviews = $query->paginate(12)->through(fn (ExpressReview $r) => $this->serializeReview($r));

        $user = $request->user('sanctum') ?? $this->userFromBearer($request);
        $canReview = false;
        if ($user) {
            $canReview = ! ExpressReview::query()
                ->where('user_id', $user->id)
                ->where('express_menu_item_id', $item->id)
                ->exists();
        }

        return response()->json([
            'summary' => [
                'average' => (float) ($item->rating ?? 0),
                'count' => (int) ($item->total_reviews ?? 0),
                'label' => 'تقييم طلباتي ',
            ],
            'rewards' => app(StoreSettings::class)->reviewRewards(),
            'can_review' => $canReview,
            'reviews' => $reviews,
        ]);
    }

    public function storeReview(Request $request, ExpressMenuItem $item)
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string|max:2000',
            'image' => 'nullable|image|max:5120',
        ]);

        $user = $request->user();
        $exists = ExpressReview::query()
            ->where('user_id', $user->id)
            ->where('express_menu_item_id', $item->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'قيّمتِ هذا الطبق مسبقاً.'], 422);
        }

        $imagePath = null;
        if ($request->file('image')) {
            $stored = $request->file('image')->store('express-reviews', 'public');
            $imagePath = '/storage/'.$stored;
        }

        $rewards = app(StoreSettings::class)->reviewRewards();
        $reward = $imagePath
            ? (int) ($rewards['with_photo_syp'] ?? 1000)
            : (int) ($rewards['text_syp'] ?? 500);
        if (! filled($data['body'] ?? null) && ! $imagePath) {
            $reward = 0;
        }

        $review = DB::transaction(function () use ($user, $item, $data, $imagePath, $reward) {
            $review = ExpressReview::query()->create([
                'user_id' => $user->id,
                'express_menu_item_id' => $item->id,
                'express_store_id' => $item->express_store_id,
                'rating' => (int) $data['rating'],
                'body' => $data['body'] ?? null,
                'image_path' => $imagePath,
                'reward_syp' => $reward,
                'status' => ExpressReview::STATUS_APPROVED,
            ]);

            if ($reward > 0) {
                $user->increment('store_credit_syp', $reward);
            }

            $this->recalculateItemRating($item);
            if ($item->store) {
                $this->recalculateStoreRating($item->store);
            }

            return $review;
        });

        ExpressCache::bump();

        return response()->json([
            'ok' => true,
            'message' => $review->reward_syp > 0
                ? 'شكراً لتقييمك! أُضيف '.number_format($review->reward_syp).' ل.س لرصيدك.'
                : 'شكراً لتقييمك!',
            'review' => $this->serializeReview($review->load('user')),
            'summary' => [
                'average' => (float) $item->fresh()->rating,
                'count' => (int) $item->fresh()->total_reviews,
                'label' => 'تقييم طلباتي ',
            ],
        ], 201);
    }

    public function questions(ExpressMenuItem $item)
    {
        $rows = ExpressQuestion::query()
            ->where('express_menu_item_id', $item->id)
            ->where('is_public', true)
            ->whereNotNull('answer')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ExpressQuestion $q) => [
                'id' => $q->id,
                'question' => $q->question,
                'answer' => $q->answer,
                'answered_at' => $q->answered_at?->toIso8601String(),
                'created_at' => $q->created_at?->toIso8601String(),
            ]);

        return response()->json(['questions' => $rows]);
    }

    public function storeQuestion(Request $request, ExpressMenuItem $item)
    {
        $data = $request->validate([
            'question' => 'required|string|min:5|max:1000',
        ]);

        $row = ExpressQuestion::query()->create([
            'express_menu_item_id' => $item->id,
            'user_id' => $request->user()->id,
            'question' => $data['question'],
            'is_public' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'تم إرسال سؤالك. سيظهر بعد رد المتجر.',
            'id' => $row->id,
        ], 201);
    }

    public function similar(ExpressMenuItem $item)
    {
        $payload = $this->browse->similar($item->id);

        return response()->json($payload)->header(
            'Cache-Control',
            'public, max-age=30, stale-while-revalidate=60'
        );
    }

    public function favoriteStatus(Request $request, ExpressMenuItem $item)
    {
        $user = $request->user('sanctum') ?? $this->userFromBearer($request);
        $wished = $user
            ? ExpressFavorite::query()->where('user_id', $user->id)->where('express_menu_item_id', $item->id)->exists()
            : false;

        return response()->json(['wished' => $wished]);
    }

    public function favorite(Request $request, ExpressMenuItem $item)
    {
        ExpressFavorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'express_menu_item_id' => $item->id,
        ]);

        return response()->json(['ok' => true, 'wished' => true]);
    }

    public function unfavorite(Request $request, ExpressMenuItem $item)
    {
        ExpressFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where('express_menu_item_id', $item->id)
            ->delete();

        return response()->json(['ok' => true, 'wished' => false]);
    }

    public function followStatus(Request $request, ExpressStore $store)
    {
        $user = $request->user('sanctum') ?? $this->userFromBearer($request);
        $following = $user
            ? ExpressStoreFollow::query()->where('user_id', $user->id)->where('express_store_id', $store->id)->exists()
            : false;

        return response()->json([
            'following' => $following,
            'followers_count' => ExpressStoreFollow::query()->where('express_store_id', $store->id)->count(),
        ]);
    }

    public function follow(Request $request, ExpressStore $store)
    {
        ExpressStoreFollow::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'express_store_id' => $store->id,
        ]);

        return response()->json(['ok' => true, 'following' => true]);
    }

    public function unfollow(Request $request, ExpressStore $store)
    {
        ExpressStoreFollow::query()
            ->where('user_id', $request->user()->id)
            ->where('express_store_id', $store->id)
            ->delete();

        return response()->json(['ok' => true, 'following' => false]);
    }

    private function recalculateItemRating(ExpressMenuItem $item): void
    {
        $agg = ExpressReview::query()
            ->where('express_menu_item_id', $item->id)
            ->where('status', ExpressReview::STATUS_APPROVED)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->first();

        $item->forceFill([
            'rating' => round((float) ($agg->avg_rating ?? 0), 2),
            'total_reviews' => (int) ($agg->cnt ?? 0),
        ])->save();
    }

    private function recalculateStoreRating(ExpressStore $store): void
    {
        $agg = ExpressReview::query()
            ->where('express_store_id', $store->id)
            ->where('status', ExpressReview::STATUS_APPROVED)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->first();

        $store->forceFill([
            'rating' => round((float) ($agg->avg_rating ?? 0), 2),
            'total_reviews' => (int) ($agg->cnt ?? 0),
        ])->save();
    }

    private function serializeReview(ExpressReview $r): array
    {
        return [
            'id' => $r->id,
            'rating' => $r->rating,
            'body' => $r->body,
            'image_url' => $r->image_url,
            'masked_identity' => $r->masked_identity,
            'avatar_letter' => $r->avatar_letter,
            'created_at' => $r->created_at?->toIso8601String(),
            'reward_syp' => $r->reward_syp,
        ];
    }

    private function userFromBearer(Request $request)
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }
        $token = PersonalAccessToken::findToken(substr($header, 7));

        return $token?->tokenable;
    }
}
