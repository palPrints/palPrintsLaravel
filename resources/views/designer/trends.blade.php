@extends('designer.layouts.app')

@section('title', 'الترندات')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'الترندات',
        'sectionDescription' => 'تابع الأفكار الرائجة واستلهم تصميمك القادم.',
        'sectionIcon' => 'bi-fire',
        'cardIcon' => 'bi-stars',
        'cardTitle' => 'استلهم تصميمك القادم',
        'cardText' => 'ستظهر هنا الأفكار الرائجة عندما يتم ربط مصدر بيانات الترندات. يمكنك البدء بتصميم جديد الآن.',
        'cardActionUrl' => route('designer.designs.create'),
        'cardActionIcon' => 'bi-palette',
        'cardActionLabel' => 'ابدأ التصميم',
    ])
@endsection
