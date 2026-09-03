@extends('designer.layouts.app')

@section('title', 'الإشعارات')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'الإشعارات',
        'sectionDescription' => 'ستظهر تنبيهات الحساب والتصاميم والمبيعات هنا.',
        'sectionIcon' => 'bi-bell',
    ])
@endsection
