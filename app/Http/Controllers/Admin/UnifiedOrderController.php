<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalPlatform;
use App\Services\Orders\UnifiedOrderFeed;
use Illuminate\Http\Request;

class UnifiedOrderController extends Controller
{
    public function index(Request $request, UnifiedOrderFeed $feed)
    {
        $source = $request->query('source');
        $status = $request->query('status');

        $rows = $feed->forAdmin($source, $status);
        $platforms = ExternalPlatform::query()->orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.orders.unified', [
            'rows' => $rows,
            'platforms' => $platforms,
            'source' => $source,
            'status' => $status,
        ]);
    }
}
