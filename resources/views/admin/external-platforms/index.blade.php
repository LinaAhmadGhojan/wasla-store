@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">External Platforms</h1>
                <p class="text-muted mb-0">Platforms customers can buy from through the "buy anything" service.</p>
            </div>
            <a href="{{ route('admin.external-platforms.create') }}" class="btn btn-primary">Create Platform</a>
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
                            <th>Currency</th>
                            <th>Requests</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($platforms as $platform)
                            <tr>
                                <td>{{ $platform->name }}</td>
                                <td>{{ $platform->type }}</td>
                                <td>{{ $platform->currency }}</td>
                                <td>{{ $platform->purchase_requests_count }}</td>
                                <td><span class="badge bg-{{ $platform->is_active ? 'success' : 'secondary' }}">{{ $platform->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.external-platforms.edit', $platform) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.external-platforms.destroy', $platform) }}" method="post" class="d-inline-block">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this platform?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No platforms found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $platforms->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
