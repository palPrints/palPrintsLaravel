@extends('errors.layout')
@section('code', '500')
@section('badge', 'خطأ في الخادم')
@section('title', 'حدث عطل مؤقت لدينا')
@section('text', 'المشكلة من جهتنا وليست منك. حاول تحديث الصفحة بعد قليل، وإذا استمرت فأبلغنا بها.')
@section('actions')
<button class="btn-brand" type="button" data-error-action="reload">إعادة المحاولة</button>
<a class="btn-brand-outline" href="{{ $homeUrl }}">الصفحة الرئيسية</a>
@endsection
@section('help', '1')
@section('script', '1')
