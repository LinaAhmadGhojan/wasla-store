@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Stores</h1>
                <p class="text-muted mb-0">Manage vendor stores, approval status, and marketplace storefronts.</p>
            </div>
            <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary">Create Store</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Store Name</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                            <tr>
                                <td>{{ $vendor->store_name }}</td>
                                <td>{{ $vendor->owner?->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-{{ $vendor->status === 'active' ? 'success' : ($vendor->status === 'pending' ? 'warning' : ($vendor->status === 'closed' ? 'dark' : 'secondary')) }}">{{ ucfirst($vendor->status) }}</span></td>
                                <td>{!! $vendor->is_featured ? '<span class="badge bg-primary">Yes</span>' : '<span class="badge bg-light text-dark">No</span>' !!}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.vendors.destroy', $vendor) }}" method="post" class="d-inline-block">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this store?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">No stores found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $vendors->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
