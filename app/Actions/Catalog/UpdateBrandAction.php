<?php

namespace App\Actions\Catalog;

use App\Models\Brand;
use Illuminate\Support\Str;

class UpdateBrandAction
{
    public function execute(Brand $brand, array $data): Brand
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $brand->update($data);

        return $brand;
    }
}
