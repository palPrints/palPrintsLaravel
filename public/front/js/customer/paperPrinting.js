"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const Core = window.PalProfile;

  if (!Core) {
    console.error("profile-core.js must load before paperPrinting.js");
    return;
  }

  const dictionary = {
    ar: {
      documentTitle: "طباعة الورق | PalPrints", skipToContent: "تخطي إلى المحتوى", sidebarLabel: "القائمة الجانبية للعميل", customerNavLabel: "روابط حساب العميل", goHome: "الانتقال إلى الصفحة الرئيسية", openSidebar: "فتح القائمة الجانبية", closeSidebar: "إغلاق القائمة الجانبية", changeTheme: "تغيير المظهر", changeLanguage: "تغيير اللغة", shoppingCart: "سلة المشتريات", breadcrumbLabel: "مسار التنقل", close: "إغلاق",
      home: "الرئيسية", profile: "الملف الشخصي", myOrders: "طلباتي", favorites: "المفضلة", savedCustomizations: "تخصيصاتي المحفوظة", shippingAddresses: "عناوين الشحن", notifications: "الإشعارات", settings: "الإعدادات والأمان", support: "مركز المساعدة", logout: "تسجيل الخروج", logoutConfirm: "هل تريد تسجيل الخروج؟",
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
      filesRequired: "أضف ملفًا جاهزًا واحدًا على الأقل.", resolveFiles: "عالج الملفات التي تحتاج إلى إجراء قبل المتابعة.", printRequired: "اختر جميع خصائص الطباعة المطلوبة.", groupingRequired: "اختر دمج الملفات أو فصلها.", bindingRequired: "حدد نوع التغليف المطلوب.", signInRequired: "يجب تسجيل الدخول قبل رفع الملفات.", unsupportedFile: "ارفع ملف PDF فقط.", fileTooLarge: "حجم الملف يتجاوز 50MB.", emptyFile: "الملف فارغ (0 بايت)، احذفه وارفع ملفًا سليمًا.", tooManyFiles: "يمكن رفع 10 ملفات كحد أقصى.", tooManyPages: "سيؤدي هذا الملف إلى تجاوز حد 500 صفحة.", duplicateFile: "هذا الملف مضاف بالفعل.", deleteConfirm: "سيؤثر حذف هذا الملف على الطلب والسعر. هل تريد المتابعة؟", fileRemoved: "تم حذف الملف.", undo: "استعادة", fileRestored: "تمت استعادة الملف.", settingsConfirmed: "تم حفظ خصائص الطباعة.", bindingConfirmed: "تم حفظ التجميع والتغليف.", orderAdded: "تمت إضافة طلب الطباعة إلى السلة.", addingToCart: "جارٍ إضافة الطلب...", addToCartFailed: "تعذّرت إضافة الطلب إلى السلة، حاول مجددًا.", applyAllConfirm: "سيتم حذف تخصيصات الملفات الفردية. هل تريد المتابعة؟", overridesCleared: "تم تطبيق الإعداد العام على جميع الملفات.", addedNeedsReview: "تمت إضافة الملف. راجع الخصائص قبل المتابعة.", overrideFirst: "أكمل الإعداد العام أولًا.", overrideSaved: "تم حفظ خصائص الملف.", priceCalculating: "يُحسب بعد إكمال الخصائص", printCost: "تكلفة الطباعة", bindingCost: "تكلفة التغليف", orderOutput: "مخرج الطلب", oneBooklet: "ملزمة واحدة", separatePrints: "مطبوعات منفصلة", mixedSettings: "خصائص مختلفة", notSelected: "لم تحدد بعد", oneFile: "ملف واحد", fileCount: "{count} ملفات", pageCount: "{count} صفحة", overridesCount: "{count} ملف بإعداد مختلف", stageReady: "جاهزة",
      A4: "A4", A5: "A5", A3: "A3", standard: "عادي 80 جم", thick: "فاخر 120 جم", coated: "مصقول 150 جم", bw: "أبيض وأسود", color: "ملون", single: "وجه واحد", double: "وجهين", layout1: "صفحة واحدة", layout2: "صفحتان", layout4: "4 صفحات", layout6: "6 صفحات", layout9: "9 صفحات",
      paperUnavailable: "الطباعة على الورق غير متاحة حاليًا لأنه لا توجد مطبعة تقدّم أنواع الورق وألوان الطباعة. جرّب لاحقًا.",
      priceFailed: "تعذّر حساب السعر الآن. حاول مرة أخرى.", from: "من"
    },
  };

  Core.init({
    dictionary: dictionary,
    sidebar: { desktopInitial: "open", persist: false }
  });

  /* The current storefront design system is intentionally light-only. */
  document.documentElement.setAttribute("data-bs-theme", "light");

  const state = {
    currentStep: 1,
    completedStep: 0,
    files: [],
    globalSettings: { size: null, paper: null, color: null, sides: null, layout: null },
    grouping: null,
    autoGrouping: false,
    collate: true,
    fileOrder: [],
    combinedBinding: "none",
    separateBindings: {},
    separateQuantities: {},
    quantity: 1,
    quote: null,
    overrideFileId: null,
    overrideDraft: null
  };

  const supportedExtensions = ["pdf"];
  const settingGroups = ["size", "paper", "color", "sides", "layout"];
  const dbPaper = (window.palPrintsCustomerAssets && window.palPrintsCustomerAssets.paperPrinting) || {};
  const bindingIcons = { spiral: "bi-journal", clearCover: "bi-file-earmark", glue: "bi-book", sideStaple: "bi-distribute-vertical", cornerStaple: "bi-paperclip", lamination: "bi-layers" };
  /* Only the bindings that at least one print shop sells, each with the lowest price among them (the price of the job itself
     comes from the server); "no binding" is always free. */
  /* A binding the admin added later has no picture of its own: it gets a general one. */
  const bindings = (dbPaper.bindings || []).map(function (binding) {
    return { id: binding.id, icon: bindingIcons[binding.id] || "bi-journal-bookmark", price: Number(binding.from) || 0 };
  }).concat([{ id: "none", icon: "bi-slash-circle", price: 0 }]);

  const $ = function (selector, scope) { return (scope || document).querySelector(selector); };
  const $$ = function (selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); };
  const el = {
    input: $("#paperFileInput"), choose: $("#chooseFilesButton"), addMore: $("#addMoreFilesButton"), zone: $("#uploadZone"), fileSection: $("#fileSection"), fileList: $("#paperFileList"), fileCounter: $("#fileCounter"), customization: $("#customizationList"), overrideEditor: $("#overrideEditor"), applyAll: $("#applyAllButton"), overrideCount: $("#overrideCountText"), bindingWorkspace: $("#bindingWorkspace"), reorderPanel: $("#reorderPanel"), reorderList: $("#reorderList"), combinedPanel: $("#combinedBindingPanel"), bindingGrid: $("#bindingGrid"), separatePanel: $("#separateBindingPanel"), separateList: $("#separateBindingList"), quantityPanel: $("#orderQuantityPanel"), quantity: $("#quantityOutput"), configurator: $(".paper-configurator-layout"), orderSummary: $(".order-summary"), mobileOrderBar: $("#mobileOrderBar"), summaryCta: $("#summaryCta"), mobileCta: $("#mobileCta"), total: $("#summaryTotal"), mobileTotal: $("#mobileTotal"), reviewTotal: $("#reviewTotal"), reviewList: $("#reviewList"), toast: $("#paperToast"), toastMessage: $("#paperToastMessage"), preview: $("#previewDialog"), mobileSummary: $("#mobileSummaryDialog")
  };

  let toastTimer = null;

  // The paper names the admin keeps (a type added later has no entry in the translation list).
  const paperNames = {};
  ((dbPaper.options && dbPaper.options.paperTypes) || []).forEach(function (type) { paperNames[type.key] = type.label; });
  (dbPaper.bindings || []).forEach(function (binding) { if (binding.label) paperNames[binding.id] = binding.label; });

  function t(key, vars) {
    let value = paperNames[key] || Core.translate(key) || key;
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
    const locale = Core.getLanguage() === "ar" ? "ar-SA" : "en-SA";
    return new Intl.NumberFormat(locale, { style: "currency", currency: "ILS", minimumFractionDigits: 2 }).format(amount || 0);
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
  function totalPages() { return readyFiles().reduce(function (sum, file) { return sum + (printedPages(file) || file.pages); }, 0); }
  function globalComplete() { return settingGroups.every(function (group) { return Boolean(state.globalSettings[group]); }); }
  // A booklet is printed on both sides, two pages a side: it decides those two choices for its file.
  function effectiveSettings(file) {
    const settings = Object.assign({}, state.globalSettings, file.override || {});
    if (file.booklet) { settings.sides = "double"; settings.layout = "2"; }
    return settings;
  }

  /* "1-9, 12" -> how many different pages it holds, or null when it is not a valid range inside a file of `pages` pages. */
  function parseRange(text, pages) {
    const marks = { "٠": "0", "١": "1", "٢": "2", "٣": "3", "٤": "4", "٥": "5", "٦": "6", "٧": "7", "٨": "8", "٩": "9", "،": ",", "؛": ",", "–": "-", "—": "-" };
    const clean = String(text).replace(/[٠-٩،؛–—]/g, function (char) { return marks[char]; });
    const seen = {};
    const tokens = clean.split(",");
    for (let index = 0; index < tokens.length; index++) {
      const match = /^(\d{1,4})(?:\s*-\s*(\d{1,4}))?$/.exec(tokens[index].trim());
      if (!match) return null;
      const from = Number(match[1]);
      const to = Number(match[2] || match[1]);
      if (from < 1 || to < from || to > pages) return null;
      for (let page = from; page <= to; page++) seen[page] = true;
    }
    return { count: Object.keys(seen).length, pages: Object.keys(seen).map(Number).sort(function (a, b) { return a - b; }) };
  }
  /* The pages of a file that are printed: all of them, or those in its range; null while the range is wrong. */
  function printedPages(file) {
    let pages;
    if (!file.range || !file.range.trim()) {
      pages = [];
      for (let page = 1; page <= file.pages; page++) pages.push(page);
    } else {
      const parsed = parseRange(file.range, file.pages);
      if (!parsed) return null;
      pages = parsed.pages;
    }
    if (file.subset === "odd" || file.subset === "even") {
      pages = pages.filter(function (page) { return (page % 2 === 1) === (file.subset === "odd"); });
    }
    return pages.length ? pages.length : null;
  }

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
      window.setTimeout(function () { window.location.href = "/login"; }, 1200);
      return;
    }
    const incoming = Array.prototype.slice.call(fileList || []);
    incoming.forEach(function (file) {
      if (state.files.length >= 10) { showToast(t("tooManyFiles"), "error"); return; }
      const ext = (file.name.split(".").pop() || "").toLowerCase();
      const duplicate = state.files.some(function (item) { return item.name === file.name && item.size === file.size; });
      const item = { id: "file-" + Date.now() + "-" + Math.random().toString(36).slice(2, 7), file: file, name: file.name, size: file.size, ext: ext, pages: 0, status: "queued", progress: 0, error: null, override: null, range: "", subset: "all", reverse: false, booklet: false, flip: "long" };
      if (duplicate) { showToast(t("duplicateFile"), "error"); return; }
      if (supportedExtensions.indexOf(ext) === -1) { item.status = "error"; item.error = "unsupportedFile"; state.files.push(item); return; }
      // An empty file (0 bytes) is not a PDF: the server would refuse the whole order because of it.
      if (file.size === 0) { item.status = "error"; item.error = "emptyFile"; state.files.push(item); return; }
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
    if (!impactful) { performRemove(id); return; }
    PalAlert.confirm({
      title: "حذف الملف",
      text: t("deleteConfirm"),
      confirmText: "نعم، احذف",
    }).then(function (ok) { if (ok) performRemove(id); });
  }

  function performRemove(id) {
    const index = state.files.findIndex(function (file) { return file.id === id; });
    if (index < 0) return;
    const file = state.files[index];
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

  /* A size, a paper type and a colour must go together at one print shop at least (the colour a shop can print depends on its
     paper). A choice that does not go with the ones already made is hidden. */
  const printTogether = ["size", "paper", "color"];
  function comboAllows(group, value) {
    const combos = dbPaper.options && dbPaper.options.combinations;
    if (!combos) return true;
    const others = printTogether.filter(function (other) { return other !== group && state.globalSettings[other]; });
    return combos.some(function (combo) { return combo[group] === value && others.every(function (other) { return combo[other] === state.globalSettings[other]; }); });
  }
  function pruneSelections() {
    [0, 1].forEach(function () {
      printTogether.forEach(function (group) {
        if (state.globalSettings[group] && !comboAllows(group, state.globalSettings[group])) state.globalSettings[group] = null;
      });
    });
  }

  function renderGlobalSelections() {
    pruneSelections();
    $$("#globalOptions [data-option-group]").forEach(function (groupEl) {
      const group = groupEl.dataset.optionGroup;
      const offered = [];
      $$('[data-value]', groupEl).forEach(function (button) {
        const sold = (optionValues[group] || []).indexOf(button.dataset.value) !== -1 && (printTogether.indexOf(group) === -1 || comboAllows(group, button.dataset.value));
        button.style.display = sold ? "" : "none";
        button.classList.toggle("is-selected", sold && state.globalSettings[group] === button.dataset.value);
        if (sold) offered.push(button);
      });

      // Only one thing is sold: it looks like any other choice, but it is already selected for the customer.
      if (offered.length === 1) {
        state.globalSettings[group] = offered[0].dataset.value;
        offered[0].classList.add("is-selected");
      }
      // The only choice takes the whole row instead of one column with empty space beside it.
      const grid = $(".choice-grid", groupEl);
      if (grid) grid.classList.toggle("is-one", offered.length === 1);
    });
  }

  function settingsSummary(settings) {
    if (!settingGroups.every(function (key) { return Boolean(settings[key]); })) return t("notSelected");
    return [t(settings.size), t(settings.paper), t(settings.color), t(settings.sides), t("layout" + settings.layout)].join(" • ");
  }

  /* What a PDF's own print dialog also offers, per file: which pages, booklet, paper direction and how a double-sided sheet is turned. */
  function fileExtrasMarkup(file) {
    const hasRange = Boolean(file.range && file.range.trim()) || (file.subset && file.subset !== "all");
    const wrong = Boolean(file.range && file.range.trim()) && !parseRange(file.range, file.pages);
    // A booklet is turned the way a booklet must be, so the flip is asked only for ordinary double-sided printing.
    const double = effectiveSettings(file).sides === "double" && !file.booklet;
    const choose = function (name, current, choices) {
      return '<select data-extra="' + name + '">' + choices.map(function (choice) { return '<option value="' + choice[0] + '"' + (current === choice[0] ? ' selected' : '') + '>' + choice[1] + '</option>'; }).join("") + '</select>';
    };
    const noPages = !wrong && printedPages(file) === null;
    const rangeNote = noPages
      ? '<small class="file-extra-error"><i class="bi bi-exclamation-circle-fill"></i> لا توجد صفحات ' + (file.subset === "odd" ? "فردية" : "زوجية") + ' ضمن هذا النطاق</small>'
      : wrong
      ? '<small class="file-extra-error"><i class="bi bi-exclamation-circle-fill"></i> اكتبي أرقامًا من 1 إلى ' + file.pages + '، مثل: 1-9, 12</small>'
      : '<small>' + (hasRange ? 'سيتم طباعة ' + printedPages(file) + ' صفحة من ' + file.pages : 'اتركيه فاضيًا لطباعة كل الملف (' + file.pages + ' صفحة)') + '</small>';
    return '<div class="file-extras">' +
      '<div class="file-extras-title"><i class="bi bi-sliders2" aria-hidden="true"></i><span>خيارات إضافية لهذا الملف</span></div>' +
      '<div class="file-extras-grid">' +
        '<label class="file-extra is-wide' + (wrong ? ' has-error' : '') + '"><span>أي صفحات تريد طباعتها؟</span><input type="text" inputmode="numeric" dir="ltr" data-extra="range" value="' + escapeHtml(file.range || "") + '" placeholder="1-9, 12" autocomplete="off">' + rangeNote + '</label>' +
        '<label class="file-extra"><span>أي صفحات من هذا النطاق؟</span>' + choose("subset", file.subset || "all", [["all", "كلها"], ["odd", "الفردية فقط (1، 3، 5...)"], ["even", "الزوجية فقط (2، 4، 6...)"]]) + '</label>' +
        '<label class="file-extra is-check' + (file.reverse ? ' is-on' : '') + '"><input type="checkbox" data-extra="reverse"' + (file.reverse ? ' checked' : '') + '><span><strong>ترتيب معكوس</strong><small>من آخر صفحة إلى الأولى</small></span></label>' +
        '<label class="file-extra"><span>اتجاه الورقة</span>' + choose("orientation", file.orientation || "auto", [["auto", "تلقائي (حسب الملف)"], ["portrait", "طولي"], ["landscape", "عرضي"]]) + '</label>' +
        '<label class="file-extra"><span>اتجاه القراءة (التجليد)</span>' + choose("direction", file.direction || "auto", [["auto", "حسب الملف"], ["rtl", "من اليمين (عربي)"], ["ltr", "من اليسار (إنجليزي)"]]) + '</label>' +
        (double ? '<label class="file-extra"><span>قلب الورقة</span>' + choose("flip", file.flip === "short" ? "short" : "long", [["long", "من الطرف الطويل (مثل الكتاب)"], ["short", "من الطرف القصير (مثل التقويم)"]]) + '</label>' : '') +
        '<label class="file-extra is-check' + (file.booklet ? ' is-on' : '') + '"><input type="checkbox" data-extra="booklet"' + (file.booklet ? ' checked' : '') + '><span><strong>كتيّب</strong><small>' + (file.booklet ? 'وجهين، وصفحتان في كل وجه' : 'تُرتّب الصفحات لتُطوى كتيّبًا') + '</small></span></label>' +
      '</div>' +
    '</div>';
  }

  function renderCustomization() {
    const ready = readyFiles();
    const count = ready.filter(function (file) { return Boolean(file.override); }).length;
    el.overrideCount.textContent = count ? t("overridesCount", { count: count }) : t("allFilesUseGeneral");
    el.applyAll.hidden = count === 0;
    el.customization.innerHTML = ready.map(function (file) {
      const icon = fileIcon(file);
      return '<div class="customization-item" data-file-id="' + file.id + '"><div class="customization-head"><span class="file-type-icon ' + icon.type + '"><i class="bi ' + icon.icon + '"></i></span><div class="file-info"><strong>' + escapeHtml(file.name) + '</strong><small>' + escapeHtml(file.override ? settingsSummary(effectiveSettings(file)) : t("allFilesUseGeneral")) + '</small></div>' + (file.override ? '<span class="override-badge"><i class="bi bi-stars"></i>' + escapeHtml(t("customized")) + '</span><button type="button" class="icon-button is-danger" data-custom-action="remove" title="' + escapeHtml(t("removeOverride")) + '"><i class="bi bi-arrow-counterclockwise"></i></button>' : '') + '<button type="button" class="paper-button is-soft is-small" data-custom-action="edit"><i class="bi bi-sliders"></i><span>' + escapeHtml(t("customize")) + '</span></button></div>' + fileExtrasMarkup(file) + '</div>';
    }).join("");
    if (state.overrideFileId && !state.files.some(function (file) { return file.id === state.overrideFileId; })) closeOverride();
  }

  const defaultOptionValues = { size: [], paper: [], color: [], sides: ["single", "double"], layout: ["1", "2", "4", "6", "9"] };
  const optionValues = Object.assign({}, defaultOptionValues, (dbPaper.options && dbPaper.options.availableValues) || {});
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
      return '<fieldset class="option-group"><legend><span>' + escapeHtml(t(optionTitles[group])) + '</span></legend><div class="choice-grid ' + (optionValues[group].length === 1 ? 'is-one' : optionValues[group].length === 2 ? 'is-two' : 'is-three') + '">' + optionValues[group].map(function (value) {
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
    // One file has nothing to merge or split, so "combine / separate" is not asked: it is picked for the customer.
    const single = ready.length === 1;
    if (single) {
      if (state.grouping !== "combined") { state.grouping = "combined"; state.autoGrouping = true; }
      if (!state.combinedBinding) state.combinedBinding = "none";
    } else if (state.autoGrouping) { state.grouping = null; state.autoGrouping = false; }
    $("#groupingChoice").hidden = single;
    // No print shop sells any binding: "no binding" is the only choice, so the binding choice is not shown either.
    const noBinding = bindings.length === 1;
    let bindingNote = $("#bindingUnavailableNote");
    if (!bindingNote) { bindingNote = document.createElement("p"); bindingNote.id = "bindingUnavailableNote"; bindingNote.className = "option-fixed"; bindingNote.innerHTML = '<i class="bi bi-info-circle-fill" aria-hidden="true"></i><span>التغليف: غير متوفر حاليًا، ستصلك المطبوعات بدون تغليف.</span>'; el.combinedPanel.before(bindingNote); }
    bindingNote.hidden = !noBinding || !state.grouping;
    const usesPerFileQuantities = state.grouping === "separate" && ready.length > 1;
    $$("[data-grouping]").forEach(function (button) { button.classList.toggle("is-selected", state.grouping === button.dataset.grouping); });
    el.bindingWorkspace.hidden = !state.grouping;
    el.reorderPanel.hidden = state.grouping !== "combined" || single;
    el.combinedPanel.hidden = state.grouping !== "combined" || noBinding;
    el.separatePanel.hidden = state.grouping !== "separate";
    el.quantityPanel.hidden = !state.grouping || usesPerFileQuantities;
    el.quantity.textContent = state.quantity;
    // How the copies are put together is asked only when there is more than one copy.
    const copies = usesPerFileQuantities ? Math.max.apply(null, ready.map(function (file) { return Number(state.separateQuantities[file.id]) || 1; })) : state.quantity;
    const collatePanel = $("#collatePanel");
    collatePanel.hidden = !state.grouping || copies < 2;
    $$("[data-collate]", collatePanel).forEach(function (button) { button.classList.toggle("is-selected", (button.dataset.collate === "true") === state.collate); });
    if (!state.grouping) return;

    if (state.grouping === "combined") {
      const files = orderedFiles();
      el.reorderList.innerHTML = files.map(function (file, index) {
        return '<div class="reorder-item" data-file-id="' + file.id + '"><span class="reorder-index">' + (index + 1) + '</span><i class="bi bi-grip-vertical" aria-hidden="true"></i><strong>' + escapeHtml(file.name) + '</strong><div class="reorder-actions"><button type="button" class="icon-button" data-move="up" title="' + escapeHtml(t("moveUp")) + '" ' + (index === 0 ? 'disabled' : '') + '><i class="bi bi-arrow-up"></i></button><button type="button" class="icon-button" data-move="down" title="' + escapeHtml(t("moveDown")) + '" ' + (index === files.length - 1 ? 'disabled' : '') + '><i class="bi bi-arrow-down"></i></button></div></div>';
      }).join("");
      el.bindingGrid.innerHTML = bindings.map(function (binding) {
        const availability = bindingAvailability(binding.id, files);
        if (!availability.ok && state.combinedBinding === binding.id) state.combinedBinding = null;
        return '<button type="button" class="choice-card binding-card ' + (state.combinedBinding === binding.id ? 'is-selected' : '') + '" data-binding="' + binding.id + '" ' + (!availability.ok ? 'disabled' : '') + '><i class="bi ' + binding.icon + '"></i><strong>' + escapeHtml(t(binding.id)) + '</strong><span class="binding-price">' + escapeHtml(binding.price ? t("from") + " " + formatMoney(binding.price) : t("free")) + '</span>' + (!availability.ok ? '<small class="binding-reason">' + escapeHtml(t(availability.reason)) + '</small>' : '') + '</button>';
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
          const suffix = availability.ok ? (binding.price ? " — " + t("from") + " " + formatMoney(binding.price) : " — " + t("free")) : " — " + t(availability.reason);
          const optionLabel = binding.id === "none" ? t("noBindingFree") : t(binding.id) + suffix;
          return '<option value="' + binding.id + '" ' + (state.separateBindings[file.id] === binding.id ? 'selected' : '') + ' ' + (!availability.ok ? 'disabled' : '') + '>' + escapeHtml(optionLabel) + '</option>';
        }).join("");
        const selectedBinding = state.separateBindings[file.id];
        const bindingControl = noBinding ? '<small>' + escapeHtml(settingsSummary(effectiveSettings(file))) + '</small>' : '<label class="binding-select-wrap"><span class="binding-select-title"><i class="bi bi-journal-bookmark" aria-hidden="true"></i>' + escapeHtml(t("chooseBinding")) + '</span><select class="has-selection" data-separate-binding="' + file.id + '" aria-label="' + escapeHtml(t("binding")) + '">' + options + '</select><small>' + escapeHtml(settingsSummary(effectiveSettings(file))) + '</small></label>';
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
    if (step === 2 && dbPaper.available === false) message = t("paperUnavailable");
    else if (step === 2 && !globalComplete()) {
      message = t("printRequired");
      $$("#globalOptions [data-option-group]").forEach(function (groupEl) { groupEl.classList.toggle("has-error", !state.globalSettings[groupEl.dataset.optionGroup]); });
    }
    if (step === 2 && !message) {
      const badRange = readyFiles().find(function (file) { return printedPages(file) === null; });
      if (badRange) {
        const rangeIsWrong = Boolean(badRange.range && badRange.range.trim()) && !parseRange(badRange.range, badRange.pages);
        message = rangeIsWrong
          ? "نطاق الصفحات غير صحيح في «" + badRange.name + "». اكتبيه ضمن صفحات الملف (من 1 إلى " + badRange.pages + ")، مثل: 1-9, 12."
          : "لا توجد صفحات " + (badRange.subset === "odd" ? "فردية" : "زوجية") + " ضمن النطاق في «" + badRange.name + "». غيّري النطاق أو اختاري «كلها».";
      }
    }
    if (step === 2 && !message && state.quote && state.quote.status === "error" && state.quote.key === JSON.stringify(currentJob())) message = state.quote.message;
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

  function usesPerFileQuantity() {
    return state.grouping === "separate" && readyFiles().length > 1;
  }

  function quantityFor(file) {
    return usesPerFileQuantity() ? Math.max(1, Number(state.separateQuantities[file.id]) || 1) : state.quantity;
  }

  /* The job as the server prices it: what the customer chose, file by file. Null while the choices are not complete. */
  function currentJob() {
    if (!globalComplete() || !readyFiles().length) return null;
    if (readyFiles().some(function (file) { return printedPages(file) === null; })) return null;
    const separate = state.grouping === "separate";
    return {
      grouping: separate ? "separate" : "combined",
      quantity: state.quantity,
      combined_binding: separate ? null : (state.combinedBinding || "none"),
      collate: state.collate,
      files: readyFiles().map(function (file) {
        const settings = effectiveSettings(file);
        return {
          pages: file.pages, range: file.range ? file.range.trim() : "", subset: file.subset || "all", reverse: Boolean(file.reverse), booklet: Boolean(file.booklet), flip: file.flip || "long", orientation: file.orientation || "auto", direction: file.direction || "auto", size: settings.size, paper: settings.paper, color: settings.color, sides: settings.sides, layout: Number(settings.layout),
          binding: separate ? (state.separateBindings[file.id] || "none") : null, quantity: quantityFor(file)
        };
      })
    };
  }

  function firstError(data) {
    const errors = data && data.errors ? Object.values(data.errors)[0] : null;
    return (errors && errors[0]) || (data && data.message) || t("priceFailed");
  }

  /* There is no price list in this page. Whenever the choices change the server prices the job with the prices of the
     print shop that would make it, and the page shows what comes back. */
  let quoteTimer = null;
  function ensureQuote() {
    const job = currentJob();
    if (!job) { state.quote = null; return; }
    const key = JSON.stringify(job);
    if (state.quote && state.quote.key === key) return;
    state.quote = { key: key, status: "loading" };
    window.clearTimeout(quoteTimer);
    quoteTimer = window.setTimeout(function () { fetchQuote(job, key); }, 250);
  }

  function fetchQuote(job, key) {
    const settle = function (quote) {
      if (!state.quote || state.quote.key !== key) return;
      state.quote = Object.assign({ key: key }, quote);
      updateSummary();
      if (state.currentStep === 4) renderReview();
    };
    fetch(window.palPrintsCustomerAssets.cartPaperQuoteUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json", "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
      body: JSON.stringify({ job: job }),
      credentials: "same-origin"
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        settle(response.ok ? { status: "ready", data: data } : { status: "error", message: firstError(data) });
      });
    }).catch(function () { settle({ status: "error", message: t("priceFailed") }); });
  }

  function calculatePrice() {
    const job = currentJob();
    const quote = state.quote;
    if (!job || !quote || quote.status !== "ready" || quote.key !== JSON.stringify(job)) return null;
    const lines = readyFiles().map(function (file, index) {
      const line = quote.data.lines[index] || {};
      return { label: file.name, amount: line.amount || 0, quantity: line.quantity || 1 };
    });
    return { lines: lines, printing: quote.data.printing, binding: quote.data.binding, total: quote.data.total };
  }
  function groupingSummary() {
    if (!state.grouping) return t("notSelected");
    if (state.grouping === "combined") return t("oneBooklet") + (state.combinedBinding ? " • " + bindingLabel(state.combinedBinding) : "");
    const selected = readyFiles().filter(function (file) { return state.separateBindings[file.id]; }).length;
    return t("separatePrints") + " • " + selected + "/" + readyFiles().length;
  }

  function updateSummary() {
    ensureQuote();
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
    if (!price && state.quote && state.quote.status === "error" && state.quote.message) $("#priceBreakdown").textContent = state.quote.message;
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
    const job = currentJob();
    const files = readyFiles();
    if (!files.length || !job) { showToast(t("filesRequired"), "error"); goToStep(1); return; }

    const button = document.getElementById("addToCartButton");
    const buttonLabel = button ? $("span", button) : null;
    const originalLabel = buttonLabel ? buttonLabel.textContent : "";
    if (button) button.disabled = true;
    if (buttonLabel) buttonLabel.textContent = t("addingToCart");

    const formData = new FormData();
    formData.append("job", JSON.stringify(job));
    formData.append("settings[paper_size]", state.globalSettings.size || "A4");
    formData.append("settings[paper_type]", state.globalSettings.paper || "standard");
    formData.append("settings[color_mode]", state.globalSettings.color || "bw");
    formData.append("settings[sides]", state.globalSettings.sides || "single");
    formData.append("settings[layout]", state.globalSettings.layout || "1");
    formData.append("settings[grouping]", state.grouping || "");
    formData.append("settings[binding]", state.grouping === "combined"
      ? bindingLabel(state.combinedBinding)
      : Object.values(state.separateBindings).map(bindingLabel).join("، "));
    formData.append("settings[quantity]", state.quantity);
    formData.append("settings[file_count]", files.length);
    formData.append("settings[page_count]", totalPages());
    files.forEach(function (file) { formData.append("files[]", file.file, file.name); });

    fetch(window.palPrintsCustomerAssets.cartPaperUrl, {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        "X-Requested-With": "XMLHttpRequest"
      },
      body: formData,
      credentials: "same-origin"
    }).then(function (response) {
      if (response.ok) {
        window.location.href = window.palPrintsCustomerAssets.basketUrl;
        return;
      }
      if (response.status === 401 || response.status === 419) {
        showToast(t("signInRequired"), "error");
        window.setTimeout(function () { window.location.href = "/login"; }, 1200);
        return;
      }
      if (response.status === 422) return response.json().then(function (data) { throw new Error(firstError(data)); });
      throw new Error("add-to-cart-failed");
    }).catch(function (failure) {
      showToast(failure && failure.message && failure.message !== "add-to-cart-failed" ? failure.message : t("addToCartFailed"), "error");
      if (button) button.disabled = false;
      if (buttonLabel) buttonLabel.textContent = originalLabel;
    });
  }

  function currentAction() {
    if (state.currentStep === 4) addToCart();
    else completeAndGo(state.currentStep + 1);
  }

  el.choose.addEventListener("click", function () { if (document.body.dataset.authenticated === "true") el.input.click(); else { showToast(t("signInRequired"), "error"); window.setTimeout(function () { window.location.href = "/login"; }, 1200); } });
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

  // The page range, the booklet and the flip are saved when the field is left (change), so typing is not interrupted.
  el.customization.addEventListener("change", function (event) {
    const field = event.target.closest("[data-extra]");
    if (!field) return;
    const file = state.files.find(function (item) { return item.id === field.closest("[data-file-id]").dataset.fileId; });
    if (!file) return;
    if (field.dataset.extra === "range") file.range = field.value.trim();
    if (field.dataset.extra === "booklet") file.booklet = field.checked;
    if (field.dataset.extra === "subset") file.subset = field.value;
    if (field.dataset.extra === "reverse") file.reverse = field.checked;
    if (field.dataset.extra === "flip") file.flip = field.value;
    if (field.dataset.extra === "orientation") file.orientation = field.value;
    if (field.dataset.extra === "direction") file.direction = field.value;
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
    PalAlert.confirm({
      title: "تطبيق الإعداد على كل الملفات",
      text: t("applyAllConfirm"),
      confirmText: "نعم، طبّق",
    }).then(function (ok) {
      if (!ok) return;
      state.files.forEach(function (file) { file.override = null; });
      if (state.completedStep >= 2) state.completedStep = 1;
      renderAll(); showToast(t("overridesCleared"));
    });
  });

  $("#groupingChoice").addEventListener("click", function (event) {
    const button = event.target.closest("[data-grouping]");
    if (!button) return;
    state.grouping = button.dataset.grouping;
    if (state.grouping === "combined" && !state.combinedBinding) state.combinedBinding = "none";
    if (state.completedStep >= 3) state.completedStep = 2;
    renderAll();
  });

  $("#collatePanel").addEventListener("click", function (event) {
    const button = event.target.closest("[data-collate]");
    if (!button) return;
    state.collate = button.dataset.collate === "true";
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

  Core.onLanguageChange(function () { document.title = t("documentTitle"); renderAll(); });

  try {
    const existingCart = JSON.parse(window.localStorage.getItem("palprints-paper-cart") || "[]");
    const badge = $("#cartBadge"); if (badge) badge.textContent = existingCart.length;
  } catch (error) { const badge = $("#cartBadge"); if (badge) badge.textContent = "0"; }

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
