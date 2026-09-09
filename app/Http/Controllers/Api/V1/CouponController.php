<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Commerce\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(private CouponService $coupons)
    {
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:60',
            'subtotal' => 'required|numeric|min:0',
        ]);

        return response()->json($this->coupons->preview($data['code'], (float) $data['subtotal']));
    }

    public function shippingMethods()
    {
        return response()->json([
            'methods' => config('shipping.methods', []),
            'gift_wrapping_fee' => (float) config('shipping.gift_wrapping_fee', 0),
        ]);
    }
}
