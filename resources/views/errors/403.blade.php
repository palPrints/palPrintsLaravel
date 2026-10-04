@extends('errors.layout')
@section('code', '403')
@section('badge', 'وصول غير مسموح')
@section('title', 'ليست لديك صلاحية للوصول إلى هذه الصفحة')
@section('text', 'لا يتيح لك حسابك الحالي فتح هذه الصفحة. إذا كنت تعتقد أن هذا خطأ، فتواصل معنا.')
@section('actions')
<a class="btn-brand" href="{{ $homeUrl }}">الصفحة الرئيسية</a>
<a class="btn-brand-outline" href="{{ $supportUrl }}">تواصل مع الدعم</a>
@endsection

