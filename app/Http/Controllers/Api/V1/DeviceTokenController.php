<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Push\FcmPushService;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request, FcmPushService $fcm)
    {
        $data = $request->validate([
            'token' => 'required|string|min:20|max:512',
            'platform' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:120',
            'app' => 'nullable|string|in:customer,driver,admin',
        ]);

        $row = $fcm->register(
            $request->user(),
            $data['token'],
            $data['platform'] ?? null,
            $data['device_name'] ?? null,
            $data['app'] ?? 'customer'
        );

        return response()->json([
            'ok' => true,
            'fcm_ready' => $fcm->enabled(),
            'device_token_id' => $row->id,
        ], 201);
    }

    public function destroy(Request $request, FcmPushService $fcm)
    {
        $data = $request->validate([
            'token' => 'required|string|max:512',
        ]);

        $fcm->unregister($request->user(), $data['token']);

        return response()->json(['ok' => true]);
    }

    public function status(FcmPushService $fcm)
    {
        return response()->json([
            'fcm_ready' => $fcm->enabled(),
            'project_id' => $fcm->enabled() ? config('firebase.project_id') : null,
        ]);
    }
}
