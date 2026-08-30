@extends('designer.layouts.app')

@section('title', 'الأرباح')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'الأرباح',
        'sectionDescription' => 'ملخص الأرباح والمعاملات وطلبات السحب سيظهر هنا.',
        'sectionIcon' => 'bi-coin',
    ])
@endsection
