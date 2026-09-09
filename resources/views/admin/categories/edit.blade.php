@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Edit Category</h1>
                <p class="text-muted mb-0">Update category details and parent assignment.</p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Back to categories</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.categories.update', $category) }}" method="post">
                    @csrf
                    @method('put')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control" required />
                        </div>
                        @include('admin.categories._icon_field')
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" class="form-control" placeholder="Optional" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Parent category</label>
                            <select name="parent_id" class="form-select">
                                <option value="">None</option>
                                @foreach($parents as $parent)
                                    <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1" @selected(old('is_active', $category->is_active) == 1)>Active</option>
                                <option value="0" @selected(old('is_active', $category->is_active) == 0)>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $category->description) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update category</button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
