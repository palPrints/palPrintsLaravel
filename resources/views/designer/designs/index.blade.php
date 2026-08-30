@extends('designer.layouts.app')

@section('title', 'تصاميمي')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'تصاميمي',
        'sectionDescription' => 'هنا ستظهر تصاميمك مع حالات النشر والمراجعة والمبيعات.',
        'sectionIcon' => 'bi-images',
    ])
@endsection
