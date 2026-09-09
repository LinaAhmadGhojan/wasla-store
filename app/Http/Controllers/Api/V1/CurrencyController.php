<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;

class CurrencyController extends Controller
{
    public function show(CurrencyService $currency)
    {
        return response()->json([
            'procurement_currency' => config('currency.procurement', 'AED'),
            'customer_currency' => config('currency.customer', 'SYP'),
            'aed_to_syp' => $currency->currentAedToSypRate(),
            'product_fee_aed' => $currency->productFeeAed(),
            'accessory_fee_aed' => $currency->accessoryFeeAed(),
            'label_syp' => config('currency.labels.SYP', 'ل.س'),
        ]);
    }
}
