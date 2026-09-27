@extends('designer.layouts.app')

@section('title', 'تصاميمي')

@section('body-class', 'dashboard-page designer-designs-page')

@push('styles')
    <link rel='stylesheet' href='{{ asset('front/designer/css/dashboard.css') }}?v={{ filemtime(public_path('front/designer/css/dashboard.css')) }}'>
    <link rel='stylesheet' href='{{ asset('front/designer/css/designerDesigns.css') }}?v={{ filemtime(public_path('front/designer/css/designerDesigns.css')) }}'>
@endpush

@section('content')
    <main class='designer-content-main dashboard-stack designs-main' id='designerMain'>
        @include('designer.partials.flash')

          <section
            class="section-header designs-heading"
            aria-labelledby="designsTitle"
          >
            <div>
              <h1 class="section-title" id="designsTitle" data-i18n="myDesigns">
                تصاميمي
              </h1>
              <p class="section-subtitle" data-i18n="designsSubtitle">
                إدارة تصاميمك ومتابعة حالتها من مكان واحد.
              </p>
            </div>
            <a
              class="designer-intro-action designs-new-button"
              href="{{ route('designer.designs.create') }}"
              ><i class="bi bi-plus-lg" aria-hidden="true"></i
              ><span data-i18n="newDesign">تصميم جديد</span></a
            >
          </section>
          <section
            class="activity-section designs-toolbar"
            aria-label="البحث وتصفية التصاميم"
            data-i18n-aria="designTools"
          >
            <div
              class="design-tabs"
              role="tablist"
              aria-label="حالة التصميم"
              data-i18n-aria="statusTabs"
            >
              <button
                class="design-tab is-active"
                type="button"
                role="tab"
                aria-selected="true"
                data-status="all"
              >
                <span data-i18n="all">الكل</span
                ><span class="tab-count" data-count="all">0</span>
              </button>
              <button
                class="design-tab"
                type="button"
                role="tab"
                aria-selected="false"
                data-status="draft"
              >
                <span class="tab-dot draft"></span
                ><span data-i18n="drafts">المسودات</span
                ><span class="tab-count" data-count="draft">0</span>
              </button>
              <button
                class="design-tab"
                type="button"
                role="tab"
                aria-selected="false"
                data-status="review"
              >
                <span class="tab-dot review"></span
                ><span data-i18n="underReview">قيد المراجعة</span
                ><span class="tab-count" data-count="review">0</span>
              </button>
              <button
                class="design-tab"
                type="button"
                role="tab"
                aria-selected="false"
                data-status="published"
              >
                <span class="tab-dot published"></span
                ><span data-i18n="published">المنشورة</span
                ><span class="tab-count" data-count="published">0</span>
              </button>
              <button
                class="design-tab"
                type="button"
                role="tab"
                aria-selected="false"
                data-status="rejected"
              >
                <span class="tab-dot rejected"></span
                ><span data-i18n="rejected">المرفوضة</span
                ><span class="tab-count" data-count="rejected">0</span>
              </button>
            </div>
            <div class="design-tools">
              <label class="design-search"
                ><i class="bi bi-search" aria-hidden="true"></i
                ><span class="sr-only" data-i18n="searchLabel"
                  >البحث في التصاميم</span
                ><input
                  id="designSearch"
                  type="search"
                  placeholder="ابحث في تصاميمك..."
                  data-i18n-placeholder="searchDesigns"
              /></label>
              <div class="filter-control">
                <button
                  type="button"
                  class="filter-button"
                  id="filterButton"
                  aria-controls="filterMenu"
                  aria-expanded="false"
                >
                  <i class="bi bi-funnel" aria-hidden="true"></i
                  ><span data-i18n="filter">فلترة</span
                  ><i
                    class="bi bi-chevron-down filter-chevron"
                    aria-hidden="true"
                  ></i>
                </button>
                <div class="filter-menu" id="filterMenu" hidden>
                  <p data-i18n="sortBy">ترتيب التصاميم</p>
                  <button
                    type="button"
                    class="filter-option is-active"
                    data-sort="newest"
                  >
                    <i class="bi bi-sort-down" aria-hidden="true"></i
                    ><span data-i18n="newest">الأحدث أولًا</span
                    ><i
                      class="bi bi-check2 filter-check"
                      aria-hidden="true"
                    ></i>
                  </button>
                  <button
                    type="button"
                    class="filter-option"
                    data-sort="oldest"
                  >
                    <i class="bi bi-sort-up" aria-hidden="true"></i
                    ><span data-i18n="oldest">الأقدم أولًا</span
                    ><i
                      class="bi bi-check2 filter-check"
                      aria-hidden="true"
                    ></i>
                  </button>
                  <button type="button" class="filter-option" data-sort="name">
                    <i class="bi bi-sort-alpha-down" aria-hidden="true"></i
                    ><span data-i18n="byName">حسب الاسم</span
                    ><i
                      class="bi bi-check2 filter-check"
                      aria-hidden="true"
                    ></i>
                  </button>
                </div>
              </div>
            </div>
          </section>
          <section class="designs-state" id="designsTrueEmpty" @if ($designs->isNotEmpty()) hidden @endif>
            <i class="bi bi-images" aria-hidden="true"></i>
            <h2>ما في تصاميم بعد</h2>
            <p>ابدأ برفع أول تصميم إلك وشاركه مع عملاء PalPrints.</p>
            <a href="{{ route('designer.designs.create') }}" class="card-btn trend state-action">
              <i class="bi bi-plus-lg" aria-hidden="true"></i><span>تصميم جديد</span>
            </a>
          </section>

          <section class="designs-state" id="designsEmpty" hidden>
            <i class="bi bi-search" aria-hidden="true"></i>
            <h2 data-i18n="noMatchesTitle">لا توجد تصاميم مطابقة لبحثك</h2>
            <p data-i18n="noMatches">
              جرّب كلمة بحث أخرى أو امسح البحث الحالي.
            </p>
            <button
              type="button"
              class="card-btn tertiary state-action"
              id="clearSearchButton"
            >
              <i class="bi bi-x-lg" aria-hidden="true"></i
              ><span data-i18n="clearSearch">مسح البحث</span>
            </button>
          </section>


          <div id="designGroups" aria-live="polite"></div>

    </main>
@endsection




@push('scripts')
    @php
        $serializedDesigns = $designs->map(function ($design) {
            $updatedAt = $design->updated_at ?? $design->created_at;

            return [
                'id' => $design->id,
                'title' => $design->title,
                'image' => $design->image ?: (filled($design->product?->image)
                    ? (preg_match('#^(https?:)?//#', $design->product->image) ? $design->product->image : asset($design->product->image))
                    : 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&h=520&fit=crop&q=85'),
                'productType' => $design->product?->name ?? '',
                'status' => $design->status,
                'updatedAt' => $updatedAt ? $updatedAt->toIso8601String() : null,
                'previewUrl' => route('designer.designs.review', ['id' => $design->id]),
                'editorUrl' => route('designer.designs.editor', ['designId' => $design->id]),
            ];
        })->values()->all();
    @endphp

    <script>
        window.palPrintsDesignerRoutes = {
            editor: @json(route('designer.designs.editor')),
            review: @json(route('designer.designs.review'))
        };

        window.palPrintsDesignerDesigns = {!! json_encode($serializedDesigns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
        window.palPrintsDesignerFallbackImage = @json(asset('front/designer/assets/images/file.png'));
    </script>
    <script src='{{ asset('front/designer/js/designerDesigns.js') }}?v={{ filemtime(public_path('front/designer/js/designerDesigns.js')) }}'></script>
@endpush
