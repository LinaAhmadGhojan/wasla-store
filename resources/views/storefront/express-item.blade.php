@extends('layouts.storefront')

@section('title', 'طبق | وصلة السريعة')
@section('meta_description', 'تفاصيل الطبق من وصلة السريعة — توصيل قريب في دمشق.')

@section('content')
<wasla-express-item data-item-id="{{ $itemId }}"></wasla-express-item>
@endsection
