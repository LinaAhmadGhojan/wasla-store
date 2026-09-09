@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Attributes</h1>
                <p class="text-muted mb-0">Manage generic product attributes such as Color, Size or Material.</p>
            </div>
            <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary">Create Attribute</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Values</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attributes as $attribute)
                            <tr>
                                <td>{{ $attribute->name }}</td>
                                <td><span class="badge bg-info text-dark">{{ $attribute->type }}</span></td>
                                <td>{{ $attribute->values_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                                    <form action="{{ route('admin.attributes.destroy', $attribute) }}" method="post" class="d-inline-block">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this attribute?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">No attributes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $attributes->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
