<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExternalPlatform;

class ExternalPlatformController extends Controller
{
    public function index()
    {
        $platforms = ExternalPlatform::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (ExternalPlatform $platform) {
                return [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'slug' => $platform->slug,
                    'logo' => $platform->logo,
                    'website' => $platform->website,
                    'type' => $platform->type,
                    'currency' => $platform->currency,
                    'country' => $platform->country,
                ];
            });

        return response()->json($platforms);
    }
}
