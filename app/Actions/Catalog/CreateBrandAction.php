<?php

namespace App\Actions\Catalog;

use App\Models\Brand;
use Illuminate\Support\Str;

class CreateBrandAction
{
    public function execute(array $data): Brand
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return Brand::create($data);
    }
}
