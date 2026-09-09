@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Create External Platform</h1>
                <p class="text-muted mb-0">Add a platform Wasla can purchase products from on customers' behalf.</p>
            </div>
            <a href="{{ route('admin.external-platforms.index') }}" class="btn btn-outline-secondary">Back to platforms</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="{{ route('admin.external-platforms.store') }}" method="post">
                    @csrf
                    @include('admin.external-platforms._form', ['platform' => $platform])

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create platform</button>
                        <a href="{{ route('admin.external-platforms.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
