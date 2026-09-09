<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalPlatformRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:external_platforms,slug',
            'logo' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'type' => 'required|string|max:100',
            'status' => 'required|in:active,inactive',
            'currency' => 'required|string|max:10',
            'country' => 'nullable|string|max:10',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'markup_rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'required|boolean',
        ];
    }
}
