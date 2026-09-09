<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Whatsapp\WhatsappGatewayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class WhatsappConnectionController extends Controller
{
    public function show(WhatsappGatewayClient $gateway): View
    {
        return view('admin.whatsapp.connection', [
            'gatewayEnabled' => $gateway->isEnabled(),
            'status' => $gateway->status(),
        ]);
    }

    public function status(WhatsappGatewayClient $gateway): JsonResponse
    {
        return response()->json($gateway->status());
    }

    public function reset(WhatsappGatewayClient $gateway): JsonResponse
    {
        try {
            $gateway->resetSession();

            return response()->json(['ok' => true, 'message' => 'تم إعادة تعيين الجلسة — انتظري QR']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function groups(WhatsappGatewayClient $gateway): JsonResponse
    {
        $status = $gateway->status();
        if (! ($status['ready'] ?? false)) {
            return response()->json([
                'ready' => false,
                'groups' => [],
                'error' => $status['error'] ?? 'not_ready',
            ]);
        }

        return response()->json([
            'ready' => true,
            'groups' => $gateway->listGroups(),
        ]);
    }
}
