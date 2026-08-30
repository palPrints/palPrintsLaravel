@extends('designer.layouts.app')

@section('title', 'الملف الشخصي')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'الملف الشخصي',
        'sectionDescription' => 'إدارة معلومات المصمم ومعرض الأعمال ستكون من هنا.',
        'sectionIcon' => 'bi-person',
    ])
@endsection
