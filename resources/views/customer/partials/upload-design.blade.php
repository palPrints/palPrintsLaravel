{{--
    "Upload your own design" button + file dialog, shared by the t-shirt,
    hoodie and mug catalog pages. The dialog only lets the customer pick and
    preview an image (validated client-side) — nothing is uploaded or saved
    yet, because there is no customer design-upload flow or storage for it.
--}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/customer/uploadDesign.css') }}?v={{ filemtime(public_path('front/css/customer/uploadDesign.css')) }}">
@endpush

<div class="upload-design-cta">
    <button type="button" class="upload-design-button" id="uploadDesignOpen" aria-haspopup="dialog">
        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
        <span>ارفع تصميمك الخاص</span>
    </button>
</div>

<dialog class="upload-design-dialog" id="uploadDesignDialog" aria-labelledby="uploadDesignTitle">
    <header class="upload-design-dialog__head">
        <h2 id="uploadDesignTitle">ارفع تصميمك الخاص</h2>
        <button type="button" class="upload-design-dialog__close" id="uploadDesignClose" aria-label="إغلاق"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </header>

    <label class="upload-design-drop" id="uploadDesignDrop" for="uploadDesignFile">
        <input type="file" id="uploadDesignFile" accept="image/png,image/jpeg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg">
        <img id="uploadDesignPreview" alt="" hidden>
        <span class="upload-design-drop__empty" id="uploadDesignEmpty">
            <i class="bi bi-image" aria-hidden="true"></i>
            <strong>اسحب الصورة هنا أو اضغط للاختيار</strong>
            <small>PNG أو JPG أو WEBP أو SVG — حتى 10MB</small>
        </span>
    </label>

    <p class="upload-design-status" id="uploadDesignStatus" role="status" aria-live="polite"></p>

    <footer class="upload-design-dialog__foot">
        <button type="button" class="upload-design-secondary" id="uploadDesignReset" hidden>اختيار صورة أخرى</button>
        <button type="button" class="upload-design-primary" id="uploadDesignDone">إغلاق</button>
    </footer>
</dialog>

@push('scripts')
    <script>
        window.palPrintsCustomerAssets = window.palPrintsCustomerAssets || {};
        window.palPrintsCustomerAssets.chooseProductUrl = @json(route('customer.chooseProduct'));
    </script>
    <script src="{{ asset('front/js/customer/imageRules.js') }}?v={{ filemtime(public_path('front/js/customer/imageRules.js')) }}"></script>
    <script src="{{ asset('front/js/customer/uploadDesign.js') }}?v={{ filemtime(public_path('front/js/customer/uploadDesign.js')) }}"></script>
@endpush
