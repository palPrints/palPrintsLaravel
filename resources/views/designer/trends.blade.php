@extends('designer.layouts.app')

@section('title', 'الترندات')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'الترندات',
        'sectionDescription' => 'تابع الأفكار الرائجة واستلهم تصميمك القادم.',
        'sectionIcon' => 'bi-fire',
    ])
@endsection
