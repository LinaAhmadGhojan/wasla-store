<?php

namespace App\Services\Whatsapp;

use App\Models\Product;
use App\Models\WhatsappGroup;

class WhatsappPublisher
{
    public function __construct(
        private WhatsappMessageBuilder $builder,
        private WhatsappGatewayClient $gateway,
        private WhatsappShareImage $shareImage,
    ) {}

    public function sendProduct(
        Product $product,
        string $target,
        ?WhatsappGroup $group = null,
        bool $includeImage = true,
    ): void {
        $group ??= WhatsappGroup::defaultGroup();
        $product->loadMissing(['vendor', 'category.parent', 'variants']);

        $message = $this->builder->fromProduct($product, $group);
        $imagePayload = $includeImage ? $this->imagePayload($product) : [];

        if ($target === 'group') {
            if (! $group?->whatsapp_chat_id) {
                throw new \RuntimeException('المجموعة غير مربوطة. اربطي Group Chat ID من WhatsApp Templates.');
            }

            $this->gateway->send($message, groupChatId: $group->whatsapp_chat_id, imagePayload: $imagePayload);
        } else {
            $phone = $this->builder->resolveRecipientPhone($group);
            if (! $phone) {
                throw new \RuntimeException('ما في رقم واتساب مضبوط.');
            }

            $this->gateway->send($message, phone: $phone, imagePayload: $imagePayload);
        }

        $product->update([
            'whatsapp_published_at' => now(),
            'whatsapp_published_target' => $target,
        ]);
    }

    /**
     * @param  list<int>  $productIds
     * @return array{sent: int, failed: list<string>}
     */
    public function sendMany(
        array $productIds,
        string $target,
        ?WhatsappGroup $group = null,
        bool $includeImages = false,
    ): array {
        set_time_limit(max(120, (int) ini_get('max_execution_time')));

        $group ??= WhatsappGroup::defaultGroup();
        $products = Product::with(['vendor', 'category.parent', 'variants'])
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->get();

        $delayMs = max(0, (int) config('whatsapp.bulk_delay_ms', 600));
        $sent = 0;
        $failed = [];

        foreach ($products as $index => $product) {
            try {
                if ($index > 0 && $delayMs > 0) {
                    usleep($delayMs * 1000);
                }

                $this->sendProduct($product, $target, $group, includeImage: $includeImages);
                $sent++;
            } catch (\Throwable $e) {
                $failed[] = $product->name.': '.$e->getMessage();
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public function sendBroadcast(
        \App\Models\WhatsappBroadcastMessage $broadcast,
        ?WhatsappGroup $group = null,
    ): void {
        $group ??= WhatsappGroup::defaultGroup();

        if (! $group?->whatsapp_chat_id) {
            throw new \RuntimeException('المجموعة غير مربوطة. اربطي Group Chat ID أولاً.');
        }

        if (! $this->gatewayReady()) {
            throw new \RuntimeException('واتساب غير متصل. اربطيه من صفحة Connection.');
        }

        $message = $this->builder->fromBroadcast($broadcast, $group);
        $this->gateway->send($message, groupChatId: $group->whatsapp_chat_id);

        $broadcast->update([
            'last_sent_at' => now(),
            'send_count' => $broadcast->send_count + 1,
        ]);
    }

    public function gatewayReady(): bool
    {
        return (bool) ($this->gateway->status()['ready'] ?? false);
    }

    public function canSendToGroup(?WhatsappGroup $group = null): bool
    {
        $group ??= WhatsappGroup::defaultGroup();

        return (bool) ($group?->whatsapp_chat_id);
    }

    public function recipientPhone(?WhatsappGroup $group = null): ?string
    {
        return $this->builder->resolveRecipientPhone($group ?? WhatsappGroup::defaultGroup());
    }

    /** @return array{image?: string, localImage?: string} */
    private function imagePayload(Product $product): array
    {
        if (! $product->image) {
            return [];
        }

        $localPath = null;
        if (! str_starts_with($product->image, 'http')) {
            $candidate = public_path(ltrim($product->image, '/'));
            if (is_file($candidate)) {
                $localPath = $candidate;
            }
        }

        if ($localPath) {
            $currency = app(\App\Services\CurrencyService::class);
            $min = $this->builder->startingSyp($product);
            $label = $this->builder->pricesDiffer($product)
                ? 'من '.$currency->formatSyp($min)
                : $currency->formatSyp($min);
            $card = $this->shareImage->withPrice($localPath, $label, $product->id);
            if ($card) {
                return ['localImage' => $card];
            }

            return ['localImage' => $localPath];
        }

        if (str_starts_with($product->image, 'http')) {
            return ['image' => $product->image];
        }

        return ['image' => url($product->image)];
    }
}
