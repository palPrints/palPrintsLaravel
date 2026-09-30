@extends('errors.layout')
@section('code', '400')
@section('badge', 'طلب غير صالح')
@section('title', 'تعذّر فهم الطلب')
@section('text', 'حدث خطأ في الطلب الذي وصلنا. عد إلى الصفحة السابقة وتأكد من البيانات، ثم حاول مرة أخرى.')
@section('actions')
<button class="btn-brand" type="button" data-error-action="back">العودة إلى الصفحة السابقة</button>
<a class="btn-brand-outline" href="{{ $homeUrl }}">الصفحة الرئيسية</a>
@endsection
@section('help', '1')
@section('script', '1')
