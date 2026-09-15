<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpressCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpressCategoryController extends Controller
{
    public function index()
    {
        return view('admin.express.categories.index', [
            'categories' => ExpressCategory::query()->orderBy('sort_order')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.express.categories.form', [
            'category' => new ExpressCategory(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        ExpressCategory::create($data);

        return redirect()->route('admin.express-categories.index')->with('success', 'تم إنشاء التصنيف.');
    }

    public function edit(ExpressCategory $express_category)
    {
        return view('admin.express.categories.form', [
            'category' => $express_category,
        ]);
    }

    public function update(Request $request, ExpressCategory $express_category)
    {
        $express_category->update($this->validated($request, $express_category));

        return redirect()->route('admin.express-categories.index')->with('success', 'تم تحديث التصنيف.');
    }

    public function destroy(ExpressCategory $express_category)
    {
        $express_category->delete();

        return redirect()->route('admin.express-categories.index')->with('success', 'تم حذف التصنيف.');
    }

    private function validated(Request $request, ?ExpressCategory $category = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:express_categories,slug,'.($category?->id ?? 'NULL'),
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);

        return $data;
    }
}
