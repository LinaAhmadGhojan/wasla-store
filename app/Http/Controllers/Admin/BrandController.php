<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\CreateBrandAction;
use App\Actions\Catalog\UpdateBrandAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::withCount('products')->orderBy('name');

        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
        }

        return view('admin.brands.index', [
            'brands' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin.brands.create', [
            'brand' => new Brand(),
        ]);
    }

    public function store(StoreBrandRequest $request, CreateBrandAction $action)
    {
        $action->execute($request->validated());

        return redirect()->route('admin.brands.index')->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', [
            'brand' => $brand,
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand, UpdateBrandAction $action)
    {
        $action->execute($brand, $request->validated());

        return redirect()->route('admin.brands.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', 'Brand deleted successfully.');
    }
}
