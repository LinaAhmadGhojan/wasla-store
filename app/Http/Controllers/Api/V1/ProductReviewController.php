<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Reviews\ProductReviewService;
use App\Services\StoreSettings;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index(Product $product, Request $request)
    {
        $sort = $request->query('sort', 'newest');

        $query = ProductReview::query()
            ->with('user:id,name,email')
            ->where('product_id', $product->id)
            ->where('status', ProductReview::STATUS_APPROVED);

        if ($sort === 'highest') {
            $query->orderByDesc('rating')->orderByDesc('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        $reviews = $query->paginate(12)->through(fn (ProductReview $r) => $this->serialize($r));

        $summary = [
            'average' => (float) ($product->rating ?? 0),
            'count' => (int) ($product->total_reviews ?? 0),
            'label' => 'تقييم الوصلة',
        ];

        $canReview = false;
        $reviewableItemId = null;
        $user = $request->user('sanctum') ?? $this->userFromBearer($request);
        if ($user) {
            $item = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('status', 'delivered');
                })
                ->whereDoesntHave('review')
                ->latest('id')
                ->first();
            if ($item) {
                $canReview = true;
                $reviewableItemId = $item->id;
            }
        }

        return response()->json([
            'summary' => $summary,
            'rewards' => app(StoreSettings::class)->reviewRewards(),
            'can_review' => $canReview,
            'reviewable_order_item_id' => $reviewableItemId,
            'reviews' => $reviews,
        ]);
    }

    public function reviewableItems(Order $order, Request $request, ProductReviewService $service)
    {
        $user = $request->user();
        $items = $service->reviewableItems($order, $user);
        $rewards = $service->rewardPreview();

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'delivered' => $order->status === 'delivered',
            'rewards' => $rewards,
            'items' => collect($items)->map(fn (OrderItem $item) => [
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->displayName(),
                'image' => $item->product?->image,
                'product_url' => $item->product_id ? url('/product/'.$item->product_id) : null,
            ])->values(),
        ]);
    }

    public function store(Request $request, ProductReviewService $service)
    {
        $data = $request->validate([
            'order_item_id' => 'required|integer|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string|max:2000',
            'fit_feedback' => 'nullable|in:too_small,perfect,too_large',
            'image' => 'nullable|image|max:5120',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
            'video' => 'nullable|mimetypes:video/mp4,video/webm,video/quicktime|max:20480',
        ]);

        $item = OrderItem::query()->with(['order', 'product'])->findOrFail($data['order_item_id']);
        $extra = $request->file('images') ?: [];
        $review = $service->create(
            $request->user(),
            $item,
            (int) $data['rating'],
            $data['body'] ?? null,
            $request->file('image'),
            $data['fit_feedback'] ?? null,
            $request->file('video'),
            is_array($extra) ? $extra : [$extra],
        );

        $request->user()->refresh();

        return response()->json([
            'ok' => true,
            'message' => $review->reward_syp > 0
                ? 'شكراً لتقييمك! أُضيف '.number_format($review->reward_syp).' ل.س لرصيدك.'
                : 'شكراً لتقييمك!',
            'review' => $this->serialize($review),
            'reward_syp' => $review->reward_syp,
            'store_credit_syp' => (int) $request->user()->store_credit_syp,
        ], 201);
    }

    private function serialize(ProductReview $r): array
    {
        return [
            'id' => $r->id,
            'rating' => $r->rating,
            'body' => $r->body,
            'fit_feedback' => $r->fit_feedback,
            'fit_label' => $r->fit_label,
            'image_url' => $r->image_url,
            'image_urls' => $r->image_urls,
            'video_url' => $r->video_url,
            'masked_identity' => $r->masked_identity,
            'avatar_letter' => $r->avatar_letter,
            'created_at' => optional($r->created_at)->toIso8601String(),
            'reward_syp' => $r->reward_syp,
        ];
    }

    private function userFromBearer(Request $request)
    {
        $plain = $request->bearerToken();
        if (! $plain) {
            return null;
        }
        $access = \Laravel\Sanctum\PersonalAccessToken::findToken($plain);

        return $access?->tokenable;
    }
}
