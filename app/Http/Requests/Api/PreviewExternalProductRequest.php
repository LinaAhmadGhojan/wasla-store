<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PreviewExternalProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => 'required|string|max:2000',
            'platform_id' => 'nullable|exists:external_platforms,id',
            // Filled in by the shopper when auto-fetch is blocked (e.g. SHEIN bot-check),
            // so we can quote AED prices from real data instead of a rough estimate.
            'manual_name' => 'nullable|string|max:255',
            'manual_price' => 'nullable|numeric|min:0|max:1000000',
            'manual_currency' => 'nullable|string|max:8',
            'manual_image' => 'nullable|string|max:2000',
            'sku' => 'nullable|string|max:80',
            'color' => 'nullable|string|max:80',
            'size' => 'nullable|string|max:80',
        ];
    }
}
