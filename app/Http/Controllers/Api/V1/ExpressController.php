<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Express\ExpressBrowseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpressController extends Controller
{
    public function __construct(private ExpressBrowseService $browse)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $payload = $this->browse->browse($request);

        return response()->json($payload)->header(
            'Cache-Control',
            'public, max-age=30, stale-while-revalidate=60'
        );
    }

    public function facets(Request $request): JsonResponse
    {
        $payload = $this->browse->facets($request);

        return response()->json($payload)->header(
            'Cache-Control',
            'public, max-age=60, stale-while-revalidate=120'
        );
    }

    public function showStore(Request $request, int $store): JsonResponse
    {
        $payload = $this->browse->store($store, $request->query('tab'));

        abort_if($payload === null, 404, 'المتجر غير موجود');

        return response()->json($payload)->header(
            'Cache-Control',
            'public, max-age=30, stale-while-revalidate=60'
        );
    }

    public function showItem(int $item): JsonResponse
    {
        $payload = $this->browse->item($item);

        abort_if($payload === null, 404, 'الطبق غير موجود');

        return response()->json($payload)->header(
            'Cache-Control',
            'public, max-age=30, stale-while-revalidate=60'
        );
    }
}
