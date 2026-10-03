@extends('customer.layouts.app')

@section('title', 'المفضلة')
@section('meta-description', 'تصاميمك المفضلة في PalPrints')
@section('body-class', 'storefront-page favorites-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/favorites.css') }}?v={{ filemtime(public_path('front/css/customer/favorites.css')) }}">
@endpush

@section('content')
    <section class="favorites-content" aria-labelledby="pageTitle">
        <div class="page-heading"><h1 id="pageTitle">المفضلة</h1><p>كل التصاميم المنشورة التي أعجبتك في مكان واحد.</p></div>
        <div class="favorites-grid" id="favoritesGrid" aria-live="polite"></div>
        <section class="favorites-empty" id="favoritesEmpty" aria-labelledby="favoritesEmptyTitle" hidden>
            <div class="favorites-empty__visual" aria-hidden="true"><span class="heart-card heart-card--back"><i class="bi bi-heart"></i></span><span class="heart-card heart-card--front"><i class="bi bi-heart"></i></span><span class="empty-heart"><i class="bi bi-heart"></i></span></div>
            <h2 id="favoritesEmptyTitle">قائمة مفضلاتك فارغة حالياً</h2>
            <p>احفظي التصاميم التي تعجبك<br>لتعودي إليها بسهولة في أي وقت.</p>
            <a class="browse-favorites btn-brand" href="{{ route('customer.store') }}#products">تصفح المنتجات <i class="bi bi-arrow-left" aria-hidden="true"></i></a>
        </section>
        <section class="favorites-suggestions" id="favoritesSuggestions" aria-labelledby="favoritesSuggestionsTitle" hidden>
            <div class="favorites-suggestions__heading"><h2 id="favoritesSuggestionsTitle">ابدئي باختيار ما تحبين</h2><p>اكتشفي منتجات PalPrints المميزة</p></div>
            <div class="favorites-suggestions__grid">
                <a href="{{ route('customer.tshirts') }}" class="favorite-suggestion"><span><i class="bi bi-person-standing-dress"></i></span><strong>تيشيرتات</strong><small>تصاميم تعبّر عنك</small></a>
                <a href="{{ route('customer.hoodies') }}" class="favorite-suggestion favorite-suggestion--coral"><span><i class="bi bi-bag"></i></span><strong>هودي</strong><small>راحة وأناقة بطابعك</small></a>
                <a href="{{ route('customer.mugs') }}" class="favorite-suggestion favorite-suggestion--cyan"><span><i class="bi bi-cup-hot"></i></span><strong>أكواب</strong><small>لحظاتك بتصميمك</small></a>
                <a href="{{ route('customer.stickers') }}" class="favorite-suggestion favorite-suggestion--violet"><span><i class="bi bi-stars"></i></span><strong>ستيكرات</strong><small>لمسات صغيرة مميزة</small></a>
            </div>
        </section>
    </section>
@endsection

@push('scripts')
    <script>
        window.palPrintsFavoritesData = {
            items: @json($favoriteDesigns),
        };
    </script>
    <script src="{{ asset('front/js/customer/favorites.js') }}?v={{ filemtime(public_path('front/js/customer/favorites.js')) }}"></script>
@endpush