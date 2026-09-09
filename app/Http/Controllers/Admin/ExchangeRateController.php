<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function edit(CurrencyService $currency)
    {
        $current = ExchangeRate::current();

        return view('admin.exchange-rate.edit', [
            'currentRate' => $currency->currentAedToSypRate(),
            'productFee' => $currency->productFeeAed(),
            'accessoryFee' => $currency->accessoryFeeAed(),
            'currentRecord' => $current,
            'history' => ExchangeRate::query()
                ->where('from_currency', config('currency.procurement', 'AED'))
                ->where('to_currency', config('currency.customer', 'SYP'))
                ->orderByDesc('id')
                ->limit(15)
                ->get(),
        ]);
    }

    public function update(Request $request, CurrencyService $currency)
    {
        $request->merge([
            'rate' => $this->parseNumber($request->input('rate')),
            'product_fee_aed' => $this->parseNumber($request->input('product_fee_aed')),
            'accessory_fee_aed' => $this->parseNumber($request->input('accessory_fee_aed')),
        ]);

        $data = $request->validate([
            'rate' => 'required|numeric|min:1|max:99999999',
            'product_fee_aed' => 'required|numeric|min:0|max:100000',
            'accessory_fee_aed' => 'required|numeric|min:0|max:100000',
            'notes' => 'nullable|string|max:500',
        ]);

        $rate = (float) $data['rate'];
        $productFee = (float) $data['product_fee_aed'];
        $accessoryFee = (float) $data['accessory_fee_aed'];
        $current = $currency->currentAedToSypRate();

        if (abs($rate - $current) > 0.0001) {
            $currency->setCurrentRate($rate, $data['notes'] ?? null, $productFee, $accessoryFee);
        } else {
            $currency->updateFees($productFee, $accessoryFee);
        }

        return redirect()
            ->route('admin.exchange-rate.edit')
            ->with('success', 'تم الحفظ: 1 د.إ = '.number_format($rate).' ل.س · منتج +'.number_format($productFee, 0).' ل.س · إكسسوار +'.number_format($accessoryFee, 0).' ل.س');
    }

    private function parseNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace([',', '،', ' '], '', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
