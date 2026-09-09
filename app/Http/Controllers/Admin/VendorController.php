<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::orderBy('created_at', 'desc');

        if ($search = $request->query('q')) {
            $query->where('store_name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
        }

        return view('admin.vendors.index', [
            'vendors' => $query->with('owner')->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        $sellerRole = Role::firstWhere('name', 'seller');

        return view('admin.vendors.create', [
            'vendor' => new Vendor(),
            'users' => User::when($sellerRole, fn ($q) => $q->where('role_id', $sellerRole->id))->orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'store_name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:stores',
            'legal_name' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:2000',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'area' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'delivery_fee' => 'nullable|numeric|min:0',
            'return_policy' => 'nullable|string|max:2000',
            'status' => 'required|in:pending,active,suspended,closed',
            'is_featured' => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',
            'is_open' => 'sometimes|boolean',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['store_name']);
        $data['is_featured'] = filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN);
        $data['is_verified'] = filter_var($request->input('is_verified'), FILTER_VALIDATE_BOOLEAN);
        $data['is_open'] = filter_var($request->input('is_open'), FILTER_VALIDATE_BOOLEAN);

        Vendor::create($data);

        return redirect()->route('admin.vendors.index')->with('success', 'Store created successfully.');
    }

    public function edit(Vendor $vendor)
    {
        $sellerRole = Role::firstWhere('name', 'seller');

        return view('admin.vendors.edit', [
            'vendor' => $vendor,
            'users' => User::when($sellerRole, fn ($q) => $q->where('role_id', $sellerRole->id))->orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'store_name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:stores,slug,' . $vendor->id,
            'legal_name' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:2000',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'area' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'delivery_fee' => 'nullable|numeric|min:0',
            'return_policy' => 'nullable|string|max:2000',
            'status' => 'required|in:pending,active,suspended,closed',
            'is_featured' => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',
            'is_open' => 'sometimes|boolean',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['store_name']);
        $data['is_featured'] = filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN);
        $data['is_verified'] = filter_var($request->input('is_verified'), FILTER_VALIDATE_BOOLEAN);
        $data['is_open'] = filter_var($request->input('is_open'), FILTER_VALIDATE_BOOLEAN);

        $vendor->update($data);

        return redirect()->route('admin.vendors.index')->with('success', 'Store updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
    }
}
