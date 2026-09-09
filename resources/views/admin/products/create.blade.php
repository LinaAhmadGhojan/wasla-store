@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Create Product</h1>
                <p class="text-muted mb-0">Add a new product to the marketplace catalog.</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Back to products</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.products.store') }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="form-control" placeholder="Optional" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" class="form-select" required>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->store_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Uncategorized</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">No brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" value="{{ old('price', $product->price) }}" class="form-control" step="0.01" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sale Price</label>
                            <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" class="form-control" step="0.01" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Featured</label>
                            <select name="is_featured" class="form-select">
                                <option value="0" @selected(old('is_featured', false) == 0)>No</option>
                                <option value="1" @selected(old('is_featured', false) == 1)>Yes</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1" @selected(old('is_active', true) == 1)>Active</option>
                                <option value="0" @selected(old('is_active', true) == 0)>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save product</button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
