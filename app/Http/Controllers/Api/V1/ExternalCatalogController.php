<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ExternalShopping\ExternalCatalogService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ExternalCatalogController extends Controller
{
    public function categories(string $platform, ExternalCatalogService $catalog)
    {
        try {
            return response()->json($catalog->categories($platform));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function products(string $platform, Request $request, ExternalCatalogService $catalog)
    {
        try {
            return response()->json($catalog->products(
                $platform,
                $request->query('category'),
                $request->query('q'),
                max(1, (int) $request->query('page', 1))
            ));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function product(string $platform, string $product, ExternalCatalogService $catalog)
    {
        try {
            return response()->json($catalog->product($platform, $product));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
