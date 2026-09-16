@extends('layouts.storefront')

@section('title', 'متجر | لقمة ')
@section('meta_description', 'قائمة متجر لقمة  — أطباق وتوصيل قريب في دمشق.')

@section('content')
<wasla-express-store data-store-id="{{ $storeId }}"></wasla-express-store>
@endsection
