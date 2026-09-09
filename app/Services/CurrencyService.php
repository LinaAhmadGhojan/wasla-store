<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CurrencyService
{
    public function currentAedToSypRate(): float
    {
        $stored = ExchangeRate::current()?->rate;

        return $stored !== null ? (float) $stored : (float) config('currency.default_aed_to_syp', 14500);
    }

    public function productFeeAed(): float
    {
        $stored = ExchangeRate::current()?->product_fee_aed;

        return $stored !== null ? (float) $stored : (float) config('currency.product_fee_aed', 75);
    }

    public function accessoryFeeAed(): float
    {
        $stored = ExchangeRate::current()?->accessory_fee_aed;

        return $stored !== null ? (float) $stored : (float) config('currency.accessory_fee_aed', 50);
    }

    public function feeAedFor(string $kind = 'product'): float
    {
        return $kind === 'accessory' ? $this->accessoryFeeAed() : $this->productFeeAed();
    }

    public function convertAedToSyp(float $aed, ?float $rate = null): int
    {
        $rate ??= $this->currentAedToSypRate();

        return (int) round($aed * $rate);
    }

    /**
     * Customer price: (dirham × SYP rate) + fee.
     * Product fee default 75 SYP, accessory 50 SYP.
     * Example: 20 AED × 38 + 75 = 835 ل.س
     */
    public function customerSyp(float $aed, string $kind = 'product', ?float $rate = null): int
    {
        return $this->convertAedToSyp($aed, $rate) + (int) round($this->feeAedFor($kind));
    }

    public function formatSyp(int|float $amount): string
    {
        return number_format((int) round($amount)).' '.config('currency.labels.SYP', 'ل.س');
    }

    public function formatAed(float $amount): string
    {
        return number_format($amount, 2).' '.config('currency.labels.AED', 'د.إ');
    }

    public function formatDual(float $aed, ?float $rate = null): string
    {
        $rate ??= $this->currentAedToSypRate();

        return $this->formatAed($aed).' ≈ '.$this->formatSyp($this->convertAedToSyp($aed, $rate));
    }

    public function formatCustomer(float $aed, string $kind = 'product'): string
    {
        return $this->formatSyp($this->customerSyp($aed, $kind));
    }

    public function setCurrentRate(
        float $rate,
        ?string $notes = null,
        ?float $productFee = null,
        ?float $accessoryFee = null
    ): ExchangeRate {
        return DB::transaction(function () use ($rate, $notes, $productFee, $accessoryFee) {
            $previous = ExchangeRate::current();

            ExchangeRate::query()
                ->where('from_currency', config('currency.procurement', 'AED'))
                ->where('to_currency', config('currency.customer', 'SYP'))
                ->where('is_current', true)
                ->update(['is_current' => false]);

            return ExchangeRate::create([
                'from_currency' => config('currency.procurement', 'AED'),
                'to_currency' => config('currency.customer', 'SYP'),
                'rate' => $rate,
                'product_fee_aed' => $productFee ?? $previous?->product_fee_aed ?? config('currency.product_fee_aed', 75),
                'accessory_fee_aed' => $accessoryFee ?? $previous?->accessory_fee_aed ?? config('currency.accessory_fee_aed', 50),
                'is_current' => true,
                'notes' => $notes,
                'set_by' => Auth::id(),
            ]);
        });
    }

    public function updateFees(float $productFee, float $accessoryFee): ExchangeRate
    {
        $current = ExchangeRate::current();
        if ($current) {
            $current->update([
                'product_fee_aed' => $productFee,
                'accessory_fee_aed' => $accessoryFee,
            ]);

            return $current->fresh();
        }

        return $this->setCurrentRate($this->currentAedToSypRate(), 'رسوم وصلة', $productFee, $accessoryFee);
    }
}
