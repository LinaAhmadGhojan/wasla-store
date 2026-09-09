<?php

namespace App\Actions\Catalog;

use App\Models\Attribute;
use Illuminate\Support\Str;

class CreateAttributeAction
{
    public function execute(array $data): Attribute
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $attribute = Attribute::create($data);
        $attribute->categories()->sync($categoryIds);

        return $attribute;
    }
}
