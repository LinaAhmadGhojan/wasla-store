<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\CreateAttributeAction;
use App\Actions\Catalog\UpdateAttributeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttributeRequest;
use App\Http\Requests\Admin\UpdateAttributeRequest;
use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $query = Attribute::withCount('values')->orderBy('name');

        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return view('admin.attributes.index', [
            'attributes' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin.attributes.create', [
            'attribute' => new Attribute(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreAttributeRequest $request, CreateAttributeAction $action)
    {
        $action->execute($request->validated());

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute created successfully.');
    }

    public function edit(Attribute $attribute)
    {
        return view('admin.attributes.edit', [
            'attribute' => $attribute->load('values', 'categories'),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAttributeRequest $request, Attribute $attribute, UpdateAttributeAction $action)
    {
        $action->execute($attribute, $request->validated());

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted successfully.');
    }
}
