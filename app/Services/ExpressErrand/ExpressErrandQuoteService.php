<?php

namespace App\Services\ExpressErrand;

class ExpressErrandQuoteService
{
    public function __construct(private ExpressErrandPricingSettings $settings)
    {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function quote(array $input): array
    {
        $cfg = $this->settings->all();
        $serviceType = $input['service_type'] ?? 'local_errand';
        $urgency = ($input['urgency'] ?? 'normal') === 'urgent' ? 'urgent' : 'normal';
        $lines = [];
        $total = 0;

        if ($serviceType === 'local_errand') {
            $local = $cfg['local_errand'] ?? [];
            $category = $input['category'] ?? 'grocery';
            $itemCount = max(1, (int) ($input['items_count'] ?? 1));
            $base = (int) ($local['category_fees'][$category] ?? $local['base_fee_syp'] ?? 20000);
            $perItem = (int) ($local['per_item_syp'] ?? 2500);
            $itemsFee = max(0, $itemCount - 1) * $perItem;

            $lines[] = $this->line('base', 'أجور مشوار · '.$this->categoryLabel($category), $base);
            if ($itemsFee > 0) {
                $lines[] = $this->line('items', 'أغراض إضافية ('.($itemCount - 1).'×)', $itemsFee);
            }
            $total = $base + $itemsFee;
            $carrier = $this->carrierByKey($cfg, 'wasla');
        } else {
            $from = trim((string) ($input['origin_governorate'] ?? ''));
            $to = trim((string) ($input['destination_governorate'] ?? ''));
            $size = $input['parcel_size'] ?? 'unknown';
            $carrierKey = $input['shipping_carrier'] ?? null;

            $route = $this->resolveRoute($cfg, $from, $to);
            $carrier = $this->resolveCarrier($cfg, $carrierKey, $route['carrier'] ?? 'qadmous');

            $lines[] = $this->line(
                'route',
                'مسار: '.$from.' ← '.$to,
                $route['fee_syp'],
                ['same_gov' => $route['same_gov']]
            );

            $sizeFee = (int) ($cfg['parcel_sizes'][$size] ?? $cfg['parcel_sizes']['unknown'] ?? 60000);
            $lines[] = $this->line('size', 'حجم الطرد · '.$this->sizeLabel($size), $sizeFee);

            if ($carrier && ($carrier['fee_markup_syp'] ?? 0) > 0) {
                $lines[] = $this->line(
                    'carrier',
                    'خدمة '.$carrier['label'],
                    (int) $carrier['fee_markup_syp']
                );
            }

            $total = $route['fee_syp'] + $sizeFee + (int) ($carrier['fee_markup_syp'] ?? 0);
        }

        if ($urgency === 'urgent') {
            $mult = (float) ($cfg['local_errand']['urgent_multiplier'] ?? 1.35);
            $extra = (int) round($total * ($mult - 1));
            if ($extra > 0) {
                $lines[] = $this->line('urgent', 'أولوية مستعجلة', $extra);
                $total += $extra;
            }
        }

        return [
            'currency' => 'SYP',
            'total_syp' => $total,
            'total_label' => number_format($total).' ل.س',
            'lines' => $lines,
            'service_type' => $serviceType,
            'shipping_carrier' => $carrier['key'] ?? null,
            'carrier_label' => $carrier['label'] ?? null,
            'carrier_available' => (bool) ($carrier['available'] ?? true),
            'disclaimer' => $serviceType === 'local_errand'
                ? 'هذا سعر خدمة المشوار والتوصيل فقط — فاتورة البقالة/الصيدلية تُدفع عند الاستلام.'
                : 'السعر شامل أجور الشحن حسب المسار والحجم — لا يشمل قيمة محتوى الطرد إن وُجد.',
            'valid_minutes' => 30,
        ];
    }

    /** @return array{fee_syp: int, same_gov: bool, carrier: string|null} */
    private function resolveRoute(array $cfg, string $from, string $to): array
    {
        if ($from === '' || $to === '') {
            return [
                'fee_syp' => (int) ($cfg['default_inter_governorate_fee_syp'] ?? 95000),
                'same_gov' => false,
                'carrier' => 'qadmous',
            ];
        }

        if ($from === $to) {
            return [
                'fee_syp' => (int) ($cfg['same_governorate_fee_syp'] ?? 25000),
                'same_gov' => true,
                'carrier' => 'wasla',
            ];
        }

        foreach ($cfg['governorate_routes'] ?? [] as $row) {
            if ($row['from'] === $from && $row['to'] === $to) {
                return [
                    'fee_syp' => (int) $row['fee_syp'],
                    'same_gov' => false,
                    'carrier' => $row['carrier'] ?? 'qadmous',
                ];
            }
        }

        return [
            'fee_syp' => (int) ($cfg['default_inter_governorate_fee_syp'] ?? 95000),
            'same_gov' => false,
            'carrier' => 'qadmous',
        ];
    }

    private function resolveCarrier(array $cfg, ?string $requested, ?string $routeDefault): array
    {
        $carriers = collect($cfg['carriers'] ?? []);
        if ($requested) {
            $pick = $carriers->firstWhere('key', $requested);
            if ($pick) {
                return $pick;
            }
        }

        $pick = $carriers->firstWhere('key', $routeDefault);
        if ($pick && ($pick['available'] ?? true)) {
            return $pick;
        }

        return $carriers->firstWhere('available', true) ?? [
            'key' => 'qadmous',
            'label' => 'شحن قدموس',
            'available' => true,
            'fee_markup_syp' => 0,
        ];
    }

    private function carrierByKey(array $cfg, string $key): array
    {
        return collect($cfg['carriers'] ?? [])->firstWhere('key', $key) ?? [
            'key' => $key,
            'label' => $key,
            'available' => true,
            'fee_markup_syp' => 0,
        ];
    }

    /** @return array{key: string, label: string, amount_syp: int, meta?: array<string, mixed>} */
    private function line(string $key, string $label, int $amount, array $meta = []): array
    {
        return array_merge([
            'key' => $key,
            'label' => $label,
            'amount_syp' => $amount,
            'amount_label' => number_format($amount).' ل.س',
        ], $meta ? ['meta' => $meta] : []);
    }

    private function categoryLabel(string $key): string
    {
        return match ($key) {
            'pharmacy' => 'صيدلية',
            'produce' => 'خضرة',
            'household' => 'منزل',
            'other' => 'أخرى',
            default => 'بقالة',
        };
    }

    private function sizeLabel(string $key): string
    {
        return match ($key) {
            'small' => 'صغير',
            'medium' => 'متوسط',
            'large' => 'كبير',
            default => 'غير محدد',
        };
    }
}
