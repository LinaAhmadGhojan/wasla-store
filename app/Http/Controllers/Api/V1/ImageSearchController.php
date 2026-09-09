<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ImageSearch\VisualSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ImageSearchController extends Controller
{
    public function __invoke(Request $request, VisualSearchService $search): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'max:8192'], // 8MB
            'limit' => ['sometimes', 'integer', 'min:1', 'max:48'],
        ]);

        try {
            $hits = $search->searchByUpload(
                $request->file('image'),
                isset($data['limit']) ? (int) $data['limit'] : null
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => [],
            ], 503);
        }

        $payload = array_map(function (array $hit) {
            $product = $hit['product'];

            return [
                'score' => $hit['score'],
                'match' => $hit['match'],
                'product' => $product,
            ];
        }, $hits);

        return response()->json([
            'driver' => config('image_search.driver'),
            'count' => count($payload),
            'data' => $payload,
        ]);
    }
}
