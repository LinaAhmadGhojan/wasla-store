<?php

namespace App\Services\ExternalShopping;

use App\Services\CurrencyService;

/**
 * Procurement pricing in AED; customer display adds SYP via CurrencyService.
 */
class PricingEngine
{
    /** Fallback FX rates into AED (approximate; replace with live FX later). */
    public const RATES_TO_AED = [
        'AED' => 1.0,
        'USD' => 3.6725,
        'EUR' => 4.00,
        'GBP' => 4.65,
        'TRY' => 0.11,
        'CNY' => 0.51,
        'SAR' => 0.98,
    ];

    public function convertToAed(float $amount, string $currency): float
    {
        $currency = strtoupper($currency);
        $rate = self::RATES_TO_AED[$currency] ?? self::RATES_TO_AED['USD'];

        return round($amount * $rate, 2);
    }

    public function exchangeRate(string $currency): float
    {
        $currency = strtoupper($currency);

        return self::RATES_TO_AED[$currency] ?? self::RATES_TO_AED['USD'];
    }

    /**
     * @return array{
     *   currency: string,
     *   original_price: float,
     *   original_currency: string,
     *   original_price_aed: float,
     *   exchange_rate: float,
     *   markup_rate: float,
     *   markup_amount: float,
     *   wasla_price: float,
     *   shipping: float,
     *   service_fee: float,
     *   total: float
     * }
     */
    public function quote(
        float $originalPrice,
        string $originalCurrency,
        float $markupRate = 15.0,
        float $serviceFee = 10.0,
        float $shipping = 15.0
    ): array {
        $exchangeRate = $this->exchangeRate($originalCurrency);
        $originalPriceAed = $this->convertToAed($originalPrice, $originalCurrency);
        $markupAmount = round($originalPriceAed * ($markupRate / 100), 2);
        $waslaPrice = round($originalPriceAed + $markupAmount, 2);
        $total = round($waslaPrice + $shipping + $serviceFee, 2);
        $currency = app(CurrencyService::class);
        $aedToSyp = $currency->currentAedToSypRate();

        return [
            'currency' => 'AED',
            'country' => 'AE',
            'original_price' => round($originalPrice, 2),
            'original_currency' => strtoupper($originalCurrency),
            'original_price_aed' => $originalPriceAed,
            'exchange_rate' => $exchangeRate,
            'markup_rate' => $markupRate,
            'markup_amount' => $markupAmount,
            'wasla_price' => $waslaPrice,
            'shipping' => round($shipping, 2),
            'service_fee' => round($serviceFee, 2),
            'total' => $total,
            'customer_currency' => config('currency.customer', 'SYP'),
            'aed_to_syp' => $aedToSyp,
            'total_syp' => $currency->convertAedToSyp($total, $aedToSyp),
            'wasla_price_syp' => $currency->convertAedToSyp($waslaPrice, $aedToSyp),
            'shipping_syp' => $currency->convertAedToSyp($shipping, $aedToSyp),
            'service_fee_syp' => $currency->convertAedToSyp($serviceFee, $aedToSyp),
        ];
    }
}
