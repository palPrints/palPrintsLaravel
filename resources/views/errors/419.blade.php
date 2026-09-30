@extends('errors.layout')
@section('code', '419')
@section('badge', 'انتهت الجلسة')
@section('title', 'انتهت صلاحية الصفحة')
@section('text', 'مرّ وقت طويل على فتح هذه الصفحة. حدّثها ثم أعد المحاولة.')
@section('actions')
<button class="btn-brand" type="button" data-error-action="reload">تحديث الصفحة</button>
<a class="btn-brand-outline" href="{{ $homeUrl }}">الصفحة الرئيسية</a>
@endsection
@section('script', '1')
