<?php

namespace App\Actions\ExternalShopping;

use App\Models\ExternalPlatform;
use Illuminate\Support\Str;

class CreateExternalPlatformAction
{
    public function execute(array $data): ExternalPlatform
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return ExternalPlatform::create($data);
    }
}
