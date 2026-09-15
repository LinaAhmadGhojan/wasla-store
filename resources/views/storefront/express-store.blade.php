@extends('layouts.storefront')

@section('title', 'متجر | وصلة السريعة')
@section('meta_description', 'قائمة متجر وصلة السريعة — أطباق وتوصيل قريب في دمشق.')

@section('content')
<wasla-express-store data-store-id="{{ $storeId }}"></wasla-express-store>
@endsection
