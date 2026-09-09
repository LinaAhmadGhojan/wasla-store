<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function show(Vendor $vendor)
    {
        return response()->json($vendor->load(['user', 'products.variants', 'products.category']));
    }
}
