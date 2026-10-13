@extends('designer.layouts.app')

@php
    $statusLabels = ['draft' => 'مسودة', 'review' => 'قيد المراجعة', 'submitted' => 'قيد المراجعة', 'published' => 'منشور', 'rejected' => 'مرفوض'];
    $statusLabel = $statusLabels[$design->status] ?? $design->status;
    $image = $design->image
        ? (preg_match('#^(https?:)?//#', $design->image) ? $design->image : asset($design->image))
        : ($design->product?->image ? asset($design->product->image) : null);
@endphp

@section('title', $design->title)
@section('body-class', 'designer-create-flow')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/designer/css/designerFlow.css') }}?v={{ filemtime(public_path('front/designer/css/designerFlow.css')) }}">
@endpush

@section('content')
<main class="designer-content-main" id="designerMain">
    <div class="designer-publish">
        <header class="dp-header">
            <div class="dp-heading">
                <span class="dp-heading__icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
                <div>
                    <h1>{{ $design->title }}</h1>
                    <p>{{ $statusLabel }}@if ($design->product) · {{ $design->product->name }}@endif</p>
                </div>
            </div>
            <a class="designer-flow-button is-ghost" href="{{ route('designer.designs.index') }}">
                <i class="bi bi-arrow-right" aria-hidden="true"></i><span>رجوع لتصاميمي</span>
            </a>
        </header>

        <div class="dp-layout">
            <section class="dp-card" aria-labelledby="previewTitle">
                <div class="dp-card__head">
                    <span aria-hidden="true"><i class="bi bi-eye"></i></span>
                    <div>
                        <h2 id="previewTitle">التصميم على المنتج</h2>
                        <p>كما أرسلته</p>
                    </div>
                </div>

                <div class="dp-stage">
                    @if ($image)
                        <img src="{{ $image }}" alt="معاينة التصميم {{ $design->title }}">
                    @else
                        <p>لا توجد صورة معاينة لهذا التصميم.</p>
                    @endif
                </div>
            </section>

            <section class="dp-card" aria-labelledby="detailsTitle">
                <div class="dp-card__head">
                    <span aria-hidden="true"><i class="bi bi-info-circle"></i></span>
                    <div>
                        <h2 id="detailsTitle">تفاصيل التصميم</h2>
                    </div>
                </div>

                @if ($design->status === 'rejected' && $design->rejection_reason)
                    <p class="dp-note"><strong>سبب الرفض:</strong> {{ $design->rejection_reason }}</p>
                @endif

                <dl class="dp-facts">
                    <div>
                        <dt><i class="bi bi-flag" aria-hidden="true"></i> الحالة</dt>
                        <dd>{{ $statusLabel }}</dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-tag" aria-hidden="true"></i> نوع المنتج</dt>
                        <dd>{{ $design->product?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-people" aria-hidden="true"></i> الفئة</dt>
                        <dd>{{ $audience ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-rulers" aria-hidden="true"></i> المقاسات المناسبة</dt>
                        <dd>{{ $sizes ? implode('، ', $sizes) : '—' }}</dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-palette" aria-hidden="true"></i> الألوان المناسبة</dt>
                        <dd>{{ $colors ? collect($colors)->pluck('name')->implode('، ') : '—' }}</dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-cash" aria-hidden="true"></i> سعر البيع</dt>
                        <dd>{{ number_format((float) $design->selling_price, 2) }} ₪</dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-coin" aria-hidden="true"></i> ربحك من القطعة</dt>
                        <dd>{{ number_format((float) $design->designer_profit, 2) }} ₪</dd>
                    </div>
                    <div>
                        <dt><i class="bi bi-calendar-event" aria-hidden="true"></i> تاريخ الإرسال</dt>
                        <dd>{{ ($design->submitted_at ?? $design->created_at)?->locale('ar')->translatedFormat('j F Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</main>
@endsection
