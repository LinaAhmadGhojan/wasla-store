<?php

namespace App\Services\Commerce;

use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function findValid(string $code): Coupon
    {
        $coupon = Coupon::query()
            ->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])
            ->first();

        if (! $coupon || ! $coupon->isCurrentlyValid()) {
            throw ValidationException::withMessages(['coupon' => 'كود الخصم غير صالح أو منتهٍ.']);
        }

        return $coupon;
    }

    public function preview(string $code, float $subtotal): array
    {
        $coupon = $this->findValid($code);
        $discount = $coupon->calculateDiscount($subtotal);
        if ($discount <= 0) {
            throw ValidationException::withMessages([
                'coupon' => $coupon->min_subtotal
                    ? 'الحد الأدنى للطلب '.number_format((float) $coupon->min_subtotal, 0).' لتطبيق الكوبون.'
                    : 'لا يمكن تطبيق هذا الكوبون على سلتك.',
            ]);
        }

        return [
            'ok' => true,
            'valid' => true,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'discount' => $discount,
            'subtotal_after' => max(0, $subtotal - $discount),
        ];
    }
}
