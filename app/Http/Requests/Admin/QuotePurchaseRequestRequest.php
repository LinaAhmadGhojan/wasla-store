<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuotePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estimated_price' => 'nullable|numeric|min:0',
            'final_price' => 'nullable|numeric|min:0',
            'admin_notes' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:purchase_request_items,id',
            'items.*.source_price' => 'nullable|numeric|min:0',
            'items.*.service_fee' => 'nullable|numeric|min:0',
            'items.*.shipping_fee' => 'nullable|numeric|min:0',
            'items.*.final_price' => 'nullable|numeric|min:0',
        ];
    }
}
