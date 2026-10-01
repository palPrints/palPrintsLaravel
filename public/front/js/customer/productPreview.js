(async function initializeProductPreview(window, document) {
  "use strict";

  const KEYS = {
    preview: "palprintsCustomerPreview", cart: "palprints-product-cart", workflow: "palprintsStudioWorkflowContext",
    selection: "palprintsDesignerSelection", review: "palprintsReviewState", design: "palprintsDesign:",
    asset: "palprintsDesignAsset:", role: "palprints-user-role"
  };
  const roleAuth = window.PALPRINTS_ROLE_AUTH;
  const objectUrls = [];
  const $ = id => document.getElementById(id);
  const elements = {
    pageTitle: $("previewPageTitle"), pageDescription: $("previewPageDescription"), breadcrumbProductName: $("breadcrumbProductName"), colorLabel: $("colorControlLabel"),
    viewTabs: $("viewTabs"), canvas: $("productCanvas"), image: $("productImage"), placement: $("designPlacement"), art: $("designArt"), tint: $("productTint"),
    imagePlaceholder: $("imagePlaceholder"), viewBadge: $("currentViewBadge"), colorOptions: $("colorOptions"), colorName: $("selectedColorName"),
    sizeOptions: $("sizeOptions"), sizeName: $("selectedSizeName"), pieceSelector: $("pieceSelector"), pieceTabs: $("pieceTabs"),
    activePieceLabel: $("activePieceLabel"), customerSize: $("customerSizeGroup"), customerQuantity: $("customerQuantitySection"),
    customerAreas: $("customerPrintAreaGroup"), quantity: $("quantityValue"), quantityLimit: $("quantityLimitText"),
    decrease: $("decreaseQuantity"), increase: $("increaseQuantity"), areaOptions: $("printAreaOptions"),
    printingSection: $("printingTechnologySection"), printingSelect: $("printingTechnology"), printingNote: $("printingTechnologyNote"),
    designerColors: $("designerColorApproval"), allowedSummary: $("allowedColorsSummary"), openAllowed: $("openAllowedColors"),
    allowedDialog: $("allowedColorsDialog"), allowedOptions: $("allowedColorOptions"), selectAll: $("selectAllColors"), clearAll: $("clearAllColors"),
    closeAllowed: $("closeAllowedColors"), cancelAllowed: $("cancelAllowedColors"), confirmAllowed: $("confirmAllowedColors"),
    purchaseRow: $("customerPurchaseRow"), mobileBar: $("previewMobileBar"),
    priceBreakdown: $("priceBreakdown"), total: $("totalPrice"), mobileTotal: $("mobileTotalPrice"), summaryTitle: $("summaryTitle"),
    designMeta: $("designMeta"), validation: $("summaryValidation"), warnings: $("customerWarnings"), zoomOut: $("zoomOut"),
    zoomIn: $("zoomIn"), zoomValue: $("zoomValue"), fit: $("fitPreview"), openFullscreen: $("openFullscreen"),
    fullscreen: $("fullscreenDialog"), fullscreenTitle: $("fullscreenTitle"), fullscreenStage: $("fullscreenStage"), closeFullscreen: $("closeFullscreen"),
    addToCart: $("addToCartButton"), mobileAdd: $("mobileAddToCart"), toast: $("previewToast"),
    toastIcon: document.querySelector("#previewToast .preview-toast-icon i"), toastMessage: $("previewToastMessage"), cartBadge: $("cartBadge")
  };
  if (!elements.canvas) return;

  function read(storage, key, fallback) {
    try { const value = storage.getItem(key); return value ? JSON.parse(value) : fallback; } catch (error) { return fallback; }
  }
  const workflow = read(sessionStorage, KEYS.workflow, null);
  const access = await roleAuth.resolve({ contextRole: workflow?.workflowMode, developmentDefault: "customer" });
  if (!access.role) { roleAuth.redirectToLogin(); return; }
  const role = access.role;
  const numeric = value => Number.isFinite(Number(value)) ? Number(value) : null;
  const validText = (value, fallback) => typeof value === "string" && value.trim() ? value.trim().slice(0, 120) : fallback;

  function fallbackPayload() {
    return {
      studioContext: false,
      product: {
        id: "hoodie-classic", name: "هودي رجال / نساء", sellingPrice: 20, currency: "ILS", pricingConfigured: true, quantityMax: 99,
        colors: [{ id: "cream", name: "كريمي", value: "#eee6d6", image: "assets/images/hoodie.png" }, { id: "black", name: "أسود", value: "#151719", image: "assets/images/hoodie.png" }, { id: "pink", name: "وردي", value: "#d9a6a9", image: "assets/images/hoodie.png" }],
        sizes: ["S", "M", "L", "XL", "XXL"].map(name => ({ id: name.toLowerCase(), name })),
        printAreas: [{ id: "front", name: "الأمام", image: "assets/images/hoodie.png", fee: 5, placement: { top: 25, left: 29, width: 42, height: 42 } }, { id: "back", name: "الخلف", image: "assets/images/hoodie-back-clean.png", fee: 5, placement: { top: 26, left: 30, width: 40, height: 42 } }],
        printing: { customerSelectable: false, technologies: [] }
      },
      design: { id: "salam", name: "سلام دائم", designerName: "Omar K", preview: { images: [], texts: [{ content: "سلام", color: "#fff", x: 50, y: 60, width: 80, sizePercent: 18, layerOrder: 1 }], icons: [] }, previewByArea: {} },
      selection: { items: [{ colorId: "black", sizeId: "m", printAreaIds: ["front"] }], defaultItem: { colorId: "black", sizeId: "m", printAreaIds: ["front"] }, activeItemIndex: 0 }, customerWarnings: []
    };
  }
  function normalizeLegacy(candidate) {
    if (!candidate?.product || !candidate?.design) return fallbackPayload();
    const fallback = fallbackPayload(), raw = candidate.product, source = candidate.selection || {};
    const colors = Array.isArray(raw.colors) && raw.colors.length ? raw.colors : fallback.product.colors;
    const sizes = Array.isArray(raw.sizes) && raw.sizes.length ? raw.sizes : fallback.product.sizes;
    const areas = Array.isArray(raw.printAreas) && raw.printAreas.length ? raw.printAreas : fallback.product.printAreas;
    const colorId = colors.some(item => item.id === source.colorId) ? source.colorId : colors[0].id;
    const sizeId = sizes.some(item => item.id === source.sizeId) ? source.sizeId : sizes[0].id;
    const ids = areas.filter(area => (source.printAreaIds || []).includes(area.id)).map(area => area.id);
    const base = { colorId, sizeId, printAreaIds: ids.length ? ids : [areas[0].id] };
    const rawItems = Array.isArray(source.items) && source.items.length ? source.items : Array.from({ length: Math.max(1, Math.min(99, Number(source.quantity) || 1)) }, () => base);
    const items = rawItems.map(item => ({
      colorId: colors.some(color => color.id === item.colorId) ? item.colorId : base.colorId,
      sizeId: sizes.some(size => size.id === item.sizeId) ? item.sizeId : base.sizeId,
      printAreaIds: areas.filter(area => (item.printAreaIds || base.printAreaIds).includes(area.id)).map(area => area.id)
    }));
    return {
      studioContext: false,
      product: { ...raw, colors, sizes, printAreas: areas, pricingConfigured: raw.pricingConfigured !== false, quantityMax: numeric(raw.quantityMax) ?? 99, printing: raw.printing || { customerSelectable: false, technologies: [] } },
      design: { ...candidate.design, preview: candidate.design.preview || { images: [], texts: [], icons: [] }, previewByArea: candidate.design.previewByArea || {} },
      selection: { items, defaultItem: source.defaultItem || base, activeItemIndex: Math.max(0, Math.min(items.length - 1, Number(source.activeItemIndex) || 0)) },
      customerWarnings: Array.isArray(candidate.customerWarnings) ? candidate.customerWarnings : []
    };
  }
  function getAssetBlob(designId, assetId) {
    if (!window.indexedDB) return Promise.resolve(null);
    return new Promise(resolve => {
      const open = indexedDB.open("palprintsStudioAssets", 1);
      open.onerror = open.onblocked = () => resolve(null);
      open.onsuccess = () => {
        try {
          const request = open.result.transaction("assets").objectStore("assets").get(designId + ":" + assetId);
          request.onsuccess = () => resolve(request.result?.blob || null); request.onerror = () => resolve(null);
        } catch (error) { resolve(null); }
      };
    });
  }
  async function assetSource(designId, assetId) {
    const blob = await getAssetBlob(designId, assetId);
    if (blob) { const url = URL.createObjectURL(blob); objectUrls.push(url); return url; }
    try { return localStorage.getItem(KEYS.asset + designId + ":" + assetId) || ""; } catch (error) { return ""; }
  }
  function printingMetadata(product) {
    const metadata = product.printing || {};
    const source = product.printingTechnologyOptions || product.printingTechnologies || metadata.technologies || [];
    return {
      customerSelectable: product.customerCanChoosePrintingTechnology === true || metadata.customerSelectable === true,
      technologies: Array.isArray(source) ? source.map((item, index) => typeof item === "string" ? { id: item, name: item, additionalCost: null } : { id: validText(item.id, "tech-" + index), name: validText(item.name, validText(item.id, "تقنية " + (index + 1))), additionalCost: numeric(item.additionalCost ?? item.cost) }) : []
    };
  }
  // The design studio's catalog has no database code; its categories map to the products the server can order.
  const STUDIO_PRODUCT_CODES = { tshirts: "TSHIRT-CLASSIC", hoodies: "HOODIE-PREMIUM", mugs: "MUG-CERAMIC" };
  async function buildStudioPayload(context) {
    if (!context?.designId || !context?.productId) return null;
    const selection = read(sessionStorage, KEYS.selection, null);
    const documentState = read(localStorage, KEYS.design + context.designId, null);
    const catalog = window.PALPRINTS_PRODUCT_CATALOG?.products || [];
    const product = catalog.find(item => item.id === context.productId) || (selection?.editorProduct?.id === context.productId ? selection.editorProduct : null);
    const draft = documentState?.drafts?.[context.productId];
    if (!product || !draft) return null;
    const graphics = new Map((window.PALPRINTS_STUDIO_GRAPHICS?.items || []).map(item => [item.id, item]));
    const ids = [...new Set(Object.values(draft.areas || {}).flatMap(area => area?.objects || []).filter(item => item.kind === "image" && item.assetId).map(item => item.assetId))];
    const sources = new Map();
    await Promise.all(ids.map(async id => sources.set(id, await assetSource(context.designId, id))));
    const rawAreas = product.editor?.printAreas || product.printAreas || [];
    const previewByArea = {};
    rawAreas.forEach(area => {
      const preview = { images: [], texts: [], icons: [] };
      (draft.areas?.[area.id]?.objects || []).forEach((model, index) => {
        const common = { x: (Number(model.x) || 0) * 100, y: (Number(model.y) || 0) * 100, rotation: Number(model.angle) || 0, layerOrder: index + 1, flipX: Boolean(model.flipX), flipY: Boolean(model.flipY) };
        if (model.kind === "image" && sources.get(model.assetId)) preview.images.push({ ...common, assetId: model.assetId, src: sources.get(model.assetId), alt: "صورة مرفوعة في التصميم", width: (Number(model.width) || .2) * 100, height: (Number(model.height) || .2) * 100 });
        if (model.kind === "graphic") {
          const graphic = graphics.get(model.graphicId);
          if (graphic?.assetPath) preview.images.push({ ...common, src: graphic.assetPath, alt: graphic.nameAr || graphic.nameEn || "رسم من التصميم", width: (Number(model.width) || .2) * 100, height: (Number(model.height) || .2) * 100, tint: graphic.recolorable ? model.color || graphic.defaultColor : null });
        }
        if (model.kind === "text") preview.texts.push({ ...common, content: model.text || "", width: (Number(model.width) || .7) * (Number(model.scaleX) || 1) * 100, sizePercent: (Number(model.fontSize) || .1) * (Number(model.scaleY) || 1) * 100, fontFamily: model.fontFamily || "Cairo", color: model.fill || "#0b1f3a", fontWeight: model.fontWeight || "normal", fontStyle: model.fontStyle || "normal", textAlign: model.textAlign || "center", lineHeight: Number(model.lineHeight) || 1.2 });
      });
      previewByArea[area.id] = preview;
    });
    const colors = (product.colors || []).map(color => ({ ...color, areaMockups: color.areaMockups || {} }));
    const sizes = product.sizes || [];
    const colorId = colors.some(item => item.id === context.preview?.colorId) ? context.preview.colorId : (draft.colorId || colors[0]?.id);
    const sizeId = sizes.some(item => item.id === context.preview?.sizeId) ? context.preview.sizeId : (draft.sizeId || sizes[0]?.id);
    const artworkAreas = rawAreas.filter(area => (draft.areas?.[area.id]?.objects || []).length).map(area => area.id);
    const selectedAreas = artworkAreas.length ? artworkAreas : [context.activeAreaId || rawAreas[0]?.id].filter(Boolean);
    const printAreas = rawAreas.map(area => ({ id: area.id, name: area.name, role: area.role, image: area.mockup || area.image || product.thumbnail, fee: numeric(area.fee) ?? 0, placement: { top: Number(area.printZone?.topPct) || 0, left: Number(area.printZone?.leftPct) || 0, width: Number(area.printZone?.widthPct) || 100, height: Number(area.printZone?.heightPct) || 100 } }));
    const basePrice = numeric(product.sellingPrice ?? product.price);
    const pricingConfigured = basePrice !== null && selectedAreas.every(id => numeric(printAreas.find(area => area.id === id)?.fee) !== null);
    const item = { colorId, sizeId, printAreaIds: selectedAreas };
    const assetMap = new Map((documentState.assets || []).map(asset => [asset.assetId, asset]));
    const warnedAssets = new Set(), qualityWarnings = [];
    rawAreas.forEach(area => {
      const zone = area.printZone || {};
      if (!(Number(zone.widthCm) > 0 && Number(zone.heightCm) > 0)) return;
      (draft.areas?.[area.id]?.objects || []).filter(model => model.kind === "image").forEach(model => {
        const asset = assetMap.get(model.assetId);
        if (!asset || asset.sourceType === "vector" || !asset.pixelWidth || !asset.pixelHeight || warnedAssets.has(asset.assetId)) return;
        const widthCm = Math.abs(Number(model.width) || 0) * Number(zone.widthCm), heightCm = Math.abs(Number(model.height) || 0) * Number(zone.heightCm);
        if (!widthCm || !heightCm) return;
        const dpi = Math.min(asset.pixelWidth / (widthCm / 2.54), asset.pixelHeight / (heightCm / 2.54));
        if (dpi < 150) { warnedAssets.add(asset.assetId); qualityWarnings.push({ message: "جودة الصورة «" + (asset.name || "المرفوعة") + "» منخفضة عند حجم الطباعة الحالي (نحو " + Math.round(dpi) + " DPI).", customerVisible: true, blocking: false }); }
      });
    });
    return {
      studioContext: true,
      product: { id: product.id, code: product.code || STUDIO_PRODUCT_CODES[product.categoryId] || null, name: product.studioTitle || product.name, sellingPrice: basePrice, currency: product.currency || "ILS", colors, sizes, printAreas, pricingConfigured, quantityMax: numeric(product.orderLimits?.maxQuantity ?? product.maxQuantity), printing: printingMetadata(product) },
      design: { id: context.designId, name: selection?.designName || "تصميم مخصص", designerName: selection?.designerName || "PALPRINTS", preview: previewByArea[context.activeAreaId] || previewByArea[rawAreas[0]?.id], previewByArea },
      selection: { items: [item], defaultItem: { ...item, printAreaIds: item.printAreaIds.slice() }, activeItemIndex: 0 },
      customerWarnings: qualityWarnings,
      studioSave: { designId: context.designId, assetIds: ids, assets: documentState.assets || [], layout: { areas: draft.areas || {}, colorId, sizeId } }
    };
  }

  const payload = await buildStudioPayload(workflow) || normalizeLegacy(read(sessionStorage, KEYS.preview, null));
  const saved = read(sessionStorage, KEYS.review, {});
  const savedMatches = saved.designId === payload.design.id && saved.productId === payload.product.id;
  const persistedItems = role === "customer" && savedMatches && Array.isArray(saved.orderItems) && saved.orderItems.length
    ? saved.orderItems.map(item => ({
      colorId: payload.product.colors.some(color => color.id === item.colorId) ? item.colorId : payload.selection.items[0].colorId,
      sizeId: payload.product.sizes.some(size => size.id === item.sizeId) ? item.sizeId : payload.selection.items[0].sizeId,
      printAreaIds: payload.product.printAreas.filter(area => (item.printAreaIds || []).includes(area.id)).map(area => area.id)
    })).filter(item => item.printAreaIds.length)
    : null;
  const configuredMaximum = numeric(payload.product.quantityMax);
  const itemSource = persistedItems?.length ? persistedItems : payload.selection.items;
  const initialItems = configuredMaximum > 0 ? itemSource.slice(0, Math.floor(configuredMaximum)) : itemSource;
  const activeIndex = Math.max(0, Math.min(initialItems.length - 1, payload.selection.activeItemIndex || 0));
  const state = {
    tintToken: 0,
    items: initialItems.map(item => ({ colorId: item.colorId, sizeId: item.sizeId, printAreaIds: new Set(item.printAreaIds) })),
    defaultItem: { ...payload.selection.defaultItem, printAreaIds: payload.selection.defaultItem.printAreaIds.slice() }, activePieceIndex: activeIndex,
    currentAreaId: workflow?.activeAreaId || payload.selection.items[activeIndex].printAreaIds[0] || payload.product.printAreas[0].id,
    zoom: 1, toastTimer: null, fullscreenOpener: null,
    allowedColorIds: new Set((savedMatches && Array.isArray(saved.allowedColorIds) ? saved.allowedColorIds : [payload.selection.items[0].colorId]).filter(id => payload.product.colors.some(color => color.id === id))),
    pendingAllowedColorIds: new Set(), printingTechnologyId: savedMatches ? saved.printingTechnologyId || "" : ""
  };
  const activePiece = () => state.items[state.activePieceIndex];
  const currentColor = (piece = activePiece()) => payload.product.colors.find(color => color.id === piece.colorId) || payload.product.colors[0];
  const currentArea = () => payload.product.printAreas.find(area => area.id === state.currentAreaId) || payload.product.printAreas[0];
  const selectedAreas = (piece = activePiece()) => payload.product.printAreas.filter(area => piece.printAreaIds.has(area.id));
  const currentPreview = () => payload.design.previewByArea?.[state.currentAreaId] || payload.design.preview || { images: [], texts: [], icons: [] };
  const quantityMaximum = () => { const value = numeric(payload.product.quantityMax); return value && value > 0 ? Math.floor(value) : null; };
  function imageFor(area, color = currentColor()) { return color?.areaMockups?.[area.id] || area.image || color?.image || ""; }
  function formatMoney(value) {
    if (numeric(value) === null) return "غير متاح";
    const labels = { SAR: "ر.س", ILS: "₪", USD: "$", EUR: "€" };
    return new Intl.NumberFormat("ar", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value)) + " " + (labels[payload.product.currency] || payload.product.currency || "");
  }
  function pricing() {
    const base = numeric(payload.product.sellingPrice);
    const techniqueRequired = payload.product.printing?.customerSelectable && payload.product.printing?.technologies?.length;
    const technique = payload.product.printing?.technologies?.find(item => item.id === state.printingTechnologyId);
    const techniqueCost = technique ? numeric(technique.additionalCost) : techniqueRequired ? null : 0;
    const feesKnown = state.items.every(piece => selectedAreas(piece).every(area => numeric(area.fee) !== null));
    const exact = base !== null && feesKnown && techniqueCost !== null && payload.product.pricingConfigured !== false;
    const baseTotal = base === null ? null : base * state.items.length;
    const areaFees = payload.product.printAreas.map(area => { const count = state.items.filter(piece => piece.printAreaIds.has(area.id)).length; const fee = numeric(area.fee); return { ...area, count, total: fee === null ? null : fee * count }; }).filter(area => area.count);
    const printingTotal = exact ? areaFees.reduce((sum, area) => sum + area.total, 0) + techniqueCost * state.items.length : null;
    return { exact, baseTotal, areaFees, total: exact ? baseTotal + printingTotal : null };
  }
  function persistReview() {
    const value = { workflowMode: role, designId: payload.design.id, productId: payload.product.id, preview: { colorId: activePiece().colorId, sizeId: activePiece().sizeId }, orderItems: role === "customer" ? state.items.map(item => ({ colorId: item.colorId, sizeId: item.sizeId, printAreaIds: [...item.printAreaIds] })) : undefined, allowedColorIds: [...state.allowedColorIds], printingTechnologyId: state.printingTechnologyId, updatedAt: new Date().toISOString() };
    try { sessionStorage.setItem(KEYS.review, JSON.stringify(value)); } catch (error) { /* Optional. */ }
  }
  function applyRoleUi() {
    const designer = role === "designer";
    document.body.dataset.userRole = role;
    elements.pageTitle.textContent = designer ? "معاينة وإعداد التصميم" : "راجع منتجك قبل الطلب";
    elements.pageDescription.textContent = designer ? "عاين التصميم على ألوان المنتج وحدد الألوان المناسبة قبل إعداد النشر." : "راجع الشكل النهائي واختياراتك، ثم أضف المنتج إلى سلتك بثقة.";
    elements.colorLabel.textContent = designer ? "لون المعاينة" : "اللون";
    elements.customerSize.hidden = designer; elements.customerQuantity.hidden = designer; elements.customerAreas.hidden = designer;
    elements.designerColors.hidden = !designer; elements.printingSection.hidden = designer; elements.purchaseRow.hidden = designer; elements.mobileBar.hidden = designer;
  }
  function renderArtwork(container) {
    container.replaceChildren();
    const preview = currentPreview();
    (preview.images || []).forEach(item => {
      const image = document.createElement(item.tint ? "span" : "img"); image.className = "preview-design-item preview-design-image";
      if (item.tint) { image.setAttribute("role", "img"); image.setAttribute("aria-label", item.alt || ""); image.style.backgroundColor = item.tint; image.style.maskImage = "url('" + item.src + "')"; image.style.webkitMaskImage = "url('" + item.src + "')"; image.style.maskSize = image.style.webkitMaskSize = "contain"; image.style.maskPosition = image.style.webkitMaskPosition = "center"; image.style.maskRepeat = image.style.webkitMaskRepeat = "no-repeat"; }
      else { image.src = item.src; image.alt = item.alt || ""; }
      Object.assign(image.style, { left: item.x + "%", top: item.y + "%", width: item.width + "%", height: item.height + "%", zIndex: String(item.layerOrder || 1), transform: "translate(-50%, -50%) rotate(" + (item.rotation || 0) + "deg) scale(" + (item.flipX ? -1 : 1) + "," + (item.flipY ? -1 : 1) + ")" }); container.appendChild(image);
    });
    (preview.texts || []).forEach(item => {
      const text = document.createElement("span"); text.className = "preview-design-item preview-design-text"; text.textContent = item.content;
      Object.assign(text.style, { left: item.x + "%", top: item.y + "%", width: item.width + "%", fontSize: item.sizePercent ? Math.max(8, container.clientHeight * item.sizePercent / 100) + "px" : (item.size || 24) + "px", fontFamily: (item.fontFamily || "Cairo") + ", sans-serif", color: item.color || "#0b1f3a", fontWeight: item.fontWeight, fontStyle: item.fontStyle, textAlign: item.textAlign, lineHeight: String(item.lineHeight || 1.2), zIndex: String(item.layerOrder || 1), transform: "translate(-50%, -50%) rotate(" + (item.rotation || 0) + "deg) scale(" + (item.flipX ? -1 : 1) + "," + (item.flipY ? -1 : 1) + ")" }); container.appendChild(text);
    });
    if (!container.children.length) { const empty = document.createElement("span"); empty.className = "preview-design-placeholder"; empty.textContent = "لا توجد عناصر تصميم في هذه الجهة"; container.appendChild(empty); }
  }
  // Tints the product picture with the color's hex from the database: a colored layer masked to the product shape sits under the picture.
  const alphaProbe = new Map();
  function tintHex(color) {
    const hex = /^#[0-9a-f]{6}$/i.test(color?.value || "") ? color.value : null; if (!hex) return null;
    const r = parseInt(hex.slice(1, 3), 16), g = parseInt(hex.slice(3, 5), 16), b = parseInt(hex.slice(5, 7), 16);
    return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.95 ? null : hex; // white needs no tint
  }
  function hasTransparentBackground(src) {
    if (!src) return Promise.resolve(false);
    if (!alphaProbe.has(src)) alphaProbe.set(src, new Promise(resolve => {
      const probe = new Image(); probe.crossOrigin = "anonymous";
      probe.onload = () => { try { const canvas = document.createElement("canvas"); canvas.width = canvas.height = 24; const context = canvas.getContext("2d"); context.drawImage(probe, 0, 0, 24, 24); const at = (x, y) => context.getImageData(x, y, 1, 1).data[3]; resolve([at(0, 0), at(23, 0), at(0, 23), at(23, 23)].every(alpha => alpha < 20)); } catch (error) { resolve(false); } };
      probe.onerror = () => resolve(false); probe.src = src;
    }));
    return alphaProbe.get(src);
  }
  function applyTint(src, hex) {
    const mask = "url('" + src + "')";
    elements.tint.style.backgroundColor = hex; elements.tint.style.webkitMaskImage = elements.tint.style.maskImage = mask; elements.tint.hidden = false;
    elements.image.className = "preview-product-image is-tinted"; // drops the old filter-based tone class
  }
  function clearTint() { elements.tint.hidden = true; elements.image.classList.remove("is-tinted"); }
  function renderCanvas() {
    const area = currentArea(), selected = activePiece().printAreaIds.has(area.id);
    elements.image.hidden = false; elements.image.className = "preview-product-image " + (currentColor()?.toneClass || ""); elements.image.src = imageFor(area); elements.image.alt = payload.product.name + " — " + area.name;
    clearTint(); const color = currentColor(), source = imageFor(area, color), token = ++state.tintToken;
    // Colors that have their own mockup picture keep it; the rest are tinted with the color's real hex when the picture is a transparent cut-out.
    if (tintHex(color) && !color.areaMockups?.[area.id]) hasTransparentBackground(source).then(ok => { if (ok && token === state.tintToken) applyTint(source, tintHex(color)); });
    elements.image.onload = () => { elements.imagePlaceholder.hidden = true; elements.image.hidden = false; elements.placement.hidden = !selected; };
    elements.image.onerror = () => { elements.image.hidden = true; elements.placement.hidden = true; elements.imagePlaceholder.hidden = false; };
    Object.assign(elements.placement.style, { top: area.placement.top + "%", left: area.placement.left + "%", width: area.placement.width + "%", height: area.placement.height + "%" });
    elements.placement.hidden = !selected; renderArtwork(elements.art); elements.canvas.style.setProperty("--preview-zoom", state.zoom);
    elements.viewBadge.textContent = area.name + (selected ? " · معاينة التصميم" : " · لا يوجد تصميم");
  }
  function renderViewTabs() {
    elements.viewTabs.replaceChildren();
    payload.product.printAreas.forEach(area => { const button = document.createElement("button"), image = document.createElement("img"), label = document.createElement("span"); button.type = "button"; button.className = "view-tab" + (activePiece().printAreaIds.has(area.id) ? "" : " is-unselected"); button.dataset.areaId = area.id; button.setAttribute("role", "tab"); button.setAttribute("aria-selected", String(area.id === state.currentAreaId)); image.src = imageFor(area); image.alt = ""; label.textContent = area.name; button.append(image, label); elements.viewTabs.appendChild(button); });
  }
  function renderColors() {
    elements.colorOptions.replaceChildren();
    payload.product.colors.forEach(color => { const button = document.createElement("button"); button.type = "button"; button.className = "color-option" + (color.id === activePiece().colorId ? " is-selected" : ""); button.dataset.colorId = color.id; button.style.setProperty("--color-value", color.value); button.title = color.name; button.setAttribute("aria-label", "اللون " + color.name); button.setAttribute("aria-pressed", String(color.id === activePiece().colorId)); elements.colorOptions.appendChild(button); });
    elements.colorName.textContent = currentColor()?.name || "غير محدد";
  }
  function renderSizes() {
    elements.sizeOptions.replaceChildren(); payload.product.sizes.forEach(size => { const button = document.createElement("button"); button.type = "button"; button.className = "size-option" + (size.id === activePiece().sizeId ? " is-selected" : ""); button.dataset.sizeId = size.id; button.textContent = size.name; elements.sizeOptions.appendChild(button); }); elements.sizeName.textContent = payload.product.sizes.find(size => size.id === activePiece().sizeId)?.name || "غير محدد";
  }
  function renderQuantity() { const max = quantityMaximum(); elements.quantity.textContent = state.items.length; elements.decrease.disabled = state.items.length <= 1; elements.increase.disabled = Boolean(max && state.items.length >= max); elements.quantityLimit.textContent = max ? "الحد الأقصى " + max + " قطعة" : "لا يوجد حد أقصى مهيأ"; }
  function renderPieces() {
    if (role === "designer") { elements.pieceSelector.hidden = true; return; }
    elements.pieceSelector.hidden = state.items.length === 1; elements.pieceTabs.replaceChildren(); elements.activePieceLabel.textContent = "القطعة " + (state.activePieceIndex + 1) + " من " + state.items.length;
    state.items.forEach((piece, index) => { const button = document.createElement("button"); button.type = "button"; button.className = "piece-tab"; button.dataset.pieceIndex = index; button.setAttribute("aria-selected", String(index === state.activePieceIndex)); button.textContent = "القطعة " + (index + 1); elements.pieceTabs.appendChild(button); });
  }
  function renderAreas() {
    elements.areaOptions.replaceChildren();
    payload.product.printAreas.forEach(area => { const button = document.createElement("button"), image = document.createElement("img"), body = document.createElement("span"), name = document.createElement("strong"), fee = document.createElement("small"); button.type = "button"; button.className = "print-area-option" + (activePiece().printAreaIds.has(area.id) ? " is-selected" : ""); button.dataset.areaId = area.id; image.src = imageFor(area); image.alt = ""; body.className = "print-area-option__body"; name.textContent = area.name; fee.textContent = numeric(area.fee) === null ? "التكلفة غير مهيأة" : "+" + formatMoney(area.fee); body.append(name, fee); button.append(image, body); elements.areaOptions.appendChild(button); });
  }
  function renderPrinting() {
    const data = payload.product.printing || { customerSelectable: false, technologies: [] }; elements.printingSelect.replaceChildren();
    if (!data.customerSelectable || !data.technologies.length) { elements.printingSelect.add(new Option("غير متاحة — لم تتم تهيئتها بعد", "")); elements.printingSelect.disabled = true; elements.printingNote.textContent = "لم تُضف بيانات تقنيات الطباعة المتاحة لهذا المنتج أو المطبعة بعد."; return; }
    elements.printingSelect.disabled = false; elements.printingSelect.add(new Option("اختر تقنية الطباعة", "")); data.technologies.forEach(item => elements.printingSelect.add(new Option(item.name + (numeric(item.additionalCost) === null ? "" : " · +" + formatMoney(item.additionalCost)), item.id))); elements.printingSelect.value = data.technologies.some(item => item.id === state.printingTechnologyId) ? state.printingTechnologyId : ""; elements.printingNote.textContent = "اختر من التقنيات التي أتاحتها المطبعة لهذا المنتج.";
  }
  function addPriceRow(label, value) { const row = document.createElement("div"), span = document.createElement("span"), strong = document.createElement("strong"); row.className = "price-line"; span.textContent = label; strong.textContent = value; row.append(span, strong); elements.priceBreakdown.appendChild(row); }
  function renderPrice() {
    const result = pricing(); elements.priceBreakdown.replaceChildren(); addPriceRow("سعر المنتج × " + state.items.length, result.baseTotal === null ? "غير متاح" : formatMoney(result.baseTotal)); result.areaFees.forEach(area => addPriceRow("طباعة " + area.name + " × " + area.count, area.total === null ? "غير مهيأة" : "+" + formatMoney(area.total))); if (!result.exact) addPriceRow("تكلفة الطباعة والإضافات", "غير متاحة حتى اكتمال بيانات المطبعة"); elements.total.textContent = result.exact ? formatMoney(result.total) : "غير متاح"; elements.mobileTotal.textContent = elements.total.textContent;
  }
  function renderWarnings() { const warnings = (payload.customerWarnings || []).filter(item => role === "designer" || item.customerVisible); elements.warnings.replaceChildren(); elements.warnings.hidden = !warnings.length; warnings.forEach(warning => { const li = document.createElement("li"); li.textContent = warning.message || warning.text; li.className = warning.blocking ? "is-blocking" : ""; elements.warnings.appendChild(li); }); }
  function validationMessage() { if (role !== "customer") return ""; if (!activePiece()?.colorId || !activePiece()?.sizeId || !activePiece()?.printAreaIds.size) return "أكمل خيارات المنتج قبل المتابعة."; if (!pricing().exact) return "تعذر حساب سعر نهائي لأن تكلفة الطباعة لم تتم تهيئتها بعد."; if ((payload.customerWarnings || []).some(item => item.customerVisible && item.blocking)) return "عالج تحذير التصميم قبل الإضافة إلى السلة."; return ""; }
  function renderValidation() { const message = validationMessage(); elements.validation.hidden = !message; elements.validation.textContent = message; elements.addToCart.disabled = Boolean(message); elements.mobileAdd.disabled = Boolean(message); }
  function renderMeta() { elements.breadcrumbProductName.textContent = payload.product.name; elements.summaryTitle.textContent = payload.product.name; elements.designMeta.textContent = payload.design.name + " · تصميم: " + payload.design.designerName; elements.fullscreenTitle.textContent = "معاينة " + payload.design.name + " على " + payload.product.name; }
  function renderAllowedSummary() { const names = payload.product.colors.filter(color => state.allowedColorIds.has(color.id)).map(color => color.name); elements.allowedSummary.textContent = names.length ? names.join("، ") : "لم يتم تحديد ألوان بعد"; }
  function renderAllowedOptions() {
    elements.allowedOptions.replaceChildren();
    payload.product.colors.forEach(color => { const label = document.createElement("label"), input = document.createElement("input"), swatch = document.createElement("span"), name = document.createElement("strong"); label.className = "allowed-color-option"; input.type = "checkbox"; input.value = color.id; input.checked = state.pendingAllowedColorIds.has(color.id); swatch.className = "allowed-color-option__swatch"; swatch.style.setProperty("--allowed-color", color.value); name.textContent = color.name; label.append(input, swatch, name); elements.allowedOptions.appendChild(label); });
  }
  function sync() {
    if (payload.studioContext) { persistReview(); return; }
    payload.selection.items = state.items.map(piece => ({ colorId: piece.colorId, sizeId: piece.sizeId, printAreaIds: [...piece.printAreaIds] })); payload.selection.activeItemIndex = state.activePieceIndex; try { sessionStorage.setItem(KEYS.preview, JSON.stringify(payload)); } catch (error) { /* Optional. */ }
  }
  function renderAll() { applyRoleUi(); renderMeta(); renderViewTabs(); renderCanvas(); renderColors(); renderSizes(); renderQuantity(); renderPieces(); renderAreas(); renderPrinting(); renderPrice(); renderWarnings(); renderAllowedSummary(); renderValidation(); elements.zoomValue.textContent = Math.round(state.zoom * 100) + "%"; sync(); }
  function showToast(message, error) { clearTimeout(state.toastTimer); elements.toast.className = "preview-toast is-visible " + (error ? "is-error" : "is-success"); elements.toastIcon.className = "bi " + (error ? "bi-exclamation-lg" : "bi-check2"); elements.toastMessage.textContent = message; state.toastTimer = setTimeout(() => elements.toast.classList.remove("is-visible"), 3000); }
  // What the cart thumbnail needs to redraw the design exactly as previewed: the product picture (and its color tint),
  // the print zone, and the artwork placed inside it.
  async function cartMockup(piece) {
    const areas = selectedAreas(piece), area = areas.find(item => (payload.design.previewByArea?.[item.id]?.images?.length || payload.design.previewByArea?.[item.id]?.texts?.length)) || areas[0];
    if (!area) return null;
    const color = currentColor(piece), source = imageFor(area, color), path = value => { try { return new URL(value, document.baseURI).pathname; } catch (error) { return ""; } };
    const hex = tintHex(color), tint = hex && !color.areaMockups?.[area.id] && await hasTransparentBackground(source) ? hex : null;
    const preview = payload.design.previewByArea?.[area.id] || { images: [], texts: [] };
    const box = item => ({ x: item.x, y: item.y, width: item.width, height: item.height, rotation: item.rotation || 0, flipX: Boolean(item.flipX), flipY: Boolean(item.flipY), layer: item.layerOrder || 1 });
    return {
      image: path(source), tint, areaName: area.name,
      zone: { top: area.placement.top, left: area.placement.left, width: area.placement.width, height: area.placement.height },
      images: (preview.images || []).map(item => ({ ...box(item), asset_id: item.assetId || null, src: item.assetId ? null : path(item.src), tint: item.tint || null })),
      texts: (preview.texts || []).map(item => ({ ...box(item), content: item.content, size_percent: item.sizePercent, font_family: item.fontFamily, color: item.color, font_weight: item.fontWeight, font_style: item.fontStyle, text_align: item.textAlign, line_height: item.lineHeight }))
    };
  }
  async function customDesignForm(groupList, pieces) {
    const save = payload.studioSave, form = new FormData();
    form.append("product_code", payload.product.code);
    form.append("design_name", payload.design.name || "");
    for (let index = 0; index < groupList.length; index += 1) groupList[index].mockup = await cartMockup(pieces[index]);
    form.append("groups", JSON.stringify(groupList));
    form.append("layout", JSON.stringify(save.layout));
    const extensions = { "image/png": "png", "image/jpeg": "jpg", "image/webp": "webp", "image/svg+xml": "svg" };
    for (const id of save.assetIds) {
      const blob = await getAssetBlob(save.designId, id); if (!blob) continue;
      const record = save.assets.find(asset => asset.assetId === id), extension = extensions[blob.type] || "png";
      const base = String(record?.name || id).replace(/\.[a-z0-9]+$/i, "").slice(0, 80) || "design";
      form.append("files[]", blob, base + "." + extension); form.append("file_assets[]", id);
    }
    return form;
  }
  // Laravel: the cart lives in the database, so "add to cart" posts to the server.
  function addToCart() {
    if (role !== "customer") return;
    const message = validationMessage(); if (message) { showToast(message, true); return; }
    const assets = window.palPrintsCustomerAssets || {};
    const custom = Boolean(payload.studioSave);
    const endpoint = custom ? assets.cartCustomDesignUrl : assets.cartCatalogUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!payload.product.code || !endpoint) { showToast("تعذر تحديد المنتج. عد إلى صفحة المنتجات واختر المنتج من جديد.", true); return; }
    const groups = new Map();
    state.items.forEach(piece => { const ids = [...piece.printAreaIds], key = JSON.stringify([piece.colorId, piece.sizeId, ids]), group = groups.get(key) || { piece, quantity: 0 }; group.quantity += 1; groups.set(key, group); });
    const groupList = [...groups.values()].map(group => {
      const color = currentColor(group.piece), size = payload.product.sizes.find(item => item.id === group.piece.sizeId);
      return { color_id: group.piece.colorId, color_name: color ? color.name : group.piece.colorId, size_id: group.piece.sizeId, size_name: size ? size.name : group.piece.sizeId, quantity: group.quantity, print_areas: selectedAreas(group.piece).map(area => area.name) };
    });
    const pieces = [...groups.values()].map(group => group.piece);
    const body = { product_code: payload.product.code, design_id: payload.design.id, groups: groupList };
    [elements.addToCart, elements.mobileAdd].forEach(button => { button.disabled = true; });
    const headers = { "Accept": "application/json", "X-CSRF-TOKEN": csrf ? csrf.content : "" };
    // A customer-made design is saved with its artwork files (multipart); a published design only sends ids (JSON).
    const request = custom
      ? customDesignForm(groupList, pieces).then(form => fetch(endpoint, { method: "POST", headers, body: form }))
      : fetch(endpoint, { method: "POST", headers: { ...headers, "Content-Type": "application/json" }, body: JSON.stringify(body) });
    request
      .then(response => response.json().catch(() => ({})).then(data => {
        if (!response.ok) { const firstError = data.errors ? Object.values(data.errors)[0][0] : data.message; throw new Error(firstError || "تعذرت إضافة المنتج إلى السلة. حاول مرة أخرى."); }
        return data;
      }))
      .then(data => { showToast("تمت إضافة المنتج إلى السلة بنجاح.", false); setTimeout(() => { window.location.href = data.redirect; }, 600); })
      .catch(error => { renderValidation(); showToast(error.message, true); });
  }

  elements.viewTabs.addEventListener("click", event => { const button = event.target.closest("[data-area-id]"); if (!button) return; state.currentAreaId = button.dataset.areaId; renderViewTabs(); renderCanvas(); persistReview(); });
  elements.colorOptions.addEventListener("click", event => { const button = event.target.closest("[data-color-id]"); if (!button) return; activePiece().colorId = button.dataset.colorId; renderColors(); renderViewTabs(); renderAreas(); renderCanvas(); renderPieces(); persistReview(); });
  elements.sizeOptions.addEventListener("click", event => { const button = event.target.closest("[data-size-id]"); if (!button) return; activePiece().sizeId = button.dataset.sizeId; renderSizes(); renderPieces(); renderValidation(); sync(); });
  elements.pieceTabs.addEventListener("click", event => { const button = event.target.closest("[data-piece-index]"); if (!button) return; state.activePieceIndex = Number(button.dataset.pieceIndex); state.currentAreaId = [...activePiece().printAreaIds][0]; renderAll(); });
  elements.areaOptions.addEventListener("click", event => { const button = event.target.closest("[data-area-id]"); if (!button || role !== "customer") return; const id = button.dataset.areaId; if (activePiece().printAreaIds.has(id) && activePiece().printAreaIds.size === 1) { showToast("يجب إبقاء منطقة طباعة واحدة على الأقل.", true); return; } activePiece().printAreaIds.has(id) ? activePiece().printAreaIds.delete(id) : activePiece().printAreaIds.add(id); state.currentAreaId = id; renderAll(); });
  elements.decrease.addEventListener("click", () => { if (role !== "customer" || state.items.length <= 1) return; state.items.splice(state.activePieceIndex, 1); state.activePieceIndex = Math.min(state.activePieceIndex, state.items.length - 1); state.currentAreaId = [...activePiece().printAreaIds][0]; renderAll(); });
  elements.increase.addEventListener("click", () => { const max = quantityMaximum(); if (role !== "customer" || (max && state.items.length >= max)) return; state.items.push({ colorId: state.defaultItem.colorId, sizeId: state.defaultItem.sizeId, printAreaIds: new Set(state.defaultItem.printAreaIds) }); state.activePieceIndex = state.items.length - 1; state.currentAreaId = [...activePiece().printAreaIds][0]; renderAll(); });
  elements.printingSelect.addEventListener("change", () => { state.printingTechnologyId = elements.printingSelect.value; renderPrice(); renderValidation(); persistReview(); });
  elements.zoomOut.addEventListener("click", () => { state.zoom = Math.max(.8, Math.round((state.zoom - .1) * 10) / 10); renderCanvas(); elements.zoomValue.textContent = Math.round(state.zoom * 100) + "%"; });
  elements.zoomIn.addEventListener("click", () => { state.zoom = Math.min(1.5, Math.round((state.zoom + .1) * 10) / 10); renderCanvas(); elements.zoomValue.textContent = Math.round(state.zoom * 100) + "%"; });
  elements.fit.addEventListener("click", () => { state.zoom = 1; renderCanvas(); elements.zoomValue.textContent = "100%"; });
  elements.openFullscreen.addEventListener("click", () => { state.fullscreenOpener = document.activeElement; const clone = elements.canvas.cloneNode(true); clone.removeAttribute("id"); clone.querySelectorAll("[id]").forEach(node => node.removeAttribute("id")); clone.style.setProperty("--preview-zoom", "1"); elements.fullscreenStage.replaceChildren(clone); elements.fullscreen.showModal(); });
  elements.closeFullscreen.addEventListener("click", () => elements.fullscreen.close()); elements.fullscreen.addEventListener("close", () => state.fullscreenOpener?.focus?.());
  elements.openAllowed.addEventListener("click", () => { state.pendingAllowedColorIds = new Set(state.allowedColorIds); renderAllowedOptions(); elements.allowedDialog.showModal(); });
  [elements.closeAllowed, elements.cancelAllowed].forEach(button => button.addEventListener("click", () => elements.allowedDialog.close()));
  elements.allowedOptions.addEventListener("change", event => {
    if (event.target.type !== "checkbox") return;
    event.target.checked ? state.pendingAllowedColorIds.add(event.target.value) : state.pendingAllowedColorIds.delete(event.target.value);
    activePiece().colorId = event.target.value;
    renderColors(); renderViewTabs(); renderCanvas(); persistReview();
  });
  elements.selectAll.addEventListener("click", () => { state.pendingAllowedColorIds = new Set(payload.product.colors.map(color => color.id)); renderAllowedOptions(); });
  elements.clearAll.addEventListener("click", () => { state.pendingAllowedColorIds.clear(); renderAllowedOptions(); });
  elements.confirmAllowed.addEventListener("click", () => { state.allowedColorIds = new Set(state.pendingAllowedColorIds); renderAllowedSummary(); persistReview(); elements.allowedDialog.close(); showToast("تم حفظ ألوان المنتج المناسبة للتصميم.", false); });
  elements.addToCart.addEventListener("click", addToCart); elements.mobileAdd.addEventListener("click", addToCart);
  window.addEventListener("beforeunload", () => objectUrls.forEach(url => URL.revokeObjectURL(url)));
  renderAll();
  const reveal = () => requestAnimationFrame(() => requestAnimationFrame(() => { document.body.classList.remove("preview-booting"); $("productPreviewMain")?.setAttribute("aria-busy", "false"); window.dispatchEvent(new Event("palprints:ready")); }));
  document.fonts?.ready ? document.fonts.ready.then(reveal, reveal) : reveal();
})(window, document);
