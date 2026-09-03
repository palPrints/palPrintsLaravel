@extends('designer.layouts.app')

@section('title', 'الدعم الفني')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'الدعم الفني',
        'sectionDescription' => 'تواصل مع فريق PalPrints وتابع طلبات الدعم.',
        'sectionIcon' => 'bi-headset',
    ])
@endsection
