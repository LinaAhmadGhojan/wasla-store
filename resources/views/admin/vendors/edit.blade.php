@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Edit Store</h1>
                <p class="text-muted mb-0">Update vendor store details and status.</p>
            </div>
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary">Back to stores</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.vendors.update', $vendor) }}" method="post">
                    @csrf
                    @method('put')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Store Owner</label>
                            <select name="owner_id" class="form-select" required>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('owner_id', $vendor->owner_id) == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Store Name</label>
                            <input type="text" name="store_name" value="{{ old('store_name', $vendor->store_name) }}" class="form-control" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $vendor->slug) }}" class="form-control" placeholder="Optional" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Legal Name</label>
                            <input type="text" name="legal_name" value="{{ old('legal_name', $vendor->legal_name) }}" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Name</label>
                            <input type="text" name="contact_name" value="{{ old('contact_name', $vendor->contact_name) }}" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="contact_email" value="{{ old('contact_email', $vendor->contact_email) }}" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="contact_phone" value="{{ old('contact_phone', $vendor->contact_phone) }}" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <select name="country_id" class="form-select">
                                <option value="">Select country</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" @selected(old('country_id', $vendor->country_id) == $country->id)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <select name="city_id" class="form-select">
                                <option value="">Select city</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" @selected(old('city_id', $vendor->city_id) == $city->id)>{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Area</label>
                            <input type="text" name="area" value="{{ old('area', $vendor->area) }}" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Street Address</label>
                            <input type="text" name="street_address" value="{{ old('street_address', $vendor->street_address) }}" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $vendor->postal_code) }}" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Commission Rate (%)</label>
                            <input type="number" name="commission_rate" value="{{ old('commission_rate', $vendor->commission_rate) }}" class="form-control" step="0.01" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Delivery Fee</label>
                            <input type="number" name="delivery_fee" value="{{ old('delivery_fee', $vendor->delivery_fee) }}" class="form-control" step="0.01" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" @selected(old('status', $vendor->status) == 'pending')>Pending</option>
                                <option value="active" @selected(old('status', $vendor->status) == 'active')>Active</option>
                                <option value="suspended" @selected(old('status', $vendor->status) == 'suspended')>Suspended</option>
                                <option value="closed" @selected(old('status', $vendor->status) == 'closed')>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Featured</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $vendor->is_featured))>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Verified</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_verified" value="1" @checked(old('is_verified', $vendor->is_verified))>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Open</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_open" value="1" @checked(old('is_open', $vendor->is_open))>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Return Policy</label>
                            <textarea name="return_policy" rows="3" class="form-control">{{ old('return_policy', $vendor->return_policy) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $vendor->description) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update store</button>
                        <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
