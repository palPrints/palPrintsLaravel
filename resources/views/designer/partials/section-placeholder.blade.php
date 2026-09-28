<main class="designer-content-main" id="designerMain">
    @include('designer.partials.flash')

    <div class="designer-breadcrumb">
        <a href="{{ route('designer.dashboard') }}">الرئيسية</a>
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span>{{ $sectionTitle }}</span>
    </div>

    <header class="designer-page-heading">
        <span class="designer-heading-icon"><i class="bi {{ $sectionIcon }}" aria-hidden="true"></i></span>
        <div>
            <h1>{{ $sectionTitle }}</h1>
            <p>{{ $sectionDescription }}</p>
        </div>
    </header>

    <section class="designer-form-card">
        <header class="designer-card-title">
            <span><i class="bi {{ $cardIcon ?? 'bi-tools' }}" aria-hidden="true"></i></span>
            <h2>{{ $cardTitle ?? 'الصفحة قيد التجهيز' }}</h2>
        </header>

        <div class="designer-security-content">
            <p>{{ $cardText ?? 'سيتم تفعيل هذه الصفحة قريبًا.' }}</p>

            @isset($cardActionUrl)
                <a class="designer-outline-btn" href="{{ $cardActionUrl }}"><i class="bi {{ $cardActionIcon ?? 'bi-arrow-left' }}"></i> {{ $cardActionLabel }}</a>
            @endisset
        </div>
    </section>
</main>
