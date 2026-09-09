@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Manage Attribute</h1>
                <p class="text-muted mb-0">Update attribute details and its values.</p>
            </div>
            <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary">Back to attributes</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="{{ route('admin.attributes.update', $attribute) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', $attribute->name) }}" class="form-control" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $attribute->slug) }}" class="form-control" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="text" @selected(old('type', $attribute->type) == 'text')>Text</option>
                                <option value="color" @selected(old('type', $attribute->type) == 'color')>Color</option>
                                <option value="number" @selected(old('type', $attribute->type) == 'number')>Number</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Applies to categories</label>
                            <div class="row">
                                @php $selectedCategories = old('category_ids', $attribute->categories->pluck('id')->toArray()); @endphp
                                @foreach($categories as $category)
                                    <div class="col-md-3 form-check ms-3">
                                        <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $category->id }}" id="cat-{{ $category->id }}" @checked(in_array($category->id, $selectedCategories))>
                                        <label class="form-check-label" for="cat-{{ $category->id }}">{{ $category->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update attribute</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h2 class="h5 mb-3">Values</h2>

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Value</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attribute->values as $value)
                                <tr>
                                    <td>{{ $value->value }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.attributes.values.destroy', [$attribute, $value]) }}" method="post" class="d-inline-block">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this value?')">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-3">No values yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form action="{{ route('admin.attributes.values.store', $attribute) }}" method="post" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="value" class="form-control" placeholder="e.g. Red, XL, Cotton" required />
                    <button type="submit" class="btn btn-primary text-nowrap">Add value</button>
                </form>
            </div>
        </div>
    </div>
@endsection
