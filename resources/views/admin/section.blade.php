@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h1 class="h3 mb-3 text-capitalize">{{ str_replace('-', ' ', $section) }}</h1>
                <p class="text-muted">This section is available in the admin panel. Use the sidebar to navigate to the primary CRUD pages for users, products, categories, and stores.</p>
            </div>
        </div>
    </div>
@endsection
