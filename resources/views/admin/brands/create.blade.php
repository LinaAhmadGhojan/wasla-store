@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Create Brand</h1>
                <p class="text-muted mb-0">Add a new brand that products can be associated with.</p>
            </div>
            <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Back to brands</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.brands.store') }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', $brand->name) }}" class="form-control" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $brand->slug) }}" class="form-control" placeholder="Optional" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Logo URL</label>
                            <input type="text" name="logo" value="{{ old('logo', $brand->logo) }}" class="form-control" placeholder="https://..." />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Banner URL</label>
                            <input type="text" name="banner" value="{{ old('banner', $brand->banner) }}" class="form-control" placeholder="https://..." />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1" @selected(old('is_active', true) == 1)>Active</option>
                                <option value="0" @selected(old('is_active', true) == 0)>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $brand->description) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create brand</button>
                        <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
