<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ExternalShopping\PreviewExternalProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PreviewExternalProductRequest;

class ExternalProductController extends Controller
{
    public function preview(PreviewExternalProductRequest $request, PreviewExternalProductAction $action)
    {
        $data = $request->validated();

        $result = $action->execute($data['url'], [
            'name' => $data['manual_name'] ?? null,
            'price' => $data['manual_price'] ?? null,
            'currency' => $data['manual_currency'] ?? null,
            'image' => $data['manual_image'] ?? null,
            'sku' => $data['sku'] ?? null,
            'color' => $data['color'] ?? null,
            'size' => $data['size'] ?? null,
        ]);

        return response()->json($result);
    }
}
