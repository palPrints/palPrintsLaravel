"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const dictionary = {
    ar: {
      documentTitle: "طباعة الورق | PalPrints", skipToContent: "تخطي إلى المحتوى", sidebarLabel: "القائمة الجانبية للعميل", customerNavLabel: "روابط حساب العميل", goHome: "الانتقال إلى الصفحة الرئيسية", openSidebar: "فتح القائمة الجانبية", closeSidebar: "إغلاق القائمة الجانبية", changeTheme: "تغيير المظهر", changeLanguage: "تغيير اللغة", shoppingCart: "سلة المشتريات", breadcrumbLabel: "مسار التنقل", close: "إغلاق",
      home: "الرئيسية", profile: "الملف الشخصي", myOrders: "طلباتي", favorites: "المفضلة", savedCustomizations: "تخصيصاتي المحفوظة", shippingAddresses: "عناوين الشحن", notifications: "الإشعارات", settings: "الإعدادات والأمان", support: "التواصل مع الدعم الفني", logout: "تسجيل الخروج", logoutConfirm: "هل تريد تسجيل الخروج؟",
      products: "المنتجات", paperPrinting: "طباعة الورق", guidedOrder: "طلب طباعة مخصّص", pageSubtitle: "ارفع ملفاتك، اضبط خصائصها، ثم اختر طريقة التجميع والتغليف.", secureFiles: "ملفاتك خاصة وآمنة", journeyLabel: "مراحل طلب الطباعة",
      filesStep: "الملفات", filesStepHint: "الرفع والمعالجة", printStep: "خصائص الطباعة", printStepHint: "الورق والألوان", bindingStep: "التجميع والتغليف", bindingStepHint: "شكل المطبوعات", reviewStep: "المراجعة", reviewStepHint: "تأكيد الطلب", stageCurrent: "المرحلة الحالية", stageLocked: "أكمل المرحلة السابقة أولًا", stageNeedsReview: "تحتاج مراجعة",
      stepOne: "الخطوة 1 من 4", stepTwo: "الخطوة 2 من 4", stepThree: "الخطوة 3 من 4", stepFour: "الخطوة 4 من 4",
      uploadTitle: "ابدأ برفع ملفاتك", uploadDescription: "سنحلل الصفحات أولًا حتى تكون الخصائص والسعر أدق.", dropFiles: "اسحب ملفاتك إلى هنا", dropOrChoose: "أو اخترها من جهازك لبدء المعالجة", chooseFiles: "اختيار الملفات", sizeLimit: "50MB لكل ملف", filesLimit: "10 ملفات / 500 صفحة", uploadedFiles: "الملفات المرفوعة", addMore: "إضافة ملفات", continueToPrint: "متابعة إلى خصائص الطباعة",
      printOptionsTitle: "اختر خصائص الطباعة", printOptionsDescription: "تطبّق هذه الخصائص على جميع الملفات، ويمكنك تخصيص أي ملف لاحقًا.", generalSettings: "الإعداد العام", allFilesUseGeneral: "سيُطبق على جميع الملفات", applyToAll: "تطبيق العام على الجميع", required: "مطلوب", paperSize: "حجم الورق", paperType: "نوع الورق", standardPaper: "عادي 80 جم", standardPaperHint: "للمستندات اليومية", thickPaper: "فاخر 120 جم", thickPaperHint: "أكثر سماكة", coatedPaper: "مصقول 150 جم", coatedPaperHint: "ألوان أوضح", printColor: "لون الطباعة", blackWhite: "أبيض وأسود", blackWhiteHint: "اقتصادي وواضح", fullColor: "ملون", fullColorHint: "للعروض والصور", printSides: "جوانب الطباعة", singleSided: "وجه واحد", singleSidedHint: "كل صفحة على ورقة", doubleSided: "وجهين", doubleSidedHint: "أوراق أقل", pageLayout: "تخطيط الصفحة", onePage: "صفحة واحدة", twoPages: "صفحتان", fourPages: "4 صفحات", perSheet: "لكل وجه", fileCustomization: "تخصيص ملفات منفردة", fileCustomizationHint: "اختياري — استخدمه فقط إذا احتاج ملف إلى خصائص مختلفة.", confirmPrint: "تأكيد خصائص الطباعة",
      bindingTitle: "كيف تريد استلام ملفاتك؟", bindingDescription: "اختر طريقة التجميع، ثم التغليف المناسب للمطبوعات.", groupingMethod: "طريقة التعامل مع الملفات", combineAll: "دمج الجميع", combineAllHint: "ملزمة واحدة حسب الترتيب", separateAll: "فصل الجميع", separateAllHint: "كل ملف كمطبوعة مستقلة", bindingOrder: "ترتيب الملفات داخل الملزمة", bindingOrderHint: "استخدم الأسهم لتحديد ترتيب الدمج.", chooseBinding: "اختر التغليف", disabledOptionsHint: "الخيار غير المتاح يوضح سببه.", bindingEachFile: "تغليف كل ملف", bindingEachFileHint: "يمكن أن يختلف التغليف من ملف لآخر.", orderQuantity: "كمية الطلب", orderQuantityHint: "تطبق الكمية على الطلب الكامل", perFileQuantity: "كمية هذا الملف", perFileQuantities: "كمية مختلفة لكل ملف", decreaseQuantity: "تقليل الكمية", increaseQuantity: "زيادة الكمية", continueReview: "متابعة إلى المراجعة",
      reviewTitle: "راجع طلبك قبل إضافته للسلة", reviewDescription: "تأكد من الملفات والخصائص والتغليف. يمكنك تعديل أي مرحلة دون فقدان اختياراتك.", printingTotal: "إجمالي الطباعة", shippingLater: "يُحسب التوصيل في السلة", addToCart: "إضافة إلى السلة", previous: "السابق", continue: "متابعة",
      yourOrder: "طلبك", orderSummary: "ملخص الطباعة", currentStep: "المرحلة الحالية", files: "الملفات", printing: "الطباعة", binding: "التغليف", quantity: "الكمية", priceBreakdown: "تفصيل السعر", shippingNotIncluded: "لا يشمل التوصيل", needHelp: "تحتاج مساعدة؟ تواصل مع الدعم الفني", filePreview: "معاينة الملف", previewUnavailable: "المعاينة المرئية غير متاحة لهذا النوع",
      customize: "تخصيص", customized: "إعدادات مختلفة", removeOverride: "إزالة التخصيص", saveOverride: "حفظ التخصيص", cancel: "إلغاء", edit: "تعديل", pages: "صفحة", ready: "جاهز", processing: "جارٍ تحليل الملف", converting: "جارٍ تحويل الملف", failed: "يحتاج إلى إجراء", retry: "إعادة المحاولة", delete: "حذف", preview: "معاينة", moveUp: "تحريك لأعلى", moveDown: "تحريك لأسفل",
      spiral: "سلك حلزوني", clearCover: "غلاف شفاف", glue: "تجليد حراري", sideStaple: "تدبيس جانبي", cornerStaple: "تدبيس زاوية", none: "بدون تغليف", noBindingFree: "بدون تغليف مجاني", lamination: "تغليف حراري شفاف", free: "مجانًا", glueMin: "يتطلب 20 صفحة على الأقل", spiralMax: "غير متاح لأكثر من 350 صفحة", stapleMax: "عدد الصفحات أكبر من الحد", laminationA3: "غير متاح مع مقاس A3",
      filesRequired: "أضف ملفًا جاهزًا واحدًا على الأقل.", resolveFiles: "عالج الملفات التي تحتاج إلى إجراء قبل المتابعة.", printRequired: "اختر جميع خصائص الطباعة المطلوبة.", groupingRequired: "اختر دمج الملفات أو فصلها.", bindingRequired: "حدد نوع التغليف المطلوب.", signInRequired: "يجب تسجيل الدخول قبل رفع الملفات.", unsupportedFile: "صيغة الملف غير مدعومة.", fileTooLarge: "حجم الملف يتجاوز 50MB.", tooManyFiles: "يمكن رفع 10 ملفات كحد أقصى.", tooManyPages: "سيؤدي هذا الملف إلى تجاوز حد 500 صفحة.", duplicateFile: "هذا الملف مضاف بالفعل.", deleteConfirm: "سيؤثر حذف هذا الملف على الطلب والسعر. هل تريد المتابعة؟", fileRemoved: "تم حذف الملف.", undo: "استعادة", fileRestored: "تمت استعادة الملف.", settingsConfirmed: "تم حفظ خصائص الطباعة.", bindingConfirmed: "تم حفظ التجميع والتغليف.", orderAdded: "تمت إضافة طلب الطباعة إلى السلة.", applyAllConfirm: "سيتم حذف تخصيصات الملفات الفردية. هل تريد المتابعة؟", overridesCleared: "تم تطبيق الإعداد العام على جميع الملفات.", addedNeedsReview: "تمت إضافة الملف. راجع الخصائص قبل المتابعة.", overrideFirst: "أكمل الإعداد العام أولًا.", overrideSaved: "تم حفظ خصائص الملف.", priceCalculating: "يُحسب بعد إكمال الخصائص", printCost: "تكلفة الطباعة", bindingCost: "تكلفة التغليف", orderOutput: "مخرج الطلب", oneBooklet: "ملزمة واحدة", separatePrints: "مطبوعات منفصلة", mixedSettings: "خصائص مختلفة", notSelected: "لم تحدد بعد", oneFile: "ملف واحد", fileCount: "{count} ملفات", pageCount: "{count} صفحة", overridesCount: "{count} ملف بإعداد مختلف", stageReady: "جاهزة",
      A4: "A4", A5: "A5", A3: "A3", standard: "عادي 80 جم", thick: "فاخر 120 جم", coated: "مصقول 150 جم", bw: "أبيض وأسود", color: "ملون", single: "وجه واحد", double: "وجهين", layout1: "صفحة واحدة", layout2: "صفحتان", layout4: "4 صفحات"
    },
    en: {
      documentTitle: "Paper Printing | PalPrints", skipToContent: "Skip to content", sidebarLabel: "Customer sidebar", customerNavLabel: "Customer account links", goHome: "Go to home page", openSidebar: "Open sidebar", closeSidebar: "Close sidebar", changeTheme: "Change theme", changeLanguage: "Change language", shoppingCart: "Shopping cart", breadcrumbLabel: "Breadcrumb", close: "Close",
      home: "Home", profile: "Profile", myOrders: "My orders", favorites: "Favorites", savedCustomizations: "Saved customizations", shippingAddresses: "Shipping addresses", notifications: "Notifications", settings: "Settings & security", support: "Technical support", logout: "Log out", logoutConfirm: "Do you want to log out?",
      products: "Products", paperPrinting: "Paper printing", guidedOrder: "Custom print order", pageSubtitle: "Upload your files, set print options, then choose grouping and binding.", secureFiles: "Your files are private and secure", journeyLabel: "Print order steps",
      filesStep: "Files", filesStepHint: "Upload & processing", printStep: "Print options", printStepHint: "Paper & color", bindingStep: "Grouping & binding", bindingStepHint: "Finished output", reviewStep: "Review", reviewStepHint: "Confirm order", stageCurrent: "Current step", stageLocked: "Complete the previous step first", stageNeedsReview: "Needs review",
      stepOne: "Step 1 of 4", stepTwo: "Step 2 of 4", stepThree: "Step 3 of 4", stepFour: "Step 4 of 4",
      uploadTitle: "Start by uploading your files", uploadDescription: "We analyze pages first so options and pricing are more accurate.", dropFiles: "Drop your files here", dropOrChoose: "or choose them from your device", chooseFiles: "Choose files", sizeLimit: "50MB per file", filesLimit: "10 files / 500 pages", uploadedFiles: "Uploaded files", addMore: "Add files", continueToPrint: "Continue to print options",
      printOptionsTitle: "Choose print options", printOptionsDescription: "These options apply to every file. You can customize individual files later.", generalSettings: "General settings", allFilesUseGeneral: "Applies to all files", applyToAll: "Apply general settings to all", required: "Required", paperSize: "Paper size", paperType: "Paper type", standardPaper: "Standard 80 gsm", standardPaperHint: "Everyday documents", thickPaper: "Premium 120 gsm", thickPaperHint: "More substantial", coatedPaper: "Coated 150 gsm", coatedPaperHint: "Richer colors", printColor: "Print color", blackWhite: "Black & white", blackWhiteHint: "Clear and economical", fullColor: "Full color", fullColorHint: "For slides and images", printSides: "Print sides", singleSided: "Single-sided", singleSidedHint: "Each page on one sheet", doubleSided: "Double-sided", doubleSidedHint: "Uses fewer sheets", pageLayout: "Page layout", onePage: "One page", twoPages: "Two pages", fourPages: "4 pages", perSheet: "per side", fileCustomization: "Customize individual files", fileCustomizationHint: "Optional — use only when a file needs different settings.", confirmPrint: "Confirm print options",
      bindingTitle: "How should we prepare your files?", bindingDescription: "Choose grouping, then the right binding for the output.", groupingMethod: "File handling", combineAll: "Combine all", combineAllHint: "One booklet in your chosen order", separateAll: "Keep separate", separateAllHint: "Each file is its own print item", bindingOrder: "File order in the booklet", bindingOrderHint: "Use the arrows to set merge order.", chooseBinding: "Choose binding", disabledOptionsHint: "Unavailable options explain why.", bindingEachFile: "Binding for each file", bindingEachFileHint: "Each file can use a different finish.", orderQuantity: "Order quantity", orderQuantityHint: "Quantity applies to the entire order", perFileQuantity: "Quantity for this file", perFileQuantities: "Different quantity per file", decreaseQuantity: "Decrease quantity", increaseQuantity: "Increase quantity", continueReview: "Continue to review",
      reviewTitle: "Review before adding to cart", reviewDescription: "Check files, options and binding. You can edit any step without losing choices.", printingTotal: "Printing total", shippingLater: "Shipping is calculated in cart", addToCart: "Add to cart", previous: "Back", continue: "Continue",
      yourOrder: "Your order", orderSummary: "Print summary", currentStep: "Current step", files: "Files", printing: "Printing", binding: "Binding", quantity: "Quantity", priceBreakdown: "Price breakdown", shippingNotIncluded: "Shipping not included", needHelp: "Need help? Contact support", filePreview: "File preview", previewUnavailable: "Visual preview is unavailable for this file type",
      customize: "Customize", customized: "Different settings", removeOverride: "Remove override", saveOverride: "Save override", cancel: "Cancel", edit: "Edit", pages: "pages", ready: "Ready", processing: "Analyzing file", converting: "Converting file", failed: "Action required", retry: "Retry", delete: "Delete", preview: "Preview", moveUp: "Move up", moveDown: "Move down",
      spiral: "Spiral binding", clearCover: "Clear cover", glue: "Glue binding", sideStaple: "Side stapling", cornerStaple: "Corner stapling", none: "No binding", noBindingFree: "No binding — Free", lamination: "Thermal lamination", free: "Free", glueMin: "Requires at least 20 pages", spiralMax: "Unavailable above 350 pages", stapleMax: "Page count exceeds the limit", laminationA3: "Unavailable with A3",
      filesRequired: "Add at least one ready file.", resolveFiles: "Resolve files that need attention before continuing.", printRequired: "Choose every required print option.", groupingRequired: "Choose combine or separate files.", bindingRequired: "Choose the required binding.", signInRequired: "Sign in before uploading files.", unsupportedFile: "Unsupported file format.", fileTooLarge: "File size exceeds 50MB.", tooManyFiles: "You can upload up to 10 files.", tooManyPages: "This file would exceed the 500-page limit.", duplicateFile: "This file is already added.", deleteConfirm: "Deleting this file will affect your order and price. Continue?", fileRemoved: "File removed.", undo: "Undo", fileRestored: "File restored.", settingsConfirmed: "Print options saved.", bindingConfirmed: "Grouping and binding saved.", orderAdded: "Print order added to cart.", applyAllConfirm: "This removes all individual file overrides. Continue?", overridesCleared: "General settings applied to every file.", addedNeedsReview: "File added. Review the options before continuing.", overrideFirst: "Complete general settings first.", overrideSaved: "File settings saved.", priceCalculating: "Calculated after completing options", printCost: "Printing cost", bindingCost: "Binding cost", orderOutput: "Order output", oneBooklet: "One booklet", separatePrints: "Separate prints", mixedSettings: "Mixed settings", notSelected: "Not selected yet", oneFile: "1 file", fileCount: "{count} files", pageCount: "{count} pages", overridesCount: "{count} files with different settings", stageReady: "Ready",
      A4: "A4", A5: "A5", A3: "A3", standard: "Standard 80 gsm", thick: "Premium 120 gsm", coated: "Coated 150 gsm", bw: "Black & white", color: "Full color", single: "Single-sided", double: "Double-sided", layout1: "One page", layout2: "Two pages", layout4: "4 pages"
    }
  };

  /* The current storefront design system is intentionally light-only. */
  document.documentElement.setAttribute("data-bs-theme", "light");

  const state = {
    currentStep: 1,
    completedStep: 0,
    files: [],
    globalSettings: { size: null, paper: null, color: null, sides: null, layout: null },
    grouping: null,
    fileOrder: [],
    combinedBinding: "none",
    separateBindings: {},
    separateQuantities: {},
    quantity: 1,
    overrideFileId: null,
    overrideDraft: null
  };

  const supportedExtensions = ["pdf", "jpg", "jpeg", "png", "docx", "pptx"];
  const settingGroups = ["size", "paper", "color", "sides", "layout"];
  const bindings = [
    { id: "spiral", icon: "bi-journal", price: 8 },
    { id: "clearCover", icon: "bi-file-earmark", price: 3 },
    { id: "glue", icon: "bi-book", price: 6 },
    { id: "sideStaple", icon: "bi-distribute-vertical", price: 1.5 },
    { id: "cornerStaple", icon: "bi-paperclip", price: 0.75 },
    { id: "none", icon: "bi-slash-circle", price: 0 },
    { id: "lamination", icon: "bi-layers", price: 12 }
  ];

  const $ = function (selector, scope) { return (scope || document).querySelector(selector); };
  const $$ = function (selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); };
  const el = {
    input: $("#paperFileInput"), choose: $("#chooseFilesButton"), addMore: $("#addMoreFilesButton"), zone: $("#uploadZone"), fileSection: $("#fileSection"), fileList: $("#paperFileList"), fileCounter: $("#fileCounter"), customization: $("#customizationList"), overrideEditor: $("#overrideEditor"), applyAll: $("#applyAllButton"), overrideCount: $("#overrideCountText"), bindingWorkspace: $("#bindingWorkspace"), reorderPanel: $("#reorderPanel"), reorderList: $("#reorderList"), combinedPanel: $("#combinedBindingPanel"), bindingGrid: $("#bindingGrid"), separatePanel: $("#separateBindingPanel"), separateList: $("#separateBindingList"), quantityPanel: $("#orderQuantityPanel"), quantity: $("#quantityOutput"), configurator: $(".paper-configurator-layout"), orderSummary: $(".order-summary"), mobileOrderBar: $("#mobileOrderBar"), summaryCta: $("#summaryCta"), mobileCta: $("#mobileCta"), total: $("#summaryTotal"), mobileTotal: $("#mobileTotal"), reviewTotal: $("#reviewTotal"), reviewList: $("#reviewList"), toast: $("#paperToast"), toastMessage: $("#paperToastMessage"), preview: $("#previewDialog"), mobileSummary: $("#mobileSummaryDialog")
  };

  let toastTimer = null;

  function t(key, vars) {
    let value = dictionary.ar[key] || key;
    Object.keys(vars || {}).forEach(function (name) {
      value = value.replace("{" + name + "}", vars[name]);
    });
    return value;
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"]/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[char];
    });
  }

  function formatSize(bytes) {
    if (bytes < 1024 * 1024) return Math.max(1, Math.round(bytes / 1024)) + " KB";
    return (bytes / (1024 * 1024)).toFixed(1) + " MB";
  }

  function formatMoney(amount) {
    return new Intl.NumberFormat("ar-SA", { style: "currency", currency: "SAR", minimumFractionDigits: 2 }).format(amount || 0);
  }

  function showToast(message, type, action) {
    clearTimeout(toastTimer);
    const oldAction = $(".paper-toast-action", el.toast);
    if (oldAction) oldAction.remove();
    el.toast.classList.toggle("is-error", type === "error");
    el.toastMessage.textContent = message;
    if (action) {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "paper-toast-action";
      button.textContent = action.label;
      button.addEventListener("click", function () {
        action.callback();
        el.toast.classList.remove("is-visible");
      });
      el.toast.appendChild(button);
    }
    el.toast.classList.add("is-visible");
    toastTimer = setTimeout(function () { el.toast.classList.remove("is-visible"); }, action ? 6500 : 3200);
  }

  function readyFiles() { return state.files.filter(function (file) { return file.status === "ready"; }); }
  function totalPages() { return readyFiles().reduce(function (sum, file) { return sum + file.pages; }, 0); }
  function globalComplete() { return settingGroups.every(function (group) { return Boolean(state.globalSettings[group]); }); }
  function effectiveSettings(file) { return Object.assign({}, state.globalSettings, file.override || {}); }

  function fileIcon(file) {
    if (["jpg", "jpeg", "png"].indexOf(file.ext) !== -1) return { icon: "bi-file-earmark-image", type: "is-image" };
    if (file.ext === "pdf") return { icon: "bi-file-earmark-pdf", type: "is-pdf" };
    return { icon: "bi-file-earmark-richtext", type: "is-office" };
  }

  async function estimatePages(file, ext) {
    if (["jpg", "jpeg", "png"].indexOf(ext) !== -1) return 1;
    if (ext === "pdf") {
      try {
        const buffer = await file.slice(0, Math.min(file.size, 12 * 1024 * 1024)).arrayBuffer();
        const text = new TextDecoder("latin1").decode(buffer);
        const matches = text.match(/\/Type\s*\/Page\b/g);
        if (matches && matches.length) return matches.length;
      } catch (error) { /* fallback below */ }
    }
    return Math.max(1, Math.min(500, Math.ceil(file.size / 90000)));
  }

  function processFile(item) {
    item.status = "processing";
    item.progress = 18;
    renderFiles();
    setTimeout(async function () {
      item.progress = 64;
      renderFiles();
      const pages = await estimatePages(item.file, item.ext);
      const usedPages = readyFiles().filter(function (file) { return file.id !== item.id; }).reduce(function (sum, file) { return sum + file.pages; }, 0);
      if (usedPages + pages > 500) {
        item.status = "error";
        item.error = "tooManyPages";
        item.progress = 0;
      } else {
        item.pages = pages;
        item.status = "ready";
        item.error = null;
        item.progress = 100;
        if (state.fileOrder.indexOf(item.id) === -1) state.fileOrder.push(item.id);
      }
      renderAll();
    }, 520);
  }

  function addFiles(fileList) {
    if (document.body.dataset.authenticated !== "true") {
      showToast(t("signInRequired"), "error");
      return;
    }
    const incoming = Array.prototype.slice.call(fileList || []);
    incoming.forEach(function (file) {
      if (state.files.length >= 10) { showToast(t("tooManyFiles"), "error"); return; }
      const ext = (file.name.split(".").pop() || "").toLowerCase();
      const duplicate = state.files.some(function (item) { return item.name === file.name && item.size === file.size; });
      const item = { id: "file-" + Date.now() + "-" + Math.random().toString(36).slice(2, 7), file: file, name: file.name, size: file.size, ext: ext, pages: 0, status: "queued", progress: 0, error: null, override: null };
      if (duplicate) { showToast(t("duplicateFile"), "error"); return; }
      if (supportedExtensions.indexOf(ext) === -1) { item.status = "error"; item.error = "unsupportedFile"; state.files.push(item); return; }
      if (file.size > 50 * 1024 * 1024) { item.status = "error"; item.error = "fileTooLarge"; state.files.push(item); return; }
      state.files.push(item);
      processFile(item);
    });
    el.input.value = "";
    if (state.completedStep >= 1) {
      state.completedStep = 0;
      state.currentStep = 1;
      showToast(t("addedNeedsReview"));
    }
    renderAll();
  }

  function statusMarkup(file) {
    if (file.status === "ready") return '<span class="file-status"><i class="bi bi-check-circle-fill"></i>' + escapeHtml(t("ready")) + "</span>";
    if (file.status === "processing") return '<span class="file-status"><i class="bi bi-arrow-repeat"></i>' + escapeHtml(t(file.ext === "docx" || file.ext === "pptx" ? "converting" : "processing")) + "</span>";
    return '<span class="file-status"><i class="bi bi-exclamation-circle-fill"></i>' + escapeHtml(t(file.error || "failed")) + "</span>";
  }

  function renderFiles() {
    const hasFiles = state.files.length > 0;
    el.zone.hidden = hasFiles;
    el.fileSection.hidden = !hasFiles;
    const ready = readyFiles();
    el.fileCounter.textContent = t("fileCount", { count: state.files.length }) + " • " + t("pageCount", { count: totalPages() }) + " • " + (10 - state.files.length) + "/10";
    el.fileList.innerHTML = state.files.map(function (file) {
      const icon = fileIcon(file);
      return '<article class="file-card is-' + file.status + '" data-file-id="' + file.id + '">' +
        '<span class="file-type-icon ' + icon.type + '"><i class="bi ' + icon.icon + '"></i></span>' +
        '<div class="file-info"><strong title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + '</strong><div class="file-meta"><span>' + escapeHtml(formatSize(file.size)) + '</span>' + (file.pages ? '<span>•</span><span>' + escapeHtml(t("pageCount", { count: file.pages })) + '</span>' : '') + '<span>•</span>' + statusMarkup(file) + '</div>' + (file.status === "processing" ? '<div class="file-progress"><span style="width:' + file.progress + '%"></span></div>' : '') + '</div>' +
        '<div class="file-actions">' + (file.status === "error" ? '<button class="icon-button" type="button" data-file-action="retry" title="' + escapeHtml(t("retry")) + '"><i class="bi bi-arrow-clockwise"></i></button>' : '<button class="icon-button" type="button" data-file-action="preview" title="' + escapeHtml(t("preview")) + '"><i class="bi bi-eye"></i></button>') + '<button class="icon-button is-danger" type="button" data-file-action="delete" title="' + escapeHtml(t("delete")) + '"><i class="bi bi-trash3"></i></button></div></article>';
    }).join("");
    if (!ready.length) state.fileOrder = [];
  }

  function removeFile(id) {
    const index = state.files.findIndex(function (file) { return file.id === id; });
    if (index < 0) return;
    const file = state.files[index];
    const impactful = state.completedStep > 0 || file.override || state.grouping;
    if (impactful && !window.confirm(t("deleteConfirm"))) return;
    state.files.splice(index, 1);
    state.fileOrder = state.fileOrder.filter(function (fileId) { return fileId !== id; });
    delete state.separateBindings[id];
    const removedQuantity = state.separateQuantities[id] || 1;
    delete state.separateQuantities[id];
    state.completedStep = 0;
    state.currentStep = 1;
    renderAll();
    showToast(t("fileRemoved"), "success", { label: t("undo"), callback: function () {
      state.files.splice(Math.min(index, state.files.length), 0, file);
      if (file.status === "ready") state.fileOrder.splice(Math.min(index, state.fileOrder.length), 0, file.id);
      state.separateQuantities[file.id] = removedQuantity;
      renderAll();
      showToast(t("fileRestored"));
    }});
  }

  function openPreview(file) {
    const image = $("#previewImage");
    const frame = $("#previewFrame");
    const placeholder = $("#previewPlaceholder");
    $("#previewTitle").textContent = file.name;
    $("#previewMeta").textContent = formatSize(file.size) + (file.pages ? " • " + t("pageCount", { count: file.pages }) : "");
    image.hidden = true; frame.hidden = true; placeholder.hidden = true;
    if (["jpg", "jpeg", "png"].indexOf(file.ext) !== -1) { image.src = URL.createObjectURL(file.file); image.alt = file.name; image.hidden = false; }
    else if (file.ext === "pdf") { frame.src = URL.createObjectURL(file.file); frame.hidden = false; }
    else placeholder.hidden = false;
    if (typeof el.preview.showModal === "function") el.preview.showModal();
  }

  function renderGlobalSelections() {
    $$("#globalOptions [data-option-group]").forEach(function (groupEl) {
      const group = groupEl.dataset.optionGroup;
      $$('[data-value]', groupEl).forEach(function (button) { button.classList.toggle("is-selected", state.globalSettings[group] === button.dataset.value); });
    });
  }

  function settingsSummary(settings) {
    if (!settingGroups.every(function (key) { return Boolean(settings[key]); })) return t("notSelected");
    return [t(settings.size), t(settings.paper), t(settings.color), t(settings.sides), t("layout" + settings.layout)].join(" • ");
  }

  function renderCustomization() {
    const ready = readyFiles();
    const count = ready.filter(function (file) { return Boolean(file.override); }).length;
    el.overrideCount.textContent = count ? t("overridesCount", { count: count }) : t("allFilesUseGeneral");
    el.applyAll.hidden = count === 0;
    el.customization.innerHTML = ready.map(function (file) {
      const icon = fileIcon(file);
      return '<div class="customization-item" data-file-id="' + file.id + '"><span class="file-type-icon ' + icon.type + '"><i class="bi ' + icon.icon + '"></i></span><div class="file-info"><strong>' + escapeHtml(file.name) + '</strong><small>' + escapeHtml(file.override ? settingsSummary(effectiveSettings(file)) : t("allFilesUseGeneral")) + '</small></div>' + (file.override ? '<span class="override-badge"><i class="bi bi-stars"></i>' + escapeHtml(t("customized")) + '</span><button type="button" class="icon-button is-danger" data-custom-action="remove" title="' + escapeHtml(t("removeOverride")) + '"><i class="bi bi-arrow-counterclockwise"></i></button>' : '') + '<button type="button" class="paper-button is-soft is-small" data-custom-action="edit"><i class="bi bi-sliders"></i><span>' + escapeHtml(t("customize")) + '</span></button></div>';
    }).join("");
    if (state.overrideFileId && !state.files.some(function (file) { return file.id === state.overrideFileId; })) closeOverride();
  }

  const optionValues = {
    size: ["A5", "A4", "A3"], paper: ["standard", "thick", "coated"], color: ["bw", "color"], sides: ["single", "double"], layout: ["1", "2", "4"]
  };
  const optionTitles = { size: "paperSize", paper: "paperType", color: "printColor", sides: "printSides", layout: "pageLayout" };

  function openOverride(id) {
    if (!globalComplete()) { showToast(t("overrideFirst"), "error"); return; }
    const file = state.files.find(function (item) { return item.id === id; });
    if (!file) return;
    state.overrideFileId = id;
    state.overrideDraft = Object.assign({}, effectiveSettings(file));
    renderOverrideEditor();
    el.overrideEditor.hidden = false;
    el.overrideEditor.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function renderOverrideEditor() {
    const file = state.files.find(function (item) { return item.id === state.overrideFileId; });
    if (!file) return;
    const groups = settingGroups.map(function (group) {
      return '<fieldset class="option-group"><legend><span>' + escapeHtml(t(optionTitles[group])) + '</span></legend><div class="choice-grid ' + (optionValues[group].length === 2 ? 'is-two' : 'is-three') + '">' + optionValues[group].map(function (value) {
        return '<button type="button" class="choice-card ' + (state.overrideDraft[group] === value ? 'is-selected' : '') + '" data-override-group="' + group + '" data-value="' + value + '"><strong>' + escapeHtml(t(group === "layout" ? "layout" + value : value)) + '</strong></button>';
      }).join("") + '</div></fieldset>';
    }).join("");
    el.overrideEditor.innerHTML = '<div class="override-editor-header"><div><h4>' + escapeHtml(t("customize")) + '</h4><p>' + escapeHtml(file.name) + '</p></div><button type="button" class="icon-button" data-override-action="cancel"><i class="bi bi-x-lg"></i></button></div><div class="option-groups">' + groups + '</div><div class="override-actions"><button type="button" class="paper-button is-ghost is-small" data-override-action="cancel">' + escapeHtml(t("cancel")) + '</button><button type="button" class="paper-button is-primary is-small" data-override-action="save">' + escapeHtml(t("saveOverride")) + '</button></div>';
  }

  function closeOverride() { state.overrideFileId = null; state.overrideDraft = null; el.overrideEditor.hidden = true; el.overrideEditor.innerHTML = ""; }

  function bindingAvailability(bindingId, files) {
    const pages = files.reduce(function (sum, file) { return sum + file.pages; }, 0);
    const hasA3 = files.some(function (file) { return effectiveSettings(file).size === "A3"; });
    if (bindingId === "glue" && pages < 20) return { ok: false, reason: "glueMin" };
    if (bindingId === "spiral" && pages > 350) return { ok: false, reason: "spiralMax" };
    if ((bindingId === "sideStaple" && pages > 80) || (bindingId === "cornerStaple" && pages > 40)) return { ok: false, reason: "stapleMax" };
    if (bindingId === "lamination" && hasA3) return { ok: false, reason: "laminationA3" };
    return { ok: true, reason: null };
  }

  function normalizeOrder() {
    const ids = readyFiles().map(function (file) { return file.id; });
    state.fileOrder = state.fileOrder.filter(function (id) { return ids.indexOf(id) !== -1; });
    ids.forEach(function (id) { if (state.fileOrder.indexOf(id) === -1) state.fileOrder.push(id); });
  }

  function orderedFiles() {
    normalizeOrder();
    return state.fileOrder.map(function (id) { return state.files.find(function (file) { return file.id === id; }); }).filter(Boolean);
  }

  function bindingLabel(id) { return id ? t(id) : t("notSelected"); }

  function renderBindings() {
    const ready = readyFiles();
    const usesPerFileQuantities = state.grouping === "separate" && ready.length > 1;
    $$("[data-grouping]").forEach(function (button) { button.classList.toggle("is-selected", state.grouping === button.dataset.grouping); });
    el.bindingWorkspace.hidden = !state.grouping;
    el.reorderPanel.hidden = state.grouping !== "combined";
    el.combinedPanel.hidden = state.grouping !== "combined";
    el.separatePanel.hidden = state.grouping !== "separate";
    el.quantityPanel.hidden = !state.grouping || usesPerFileQuantities;
    el.quantity.textContent = state.quantity;
    if (!state.grouping) return;

    if (state.grouping === "combined") {
      const files = orderedFiles();
      el.reorderList.innerHTML = files.map(function (file, index) {
        return '<div class="reorder-item" data-file-id="' + file.id + '"><span class="reorder-index">' + (index + 1) + '</span><i class="bi bi-grip-vertical" aria-hidden="true"></i><strong>' + escapeHtml(file.name) + '</strong><div class="reorder-actions"><button type="button" class="icon-button" data-move="up" title="' + escapeHtml(t("moveUp")) + '" ' + (index === 0 ? 'disabled' : '') + '><i class="bi bi-arrow-up"></i></button><button type="button" class="icon-button" data-move="down" title="' + escapeHtml(t("moveDown")) + '" ' + (index === files.length - 1 ? 'disabled' : '') + '><i class="bi bi-arrow-down"></i></button></div></div>';
      }).join("");
      el.bindingGrid.innerHTML = bindings.map(function (binding) {
        const availability = bindingAvailability(binding.id, files);
        if (!availability.ok && state.combinedBinding === binding.id) state.combinedBinding = null;
        return '<button type="button" class="choice-card binding-card ' + (state.combinedBinding === binding.id ? 'is-selected' : '') + '" data-binding="' + binding.id + '" ' + (!availability.ok ? 'disabled' : '') + '><i class="bi ' + binding.icon + '"></i><strong>' + escapeHtml(t(binding.id)) + '</strong><span class="binding-price">' + escapeHtml(binding.price ? "+ " + formatMoney(binding.price) : t("free")) + '</span>' + (!availability.ok ? '<small class="binding-reason">' + escapeHtml(t(availability.reason)) + '</small>' : '') + '</button>';
      }).join("");
    } else {
      const files = ready;
      files.forEach(function (file) {
        if (!(file.id in state.separateBindings)) state.separateBindings[file.id] = "none";
        if (!(file.id in state.separateQuantities)) state.separateQuantities[file.id] = 1;
        const chosen = state.separateBindings[file.id];
        if (chosen && !bindingAvailability(chosen, [file]).ok) state.separateBindings[file.id] = null;
      });
      el.separateList.innerHTML = files.map(function (file) {
        const icon = fileIcon(file);
        const options = bindings.map(function (binding) {
          const availability = bindingAvailability(binding.id, [file]);
          const suffix = availability.ok ? (binding.price ? " — " + formatMoney(binding.price) : " — " + t("free")) : " — " + t(availability.reason);
          const optionLabel = binding.id === "none" ? t("noBindingFree") : t(binding.id) + suffix;
          return '<option value="' + binding.id + '" ' + (state.separateBindings[file.id] === binding.id ? 'selected' : '') + ' ' + (!availability.ok ? 'disabled' : '') + '>' + escapeHtml(optionLabel) + '</option>';
        }).join("");
        const selectedBinding = state.separateBindings[file.id];
        const bindingControl = '<label class="binding-select-wrap"><span class="binding-select-title"><i class="bi bi-journal-bookmark" aria-hidden="true"></i>' + escapeHtml(t("chooseBinding")) + '</span><select class="has-selection" data-separate-binding="' + file.id + '" aria-label="' + escapeHtml(t("binding")) + '">' + options + '</select><small>' + escapeHtml(settingsSummary(effectiveSettings(file))) + '</small></label>';
        const quantityControl = usesPerFileQuantities ? '<div class="per-file-quantity"><span>' + escapeHtml(t("perFileQuantity")) + '</span><div class="quantity-control"><button type="button" data-file-quantity-action="decrease" aria-label="' + escapeHtml(t("decreaseQuantity")) + '"><i class="bi bi-dash-lg"></i></button><output>' + state.separateQuantities[file.id] + '</output><button type="button" data-file-quantity-action="increase" aria-label="' + escapeHtml(t("increaseQuantity")) + '"><i class="bi bi-plus-lg"></i></button></div></div>' : '';
        return '<div class="separate-binding-item" data-file-id="' + file.id + '"><span class="file-type-icon ' + icon.type + '"><i class="bi ' + icon.icon + '"></i></span><div class="file-info"><strong>' + escapeHtml(file.name) + '</strong><small>' + escapeHtml(t("pageCount", { count: file.pages })) + '</small></div><div class="separate-binding-controls">' + bindingControl + quantityControl + '</div></div>';
      }).join("");
    }
  }

  function validateStep(step) {
    const error = $("#stageError" + step);
    let message = "";
    if (step === 1) {
      if (!readyFiles().length) message = t("filesRequired");
      else if (state.files.some(function (file) { return file.status !== "ready"; })) message = t("resolveFiles");
    }
    if (step === 2 && !globalComplete()) {
      message = t("printRequired");
      $$("#globalOptions [data-option-group]").forEach(function (groupEl) { groupEl.classList.toggle("has-error", !state.globalSettings[groupEl.dataset.optionGroup]); });
    }
    if (step === 3) {
      if (!state.grouping) message = t("groupingRequired");
      else if (state.grouping === "combined" && !state.combinedBinding) message = t("bindingRequired");
      else if (state.grouping === "separate" && readyFiles().some(function (file) { return !state.separateBindings[file.id]; })) message = t("bindingRequired");
    }
    if (error) { error.hidden = !message; error.textContent = message; }
    return !message;
  }

  function completeAndGo(target) {
    if (!validateStep(state.currentStep)) return;
    state.completedStep = Math.max(state.completedStep, state.currentStep);
    if (state.currentStep === 2) showToast(t("settingsConfirmed"));
    if (state.currentStep === 3) showToast(t("bindingConfirmed"));
    goToStep(target);
  }

  function goToStep(step) {
    const max = Math.min(4, state.completedStep + 1);
    if (step > max) return;
    closeOverride();
    state.currentStep = step;
    if (step === 4) renderReview();
    updateJourney();
    updateSummary();
    const active = $('.journey-stage[data-stage="' + step + '"]');
    if (active && window.innerWidth < 992) active.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function stageSummary(step) {
    if (step === 1) return readyFiles().length ? t("fileCount", { count: readyFiles().length }) + " • " + t("pageCount", { count: totalPages() }) : t("stageCurrent");
    if (step === 2) return globalComplete() ? settingsSummary(state.globalSettings) : t("stageNeedsReview");
    if (step === 3) return state.grouping === "combined" ? t("oneBooklet") + " • " + bindingLabel(state.combinedBinding) : state.grouping === "separate" ? t("separatePrints") : t("stageNeedsReview");
    return t("stageReady");
  }

  function updateJourney() {
    const max = state.completedStep + 1;
    const isReviewMode = state.currentStep === 4;
    el.configurator.classList.toggle("is-review-mode", isReviewMode);
    el.orderSummary.hidden = isReviewMode;
    el.mobileOrderBar.hidden = isReviewMode;
    document.body.classList.toggle("paper-review-active", isReviewMode);
    $$(".journey-stage").forEach(function (stage) {
      const number = Number(stage.dataset.stage);
      stage.classList.toggle("is-active", number === state.currentStep);
      stage.classList.toggle("is-complete", number <= state.completedStep);
      const toggle = $("[data-stage-toggle]", stage);
      toggle.disabled = number > max;
      toggle.setAttribute("aria-expanded", number === state.currentStep ? "true" : "false");
      const summary = $("#stageSummary" + number);
      if (summary) summary.textContent = number > max ? t("stageLocked") : stageSummary(number);
    });
    $$(".paper-progress-step").forEach(function (button) {
      const number = Number(button.dataset.progressStep);
      button.disabled = number > max;
      button.classList.toggle("is-active", number === state.currentStep);
      button.classList.toggle("is-complete", number <= state.completedStep);
      button.toggleAttribute("aria-current", number === state.currentStep);
      const badge = $(".progress-number", button);
      badge.innerHTML = number <= state.completedStep ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : String(number);
    });
    $$(".progress-line").forEach(function (line, index) { line.classList.toggle("is-complete", index + 1 <= state.completedStep); });
    $("#summaryProgressBar").style.width = (state.currentStep * 25) + "%";
  }

  function printCostFor(file) {
    const settings = effectiveSettings(file);
    if (!settingGroups.every(function (key) { return settings[key]; })) return 0;
    const pagesPerSheet = Number(settings.layout) * (settings.sides === "double" ? 2 : 1);
    const sheets = Math.ceil(file.pages / pagesPerSheet);
    const sheetBase = { A5: 0.12, A4: 0.2, A3: 0.42 }[settings.size];
    const paperMultiplier = { standard: 1, thick: 1.55, coated: 2.1 }[settings.paper];
    const ink = settings.color === "color" ? 0.42 : 0.09;
    return (sheets * sheetBase * paperMultiplier) + (file.pages * ink);
  }

  function bindingPrice(id) {
    const found = bindings.find(function (binding) { return binding.id === id; });
    return found ? found.price : 0;
  }

  function usesPerFileQuantity() {
    return state.grouping === "separate" && readyFiles().length > 1;
  }

  function quantityFor(file) {
    return usesPerFileQuantity() ? Math.max(1, Number(state.separateQuantities[file.id]) || 1) : state.quantity;
  }

  function calculatePrice() {
    if (!globalComplete() || !readyFiles().length) return null;
    const lines = readyFiles().map(function (file) {
      const quantity = quantityFor(file);
      return { label: file.name, amount: printCostFor(file) * quantity, quantity: quantity };
    });
    let bindingCost = 0;
    if (state.grouping === "combined" && state.combinedBinding) bindingCost = bindingPrice(state.combinedBinding) * state.quantity;
    if (state.grouping === "separate") bindingCost = readyFiles().reduce(function (sum, file) { return sum + (bindingPrice(state.separateBindings[file.id]) * quantityFor(file)); }, 0);
    const printTotal = lines.reduce(function (sum, line) { return sum + line.amount; }, 0);
    return { lines: lines, printing: printTotal, binding: bindingCost, total: printTotal + bindingCost };
  }

  function groupingSummary() {
    if (!state.grouping) return t("notSelected");
    if (state.grouping === "combined") return t("oneBooklet") + (state.combinedBinding ? " • " + bindingLabel(state.combinedBinding) : "");
    const selected = readyFiles().filter(function (file) { return state.separateBindings[file.id]; }).length;
    return t("separatePrints") + " • " + selected + "/" + readyFiles().length;
  }

  function updateSummary() {
    const price = calculatePrice();
    const stepNames = { 1: "filesStep", 2: "printStep", 3: "bindingStep", 4: "reviewStep" };
    $("#summaryCurrentStep").textContent = t(stepNames[state.currentStep]);
    $("#summaryFiles").textContent = readyFiles().length ? t("fileCount", { count: readyFiles().length }) + " • " + t("pageCount", { count: totalPages() }) : "—";
    $("#summaryPrint").textContent = globalComplete() ? settingsSummary(state.globalSettings) + (readyFiles().some(function (file) { return file.override; }) ? " • " + t("mixedSettings") : "") : t("notSelected");
    $("#summaryBinding").textContent = groupingSummary();
    $("#summaryQuantity").textContent = usesPerFileQuantity() ? t("perFileQuantities") : state.quantity;
    const totalText = price ? formatMoney(price.total) : "—";
    el.total.textContent = totalText; el.mobileTotal.textContent = totalText; el.reviewTotal.textContent = totalText;
    $("#priceBreakdown").innerHTML = price ? price.lines.map(function (line) { return '<div class="price-line"><span>' + escapeHtml(line.label) + (usesPerFileQuantity() ? ' × ' + line.quantity : '') + '</span><strong>' + escapeHtml(formatMoney(line.amount)) + '</strong></div>'; }).join("") + '<div class="price-line"><span>' + escapeHtml(t("bindingCost")) + '</span><strong>' + escapeHtml(formatMoney(price.binding)) + '</strong></div>' : '<div class="price-line"><span>' + escapeHtml(t("priceCalculating")) + '</span></div>';
    const ctaKeys = { 1: "continueToPrint", 2: "confirmPrint", 3: "continueReview", 4: "addToCart" };
    [el.summaryCta, el.mobileCta].forEach(function (button) {
      $("span", button).textContent = t(ctaKeys[state.currentStep]);
      const icon = $("i", button);
      icon.className = state.currentStep === 4 ? "bi bi-cart-plus" : "bi bi-arrow-left";
    });
  }

  function renderReview() {
    const price = calculatePrice();
    const files = state.grouping === "combined" ? [{ name: t("oneBooklet"), pages: totalPages(), files: orderedFiles(), settings: readyFiles().some(function (file) { return file.override; }) ? t("mixedSettings") : settingsSummary(state.globalSettings), binding: state.combinedBinding, quantity: state.quantity }] : readyFiles().map(function (file) { return { name: file.name, pages: file.pages, files: [file], settings: settingsSummary(effectiveSettings(file)), binding: state.separateBindings[file.id], quantity: quantityFor(file) }; });
    el.reviewList.innerHTML = files.map(function (item) {
      const names = item.files.length > 1 ? item.files.map(function (file) { return file.name; }).join("، ") : item.name;
      return '<article class="review-card"><div class="review-card-header"><span class="review-file-icon"><i class="bi bi-files"></i></span><div><strong title="' + escapeHtml(names) + '">' + escapeHtml(item.name) + '</strong><small>' + escapeHtml(t("pageCount", { count: item.pages })) + '</small></div></div><div class="review-details"><div class="review-detail"><span>' + escapeHtml(t("printing")) + '</span><strong>' + escapeHtml(item.settings) + '</strong></div><div class="review-detail"><span>' + escapeHtml(t("binding")) + '</span><strong>' + escapeHtml(bindingLabel(item.binding)) + '</strong></div><div class="review-detail"><span>' + escapeHtml(t("files")) + '</span><strong>' + escapeHtml(names) + '</strong></div><div class="review-detail"><span>' + escapeHtml(t("quantity")) + '</span><strong>' + item.quantity + '</strong></div></div><button type="button" class="paper-button is-soft is-small review-edit" data-review-edit="3"><i class="bi bi-pencil"></i><span>' + escapeHtml(t("edit")) + '</span></button></article>';
    }).join("");
    el.reviewTotal.textContent = price ? formatMoney(price.total) : "—";
  }

  function renderAll() {
    renderFiles();
    renderGlobalSelections();
    renderCustomization();
    renderBindings();
    updateJourney();
    updateSummary();
    if (state.currentStep === 4) renderReview();
  }

  function addToCart() {
    if (!validateStep(3)) { goToStep(3); return; }
    const price = calculatePrice();
    const order = { id: "paper-" + Date.now(), createdAt: new Date().toISOString(), files: readyFiles().map(function (file) { return { name: file.name, size: file.size, pages: file.pages, settings: effectiveSettings(file), quantity: quantityFor(file) }; }), grouping: state.grouping, fileOrder: state.fileOrder.slice(), combinedBinding: state.combinedBinding, separateBindings: Object.assign({}, state.separateBindings), separateQuantities: Object.assign({}, state.separateQuantities), quantity: state.quantity, total: price ? price.total : 0 };
    try {
      const cart = JSON.parse(window.localStorage.getItem("palprints-paper-cart") || "[]");
      cart.push(order);
      window.localStorage.setItem("palprints-paper-cart", JSON.stringify(cart));
      const badge = $("#cartCount");
      if (badge) { badge.textContent = cart.length; badge.hidden = cart.length === 0; }
    } catch (error) { /* UI remains usable if storage is unavailable */ }
    showToast(t("orderAdded"));
    el.added = true;
    $("#addToCartButton span").textContent = t("orderAdded");
  }

  function currentAction() {
    if (state.currentStep === 4) addToCart();
    else completeAndGo(state.currentStep + 1);
  }

  el.choose.addEventListener("click", function () { if (document.body.dataset.authenticated === "true") el.input.click(); else showToast(t("signInRequired"), "error"); });
  el.addMore.addEventListener("click", function () { el.input.click(); });
  el.input.addEventListener("change", function () { addFiles(el.input.files); });
  ["dragenter", "dragover"].forEach(function (type) { el.zone.addEventListener(type, function (event) { event.preventDefault(); el.zone.classList.add("is-dragging"); }); });
  ["dragleave", "drop"].forEach(function (type) { el.zone.addEventListener(type, function (event) { event.preventDefault(); el.zone.classList.remove("is-dragging"); }); });
  el.zone.addEventListener("drop", function (event) { addFiles(event.dataTransfer.files); });

  el.fileList.addEventListener("click", function (event) {
    const button = event.target.closest("[data-file-action]");
    if (!button) return;
    const card = button.closest("[data-file-id]");
    const file = state.files.find(function (item) { return item.id === card.dataset.fileId; });
    if (!file) return;
    if (button.dataset.fileAction === "delete") removeFile(file.id);
    if (button.dataset.fileAction === "retry") {
      if (supportedExtensions.indexOf(file.ext) === -1 || file.size > 50 * 1024 * 1024) {
        showToast(t(file.error), "error");
      } else {
        processFile(file);
      }
    }
    if (button.dataset.fileAction === "preview") openPreview(file);
  });

  $("#globalOptions").addEventListener("click", function (event) {
    const button = event.target.closest("[data-value]");
    if (!button) return;
    const groupEl = button.closest("[data-option-group]");
    state.globalSettings[groupEl.dataset.optionGroup] = button.dataset.value;
    groupEl.classList.remove("has-error");
    if (state.completedStep >= 2) state.completedStep = 1;
    renderAll();
  });

  el.customization.addEventListener("click", function (event) {
    const button = event.target.closest("[data-custom-action]");
    if (!button) return;
    const id = button.closest("[data-file-id]").dataset.fileId;
    const file = state.files.find(function (item) { return item.id === id; });
    if (button.dataset.customAction === "edit") openOverride(id);
    if (button.dataset.customAction === "remove" && file) { file.override = null; if (state.completedStep >= 2) state.completedStep = 1; renderAll(); }
  });

  el.overrideEditor.addEventListener("click", function (event) {
    const option = event.target.closest("[data-override-group]");
    if (option) { state.overrideDraft[option.dataset.overrideGroup] = option.dataset.value; renderOverrideEditor(); return; }
    const action = event.target.closest("[data-override-action]");
    if (!action) return;
    if (action.dataset.overrideAction === "cancel") closeOverride();
    if (action.dataset.overrideAction === "save") {
      const file = state.files.find(function (item) { return item.id === state.overrideFileId; });
      if (file) file.override = Object.assign({}, state.overrideDraft);
      if (state.completedStep >= 2) state.completedStep = 1;
      closeOverride(); renderAll(); showToast(t("overrideSaved"));
    }
  });

  el.applyAll.addEventListener("click", function () {
    if (!window.confirm(t("applyAllConfirm"))) return;
    state.files.forEach(function (file) { file.override = null; });
    if (state.completedStep >= 2) state.completedStep = 1;
    renderAll(); showToast(t("overridesCleared"));
  });

  $("#groupingChoice").addEventListener("click", function (event) {
    const button = event.target.closest("[data-grouping]");
    if (!button) return;
    state.grouping = button.dataset.grouping;
    if (state.grouping === "combined" && !state.combinedBinding) state.combinedBinding = "none";
    if (state.completedStep >= 3) state.completedStep = 2;
    renderAll();
  });

  el.bindingGrid.addEventListener("click", function (event) {
    const button = event.target.closest("[data-binding]");
    if (!button || button.disabled) return;
    state.combinedBinding = button.dataset.binding;
    if (state.completedStep >= 3) state.completedStep = 2;
    renderAll();
  });

  el.separateList.addEventListener("change", function (event) {
    if (!event.target.matches("[data-separate-binding]")) return;
    state.separateBindings[event.target.dataset.separateBinding] = event.target.value || null;
    if (state.completedStep >= 3) state.completedStep = 2;
    renderAll();
  });

  el.separateList.addEventListener("click", function (event) {
    const button = event.target.closest("[data-file-quantity-action]");
    if (!button) return;
    const fileId = button.closest("[data-file-id]").dataset.fileId;
    const current = Math.max(1, Number(state.separateQuantities[fileId]) || 1);
    state.separateQuantities[fileId] = button.dataset.fileQuantityAction === "increase"
      ? Math.min(99, current + 1)
      : Math.max(1, current - 1);
    if (state.completedStep >= 3) state.completedStep = 2;
    renderAll();
  });

  el.reorderList.addEventListener("click", function (event) {
    const button = event.target.closest("[data-move]");
    if (!button) return;
    const id = button.closest("[data-file-id]").dataset.fileId;
    const index = state.fileOrder.indexOf(id);
    const target = button.dataset.move === "up" ? index - 1 : index + 1;
    if (target < 0 || target >= state.fileOrder.length) return;
    const temp = state.fileOrder[index]; state.fileOrder[index] = state.fileOrder[target]; state.fileOrder[target] = temp;
    if (state.completedStep >= 3) state.completedStep = 2;
    renderAll();
  });

  $("#decreaseQuantity").addEventListener("click", function () { state.quantity = Math.max(1, state.quantity - 1); if (state.completedStep >= 3) state.completedStep = 2; renderAll(); });
  $("#increaseQuantity").addEventListener("click", function () { state.quantity = Math.min(99, state.quantity + 1); if (state.completedStep >= 3) state.completedStep = 2; renderAll(); });
  $$('[data-next-step]').forEach(function (button) { button.addEventListener("click", function () { completeAndGo(Number(button.dataset.nextStep)); }); });
  $$('[data-go-step]').forEach(function (button) { button.addEventListener("click", function () { goToStep(Number(button.dataset.goStep)); }); });
  $$('[data-progress-step]').forEach(function (button) { button.addEventListener("click", function () { goToStep(Number(button.dataset.progressStep)); }); });
  $$('[data-stage-toggle]').forEach(function (button) { button.addEventListener("click", function () { goToStep(Number(button.dataset.stageToggle)); }); });
  el.summaryCta.addEventListener("click", currentAction);
  el.mobileCta.addEventListener("click", currentAction);
  $("#addToCartButton").addEventListener("click", addToCart);
  el.reviewList.addEventListener("click", function (event) { const button = event.target.closest("[data-review-edit]"); if (button) goToStep(Number(button.dataset.reviewEdit)); });

  $$('[data-close-dialog]').forEach(function (button) { button.addEventListener("click", function () { const dialog = document.getElementById(button.dataset.closeDialog); if (dialog) dialog.close(); }); });
  $("#openMobileSummary").addEventListener("click", function () {
    const price = calculatePrice();
    $("#mobileSummaryContent").innerHTML = '<div class="price-line"><span>' + escapeHtml(t("files")) + '</span><strong>' + escapeHtml($("#summaryFiles").textContent) + '</strong></div><div class="price-line"><span>' + escapeHtml(t("printing")) + '</span><strong>' + escapeHtml($("#summaryPrint").textContent) + '</strong></div><div class="price-line"><span>' + escapeHtml(t("binding")) + '</span><strong>' + escapeHtml(groupingSummary()) + '</strong></div><div class="price-line"><span>' + escapeHtml(t("printingTotal")) + '</span><strong>' + escapeHtml(price ? formatMoney(price.total) : "—") + '</strong></div>';
    if (typeof el.mobileSummary.showModal === "function") el.mobileSummary.showModal();
  });

  try {
    const existingCart = JSON.parse(window.localStorage.getItem("palprints-paper-cart") || "[]");
    const badge = $("#cartCount");
    if (badge) { badge.textContent = existingCart.length; badge.hidden = existingCart.length === 0; }
  } catch (error) { /* cart badge stays at its default count */ }

  /* حالة معاينة اختيارية للاختبارات البصرية فقط، ولا تعمل في المسار العادي. */
  const demoStage = new URLSearchParams(window.location.search).get("demo");
  if (demoStage) {
    state.files = [
      { id: "demo-1", file: new File(["%PDF-demo"], "ملخص_الجامعة.pdf", { type: "application/pdf" }), name: "ملخص_الجامعة.pdf", size: 2480000, ext: "pdf", pages: 36, status: "ready", progress: 100, error: null, override: null },
      { id: "demo-2", file: new File(["demo"], "مخططات_المشروع.docx", { type: "application/vnd.openxmlformats-officedocument.wordprocessingml.document" }), name: "مخططات_المشروع.docx", size: 1160000, ext: "docx", pages: 12, status: "ready", progress: 100, error: null, override: { size: "A4", paper: "thick", color: "color", sides: "single", layout: "1" } }
    ];
    state.fileOrder = ["demo-1", "demo-2"];
    state.globalSettings = { size: "A4", paper: "standard", color: "bw", sides: "double", layout: "1" };
    if (demoStage === "binding" || demoStage === "review") {
      state.grouping = "separate";
      state.separateBindings = { "demo-1": "spiral", "demo-2": "clearCover" };
      state.separateQuantities = { "demo-1": 2, "demo-2": 1 };
      state.completedStep = demoStage === "review" ? 3 : 2;
      state.currentStep = demoStage === "review" ? 4 : 3;
    } else {
      state.completedStep = 1;
      state.currentStep = 2;
    }
  }

  renderAll();
});
