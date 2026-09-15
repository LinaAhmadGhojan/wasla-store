<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpressCategory;
use App\Models\ExpressMenuItem;
use App\Models\ExpressMenuItemExtra;
use App\Models\ExpressMenuItemVariant;
use App\Models\ExpressStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpressMenuItemController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpressMenuItem::query()->with(['store', 'category'])->orderByDesc('created_at');

        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($storeId = $request->query('store_id')) {
            $query->where('express_store_id', (int) $storeId);
        }

        return view('admin.express.items.index', [
            'items' => $query->paginate(20)->withQueryString(),
            'stores' => ExpressStore::orderBy('store_name')->get(),
            'search' => $search,
            'storeId' => $storeId,
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.express.items.form', [
            'item' => new ExpressMenuItem([
                'express_store_id' => $request->query('store_id'),
                'is_active' => true,
                'price_syp' => 10000,
            ]),
            'stores' => ExpressStore::orderBy('store_name')->get(),
            'categories' => ExpressCategory::orderBy('sort_order')->get(),
            'variants' => collect(),
            'extras' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $item = ExpressMenuItem::create($this->validated($request));
        $this->syncOptions($request, $item);

        return redirect()->route('admin.express-items.index')->with('success', 'تم إضافة الطبق.');
    }

    public function edit(ExpressMenuItem $express_item)
    {
        $express_item->load(['variants', 'extras']);

        return view('admin.express.items.form', [
            'item' => $express_item,
            'stores' => ExpressStore::orderBy('store_name')->get(),
            'categories' => ExpressCategory::orderBy('sort_order')->get(),
            'variants' => $express_item->variants,
            'extras' => $express_item->extras,
        ]);
    }

    public function update(Request $request, ExpressMenuItem $express_item)
    {
        $express_item->update($this->validated($request, $express_item));
        $this->syncOptions($request, $express_item);

        return redirect()->route('admin.express-items.index')->with('success', 'تم تحديث الطبق.');
    }

    public function destroy(ExpressMenuItem $express_item)
    {
        $express_item->delete();

        return redirect()->route('admin.express-items.index')->with('success', 'تم حذف الطبق.');
    }

    private function validated(Request $request, ?ExpressMenuItem $item = null): array
    {
        $data = $request->validate([
            'express_store_id' => 'required|exists:express_stores,id',
            'express_category_id' => 'nullable|exists:express_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:express_menu_items,slug,'.($item?->id ?? 'NULL'),
            'description' => 'nullable|string|max:2000',
            'unit_label' => 'nullable|string|max:50',
            'serving_note' => 'nullable|string|max:500',
            'ingredients' => 'nullable|string|max:2000',
            'image' => 'nullable|string|max:500',
            'price_syp' => 'required|integer|min:0',
            'sale_price_syp' => 'nullable|integer|min:0',
            'eta_min_minutes' => 'nullable|integer|min:1|max:180',
            'is_offer' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'variants' => 'nullable|array',
            'variants.*.label' => 'nullable|string|max:120',
            'variants.*.unit_label' => 'nullable|string|max:50',
            'variants.*.price_syp' => 'nullable|integer|min:0',
            'variants.*.sale_price_syp' => 'nullable|integer|min:0',
            'variants.*.is_default' => 'sometimes|boolean',
            'extras' => 'nullable|array',
            'extras.*.group_name' => 'nullable|string|max:80',
            'extras.*.label' => 'nullable|string|max:120',
            'extras.*.price_delta_syp' => 'nullable|integer',
            'extras.*.is_default' => 'sometimes|boolean',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']).'-'.Str::random(4);
        $data['sale_price_syp'] = $data['sale_price_syp'] !== null && $data['sale_price_syp'] !== ''
            ? (int) $data['sale_price_syp']
            : null;
        $data['eta_min_minutes'] = $data['eta_min_minutes'] !== null && $data['eta_min_minutes'] !== ''
            ? (int) $data['eta_min_minutes']
            : null;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['rating'] = (float) ($data['rating'] ?? 0);
        $data['is_offer'] = filter_var($request->input('is_offer'), FILTER_VALIDATE_BOOLEAN);
        $data['is_featured'] = filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN);
        $data['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);

        unset($data['variants'], $data['extras']);

        return $data;
    }

    private function syncOptions(Request $request, ExpressMenuItem $item): void
    {
        $item->variants()->delete();
        $item->extras()->delete();

        foreach ($request->input('variants', []) as $i => $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '' || ! isset($row['price_syp']) || $row['price_syp'] === '') {
                continue;
            }
            ExpressMenuItemVariant::query()->create([
                'express_menu_item_id' => $item->id,
                'label' => $label,
                'unit_label' => $row['unit_label'] ?? null,
                'price_syp' => (int) $row['price_syp'],
                'sale_price_syp' => ($row['sale_price_syp'] ?? '') !== '' ? (int) $row['sale_price_syp'] : null,
                'is_default' => filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        foreach ($request->input('extras', []) as $i => $row) {
            $group = trim((string) ($row['group_name'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            if ($group === '' || $label === '') {
                continue;
            }
            ExpressMenuItemExtra::query()->create([
                'express_menu_item_id' => $item->id,
                'group_name' => $group,
                'label' => $label,
                'price_delta_syp' => (int) ($row['price_delta_syp'] ?? 0),
                'is_default' => filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }
    }
}
