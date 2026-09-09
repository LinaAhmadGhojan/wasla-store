<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\WishlistList;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WishlistController extends Controller
{
    public function lists(Request $request)
    {
        $user = $request->user();
        WishlistList::defaultFor($user);

        $lists = WishlistList::query()
            ->where('user_id', $user->id)
            ->withCount('items')
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (WishlistList $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'slug' => $l->slug,
                'is_default' => $l->is_default,
                'items_count' => $l->items_count,
            ]);

        return response()->json(['lists' => $lists]);
    }

    public function createList(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
        ]);

        $user = $request->user();
        WishlistList::defaultFor($user);

        $list = WishlistList::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) ?: 'list',
            'is_default' => false,
            'sort_order' => (int) WishlistList::query()->where('user_id', $user->id)->max('sort_order') + 1,
        ]);

        return response()->json([
            'ok' => true,
            'list' => [
                'id' => $list->id,
                'name' => $list->name,
                'slug' => $list->slug,
                'is_default' => false,
                'items_count' => 0,
            ],
        ], 201);
    }

    public function renameList(Request $request, WishlistList $list)
    {
        $this->assertListOwner($request, $list);
        $data = $request->validate(['name' => 'required|string|max:80']);
        $list->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) ?: $list->slug,
        ]);

        return response()->json(['ok' => true, 'list' => $list]);
    }

    public function deleteList(Request $request, WishlistList $list)
    {
        $this->assertListOwner($request, $list);
        if ($list->is_default) {
            throw ValidationException::withMessages(['list' => 'لا يمكن حذف القائمة الافتراضية.']);
        }
        $list->delete();

        return response()->json(['ok' => true]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $default = WishlistList::defaultFor($user);
        $listId = (int) ($request->query('list_id') ?: $default->id);
        $list = WishlistList::query()->where('user_id', $user->id)->findOrFail($listId);

        $items = Wishlist::query()
            ->with(['product.images', 'product.brand', 'product.vendor', 'product.variants'])
            ->where('user_id', $user->id)
            ->where('list_id', $list->id)
            ->latest()
            ->get()
            ->map(fn (Wishlist $w) => $this->serializeItem($w));

        return response()->json([
            'list' => [
                'id' => $list->id,
                'name' => $list->name,
                'is_default' => $list->is_default,
            ],
            'items' => $items,
        ]);
    }

    public function ids(Request $request)
    {
        $ids = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->pluck('product_id')
            ->unique()
            ->values();

        return response()->json(['product_ids' => $ids]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'list_id' => 'nullable|exists:wishlist_lists,id',
        ]);

        $user = $request->user();
        $list = ! empty($data['list_id'])
            ? WishlistList::query()->where('user_id', $user->id)->findOrFail($data['list_id'])
            : WishlistList::defaultFor($user);

        $row = Wishlist::query()->firstOrCreate([
            'list_id' => $list->id,
            'product_id' => $data['product_id'],
        ], [
            'user_id' => $user->id,
        ]);

        // also ensure user_id set on race
        if (! $row->user_id) {
            $row->update(['user_id' => $user->id]);
        }

        return response()->json([
            'ok' => true,
            'id' => $row->id,
            'list_id' => $list->id,
        ], 201);
    }

    public function destroy(Request $request, int $productId)
    {
        $q = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $productId);

        if ($request->filled('list_id')) {
            $q->where('list_id', (int) $request->query('list_id'));
        }

        $q->delete();

        return response()->json(['ok' => true]);
    }

    public function move(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_list_id' => 'required|exists:wishlist_lists,id',
            'to_list_id' => 'required|exists:wishlist_lists,id',
        ]);

        $user = $request->user();
        $from = WishlistList::query()->where('user_id', $user->id)->findOrFail($data['from_list_id']);
        $to = WishlistList::query()->where('user_id', $user->id)->findOrFail($data['to_list_id']);

        Wishlist::query()
            ->where('user_id', $user->id)
            ->where('list_id', $from->id)
            ->where('product_id', $data['product_id'])
            ->delete();

        $row = Wishlist::query()->firstOrCreate([
            'list_id' => $to->id,
            'product_id' => $data['product_id'],
        ], [
            'user_id' => $user->id,
        ]);

        return response()->json(['ok' => true, 'id' => $row->id, 'list_id' => $to->id]);
    }

    private function assertListOwner(Request $request, WishlistList $list): void
    {
        if ((int) $list->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }

    private function serializeItem(Wishlist $w): array
    {
        $p = $w->product;

        return [
            'id' => $w->id,
            'list_id' => $w->list_id,
            'product_id' => $w->product_id,
            'product' => $p ? [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'sale_price' => $p->sale_price,
                'pricing_kind' => $p->pricing_kind,
                'image' => $p->image
                    ?? $p->images->first()?->url
                    ?? $p->images->first()?->path
                    ?? null,
                'url' => url('/products/'.$p->id),
                'brand' => $p->brand?->name,
                'vendor_id' => $p->vendor_id,
                'in_stock' => $p->variants->isEmpty()
                    || $p->variants->contains(fn ($v) => (int) $v->stock_qty > 0),
                'default_variant_id' => $p->variants->firstWhere('stock_qty', '>', 0)?->id
                    ?? $p->variants->first()?->id,
            ] : null,
        ];
    }
}
