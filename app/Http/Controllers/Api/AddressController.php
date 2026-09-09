<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->addresses()->orderByDesc('is_default')->latest('id')->get()
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $this->normalize($validator->validated());

        if (($data['is_default'] ?? false) || $request->user()->addresses()->count() === 0) {
            $request->user()->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $address = $request->user()->addresses()->create($data);

        return response()->json($address->fresh(), 201);
    }

    public function update(Request $request, Address $address)
    {
        $this->authorize('update', $address);

        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $this->normalize($validator->validated());

        if (! empty($data['is_default'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $address->update($data);

        return response()->json($address->fresh());
    }

    public function setDefault(Request $request, Address $address)
    {
        $this->authorize('update', $address);

        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json($address->fresh());
    }

    public function destroy(Request $request, Address $address)
    {
        $this->authorize('delete', $address);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $request->user()->addresses()->latest('id')->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return response()->json(['message' => 'تم حذف العنوان.']);
    }

    private function rules(): array
    {
        return [
            'label' => 'nullable|string|max:100',
            'label_type' => ['nullable', Rule::in(Address::LABEL_TYPES)],
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'country' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:50',
            'street_address' => 'required|string|max:500',
            'courier_notes' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ];
    }

    private function normalize(array $data): array
    {
        $type = $data['label_type'] ?? Address::LABEL_HOME;
        $data['label_type'] = in_array($type, Address::LABEL_TYPES, true) ? $type : Address::LABEL_HOME;
        $data['country'] = $data['country'] ?? 'سوريا';
        $data['postal_code'] = $data['postal_code'] ?? '-';
        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        if (empty($data['label'])) {
            $data['label'] = match ($data['label_type']) {
                Address::LABEL_WORK => 'العمل',
                Address::LABEL_OTHER => 'عنوان آخر',
                default => 'البيت',
            };
        }

        return $data;
    }
}
