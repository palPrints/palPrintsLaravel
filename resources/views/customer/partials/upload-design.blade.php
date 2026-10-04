{{--
    "Upload your own design" button, shown on the store page. It no longer opens a pop-up: it sends the customer to the
    product chooser, and the design itself is added in the design studio after a product is picked.
--}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/uploadDesign.css') }}?v={{ filemtime(public_path('front/css/customer/uploadDesign.css')) }}">
@endpush

<div class="upload-design-cta">
    <a class="upload-design-button" href="{{ route('customer.chooseProduct') }}">
        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
        <span>ارفع تصميمك الخاص</span>
    </a>
</div>