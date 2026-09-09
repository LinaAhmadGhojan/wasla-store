<?php

namespace App\Services\Reviews;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\StoreCreditTransaction;
use App\Models\User;
use App\Services\StoreSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductReviewService
{
    public function __construct(private StoreSettings $settings)
    {
    }

    public function create(
        User $user,
        OrderItem $item,
        int $rating,
        ?string $body,
        ?UploadedFile $image = null,
        ?string $fitFeedback = null,
        ?UploadedFile $video = null,
        array $extraImages = [],
    ): ProductReview {
        return DB::transaction(function () use ($user, $item, $rating, $body, $image, $fitFeedback, $video, $extraImages) {
            $item->loadMissing(['order', 'product']);
            $order = $item->order;

            if (! $order || (int) $order->user_id !== (int) $user->id) {
                throw ValidationException::withMessages(['order_item_id' => 'هذا البند ليس لطلبك.']);
            }

            if ($order->status !== 'delivered') {
                throw ValidationException::withMessages(['order_item_id' => 'يمكن التقييم بعد استلام الطلبية فقط.']);
            }

            if (! $item->product_id || ! $item->product) {
                throw ValidationException::withMessages(['order_item_id' => 'التقييم متاح لمنتجات وصلة المحلية فقط.']);
            }

            if (ProductReview::query()->where('order_item_id', $item->id)->exists()) {
                throw ValidationException::withMessages(['order_item_id' => 'قيّمتِ هذا المنتج مسبقاً لهذا الطلب.']);
            }

            $body = $body !== null ? trim($body) : null;
            if ($body === '') {
                $body = null;
            }

            if ($fitFeedback && ! isset(ProductReview::FIT_LABELS[$fitFeedback])) {
                throw ValidationException::withMessages(['fit_feedback' => 'خيار القياس غير صالح.']);
            }

            $imagePaths = [];
            if ($image) {
                $imagePaths[] = $image->store('reviews', 'public');
            }
            foreach ($extraImages as $file) {
                if ($file instanceof UploadedFile) {
                    $imagePaths[] = $file->store('reviews', 'public');
                }
            }

            $videoPath = null;
            if ($video) {
                $videoPath = $video->store('reviews/videos', 'public');
            }

            $hasMedia = $imagePaths !== [] || $videoPath;
            if (! $body && ! $hasMedia) {
                throw ValidationException::withMessages(['body' => 'اكتبي تعليقاً أو ارفعي صورة/فيديو (واحد على الأقل).']);
            }

            if ($rating < 1 || $rating > 5) {
                throw ValidationException::withMessages(['rating' => 'التقييم يجب أن يكون بين 1 و 5.']);
            }

            $reward = $this->settings->reviewRewardFor($body, $imagePaths !== [] || (bool) $videoPath);

            $review = ProductReview::query()->create([
                'user_id' => $user->id,
                'product_id' => $item->product_id,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'rating' => $rating,
                'body' => $body,
                'fit_feedback' => $fitFeedback,
                'image_path' => $imagePaths[0] ?? null,
                'image_paths' => $imagePaths ?: null,
                'video_path' => $videoPath,
                'reward_syp' => $reward,
                'status' => ProductReview::STATUS_APPROVED,
            ]);

            if ($reward > 0) {
                $user->creditStoreCredit(
                    $reward,
                    StoreCreditTransaction::TYPE_REVIEW_REWARD,
                    $review,
                    'مكافأة تقييم منتج #'.$item->product_id
                );
            }

            $this->recalculateProductRating($item->product);

            return $review->fresh(['user', 'product']);
        });
    }

    public function recalculateProductRating(Product $product): void
    {
        $stats = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('status', ProductReview::STATUS_APPROVED)
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')
            ->first();

        $product->forceFill([
            'total_reviews' => (int) ($stats->total ?? 0),
            'rating' => $stats && $stats->total
                ? round((float) $stats->avg_rating, 1)
                : 0,
        ])->save();
    }

    /** @return list<OrderItem> */
    public function reviewableItems(Order $order, User $user): array
    {
        if ((int) $order->user_id !== (int) $user->id) {
            throw ValidationException::withMessages(['order' => 'طلب غير موجود.']);
        }

        if ($order->status !== 'delivered') {
            return [];
        }

        return $order->items()
            ->with(['product', 'review'])
            ->whereNotNull('product_id')
            ->whereDoesntHave('review')
            ->get()
            ->all();
    }

    public function rewardPreview(?string $body = null, bool $hasImage = false): array
    {
        $rewards = $this->settings->reviewRewards();

        return [
            'text_syp' => $rewards['text_syp'],
            'with_photo_syp' => $rewards['with_photo_syp'],
            'estimated_syp' => $this->settings->reviewRewardFor($body, $hasImage),
            'fit_options' => ProductReview::FIT_LABELS,
        ];
    }
}
