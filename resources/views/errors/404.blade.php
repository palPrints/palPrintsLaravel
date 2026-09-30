@extends('errors.layout')
@section('code', '404')
@section('badge', 'الصفحة غير موجودة')
@section('title', 'تعذّر العثور على هذه الصفحة')
@section('text', 'ربما كُتب الرابط بشكل خاطئ أو نُقلت الصفحة. تصفّح المنتجات أو عد إلى الصفحة الرئيسية.')
@section('actions')
<a class="btn-brand" href="{{ $homeUrl }}">الصفحة الرئيسية</a>
<a class="btn-brand-outline" href="{{ $storeUrl }}">تصفّح المنتجات</a>
@endsection

