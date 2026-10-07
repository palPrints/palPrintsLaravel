{{--
    Design studio, ported as-is from palPrintFront/design-studio.html.
    The studio's own sidebar, top bar and footer are dropped: the page now uses
    the site's customer layout (header, sidebar, footer) like every other page.
--}}
@extends('customer.layouts.app')

@section('title', 'استوديو التصميم')
@section('meta-description', 'استوديو PALPRINTS لتصميم المنتجات')
@section('body-class', 'storefront-page design-studio-page')
@section('page-loader-wait', '1')
@section('main-class', 'studio-main')

@push('styles')
  <base href="{{ asset('front/studio') }}/">
  <link rel="stylesheet" href="assets/css/pages/design-studio.css?v={{ filemtime(public_path('front/studio/assets/css/pages/design-studio.css')) }}">
  {{-- The studio stylesheet sets .studio-main to width:100%; inside the site layout that would overflow the sidebar margin. --}}
  <style>body.storefront-page .studio-main.store-main { width: auto; min-width: 0; }</style>
@endpush

@section('content')
        <div class="studio-page-heading">
          <span>استوديو التصميم</span>
          <h1>صمّم منتجك</h1>
          <p>أضف لمستك الإبداعية داخل منطقة الطباعة المعتمدة للمنتج.</p>
        </div>

        <section class="studio-product-strip" aria-label="المنتج المحدد">
          <div class="studio-product-identity">
            <span class="studio-product-thumb"><img id="summaryImage" alt=""></span>
            <span>
              <small>المنتج المحدد</small>
              <strong id="summaryProductName">جارٍ تحميل المنتج…</strong>
            </span>
          </div>
          <dl class="studio-product-meta">
            <div>
              <dt>اللون</dt>
              <dd id="summaryColor">—</dd>
            </div>
            <div>
              <dt>المقاس</dt>
              <dd id="summarySize">—</dd>
            </div>
            <div>
              <dt>السعر الأساسي</dt>
              <dd id="summaryPrice">—</dd>
            </div>
          </dl>
          <button class="studio-swap-toggle" id="swapProductToggle" type="button" aria-expanded="false"
            aria-controls="productSwapPanel">
            <i class="bi bi-arrow-left-right" aria-hidden="true"></i><span>تبديل المنتج</span>
          </button>
        </section>

        <section class="studio-product-swap" id="productSwapPanel" aria-labelledby="productSwapTitle" hidden>
          <div class="studio-product-swap-head">
            <span><strong id="productSwapTitle">اختر منتجًا آخر</strong><small>سيُحفظ تصميم كل منتج بشكل مستقل.</small></span>
            <button id="closeProductSwap" type="button" aria-label="إغلاق اختيار المنتج"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
          </div>
          <div class="studio-product-rail" id="productSwapOptions" role="list"></div>
        </section>

        <div class="studio-layout">
          <aside class="studio-product-panel" aria-labelledby="productPanelTitle">
            <div class="studio-product-options-scroll">
            <div class="studio-panel-heading">
              <span class="studio-panel-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
              <span><small>إعدادات العرض</small>
                <h2 id="productPanelTitle">المنتج ومنطقة الطباعة</h2>
              </span>
            </div>

            <section class="studio-option-group" aria-labelledby="colorTitle">
              <div class="studio-section-title">
                <h3 id="colorTitle">اللون</h3><span id="selectedColorName">—</span>
              </div>
              <div class="studio-swatches" id="colorOptions" aria-label="ألوان المنتج"></div>
            </section>

            <section class="studio-option-group" aria-labelledby="sizeTitle">
              <div class="studio-section-title">
                <h3 id="sizeTitle">المقاس</h3><span>حسب اختيار المنتج</span>
              </div>
              <div class="studio-sizes" id="sizeOptions" aria-label="مقاسات المنتج"></div>
            </section>

            <section class="studio-option-group" aria-labelledby="areaTitle">
              <div class="studio-section-title">
                <h3 id="areaTitle">جهة التصميم</h3><span id="areaCount">—</span>
              </div>
              <div class="studio-areas" id="areaOptions" role="tablist" aria-label="مناطق الطباعة"></div>
            </section>

            <section class="studio-option-group studio-copy-group" id="areaCopyGroup" aria-labelledby="areaCopyTitle" hidden>
              <div class="studio-section-title">
                <h3 id="areaCopyTitle">نسخ التصميم</h3><span>إلى جهة أخرى</span>
              </div>
              <div class="studio-copy-actions" id="areaCopyActions"></div>
            </section>
            </div>

            <section class="studio-selection-actions" id="selectedObjectControls" aria-labelledby="selectedObjectTitle">
              <div class="studio-section-title">
                <h3 id="selectedObjectTitle">العنصر المحدد</h3><span id="selectedObjectName" aria-live="polite">حدد عنصرًا</span>
              </div>
              <div class="studio-object-actions" role="group" aria-label="ترتيب العنصر وتحريره">
                <button type="button" data-object-action="forward" title="تقديم للأمام" aria-label="تقديم للأمام" disabled><i
                    class="bi bi-front" aria-hidden="true"></i></button>
                <button type="button" data-object-action="backward" title="إرسال للخلف" aria-label="إرسال للخلف" disabled><i
                    class="bi bi-back" aria-hidden="true"></i></button>
                <button type="button" data-object-action="duplicate" title="تكرار" aria-label="تكرار" disabled><i
                    class="bi bi-copy" aria-hidden="true"></i></button>
                <button type="button" data-object-action="delete" title="حذف" aria-label="حذف" disabled><i class="bi bi-trash"
                    aria-hidden="true"></i></button>
              </div>
            </section>

          </aside>

          <section class="studio-workspace" aria-labelledby="workspaceTitle">
            <div class="studio-workspace-head">
              <span>
                <small id="workspaceEyebrow">مساحة المنتج</small>
                <strong id="workspaceTitle">معاينة منطقة الطباعة</strong>
              </span>
            </div>

            <div class="studio-stage is-loading" id="studioStage" aria-busy="true">
              <div class="studio-stage-loading" role="status" aria-live="polite">
                <span class="studio-loading-spinner" aria-hidden="true"></span>
                <span>جارٍ تجهيز مساحة التصميم…</span>
              </div>
              <div class="studio-stage-glow" aria-hidden="true"></div>
              <div class="studio-stage-coordinate-system" id="stageCoordinateSystem">
                <div class="studio-product-canvas" id="productCanvas">
                  <img id="productMockup" alt="">
                  <div class="studio-print-zone" id="printZone" aria-label="منطقة الطباعة">
                    <span class="studio-zone-label"><i class="bi bi-bounding-box" aria-hidden="true"></i><span
                        id="printZoneLabelText">منطقة الطباعة</span></span>
                  </div>
                </div>
                <canvas id="designCanvas" aria-label="لوحة التصميم"></canvas>
                <div class="studio-snap-guide studio-snap-guide-vertical" id="snapGuideVertical" aria-hidden="true"
                  hidden></div>
                <div class="studio-snap-guide studio-snap-guide-horizontal" id="snapGuideHorizontal" aria-hidden="true"
                  hidden></div>
              </div>
            </div>

            <div class="studio-workspace-foot" aria-label="أدوات العرض">
              <span class="studio-history-placeholder" aria-label="أدوات التراجع والإعادة">
                <button id="studioUndo" type="button" disabled aria-label="تراجع"><i class="bi bi-arrow-counterclockwise"
                    aria-hidden="true"></i></button>
                <button id="studioRedo" type="button" disabled aria-label="إعادة"><i class="bi bi-arrow-clockwise"
                    aria-hidden="true"></i></button>
              </span>
              <span class="studio-zoom-placeholder" role="group" aria-label="أدوات تكبير مساحة التصميم">
                <button class="studio-zoom-step" id="studioZoomOut" type="button" aria-label="تصغير العرض" title="تصغير العرض"><i class="bi bi-dash-lg"
                    aria-hidden="true"></i></button>
                <button class="studio-zoom-value" id="studioZoomFit" type="button" aria-label="ملاءمة التصميم للعرض"
                  title="ملاءمة التصميم للعرض" aria-pressed="true"><strong id="studioZoomValue">100%</strong></button>
                <button class="studio-zoom-step" id="studioZoomIn" type="button" aria-label="تكبير العرض" title="تكبير العرض"><i class="bi bi-plus-lg"
                    aria-hidden="true"></i></button>
              </span>
            </div>
          </section>

          <aside class="studio-tools-panel" aria-labelledby="toolsTitle">
            <div class="studio-panel-heading studio-tools-heading">
              <span class="studio-panel-icon"><i class="bi bi-sliders" aria-hidden="true"></i></span>
              <span>
                <h2 id="toolsTitle">أدوات التصميم</h2><small>اختر أداة للبدء</small>
              </span>
            </div>

            <div class="studio-tool-tabs" role="tablist" aria-label="أدوات التصميم">
              <button class="studio-tool-tab is-active" id="uploadTab" type="button" role="tab" aria-selected="true"
                aria-controls="toolPanel" data-studio-tool="upload">
                <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i><span>رفع صورة</span>
              </button>
              <button class="studio-tool-tab" id="textTab" type="button" role="tab" aria-selected="false"
                aria-controls="toolPanel" data-studio-tool="text">
                <i class="bi bi-type" aria-hidden="true"></i><span>إضافة نص</span>
              </button>
              <button class="studio-tool-tab" id="graphicsTab" type="button" role="tab" aria-selected="false"
                aria-controls="toolPanel" data-studio-tool="graphics">
                <i class="bi bi-images" aria-hidden="true"></i><span>الرسومات والعناصر</span>
              </button>
            </div>

            <div class="studio-tool-panel" id="toolPanel" role="tabpanel" aria-live="polite"
              aria-labelledby="uploadTab">
              <div id="toolPanelContent"></div>
            </div>
          </aside>
        </div>

        <div class="studio-bottom-actions">
          <button class="studio-preview-button" id="previewButton" type="button" disabled>
            <i class="bi bi-eye" aria-hidden="true"></i>
            <span>معاينة التصميم</span>
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
          </button>
        </div>

        <section class="studio-error-state" id="studioError" hidden role="alert">
          <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
          <h2>تعذر فتح استوديو التصميم</h2>
          <p id="studioErrorMessage"></p>
          <a href="{{ $chooseProductUrl }}">العودة إلى اختيار المنتج</a>
        </section>

  <dialog class="studio-confirm-dialog" id="deleteAssetDialog" aria-labelledby="deleteAssetTitle"
    aria-describedby="deleteAssetMessage">
    <div class="studio-confirm-content">
      <div class="studio-confirm-thumbnail"><img id="deleteAssetThumbnail" alt=""></div>
      <div class="studio-confirm-copy">
        <h2 id="deleteAssetTitle">حذف الصورة؟</h2>
        <p id="deleteAssetMessage"></p>
      </div>
    </div>
    <div class="studio-confirm-actions">
      <button class="studio-confirm-cancel" id="cancelAssetDelete" type="button">إلغاء</button>
      <button class="studio-confirm-delete" id="confirmAssetDelete" type="button"><i class="bi bi-trash3"
          aria-hidden="true"></i> حذف الصورة</button>
    </div>
  </dialog>

  <dialog class="studio-confirm-dialog" id="replaceAreaDialog" aria-labelledby="replaceAreaTitle"
    aria-describedby="replaceAreaMessage">
    <div class="studio-confirm-content">
      <div class="studio-confirm-thumbnail"><i class="bi bi-copy" aria-hidden="true"></i></div>
      <div class="studio-confirm-copy">
        <h2 id="replaceAreaTitle">استبدال تصميم الجهة؟</h2>
        <p id="replaceAreaMessage"></p>
      </div>
    </div>
    <div class="studio-confirm-actions">
      <button class="studio-confirm-cancel" id="cancelAreaReplace" type="button">إلغاء</button>
      <button class="studio-confirm-delete studio-confirm-primary" id="confirmAreaReplace" type="button"><i class="bi bi-copy"
          aria-hidden="true"></i> استبدال ونسخ</button>
    </div>
  </dialog>

  <div class="studio-notice" id="studioNotice" role="status" aria-live="polite" hidden>
    <i class="bi bi-info-circle" aria-hidden="true"></i><span id="studioNoticeText"></span>
  </div>

  <div class="sr-only" id="studioLiveRegion" aria-live="polite" aria-atomic="true"></div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/fabric@7.4.0/dist/index.min.js"></script>
  <script src="assets/js/pages/canvas-history-manager.js"></script>
  <script src="assets/js/pages/design-studio-product-catalog.js"></script>
  <script src="assets/js/pages/design-studio-graphics.js"></script>
  <script src="assets/js/pages/design-studio-svg-renderer.js?v=4"></script>
  <script>
    /* Laravel bridge. The studio script itself is unchanged; this block runs first to
       1) replace the studio's built-in product list with the real products from the database
          (same products as the "choose product" page), keeping only the studio's drawing
          geometry and mockup pictures;
       2) when the customer comes from "upload your design", open the chosen product with the
          uploaded image placed on the chosen print area;
       3) enable the preview button and continue to the customer preview page. */
    (async function loadStudio() {
      const DB = @json($dbCatalog);
      const SEED_KEY = "palprintsStudioSeed";
      const SELECTION_KEY = "palprintsDesignerSelection";
      const DESIGN_PREFIX = "palprintsDesign:";
      const ASSET_FALLBACK_PREFIX = "palprintsDesignAsset:";

      function kindOf(code) {
        const c = String(code || "").toLowerCase();
        if (c.includes("tshirt")) return "tshirts";
        if (c.includes("hoodie")) return "hoodies";
        if (c.includes("mug")) return "mugs";
        if (c.includes("tote") || c.includes("bag")) return "bags";
        if (c.includes("cap")) return "caps";
        return null;
      }
      const clone = function (value) { return JSON.parse(JSON.stringify(value)); };
      const studioSizeId = function (id) { return String(id) === "one-size" ? "standard" : String(id).toUpperCase(); };
      const studioAreaId = function (id) { return String(id) === "wrap" ? "front" : String(id); };

      function mergeCatalog() {
        const base = (window.PALPRINTS_PRODUCT_CATALOG || {}).products || [];
        const merged = [];
        DB.forEach(function (db) {
          const src = base.find(function (item) { return item.categoryId === kindOf(db.code); });
          if (!src) return;
          const dbColorIds = (db.colors || []).map(function (color) { return String(color.id).toLowerCase(); });
          let colors = src.colors.filter(function (color) { return dbColorIds.includes(color.id); });
          // Colours the admin added have no photo: tint the white garment (t-shirts and hoodies only).
          const white = src.colors.find(function (color) { return color.id === "white"; });
          if (white && (src.categoryId === "tshirts" || src.categoryId === "hoodies")) {
            colors = (db.colors || []).map(function (color) {
              const id = String(color.id).toLowerCase();
              const known = src.colors.find(function (item) { return item.id === id; });
              return known || { id: id, name: color.name, value: color.value, image: white.image, areaMockups: white.areaMockups, tint: color.value };
            });
          }
          if (!colors.length) colors = src.colors;
          let sizes = (db.sizes || []).map(function (size) { const id = studioSizeId(size.id); return { id: id, name: id === "standard" ? "قياسي" : id }; });
          if (!sizes.length) sizes = src.sizes;
          let areas = (db.printAreas || []).map(function (area) {
            const match = src.printAreas.find(function (item) { return item.id === studioAreaId(area.id); });
            return match ? Object.assign(clone(match), { name: area.name, fromWrap: String(area.id) !== studioAreaId(area.id) }) : null;
          }).filter(Boolean);
          // The studio has one area per id, but the database can list a mug's "wrap" next to "front" (both become "front"):
          // keep one per id and prefer the remapped one ("wrap", the real full-wrap area).
          const unique = new Map();
          areas.forEach(function (area) { if (!unique.has(area.id) || area.fromWrap) unique.set(area.id, area); });
          areas = Array.from(unique.values()).map(function (area) { delete area.fromWrap; return area; });
          if (!areas.length) areas = clone(src.printAreas);
          const defaultAreaId = areas.some(function (area) { return area.id === "front"; }) ? "front" : areas[0].id;
          merged.push(Object.assign({}, src, {
            name: db.name, studioTitle: db.name, price: db.price, code: String(db.code).toUpperCase(),
            colors: colors, sizes: sizes, printAreas: areas, defaultAreaId: defaultAreaId, defaultColor: colors[0].id,
            editor: { defaultAreaId: defaultAreaId, printAreas: clone(areas) }
          }));
        });
        if (merged.length) window.PALPRINTS_PRODUCT_CATALOG = Object.freeze({ products: Object.freeze(merged) });
      }

      function start() {
        const script = document.createElement("script");
        script.src = "assets/js/pages/design-studio.js";
        document.body.appendChild(script);
        enablePreviewWhenReady();
      }

      function readPendingUpload() {
        return new Promise(function (resolve) {
          if (!window.indexedDB) return resolve(null);
          const request = indexedDB.open("palprintsUploads", 1);
          request.onupgradeneeded = function () { request.result.createObjectStore("files"); };
          request.onerror = function () { resolve(null); };
          request.onsuccess = function () {
            try {
              const store = request.result.transaction("files", "readwrite").objectStore("files");
              const get = store.get("pending");
              get.onsuccess = function () { store.delete("pending"); resolve(get.result || null); };
              get.onerror = function () { resolve(null); };
            } catch (error) { resolve(null); }
          };
        });
      }

      function putAsset(designId, assetId, blob) {
        return new Promise(function (resolve) {
          if (!window.indexedDB) return resolve(false);
          const timer = setTimeout(function () { resolve(false); }, 1800);
          const request = indexedDB.open("palprintsStudioAssets", 1);
          request.onupgradeneeded = function () {
            if (!request.result.objectStoreNames.contains("assets")) request.result.createObjectStore("assets", { keyPath: "key" });
          };
          request.onerror = request.onblocked = function () { clearTimeout(timer); resolve(false); };
          request.onsuccess = function () {
            try {
              const put = request.result.transaction("assets", "readwrite").objectStore("assets").put({ key: designId + ":" + assetId, blob: blob });
              put.onsuccess = function () { clearTimeout(timer); resolve(true); };
              put.onerror = function () { clearTimeout(timer); resolve(false); };
            } catch (error) { clearTimeout(timer); resolve(false); }
          };
        });
      }

      function imageSize(blob) {
        return new Promise(function (resolve) {
          const url = URL.createObjectURL(blob), image = new Image();
          image.onload = function () { URL.revokeObjectURL(url); resolve({ width: image.naturalWidth, height: image.naturalHeight }); };
          image.onerror = function () { URL.revokeObjectURL(url); resolve(null); };
          image.src = url;
        });
      }

      async function openUploadedDesign(seed) {
        const catalog = (window.PALPRINTS_PRODUCT_CATALOG || {}).products || [];
        const product = catalog.find(function (item) { return item.categoryId === seed.kind; });
        if (!product) return;
        const blob = await readPendingUpload();

        const designId = "customer-" + seed.designId;
        const colorId = product.colors.some(function (c) { return c.id === String(seed.colorId || "").toLowerCase(); }) ? String(seed.colorId).toLowerCase() : product.defaultColor;
        const sizeId = product.sizes.some(function (s) { return s.id === studioSizeId(seed.sizeId); }) ? studioSizeId(seed.sizeId) : product.sizes[0].id;
        const areaId = product.editor.printAreas.some(function (a) { return a.id === studioAreaId(seed.areaId); }) ? studioAreaId(seed.areaId) : product.editor.defaultAreaId;
        // No uploaded picture to place: still open the studio on the product the customer chose.
        if (!blob) {
          sessionStorage.setItem(SELECTION_KEY, JSON.stringify({ productId: product.id, editorProduct: product, colorId: colorId, sizeId: sizeId, printAreaIds: [areaId] }));
          return;
        }
        sessionStorage.setItem(SELECTION_KEY, JSON.stringify({ productId: product.id, editorProduct: product, colorId: colorId, sizeId: sizeId, printAreaIds: [areaId], designId: designId }));

        const size = await imageSize(blob);
        const assetId = "asset-" + Date.now();
        if (!(await putAsset(designId, assetId, blob))) {
          const reader = new FileReader();
          const dataUrl = await new Promise(function (resolve, reject) { reader.onload = function () { resolve(reader.result); }; reader.onerror = reject; reader.readAsDataURL(blob); });
          localStorage.setItem(ASSET_FALLBACK_PREFIX + designId + ":" + assetId, dataUrl);
        }
        const zone = product.editor.printAreas.find(function (a) { return a.id === areaId; }).printZone;
        const aspect = size && size.width && size.height ? size.height / size.width : 1;
        let width = 0.8, height = width * aspect * (zone.widthCm / zone.heightCm);
        if (height > 0.9) { width = width * 0.9 / height; height = 0.9; }
        const areas = {};
        product.editor.printAreas.forEach(function (a) { areas[a.id] = { objects: [] }; });
        areas[areaId].objects.push({ id: "object-" + assetId, kind: "image", x: 0.5, y: 0.5, angle: 0, flipX: false, flipY: false, assetId: assetId, width: width, height: height });
        const record = { assetId: assetId, name: seed.name || "تصميمي", mimeType: blob.type || "image/png", size: blob.size, lastModified: 0,
          fingerprint: "seed:" + seed.designId, pixelWidth: size ? size.width : null, pixelHeight: size ? size.height : null, sourceType: "raster", createdAt: new Date().toISOString() };
        localStorage.setItem(DESIGN_PREFIX + designId, JSON.stringify({ schemaVersion: 2, designId: designId, activeProductId: product.id, assets: [record],
          drafts: { [product.id]: { productId: product.id, colorId: colorId, sizeId: sizeId, activeAreaId: areaId, areas: areas } }, updatedAt: new Date().toISOString() }));
      }

      /* The studio build in use ships the "preview" button disabled and unwired.
         Enable it once the canvas exists and continue to the customer preview page. */
      function enablePreviewWhenReady() {
        const button = document.getElementById("previewButton");
        if (!button) return;
        let tries = 0;
        const timer = setInterval(function () {
          tries += 1;
          if (document.querySelector(".canvas-container")) { clearInterval(timer); button.disabled = false; window.dispatchEvent(new Event("palprints:ready")); }
          else if (tries > 50) clearInterval(timer);
        }, 200);

        button.addEventListener("click", function () {
          // The studio saves edits a moment after each change; wait for the last one.
          setTimeout(function () {
            try {
              const selection = JSON.parse(sessionStorage.getItem(SELECTION_KEY) || "null");
              const doc = selection && JSON.parse(localStorage.getItem(DESIGN_PREFIX + selection.designId) || "null");
              const productId = doc && doc.activeProductId;
              const draft = doc && doc.drafts && doc.drafts[productId];
              if (!draft) { PalAlert.alert("تعذر حفظ التصميم. حاول مرة أخرى.", { icon: "error" }); return; }
              sessionStorage.setItem("palprintsStudioWorkflowContext", JSON.stringify({
                schemaVersion: 1, workflowMode: @json($workflowMode ?? 'customer'), source: "design-studio", designId: selection.designId,
                productId: productId, activeAreaId: draft.activeAreaId,
                preview: { colorId: draft.colorId || null, sizeId: draft.sizeId || null }, updatedAt: new Date().toISOString()
              }));
              window.location.href = @json($previewUrl ?? route('customer.productPreview'));
            } catch (error) { PalAlert.alert("تعذر فتح المعاينة. حاول مرة أخرى.", { icon: "error" }); }
          }, 400);
        });
      }

      /* The studio re-reads the product saved in the browser session (a refresh, or coming back to the studio).
         A copy saved by an older page load can be stale or invalid (for example a mug with two areas that map to the
         same id), and the studio refuses to open it. Replace it with the current product from the catalog. */
      function refreshStoredSelection() {
        try {
          const selection = JSON.parse(sessionStorage.getItem(SELECTION_KEY) || "null");
          if (!selection || !selection.editorProduct) return;
          const catalog = (window.PALPRINTS_PRODUCT_CATALOG || {}).products || [];
          const stored = selection.editorProduct;
          const fresh = catalog.find(function (item) { return item.id === stored.id; })
            || catalog.find(function (item) { return stored.categoryId && item.categoryId === stored.categoryId; });
          if (!fresh) { sessionStorage.removeItem(SELECTION_KEY); return; }
          const areaIds = fresh.editor.printAreas.map(function (area) { return area.id; });
          selection.editorProduct = clone(fresh);
          selection.productId = fresh.id;
          const kept = (selection.printAreaIds || []).filter(function (id) { return areaIds.includes(id); });
          selection.printAreaIds = kept.length ? kept : [fresh.editor.defaultAreaId];
          if (!fresh.colors.some(function (color) { return color.id === selection.colorId; })) selection.colorId = fresh.defaultColor;
          if (!fresh.sizes.some(function (size) { return size.id === selection.sizeId; })) selection.sizeId = fresh.sizes[0].id;
          sessionStorage.setItem(SELECTION_KEY, JSON.stringify(selection));
        } catch (error) { /* Nothing stored, or storage is blocked. */ }
      }

      /* A designer picks the product on the designer "choose product" page, which saves only database ids
         (product, color, size, print areas). Turn that choice into the studio's own product so the same studio,
         preview and cart code serve designers and customers. */
      function adoptDesignerSelection() {
        try {
          const selection = JSON.parse(sessionStorage.getItem(SELECTION_KEY) || "null");
          if (!selection || selection.editorProduct || !selection.productId) return;
          const db = DB.find(function (item) { return String(item.id) === String(selection.productId); });
          const catalog = (window.PALPRINTS_PRODUCT_CATALOG || {}).products || [];
          const product = db && catalog.find(function (item) { return item.code === String(db.code).toUpperCase(); });
          if (!product) { sessionStorage.removeItem(SELECTION_KEY); return; }
          const areaIds = product.editor.printAreas.map(function (area) { return area.id; });
          const areas = (selection.printAreaIds || []).map(studioAreaId).filter(function (id) { return areaIds.includes(id); });
          const colorId = String(selection.colorId || "").toLowerCase();
          sessionStorage.setItem(SELECTION_KEY, JSON.stringify({
            productId: product.id, editorProduct: product,
            colorId: product.colors.some(function (color) { return color.id === colorId; }) ? colorId : product.defaultColor,
            sizeId: product.sizes.some(function (size) { return size.id === studioSizeId(selection.sizeId); }) ? studioSizeId(selection.sizeId) : product.sizes[0].id,
            printAreaIds: areas.length ? [areas[0]] : [product.editor.defaultAreaId],
            category: selection.category || null,
            designId: "designer-" + Date.now()
          }));
        } catch (error) { /* The studio then asks the designer to choose a product. */ }
      }

      try {
        mergeCatalog();
        const seed = JSON.parse(sessionStorage.getItem(SEED_KEY) || "null");
        sessionStorage.removeItem(SEED_KEY);
        if (seed && seed.source === "upload") await openUploadedDesign(seed);
        adoptDesignerSelection();
        refreshStoredSelection();
      } catch (error) { /* The studio opens with whatever selection already exists. */ }

      start();
    })();
  </script>
@endpush
