<?php

namespace App\Services\Whatsapp;

use App\Models\Product;
use App\Models\WhatsappGroup;
use App\Services\CurrencyService;
use App\Support\SizeSorter;

class WhatsappMessageBuilder
{
    public function fromProduct(Product $product, ?WhatsappGroup $group = null): string
    {
        $group ??= WhatsappGroup::defaultGroup();
        $template = $group?->resolvedTemplate() ?? config('whatsapp.default_template');
        if (! str_contains($template, '{catalog}')) {
            $template = config('whatsapp.default_template');
        }
        $currency = app(CurrencyService::class);
        $product->loadMissing(['vendor', 'variants', 'category.parent']);
        $kind = $product->pricingKind();

        $priceSyp = $currency->formatCustomer((float) $product->price, $kind);
        $saleLine = '';
        if ($product->sale_price && (float) $product->sale_price < (float) $product->price) {
            $saleLine = '🔥 عرض: *'.$currency->formatCustomer((float) $product->sale_price, $kind).'*';
        }

        $productUrl = config('whatsapp.include_product_url')
            ? url('/product/'.$product->id)
            : '';

        $replacements = [
            '{name}' => $product->name,
            '{description}' => '',
            '{catalog}' => $this->catalogBlock($product, $currency, $kind, $group),
            '{price}' => '*'.$priceSyp.'*',
            '{price_syp}' => '*'.$priceSyp.'*',
            '{sale_price}' => '*'.($product->sale_price
                ? $currency->formatCustomer((float) $product->sale_price, $kind)
                : $priceSyp).'*',
            '{currency}' => '',
            '{url}' => $productUrl,
            '{image}' => '',
            '{store}' => $product->vendor?->store_name ?? 'Wasla',
            '{group_name}' => $group?->name ?? 'وصلة',
            '{sale_line}' => $saleLine,
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $template);
        $message = $this->stripUnwantedCaptionBits($message);

        return trim(preg_replace("/\n{3,}/", "\n\n", $message));
    }

    public function fromBroadcast(\App\Models\WhatsappBroadcastMessage $broadcast, ?WhatsappGroup $group = null): string
    {
        $group ??= WhatsappGroup::defaultGroup();

        $replacements = [
            '{group_name}' => $group?->name ?? 'وصلة',
            '{store}' => config('app.name', 'Wasla'),
            '{date}' => now()->format('d/m/Y'),
            '{time}' => now()->format('H:i'),
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $broadcast->body);

        return trim(preg_replace("/\n{3,}/", "\n\n", $message));
    }

    public function resolveRecipientPhone(?WhatsappGroup $group = null): ?string
    {
        $candidates = [
            config('whatsapp.default_phone'),
            $group?->admin_phone,
        ];

        foreach ($candidates as $phone) {
            $digits = preg_replace('/\D+/', '', (string) $phone);
            if ($digits !== '') {
                return $digits;
            }
        }

        return null;
    }

    public function startingSyp(Product $product): int
    {
        [$min] = $this->sypRange($product);

        return $min;
    }

    public function pricesDiffer(Product $product): bool
    {
        [$min, $max] = $this->sypRange($product);

        return ($max - $min) > 150;
    }

    /** Opens WhatsApp chat with recipient phone and pre-filled message. */
    public function shareUrl(string $message, ?WhatsappGroup $group = null): ?string
    {
        $phone = $this->resolveRecipientPhone($group);
        if ($phone === null) {
            return null;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    private function catalogBlock(Product $product, CurrencyService $currency, string $kind, ?WhatsappGroup $group): string
    {
        $colors = [];
        $sizes = [];
        $rows = [];

        foreach ($product->variants as $variant) {
            $aed = (float) $variant->price;
            if ($variant->sale_price && (float) $variant->sale_price > 0 && (float) $variant->sale_price < $aed) {
                $aed = (float) $variant->sale_price;
            }
            if ($aed <= 0) {
                continue;
            }

            $meta = $variant->metadata ?? [];
            $color = trim((string) ($meta['color_name'] ?? ''));
            if ($color !== '' && ! in_array($color, $colors, true)) {
                $colors[] = $color;
            }

            $size = trim((string) ($meta['size'] ?? $meta['size_key'] ?? ''));
            if ($size === '') {
                $size = (string) $variant->option_value;
            }
            if ($this->isPlaceholderSize($size)) {
                $size = '';
            }
            if ($size !== '' && ! in_array($size, $sizes, true)) {
                $sizes[] = $size;
            }

            $rows[] = [
                'color' => $color,
                'size' => $size,
                'syp' => $currency->customerSyp($aed, $kind),
            ];
        }

        $sizes = SizeSorter::sort($sizes);
        $prices = array_column($rows, 'syp');
        $min = $prices === []
            ? $currency->customerSyp((float) $product->price, $kind)
            : min($prices);
        $max = $prices === [] ? $min : max($prices);

        $lines = [];
        if ($colors !== []) {
            $lines[] = '🎨 الألوان المتاحة: '.implode('، ', $colors);
        }
        if ($sizes !== []) {
            $lines[] = '📏 المقاسات المتوفرة: '.implode('، ', $sizes);
        }

        $lines[] = $this->priceLine($currency, $min, $max, $rows);

        $phones = $this->inquiryPhones($group);
        if ($phones !== []) {
            $lines[] = '📲 للاستفسار عن السعر راسلينا على:';
            foreach ($phones as $phone) {
                $lines[] = $this->formatIntlPhone($phone);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array{color: string, size: string, syp: int}>  $rows
     */
    private function priceLine(CurrencyService $currency, int $min, int $max, array $rows): string
    {
        $minLabel = '*'.$currency->formatSyp($min).'*';
        $maxLabel = '*'.$currency->formatSyp($max).'*';

        if (($max - $min) <= 150) {
            return '💰 '.$minLabel;
        }

        $byColor = [];
        $bySize = [];
        foreach ($rows as $row) {
            if ($row['color'] !== '') {
                $byColor[$row['color']][] = $row['syp'];
            }
            if ($row['size'] !== '') {
                $bySize[$row['size']][] = $row['syp'];
            }
        }

        // Bags: each color has its own price, often with no real sizes.
        // Clothes: same color, different sizes can differ.
        $variesBySize = $this->groupVariesInternally($byColor)
            || (count($byColor) < 2 && $this->representativesDiffer($bySize));
        $variesByColor = $this->representativesDiffer($byColor)
            || $this->groupVariesInternally($bySize);

        $phrase = match (true) {
            $variesByColor && $variesBySize => 'في ألوان ومقاسات بسعر مختلف عن هاد السعر.',
            $variesByColor => 'في ألوان بسعر مختلف عن هاد السعر.',
            $variesBySize => 'في مقاسات بسعر مختلف عن هاد السعر.',
            default => 'في خيارات بسعر مختلف عن هاد السعر.',
        };

        return '💰 '.$minLabel.' '.$phrase.' '.$maxLabel;
    }

    private function isPlaceholderSize(string $size): bool
    {
        $normalized = mb_strtolower(trim($size));

        return in_array($normalized, ['os', 'one size', 'one-size', 'onesize', 'default', 'free size', 'free-size', 'مقاس واحد', '—', '-'], true);
    }

    /** @param  array<string, list<int>>  $groups */
    private function groupVariesInternally(array $groups): bool
    {
        foreach ($groups as $prices) {
            if ($prices !== [] && (max($prices) - min($prices)) > 150) {
                return true;
            }
        }

        return false;
    }

    /** @param  array<string, list<int>>  $groups */
    private function representativesDiffer(array $groups): bool
    {
        if (count($groups) < 2) {
            return false;
        }

        $reps = array_map(fn (array $prices) => min($prices), $groups);

        return (max($reps) - min($reps)) > 150;
    }

    /** @return array{0: int, 1: int} */
    private function sypRange(Product $product): array
    {
        $currency = app(CurrencyService::class);
        $kind = $product->pricingKind();
        $prices = $this->variantSypPrices($product, $currency, $kind);

        if ($prices === []) {
            $one = $currency->customerSyp((float) $product->price, $kind);

            return [$one, $one];
        }

        return [min($prices), max($prices)];
    }

    /** @return list<int> */
    private function variantSypPrices(Product $product, CurrencyService $currency, string $kind): array
    {
        $prices = [];
        foreach ($product->variants as $variant) {
            $aed = (float) $variant->price;
            if ($variant->sale_price && (float) $variant->sale_price > 0 && (float) $variant->sale_price < $aed) {
                $aed = (float) $variant->sale_price;
            }
            if ($aed <= 0) {
                continue;
            }

            $prices[] = $currency->customerSyp($aed, $kind);
        }

        return $prices;
    }

    /** @return list<string> E.164 digits */
    private function inquiryPhones(?WhatsappGroup $group): array
    {
        $candidates = array_merge(
            (array) config('whatsapp.contact_phones', []),
            [config('whatsapp.default_phone'), $group?->admin_phone]
        );

        $phones = [];
        foreach ($candidates as $phone) {
            $digits = preg_replace('/\D+/', '', (string) $phone);
            if ($digits === '' || in_array($digits, $phones, true)) {
                continue;
            }
            $phones[] = $digits;
        }

        return $phones;
    }

    private function formatIntlPhone(string $digits): string
    {
        if (str_starts_with($digits, '963') && strlen($digits) === 12) {
            return '+963 '.substr($digits, 3, 3).' '.substr($digits, 6, 3).' '.substr($digits, 9, 3);
        }

        return '+'.$digits;
    }

    private function stripUnwantedCaptionBits(string $message): string
    {
        $message = preg_replace('#https?://\S+#i', '', $message) ?? $message;
        $message = preg_replace('/^(?:🔗|🖼|🖼️)\s*$/mu', '', $message) ?? $message;
        $message = preg_replace('/^💰 السعر:\s*$/mu', '', $message) ?? $message;

        if (! config('whatsapp.include_product_url')) {
            $message = preg_replace('/^🔗.*$/mu', '', $message) ?? $message;
        }

        return $message;
    }
}
