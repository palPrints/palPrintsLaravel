@extends('designer.layouts.app')

@section('title', 'الدعم الفني')

@section('content')
    @include('designer.partials.section-placeholder', [
        'sectionTitle' => 'الدعم الفني',
        'sectionDescription' => 'تواصل مع فريق PalPrints وتابع طلبات الدعم.',
        'sectionIcon' => 'bi-headset',
        'cardIcon' => 'bi-envelope',
        'cardTitle' => 'راسل فريق الدعم',
        'cardText' => 'أرسل استفسارك عبر البريد الإلكتروني وسنرد عليك في أقرب وقت. اذكر بريد حسابك ووصفًا واضحًا للمشكلة.',
        'cardActionUrl' => 'mailto:'.config('mail.from.address'),
        'cardActionIcon' => 'bi-envelope',
        'cardActionLabel' => config('mail.from.address'),
    ])
@endsection
