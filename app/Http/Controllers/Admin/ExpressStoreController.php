<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpressCategory;
use App\Models\ExpressStore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpressStoreController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpressStore::query()->with(['category', 'owner'])->orderByDesc('created_at');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return view('admin.express.stores.index', [
            'stores' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin.express.stores.form', [
            'store' => new ExpressStore([
                'status' => 'active',
                'is_open' => true,
                'delivery_fee_syp' => 5000,
                'eta_min_minutes' => 20,
                'eta_max_minutes' => 35,
                'commission_rate' => 15,
            ]),
            'categories' => ExpressCategory::orderBy('sort_order')->get(),
            'users' => User::orderBy('name')->limit(200)->get(),
        ]);
    }

    public function store(Request $request)
    {
        ExpressStore::create($this->validated($request));

        return redirect()->route('admin.express-stores.index')->with('success', 'تم إضافة متجر طلباتي .');
    }

    public function edit(ExpressStore $express_store)
    {
        return view('admin.express.stores.form', [
            'store' => $express_store,
            'categories' => ExpressCategory::orderBy('sort_order')->get(),
            'users' => User::orderBy('name')->limit(200)->get(),
        ]);
    }

    public function update(Request $request, ExpressStore $express_store)
    {
        $express_store->update($this->validated($request, $express_store));

        return redirect()->route('admin.express-stores.index')->with('success', 'تم تحديث المتجر.');
    }

    public function destroy(ExpressStore $express_store)
    {
        $express_store->delete();

        return redirect()->route('admin.express-stores.index')->with('success', 'تم حذف المتجر.');
    }

    private function validated(Request $request, ?ExpressStore $store = null): array
    {
        $data = $request->validate([
            'owner_id' => 'nullable|exists:users,id',
            'express_category_id' => 'nullable|exists:express_categories,id',
            'store_name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:express_stores,slug,'.($store?->id ?? 'NULL'),
            'cuisine' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'logo' => 'nullable|string|max:500',
            'banner' => 'nullable|string|max:500',
            'area' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'delivery_fee_syp' => 'nullable|integer|min:0',
            'eta_min_minutes' => 'nullable|integer|min:1|max:180',
            'eta_max_minutes' => 'nullable|integer|min:1|max:180',
            'rating' => 'nullable|numeric|min:0|max:5',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:pending,active,suspended,closed',
            'is_featured' => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',
            'is_open' => 'sometimes|boolean',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['store_name']);
        $data['delivery_fee_syp'] = (int) ($data['delivery_fee_syp'] ?? 0);
        $data['eta_min_minutes'] = (int) ($data['eta_min_minutes'] ?? 20);
        $data['eta_max_minutes'] = (int) ($data['eta_max_minutes'] ?? 35);
        $data['commission_rate'] = (float) ($data['commission_rate'] ?? 15);
        $data['rating'] = (float) ($data['rating'] ?? 0);
        $data['is_featured'] = filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN);
        $data['is_verified'] = filter_var($request->input('is_verified'), FILTER_VALIDATE_BOOLEAN);
        $data['is_open'] = filter_var($request->input('is_open'), FILTER_VALIDATE_BOOLEAN);

        return $data;
    }
}
