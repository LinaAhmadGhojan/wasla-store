<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnExchangeRequest;
use App\Services\Orders\ReturnExchangePolicy;
use Illuminate\Http\Request;

class ReturnExchangeController extends Controller
{
    public function eligible(Order $order, Request $request, ReturnExchangePolicy $policy)
    {
        $this->authorizeOrder($order, $request);

        return response()->json([
            'order_id' => $order->id,
            'delivered' => $order->status === 'delivered',
            'defaults' => $policy->defaults(),
            'items' => $policy->eligibleItemsForOrder($order, $request->user()),
        ]);
    }

    public function store(Request $request, ReturnExchangePolicy $policy)
    {
        $data = $request->validate([
            'order_item_id' => 'required|integer|exists:order_items,id',
            'type' => 'required|in:return,exchange',
            'quantity' => 'nullable|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'pickup_at' => 'nullable|date|after:now',
            'pickup_address' => 'nullable|string|max:2000',
            'exchange_variant_id' => 'nullable|integer|exists:product_variants,id',
            'exchange_size_label' => 'nullable|string|max:120',
            'refund_method' => 'nullable|in:store_credit,original,bank',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
        ]);

        $item = OrderItem::query()->with(['order', 'product', 'variant'])->findOrFail($data['order_item_id']);
        $row = $policy->createRequest($request->user(), $item, $data['type'], [
            'quantity' => $data['quantity'] ?? null,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'pickup_at' => $data['pickup_at'] ?? null,
            'pickup_address' => $data['pickup_address'] ?? null,
            'exchange_variant_id' => $data['exchange_variant_id'] ?? null,
            'exchange_size_label' => $data['exchange_size_label'] ?? null,
            'refund_method' => $data['refund_method'] ?? 'store_credit',
            'images' => $request->file('images') ?: [],
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'تم إرسال طلب '.$row->typeLabel().' — بانتظار مراجعة وصلة.',
            'request' => $policy->serialize($row),
        ], 201);
    }

    public function mine(Request $request, ReturnExchangePolicy $policy)
    {
        $rows = ReturnExchangeRequest::query()
            ->with(['orderItem', 'product', 'exchangeVariant'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ReturnExchangeRequest $r) => $policy->serialize($r));

        return response()->json(['data' => $rows]);
    }

    public function show(ReturnExchangeRequest $returnExchangeRequest, Request $request, ReturnExchangePolicy $policy)
    {
        if ((int) $returnExchangeRequest->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'request' => $policy->serialize($returnExchangeRequest),
        ]);
    }

    public function forOrder(Order $order, Request $request, ReturnExchangePolicy $policy)
    {
        $this->authorizeOrder($order, $request);

        $rows = ReturnExchangeRequest::query()
            ->with(['orderItem', 'product', 'exchangeVariant'])
            ->where('user_id', $request->user()->id)
            ->where('order_id', $order->id)
            ->latest()
            ->get()
            ->map(fn (ReturnExchangeRequest $r) => $policy->serialize($r));

        return response()->json(['data' => $rows]);
    }

    private function authorizeOrder(Order $order, Request $request): void
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }
}
