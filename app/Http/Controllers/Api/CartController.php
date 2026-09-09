<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $items = $request->user()->cartItems()
            ->with(['product.category.parent', 'product.vendor', 'product.variants', 'product.images', 'variant'])
            ->orderBy('saved_for_later')
            ->latest('id')
            ->get();

        return response()->json([
            'items' => $items->where('saved_for_later', false)->values(),
            'saved_for_later' => $items->where('saved_for_later', true)->values(),
            'meta' => [
                'low_stock_threshold' => (int) config('shipping.low_stock_threshold', 3),
                'alerts' => $this->buildAlerts($items->where('saved_for_later', false)),
            ],
        ]);
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'saved_for_later' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $savedForLater = $request->boolean('saved_for_later');

        $variant = $request->variant_id
            ? ProductVariant::query()->find($request->variant_id)
            : null;

        $unit = $variant
            ? (float) ($variant->sale_price ?: $variant->price)
            : null;

        if ($unit === null) {
            $product = \App\Models\Product::query()->find($request->product_id);
            $unit = (float) ($product?->sale_price ?: $product?->price ?: 0);
        }

        $cartItem = $request->user()->cartItems()->updateOrCreate([
            'product_id' => $request->product_id,
            'product_variant_id' => $request->variant_id,
            'saved_for_later' => $savedForLater,
        ], [
            'quantity' => $request->quantity,
            'price_snapshot' => $unit,
            'saved_for_later' => $savedForLater,
        ]);

        return response()->json($cartItem->load(['product', 'variant']), 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'sometimes|integer|min:1',
            'variant_id' => 'sometimes|nullable|exists:product_variants,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cartItem = $request->user()->cartItems()->where('id', $id)->first();
        if (! $cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $data = [];
        if ($request->filled('quantity')) {
            $data['quantity'] = (int) $request->quantity;
        }
        if ($request->has('variant_id')) {
            $data['product_variant_id'] = $request->variant_id;
            $variant = ProductVariant::query()->find($request->variant_id);
            if ($variant) {
                $data['price_snapshot'] = (float) ($variant->sale_price ?: $variant->price);
            }
        }

        $cartItem->update($data);

        return response()->json($cartItem->fresh()->load(['product.variants', 'variant']));
    }

    public function saveForLater(Request $request, $id)
    {
        $cartItem = $request->user()->cartItems()->where('id', $id)->first();
        if (! $cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }
        $cartItem->update(['saved_for_later' => true]);

        return response()->json(['ok' => true, 'item' => $cartItem->fresh()->load(['product', 'variant'])]);
    }

    public function moveToCart(Request $request, $id)
    {
        $cartItem = $request->user()->cartItems()->where('id', $id)->first();
        if (! $cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }
        $cartItem->update([
            'saved_for_later' => false,
            'price_snapshot' => $cartItem->currentUnitPrice(),
        ]);

        return response()->json(['ok' => true, 'item' => $cartItem->fresh()->load(['product', 'variant'])]);
    }

    public function remove(Request $request, $id)
    {
        $request->user()->cartItems()->where('id', $id)->delete();

        return response()->json(['message' => 'Cart item removed']);
    }

    private function buildAlerts($items): array
    {
        $alerts = [];
        foreach ($items as $item) {
            if ($item->out_of_stock) {
                $alerts[] = [
                    'type' => 'out_of_stock',
                    'cart_item_id' => $item->id,
                    'message' => ($item->product?->name ?? 'منتج').' نفد من المخزون.',
                ];
            } elseif ($item->low_stock) {
                $alerts[] = [
                    'type' => 'low_stock',
                    'cart_item_id' => $item->id,
                    'message' => ($item->product?->name ?? 'منتج').' أوشك على النفاد (متبقي '.$item->stock_qty.').',
                ];
            }
            if ($item->price_changed) {
                $alerts[] = [
                    'type' => 'price_changed',
                    'cart_item_id' => $item->id,
                    'message' => 'تغير سعر '.($item->product?->name ?? 'منتج').' من '.$item->price_snapshot.' إلى '.$item->unit_price_now.'.',
                ];
            }
        }

        return $alerts;
    }
}
