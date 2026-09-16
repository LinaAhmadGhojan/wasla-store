@extends('layouts.storefront')

@section('title', 'طبق | لقمة ')
@section('meta_description', 'تفاصيل الطبق من لقمة  — توصيل قريب في دمشق.')

@section('content')
<wasla-express-item data-item-id="{{ $itemId }}"></wasla-express-item>
@endsection
