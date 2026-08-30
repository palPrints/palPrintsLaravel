@extends('designer.layouts.app')

@section('title', 'رفع تصميم جديد')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'رفع تصميم جديد',
        'sectionDescription' => 'صفحة رفع التصميم مجهزة ضمن نفس تخطيط المصمم المشترك.',
        'sectionIcon' => 'bi-cloud-arrow-up',
    ])
@endsection
