@extends('errors.layout')
@section('code', '401')
@section('badge', 'تسجيل الدخول مطلوب')
@section('title', 'سجّل دخولك للمتابعة')
@section('text', 'هذه الصفحة مخصصة للمستخدمين المسجّلين. سجّل دخولك بحسابك ثم أكمل من حيث توقفت.')
@section('actions')
<a class="btn-brand" href="{{ $loginUrl }}">تسجيل الدخول</a>
<a class="btn-brand-outline" href="{{ $homeUrl }}">الصفحة الرئيسية</a>
@endsection
@section('help', '1')
