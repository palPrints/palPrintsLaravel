<section class='designer-intro' aria-labelledby='designerSectionTitle'>
    <div class='designer-intro-copy'>
        <span class='designer-intro-eyebrow'>
            <i class='bi {{ $sectionIcon }}' aria-hidden='true'></i>
            <span>مساحة المصمم</span>
        </span>

        <h1 class='designer-intro-text' id='designerSectionTitle'>
            <span class='designer-intro-title-main'>{{ $sectionTitle }}</span>
        </h1>

        <p class='designer-intro-subtitle'>{{ $sectionDescription }}</p>
    </div>

    <a href='{{ route('designer.dashboard') }}' class='designer-intro-action'>
        <i class='bi bi-grid' aria-hidden='true'></i>
        <span>العودة إلى لوحة التحكم</span>
    </a>
</section>

<section class='activity-section'>
    <div class='activity-header'>
        <h2 class='activity-title'>
            <i class='bi bi-tools' aria-hidden='true'></i>
            <span>الصفحة قيد التجهيز</span>
        </h2>
    </div>

    <p class='section-subtitle'>
        تم تجهيز ملف الصفحة ومسارها والتخطيط المشترك، ويمكن إكمال محتواها لاحقًا بدون تكرار الـ sidebar أو الـ topbar.
    </p>
</section>
