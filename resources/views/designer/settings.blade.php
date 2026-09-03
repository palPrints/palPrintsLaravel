@extends('designer.layouts.app')

@section('title', 'الإعدادات')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'الإعدادات',
        'sectionDescription' => 'إعدادات الحساب واللغة والمظهر مجمعة في هذه الصفحة.',
        'sectionIcon' => 'bi-gear',
    ])
@endsection
