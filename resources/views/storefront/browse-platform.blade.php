@extends('layouts.storefront')

@section('title', 'تصفح ' . ($platform ?? ''))

@section('content')
<wasla-browse-platform data-platform="{{ $platform }}"></wasla-browse-platform>
@endsection
