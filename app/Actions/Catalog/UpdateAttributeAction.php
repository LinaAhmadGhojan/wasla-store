<?php

namespace App\Actions\Catalog;

use App\Models\Attribute;
use Illuminate\Support\Str;

class UpdateAttributeAction
{
    public function execute(Attribute $attribute, array $data): Attribute
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $attribute->update($data);
        $attribute->categories()->sync($categoryIds);

        return $attribute;
    }
}
