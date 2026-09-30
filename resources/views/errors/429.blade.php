@extends('errors.layout')
@section('code', '429')
@section('badge', 'طلبات كثيرة')
@section('title', 'المحاولات كثيرة، انتظر قليلًا')
@section('text', 'أرسلت عددًا كبيرًا من الطلبات في وقت قصير. انتظر دقيقة ثم حاول مرة أخرى.')
@section('actions')
<a class="btn-brand" href="{{ $homeUrl }}">الصفحة الرئيسية</a>
@endsection
@section('help', '1')
