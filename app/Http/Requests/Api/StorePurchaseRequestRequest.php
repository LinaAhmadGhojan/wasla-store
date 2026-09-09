<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform_id' => 'nullable|exists:external_platforms,id',
            'url' => 'nullable|string|max:2000',
            'customer_notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.external_product_id' => 'nullable|exists:external_products,id',
            'items.*.url' => 'nullable|string|max:2000',
            'items.*.variant_data' => 'nullable|array',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
