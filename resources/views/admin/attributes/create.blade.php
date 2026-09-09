@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Create Attribute</h1>
                <p class="text-muted mb-0">Define a new attribute, e.g. Color, Size or Material.</p>
            </div>
            <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary">Back to attributes</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.attributes.store') }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', $attribute->name) }}" class="form-control" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $attribute->slug) }}" class="form-control" placeholder="Optional" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="text" @selected(old('type', 'text') == 'text')>Text</option>
                                <option value="color" @selected(old('type') == 'color')>Color</option>
                                <option value="number" @selected(old('type') == 'number')>Number</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Applies to categories</label>
                            <div class="row">
                                @foreach($categories as $category)
                                    <div class="col-md-3 form-check ms-3">
                                        <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $category->id }}" id="cat-{{ $category->id }}" @checked(in_array($category->id, old('category_ids', [])))>
                                        <label class="form-check-label" for="cat-{{ $category->id }}">{{ $category->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create attribute</button>
                        <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
