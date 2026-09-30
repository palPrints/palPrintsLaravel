@extends('errors.layout')
@section('code', '503')
@section('badge', 'صيانة مؤقتة')
@section('title', 'نعمل على تحسين المنصة')
@section('text', 'الموقع غير متاح حاليًا بسبب صيانة قصيرة. عُد بعد قليل.')
@section('actions')
<button class="btn-brand" type="button" data-error-action="reload">إعادة المحاولة</button>
@endsection
@section('script', '1')
