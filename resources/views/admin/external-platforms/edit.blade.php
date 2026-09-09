@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Edit External Platform</h1>
                <p class="text-muted mb-0">Update platform details.</p>
            </div>
            <a href="{{ route('admin.external-platforms.index') }}" class="btn btn-outline-secondary">Back to platforms</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.external-platforms.update', $platform) }}" method="post">
                    @csrf
                    @method('put')
                    @include('admin.external-platforms._form', ['platform' => $platform])

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update platform</button>
                        <a href="{{ route('admin.external-platforms.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
