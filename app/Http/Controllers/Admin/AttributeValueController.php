<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\AddAttributeValueAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttributeValueRequest;
use App\Models\Attribute;
use App\Models\AttributeValue;

class AttributeValueController extends Controller
{
    public function store(StoreAttributeValueRequest $request, Attribute $attribute, AddAttributeValueAction $action)
    {
        $action->execute($attribute, $request->validated());

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Value added successfully.');
    }

    public function destroy(Attribute $attribute, AttributeValue $value)
    {
        abort_unless($value->attribute_id === $attribute->id, 404);

        $value->delete();

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Value removed successfully.');
    }
}
