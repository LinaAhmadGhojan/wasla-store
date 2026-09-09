<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::where('is_active', true)->orderBy('name')->paginate(20);

        return response()->json($brands);
    }

    public function show(Brand $brand)
    {
        return response()->json($brand->load('products'));
    }
}
