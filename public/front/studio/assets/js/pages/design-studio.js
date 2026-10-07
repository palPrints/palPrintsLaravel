(function initializeDesignStudio(window, document) {
  "use strict";

  const SELECTION_KEY = "palprintsDesignerSelection";
  const DESIGN_PREFIX = "palprintsDesign:";
  const FALLBACK_ASSET_PREFIX = "palprintsDesignAsset:";
  // const DESKTOP_PRODUCT_HEIGHT_RATIO = 0.94;
  const DESKTOP_PRODUCT_HEIGHT_RATIO = 1.0;
  const COMPACT_PRODUCT_HEIGHT_RATIO = 1;
  const ALLOWED_UPLOADS = new Set(["image/png", "image/jpeg", "image/svg+xml"]);
  const GRAPHICS = window.PALPRINTS_STUDIO_GRAPHICS || { categories: [], items: [] };
  const APPROVED_GRAPHICS = new Map(GRAPHICS.items.filter(item => item.reviewStatus === "approved").map(item => [item.id, item]));
  const GRAPHICS_BY_CATEGORY = new Map(GRAPHICS.categories.map(category => [category,
    [...APPROVED_GRAPHICS.values()].filter(item => item.category === category)]));
  const GRAPHICS_PAGE_SIZE = 24;
  const VIEW_ZOOM = Object.freeze({ min: 0.5, max: 2, step: 0.1 });
  // Provisional V1 defaults. Printshop-specific thresholds can replace these
  // without changing the quality calculation or UI states.
  const IMAGE_QUALITY_THRESHOLDS = Object.freeze({ excellent: 300, good: 150, provisional: true });
  const FONT_FAMILIES = [
    "Cairo", "Tajawal", "Almarai", "Changa", "El Messiri", "Reem Kufi", "Noto Kufi Arabic", "Noto Naskh Arabic",
    "Amiri", "Lateef", "Scheherazade New", "Markazi Text", "Mada", "IBM Plex Sans Arabic", "Harmattan", "Katibeh",
    "Lemonada", "Mirza", "Rakkas", "Aref Ruqaa", "Baloo Bhaijaan 2", "Readex Pro", "Roboto", "Open Sans",
    "Montserrat", "Poppins", "Lato", "Oswald", "Raleway", "Playfair Display", "Merriweather", "Bebas Neue",
    "Anton", "Lobster", "Pacifico", "Dancing Script", "Abril Fatface", "Cinzel", "Nunito", "Inter"
  ];

  const $ = id => document.getElementById(id);
  const elements = {
    summaryImage: $("summaryImage"), summaryProductName: $("summaryProductName"), summaryColor: $("summaryColor"),
    summarySize: $("summarySize"), summaryPrice: $("summaryPrice"), selectedColorName: $("selectedColorName"),
    colorOptions: $("colorOptions"), sizeOptions: $("sizeOptions"), areaOptions: $("areaOptions"), areaCount: $("areaCount"),
    workspaceEyebrow: $("workspaceEyebrow"), stage: $("studioStage"), coordinateSystem: $("stageCoordinateSystem"), productCanvas: $("productCanvas"),
    productMockup: $("productMockup"),
    printZone: $("printZone"), printZoneLabelText: $("printZoneLabelText"), designCanvas: $("designCanvas"),
    toolPanel: $("toolPanel"), toolPanelContent: $("toolPanelContent"), error: $("studioError"),
    errorMessage: $("studioErrorMessage"), liveRegion: $("studioLiveRegion"), notice: $("studioNotice"), noticeText: $("studioNoticeText"),
    deleteDialog: $("deleteAssetDialog"), deleteThumbnail: $("deleteAssetThumbnail"), deleteMessage: $("deleteAssetMessage"),
    cancelAssetDelete: $("cancelAssetDelete"), confirmAssetDelete: $("confirmAssetDelete"),
    selectedObjectControls: $("selectedObjectControls"), selectedObjectName: $("selectedObjectName"),
    snapGuideVertical: $("snapGuideVertical"), snapGuideHorizontal: $("snapGuideHorizontal"),
    areaCopyGroup: $("areaCopyGroup"), areaCopyActions: $("areaCopyActions"),
    swapProductToggle: $("swapProductToggle"), productSwapPanel: $("productSwapPanel"), closeProductSwap: $("closeProductSwap"), productSwapOptions: $("productSwapOptions"),
    undo: $("studioUndo"), redo: $("studioRedo"),
    zoomOut: $("studioZoomOut"), zoomFit: $("studioZoomFit"), zoomIn: $("studioZoomIn"), zoomValue: $("studioZoomValue"),
    replaceAreaDialog: $("replaceAreaDialog"), replaceAreaMessage: $("replaceAreaMessage"), cancelAreaReplace: $("cancelAreaReplace"), confirmAreaReplace: $("confirmAreaReplace")
  };

  const app = {
    selection: null, product: null, color: null, size: null, area: null, design: null, canvas: null, assetStore: null,
    activeTool: "upload", objectUrls: new Map(), fontPromises: new Map(), suppressCanvasEvents: false,
    geometryReady: false, viewportZone: null, logicalStage: null, baseStageScale: 1, stageScale: 1, viewZoom: 1, areaSwitchToken: 0, snapDrag: null,
    persistTimer: null, resizeObserver: null, resizeFrame: null, noticeTimer: null, panelRenderToken: 0,
    pendingAssetFingerprints: new Set(), graphicsCategory: GRAPHICS.categories[0] || "", graphicsSearch: "", graphicsLimit: GRAPHICS_PAGE_SIZE,
    products: new Map(), drafts: {}, history: [], historyIndex: -1, historyTimer: null, historyRestoring: false
  };

  class DesignAssetStore {
    constructor(designId) { this.designId = designId; this.dbPromise = this.open(); }
    open() {
      if (!window.indexedDB) return Promise.resolve(null);
      return new Promise(resolve => {
        let finished = false;
        const finish = value => { if (finished) return; finished = true; clearTimeout(timeout); resolve(value); };
        const timeout = setTimeout(() => finish(null), 1800);
        const request = window.indexedDB.open("palprintsStudioAssets", 1);
        request.onupgradeneeded = () => {
          if (!request.result.objectStoreNames.contains("assets")) request.result.createObjectStore("assets", { keyPath: "key" });
        };
        request.onsuccess = () => finish(request.result);
        request.onerror = request.onblocked = () => finish(null);
      });
    }
    key(assetId) { return `${this.designId}:${assetId}`; }
    async putFallback(assetId, blob) {
      const reader = new FileReader();
      const dataUrl = await new Promise((resolve, reject) => {
        reader.onload = () => resolve(reader.result); reader.onerror = reject; reader.readAsDataURL(blob);
      });
      try { localStorage.setItem(`${FALLBACK_ASSET_PREFIX}${this.key(assetId)}`, dataUrl); }
      catch (error) { /* IndexedDB remains the primary store when quota is unavailable. */ }
    }
    async put(assetId, blob) {
      const db = await this.dbPromise;
      if (db) {
        try {
          await new Promise((resolve, reject) => {
            const request = db.transaction("assets", "readwrite").objectStore("assets").put({ key: this.key(assetId), blob });
            request.onsuccess = resolve; request.onerror = () => reject(request.error);
          });
          if (location.protocol === "file:") await this.putFallback(assetId, blob);
          return;
        } catch (error) {
          // Continue to the isolated prototype fallback if browser storage rejects the blob.
        }
      }
      await this.putFallback(assetId, blob);
    }
    async get(assetId) {
      const db = await this.dbPromise;
      if (db) {
        const blob = await new Promise(resolve => {
          const request = db.transaction("assets").objectStore("assets").get(this.key(assetId));
          request.onsuccess = () => resolve(request.result?.blob || null); request.onerror = () => resolve(null);
        });
        if (blob) return blob;
      }
      const dataUrl = window.localStorage.getItem(`${FALLBACK_ASSET_PREFIX}${this.key(assetId)}`);
      return dataUrl ? fetch(dataUrl).then(response => response.blob()) : null;
    }
    async delete(assetId) {
      const db = await this.dbPromise;
      if (db) await new Promise(resolve => {
        const request = db.transaction("assets", "readwrite").objectStore("assets").delete(this.key(assetId));
        request.onsuccess = request.onerror = resolve;
      });
      window.localStorage.removeItem(`${FALLBACK_ASSET_PREFIX}${this.key(assetId)}`);
    }
  }

  function uid(prefix) { return `${prefix}-${window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(16).slice(2)}`}`; }
  function readSelection() { try { return JSON.parse(window.sessionStorage.getItem(SELECTION_KEY) || "null"); } catch (error) { return null; } }
  function selectedItem(items, id) { return (items || []).find(item => item.id === id) || items?.[0] || null; }
  function formatPrice(value) { const amount = Number(value); return Number.isFinite(amount) ? `${amount.toFixed(2)} ₪` : "—"; }
  function announce(message) { elements.liveRegion.textContent = ""; setTimeout(() => { elements.liveRegion.textContent = message; }, 20); }
  function showNotice(message, tone = "info") {
    clearTimeout(app.noticeTimer); elements.noticeText.textContent = message; elements.notice.className = `studio-notice is-${tone}`; elements.notice.hidden = false;
    app.noticeTimer = setTimeout(() => { elements.notice.hidden = true; }, 4200);
  }
  function showError(message) { elements.errorMessage.textContent = message; elements.error.hidden = false; }
  function resolveZone(area, size) { return area?.sizePrintZones?.[size?.id] || area?.printZone || null; }
  function applyZoneToOverlay(zone) {
    ["left", "top", "width", "height"].forEach(key => elements.printZone.style.setProperty(`--zone-${key}`, `${zone[`${key}Pct`]}%`));
    elements.printZone.classList.toggle("has-format-conflict", zone.physicalFitStatus === "conflict-review-required");
  }
  function rawMockup(area, color) { return color?.areaMockups?.[area.id] || (["front", "primary"].includes(area.role) && color?.image) || area.mockup || app.product.thumbnail || color?.image || ""; }
  // Colours added from the admin have no photo of their own: they carry a `tint` hex and are painted
  // onto the white garment photo (multiply keeps the folds and shadows, the original alpha keeps the shape).
  const tintedMockups = new Map();
  const tintKey = (url, hex) => `${hex}|${url}`;
  // The colour to paint an area with: a new colour is painted everywhere; a colour with photos is painted only where it has
  // none (the sleeves have a white photo only), unless the colour is white.
  function tintFor(area, color) {
    if (!color) return null;
    if (color.tint) return color.tint;
    const ownPhoto = color.areaMockups?.[area.id] || (["front", "primary"].includes(area.role) && color.image);
    const match = /^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(color.value || "");
    if (ownPhoto || !match) return null;
    const light = (0.299 * parseInt(match[1], 16) + 0.587 * parseInt(match[2], 16) + 0.114 * parseInt(match[3], 16)) / 255;
    return light > 0.95 ? null : color.value;
  }
  function resolveMockup(area, color) {
    const url = rawMockup(area, color), tint = tintFor(area, color);
    return tint ? tintedMockups.get(tintKey(url, tint)) || url : url;
  }
  async function prepareMockup(area, color) {
    const url = rawMockup(area, color), tint = tintFor(area, color);
    if (!tint || !url || tintedMockups.has(tintKey(url, tint))) return;
    try {
      // A colour that has photos is painted with the colour those photos show, so the sleeves match the front and back.
      let paint = tint;
      const white = app.product.colors.find(item => item.id === "white");
      if (!color.tint && color.image && white?.image && window.PALPRINTS_PHOTO_TINT) paint = (await window.PALPRINTS_PHOTO_TINT(color.image, white.image)) || tint;
      const source = await new Promise((resolve, reject) => {
        const image = new Image(); image.onload = () => resolve(image); image.onerror = reject; image.src = url;
      });
      const canvas = document.createElement("canvas"); canvas.width = source.naturalWidth; canvas.height = source.naturalHeight;
      const context = canvas.getContext("2d");
      context.drawImage(source, 0, 0);
      context.globalCompositeOperation = "multiply"; context.fillStyle = paint; context.fillRect(0, 0, canvas.width, canvas.height);
      context.globalCompositeOperation = "destination-in"; context.drawImage(source, 0, 0);
      tintedMockups.set(tintKey(url, tint), canvas.toDataURL("image/png"));
    } catch (error) { /* keep the untinted photo if the image cannot be read */ }
  }
  function zonesDiffer(a, b) { return ["leftPct", "topPct", "widthPct", "heightPct", "widthCm", "heightCm"].some(key => Number(a?.[key]) !== Number(b?.[key])); }

  function validateProduct(product) {
    if (!product?.id || !product.editor) return { valid: false, message: "بيانات المنتج المرسلة إلى الاستوديو غير مكتملة." };
    const areas = Array.isArray(product.editor.printAreas) ? product.editor.printAreas : [];
    if (!areas.length || !areas.some(area => area.id === product.editor.defaultAreaId)) return { valid: false, message: "مناطق الطباعة غير مهيأة بصورة صالحة." };
    const ids = new Set();
    for (const area of areas) {
      const zone = area.printZone || {}, values = [zone.leftPct, zone.topPct, zone.widthPct, zone.heightPct, zone.widthCm, zone.heightCm].map(Number);
      const [left, top, width, height, physicalWidth, physicalHeight] = values;
      if (!area.id || ids.has(area.id) || !area.mockup || !values.every(Number.isFinite) || left < 0 || top < 0 || width <= 0 || height <= 0
        || left + width > 100 || top + height > 100 || physicalWidth <= 0 || physicalHeight <= 0) {
        return { valid: false, message: `بيانات منطقة الطباعة «${area.name || area.id || "غير معروفة"}» غير صالحة.` };
      }
      ids.add(area.id);
    }
    return { valid: true, message: "" };
  }

  function resolveEligibleProducts(selection) {
    const shared = Array.isArray(window.PALPRINTS_PRODUCT_CATALOG?.products) ? window.PALPRINTS_PRODUCT_CATALOG.products : [];
    const payload = Array.isArray(selection?.editorProducts) ? selection.editorProducts : [];
    const sharedIds = new Set(shared.map(product => product?.id).filter(Boolean));
    const byId = new Map();
    [...payload, selection?.editorProduct, ...shared].forEach(product => {
      if (!product?.id || (shared.length && !sharedIds.has(product.id)) || !validateProduct(product).valid) return;
      byId.set(product.id, product);
    });
    return [...byId.values()];
  }

  function setupSiteShell() {
    const menu = $("menuButton"), overlay = $("sidebarOverlay"), mobile = matchMedia("(max-width: 900px)");
    if (!menu || !overlay) return;
    const sync = () => { const open = mobile.matches ? document.body.classList.contains("sidebar-open") : !document.body.classList.contains("sidebar-collapsed"); menu.setAttribute("aria-expanded", String(open)); menu.setAttribute("aria-label", open ? "طي القائمة" : "فتح القائمة"); };
    const close = () => { document.body.classList.remove("sidebar-open"); sync(); };
    menu.addEventListener("click", () => { document.body.classList.toggle(mobile.matches ? "sidebar-open" : "sidebar-collapsed"); sync(); });
    overlay.addEventListener("click", close); document.querySelectorAll(".sidebar a").forEach(link => link.addEventListener("click", () => { if (mobile.matches) close(); }));
    mobile.addEventListener?.("change", close); sync();
  }

  function ensureDesignId(selection) {
    if (!selection.designId) { selection.designId = uid("design"); sessionStorage.setItem(SELECTION_KEY, JSON.stringify(selection)); }
  }
  function clone(value) { return JSON.parse(JSON.stringify(value)); }
  function createDraft(product, options = {}) {
    const areas = {}; product.editor.printAreas.forEach(area => { areas[area.id] = { objects: [] }; });
    return { productId: product.id, colorId: options.colorId || product.defaultColor || product.colors?.[0]?.id,
      sizeId: options.sizeId || product.sizes?.[0]?.id,
      activeAreaId: options.activeAreaId || product.editor.defaultAreaId, areas };
  }
  function activeDraftSnapshot() {
    return { productId: app.product.id, colorId: app.color?.id, sizeId: app.size?.id, activeAreaId: app.area?.id,
      areas: clone(app.design.areas) };
  }
  function storeActiveDraft({ saveCanvas = true } = {}) {
    if (!app.design || !app.product) return;
    if (saveCanvas) saveActiveArea();
    app.drafts[app.product.id] = activeDraftSnapshot();
  }
  function activateDraft(product, draft) {
    app.product = product;
    app.design = { schemaVersion: 2, designId: app.selection.designId, assets: app.assets, ...clone(draft) };
    app.color = selectedItem(product.colors, draft.colorId);
    app.size = selectedItem(product.sizes, draft.sizeId);
    app.area = product.editor.printAreas.find(area => area.id === draft.activeAreaId)
      || product.editor.printAreas.find(area => area.id === product.editor.defaultAreaId) || product.editor.printAreas[0];
  }
  function uniqueAssetRecords(records) {
    const seen = new Set();
    return (Array.isArray(records) ? records : []).filter(asset => asset?.assetId && !seen.has(asset.assetId) && seen.add(asset.assetId));
  }
  function loadDesignDocument(selection) {
    try {
      const saved = JSON.parse(localStorage.getItem(`${DESIGN_PREFIX}${selection.designId}`) || "null");
      if (saved?.schemaVersion === 2 && saved.drafts) {
        return { assets: uniqueAssetRecords(saved.assets), drafts: saved.drafts, activeProductId: saved.activeProductId || selection.productId };
      }
      if (saved?.schemaVersion === 1 && saved.productId === selection.productId) {
        const draft = { productId: saved.productId, colorId: saved.colorId, sizeId: saved.sizeId, activeAreaId: saved.activeAreaId, areas: saved.areas };
        return { assets: uniqueAssetRecords(saved.assets), drafts: { [saved.productId]: draft }, activeProductId: saved.productId };
      }
    } catch (error) { /* fresh document */ }
    return { assets: [], drafts: { [selection.productId]: createDraft(selection.editorProduct, {
      colorId: selection.colorId, sizeId: selection.sizeId,
      activeAreaId: selection.printAreaIds?.[0] || selection.editorProduct.editor.defaultAreaId
    }) }, activeProductId: selection.productId };
  }
  function commitDesign() {
    if (!app.design) return;
    storeActiveDraft({ saveCanvas: false });
    const documentState = { schemaVersion: 2, designId: app.selection.designId, activeProductId: app.product.id,
      assets: app.assets, drafts: app.drafts, updatedAt: new Date().toISOString() };
    localStorage.setItem(`${DESIGN_PREFIX}${app.selection.designId}`, JSON.stringify(documentState));
  }
  function scheduleCommit() {
    saveActiveArea();
    clearTimeout(app.persistTimer);
    app.persistTimer = setTimeout(commitDesign, 120);
    scheduleHistoryCapture();
  }

  function historySnapshot() {
    storeActiveDraft();
    return JSON.stringify({ activeProductId: app.product.id, drafts: app.drafts });
  }
  function updateHistoryControls() {
    if (!elements.undo || !elements.redo) return;
    elements.undo.disabled = app.historyRestoring || app.historyIndex <= 0;
    elements.redo.disabled = app.historyRestoring || app.historyIndex >= app.history.length - 1;
  }
  function captureHistory() {
    if (app.historyRestoring || !app.design) return false;
    clearTimeout(app.historyTimer); app.historyTimer = null;
    const snapshot = historySnapshot();
    if (snapshot === app.history[app.historyIndex]) return false;
    if (app.historyIndex < app.history.length - 1) app.history.splice(app.historyIndex + 1);
    app.history.push(snapshot); if (app.history.length > 100) app.history.shift();
    app.historyIndex = app.history.length - 1; updateHistoryControls(); return true;
  }
  function scheduleHistoryCapture(delay = 180) {
    if (app.historyRestoring) return;
    clearTimeout(app.historyTimer); app.historyTimer = setTimeout(captureHistory, delay);
  }
  async function restoreHistory(index) {
    if (app.historyRestoring || index < 0 || index >= app.history.length) return;
    clearTimeout(app.historyTimer); app.historyTimer = null; app.historyRestoring = true; updateHistoryControls();
    try {
      const snapshot = JSON.parse(app.history[index]), product = app.products.get(snapshot.activeProductId);
      if (!product || !snapshot.drafts?.[product.id]) return;
      app.drafts = clone(snapshot.drafts); activateDraft(product, app.drafts[product.id]);
      await renderActiveProduct(); app.historyIndex = index; commitDesign();
    } finally { app.historyRestoring = false; updateHistoryControls(); }
  }
  function setupHistory() {
    elements.undo?.addEventListener("click", () => { if (app.historyTimer) captureHistory(); void restoreHistory(app.historyIndex - 1); });
    elements.redo?.addEventListener("click", () => { if (app.historyTimer) captureHistory(); void restoreHistory(app.historyIndex + 1); });
    document.addEventListener("keydown", event => {
      if (event.defaultPrevented || event.altKey || !(event.ctrlKey || event.metaKey) || event.target.closest?.("input,textarea,select,[contenteditable='true']")) return;
      const key = String(event.key).toLowerCase(), redo = key === "y" || (key === "z" && event.shiftKey), undo = key === "z" && !event.shiftKey;
      if (undo && app.historyIndex > 0) { event.preventDefault(); if (app.historyTimer) captureHistory(); void restoreHistory(app.historyIndex - 1); }
      else if (redo && app.historyIndex < app.history.length - 1) { event.preventDefault(); void restoreHistory(app.historyIndex + 1); }
    }, true);
  }

  function transferredModels(models, sourceArea, sourceSize, targetArea, targetSize) {
    const sourceZone = resolveZone(sourceArea, sourceSize), targetZone = resolveZone(targetArea, targetSize);
    const sourceWidth = Number(sourceZone?.widthCm), sourceHeight = Number(sourceZone?.heightCm);
    const targetWidth = Number(targetZone?.widthCm), targetHeight = Number(targetZone?.heightCm);
    let xFactor = 1, yFactor = 1;
    if ([sourceWidth, sourceHeight, targetWidth, targetHeight].every(value => Number.isFinite(value) && value > 0)) {
      const fit = Math.min(targetWidth / sourceWidth, targetHeight / sourceHeight);
      xFactor = sourceWidth * fit / targetWidth; yFactor = sourceHeight * fit / targetHeight;
    }
    return models.map(source => {
      const model = clone(source); model.id = uid("object");
      model.x = 0.5 + (Number(model.x) - 0.5) * xFactor; model.y = 0.5 + (Number(model.y) - 0.5) * yFactor;
      if (Number.isFinite(Number(model.width))) model.width *= xFactor;
      if (Number.isFinite(Number(model.height))) model.height *= yFactor;
      if (model.kind === "text" && Number.isFinite(Number(model.fontSize))) model.fontSize *= yFactor;
      return model;
    });
  }
  function confirmAreaReplacement(area) {
    const dialog = elements.replaceAreaDialog;
    if (!dialog || !area) return Promise.resolve(false);
    elements.replaceAreaMessage.textContent = `تحتوي جهة «${area.name}» على تصميم. سيُستبدل محتواها بالكامل بالنسخة الجديدة.`;
    return new Promise(resolve => {
      let settled = false;
      const finish = value => { if (settled) return; settled = true; cleanup(); if (dialog.open) dialog.close(); resolve(value); };
      const cancel = event => { event?.preventDefault?.(); finish(false); }, confirm = () => finish(true);
      const backdrop = event => { if (event.target === dialog) cancel(event); };
      const cleanup = () => { elements.cancelAreaReplace.removeEventListener("click", cancel); elements.confirmAreaReplace.removeEventListener("click", confirm); dialog.removeEventListener("cancel", cancel); dialog.removeEventListener("click", backdrop); };
      elements.cancelAreaReplace.addEventListener("click", cancel); elements.confirmAreaReplace.addEventListener("click", confirm);
      dialog.addEventListener("cancel", cancel); dialog.addEventListener("click", backdrop); dialog.showModal(); elements.cancelAreaReplace.focus();
    });
  }
  async function copyActiveAreaTo(targetId) {
    saveActiveArea(); const sourceArea = app.area, targetArea = app.product.editor.printAreas.find(area => area.id === targetId);
    if (!targetArea || targetArea.id === sourceArea.id) return;
    const destination = app.design.areas[targetId];
    if (destination.objects.length && !await confirmAreaReplacement(targetArea)) return;
    destination.objects = transferredModels(app.design.areas[sourceArea.id].objects, sourceArea, app.size, targetArea, app.size);
    storeActiveDraft({ saveCanvas: false }); commitDesign(); captureHistory();
    showNotice(`تم نسخ التصميم إلى «${targetArea.name}».`, "success"); renderAreaCopyActions();
  }
  function renderAreaCopyActions() {
    if (!elements.areaCopyActions) return;
    const destinations = app.product.editor.printAreas.filter(area => area.id !== app.area.id);
    elements.areaCopyGroup.hidden = !destinations.length; elements.areaCopyActions.replaceChildren();
    destinations.forEach(area => {
      const button = document.createElement("button"); button.type = "button"; button.className = "studio-copy-action";
      button.innerHTML = '<i class="bi bi-copy" aria-hidden="true"></i><span></span>'; button.querySelector("span").textContent = `نسخ التصميم إلى ${area.name}`;
      button.disabled = !app.design.areas[app.area.id]?.objects.length; button.addEventListener("click", () => { void copyActiveAreaTo(area.id); });
      elements.areaCopyActions.appendChild(button);
    });
  }

  function areaMapping(sourceProduct, targetProduct) {
    const mapping = new Map(), used = new Set(), sources = sourceProduct.editor.printAreas, targets = targetProduct.editor.printAreas;
    sources.forEach(source => { const target = targets.find(area => area.id === source.id && !used.has(area.id)); if (target) { mapping.set(source.id, target.id); used.add(target.id); } });
    sources.filter(source => !mapping.has(source.id)).forEach(source => { const target = targets.find(area => area.role && area.role === source.role && !used.has(area.id)); if (target) { mapping.set(source.id, target.id); used.add(target.id); } });
    if (sources.length === 1 && targets.length === 1 && !mapping.has(sources[0].id)) mapping.set(sources[0].id, targets[0].id);
    if (!mapping.has(app.area.id)) mapping.set(app.area.id, targetProduct.editor.defaultAreaId);
    return mapping;
  }
  function transferredDraft(targetProduct) {
    const sourceProduct = app.product, sourceSize = app.size, targetSize = selectedItem(targetProduct.sizes, targetProduct.sizes?.[0]?.id);
    const draft = createDraft(targetProduct), mapping = areaMapping(sourceProduct, targetProduct);
    mapping.forEach((targetId, sourceId) => {
      const sourceArea = sourceProduct.editor.printAreas.find(area => area.id === sourceId), targetArea = targetProduct.editor.printAreas.find(area => area.id === targetId);
      if (!sourceArea || !targetArea) return;
      draft.areas[targetId].objects = transferredModels(app.design.areas[sourceId]?.objects || [], sourceArea, sourceSize, targetArea, targetSize);
    });
    draft.activeAreaId = mapping.get(app.area.id) || targetProduct.editor.defaultAreaId; return draft;
  }
  function productNeedsManualReview(sourceProduct, targetProduct) {
    if (sourceProduct.editor.printAreas.length !== targetProduct.editor.printAreas.length) return true;
    const mapping = areaMapping(sourceProduct, targetProduct);
    if (mapping.size !== sourceProduct.editor.printAreas.length) return true;
    return [...mapping].some(([sourceId, targetId]) => {
      const sourceArea = sourceProduct.editor.printAreas.find(area => area.id === sourceId), targetArea = targetProduct.editor.printAreas.find(area => area.id === targetId);
      const sourceZone = resolveZone(sourceArea, app.size), targetSize = selectedItem(targetProduct.sizes, targetProduct.sizes?.[0]?.id), targetZone = resolveZone(targetArea, targetSize);
      const sourceRatio = Number(sourceZone?.widthCm) / Number(sourceZone?.heightCm), targetRatio = Number(targetZone?.widthCm) / Number(targetZone?.heightCm);
      const widthChange = Number(targetZone?.widthCm) / Number(sourceZone?.widthCm), heightChange = Number(targetZone?.heightCm) / Number(sourceZone?.heightCm);
      return ![sourceRatio, targetRatio, widthChange, heightChange].every(Number.isFinite)
        || Math.abs((targetRatio / sourceRatio) - 1) > 0.15 || widthChange < 0.75 || widthChange > 1.25 || heightChange < 0.75 || heightChange > 1.25;
    });
  }
  async function renderActiveProduct() {
    renderProductSummary(); renderColors(); renderSizes(); renderAreas(); renderAreaCopyActions(); renderProductSelector();
    await loadMockup(app.area, app.color); await restoreArea(app.area.id); revealStage(); renderToolPanel(app.activeTool);
  }
  async function swapProduct(productId) {
    const target = app.products.get(productId); if (!target || target.id === app.product.id || app.historyRestoring) return;
    const source = app.product, needsReview = productNeedsManualReview(source, target); storeActiveDraft(); const existing = app.drafts[target.id];
    const nextDraft = existing ? clone(existing) : transferredDraft(target);
    app.drafts[source.id] = activeDraftSnapshot(); app.drafts[target.id] = nextDraft; activateDraft(target, nextDraft);
    await renderActiveProduct(); commitDesign(); captureHistory();
    if (needsReview) showNotice("تختلف مناطق الطباعة في المنتج الجديد. راجع موضع التصميم واضبطه يدويًا عند الحاجة.", "warning");
    elements.productSwapPanel.hidden = true; elements.swapProductToggle.setAttribute("aria-expanded", "false");
  }
  function renderProductSelector() {
    if (!elements.productSwapOptions) return; elements.productSwapOptions.replaceChildren();
    elements.productSwapPanel.classList.toggle("is-limited", app.products.size <= 1);
    app.products.forEach(product => {
      const button = document.createElement("button"); button.type = "button"; button.className = `studio-product-choice${product.id === app.product.id ? " is-active" : ""}`;
      button.disabled = product.id === app.product.id; button.setAttribute("role", "listitem");
      const image = document.createElement("img"); image.src = product.thumbnail; image.alt = "";
      const label = document.createElement("span"); label.textContent = product.name; button.append(image, label);
      button.addEventListener("click", () => { void swapProduct(product.id); }); elements.productSwapOptions.appendChild(button);
    });
    if (app.products.size <= 1) {
      const state = document.createElement("p"); state.className = "studio-product-limited";
      state.innerHTML = '<i class="bi bi-info-circle" aria-hidden="true"></i><span>لا تتوفر منتجات أخرى مهيأة للاستوديو حاليًا.</span>';
      elements.productSwapOptions.appendChild(state);
    }
  }
  function setupProductSwap() {
    elements.swapProductToggle?.addEventListener("click", () => { const opening = elements.productSwapPanel.hidden; elements.productSwapPanel.hidden = !opening; elements.swapProductToggle.setAttribute("aria-expanded", String(opening)); });
    elements.closeProductSwap?.addEventListener("click", () => { elements.productSwapPanel.hidden = true; elements.swapProductToggle.setAttribute("aria-expanded", "false"); elements.swapProductToggle.focus(); });
  }

  function getZoneRect() {
    const root = elements.coordinateSystem.getBoundingClientRect(), zone = elements.printZone.getBoundingClientRect(), scale = app.stageScale || 1;
    return { left: (zone.left - root.left) / scale, top: (zone.top - root.top) / scale, width: zone.width / scale, height: zone.height / scale };
  }
  function updatePrintZoneFeedback() {
    if (!app.canvas || !app.geometryReady) return;
    const zone = getZoneRect(), tolerance = 0.75;
    let outsideCount = 0;
    app.canvas.getObjects().forEach(object => {
      object.setCoords();
      const bounds = object.getBoundingRect();
      const outside = bounds.left < zone.left - tolerance || bounds.top < zone.top - tolerance
        || bounds.left + bounds.width > zone.left + zone.width + tolerance
        || bounds.top + bounds.height > zone.top + zone.height + tolerance;
      outsideCount += outside ? 1 : 0;
      object.studioOutsidePrintZone = outside;
      object.set({ borderColor: outside ? "#dc3f45" : "#1677ff", cornerColor: outside ? "#dc3f45" : "#1677ff" });
    });
    const hasOverflow = outsideCount > 0;
    const formatConflict = elements.printZone.classList.contains("has-format-conflict");
    elements.printZone.classList.toggle("has-overflow", hasOverflow);
    elements.printZone.setAttribute("aria-label", hasOverflow
      ? `منطقة الطباعة — ${outsideCount} عنصر خارج الحدود جزئيًا`
      : formatConflict ? "منطقة الطباعة — ملاءمة A4 تحتاج إلى تأكيد" : "منطقة الطباعة");
    elements.printZone.title = hasOverflow ? "الأجزاء الواقعة خارج الحدود لن تدخل في ملف الطباعة."
      : formatConflict ? "المساحة الآمنة فوق الجيب لا تسمح بعرض ارتفاع A4 كاملًا. يلزم تأكيد المطبعة." : "";
    if (elements.printZoneLabelText) elements.printZoneLabelText.textContent = hasOverflow ? "تجاوز حدود الطباعة"
      : formatConflict ? "A4 بحاجة تأكيد" : "منطقة الطباعة";
  }
  function createPrintAreaExport(multiplier = 1) {
    if (!app.canvas || !app.geometryReady) return null;
    const zone = getZoneRect(), scale = Math.max(0.1, Number(multiplier) || 1);
    app.canvas.renderAll();
    return app.canvas.toCanvasElement(scale, { left: zone.left, top: zone.top, width: zone.width, height: zone.height });
  }
  function objectToModel(object, zone) {
    const common = { id: object.studioId, kind: object.studioKind, x: (object.left - zone.left) / zone.width, y: (object.top - zone.top) / zone.height,
      angle: Number(object.angle) || 0, flipX: Boolean(object.flipX), flipY: Boolean(object.flipY) };
    if (object.studioKind === "image") return { ...common, assetId: object.assetId, width: object.getScaledWidth() / zone.width, height: object.getScaledHeight() / zone.height };
    if (object.studioKind === "graphic") return { ...common, graphicId: object.graphicId, color: object.graphicColor || null,
      width: object.getScaledWidth() / zone.width, height: object.getScaledHeight() / zone.height };
    return { ...common, text: object.text || "", width: object.width / zone.width, fontSize: object.fontSize / zone.height, scaleX: object.scaleX, scaleY: object.scaleY,
      fontFamily: object.fontFamily, fill: object.fill, fontWeight: object.fontWeight, fontStyle: object.fontStyle, textAlign: object.textAlign,
      charSpacing: object.charSpacing, lineHeight: object.lineHeight };
  }
  function saveActiveArea() {
    if (!app.canvas || !app.area || app.suppressCanvasEvents || !app.geometryReady) return;
    const zone = app.viewportZone || getZoneRect();
    app.design.areas[app.area.id].objects = app.canvas.getObjects().map(object => objectToModel(object, zone));
  }
  function configureObject(object, model) {
    object.set({ originX: "center", originY: "center", studioId: model.id, studioKind: model.kind, transparentCorners: false,
      cornerStyle: "circle", cornerColor: "#1677ff", borderColor: "#1677ff", cornerSize: 10 });
    if (model.kind === "graphic") {
      object.set({ lockScalingFlip: true });
      // Fabric's Shift key normally toggles proportional scaling. Graphics stay proportional.
      const equalScale = (event, transform, x, y) => {
        const canvas = transform.target.canvas, previous = canvas.uniformScaling;
        canvas.uniformScaling = true;
        try {
          const scaleEvent = { ...event, [canvas.uniScaleKey || "shiftKey"]: false };
          return fabric.controlsUtils.scalingEqually(scaleEvent, transform, x, y);
        } finally { canvas.uniformScaling = previous; }
      };
      object.controls = { ...object.controls };
      ["tl", "tr", "bl", "br"].forEach(key => {
        object.controls[key] = new fabric.Control({ ...object.controls[key], actionHandler: equalScale });
      });
      object.setControlsVisibility({ mt: false, mb: false, ml: false, mr: false });
    }
    object.setCoords(); return object;
  }

  function recolorGraphic(image, color) {
    const Filter = fabric.filters?.BlendColor || fabric.Image?.filters?.BlendColor;
    if (!Filter) throw new Error("Fabric one-color filter is unavailable");
    image.filters = [new Filter({ color, mode: "tint", alpha: 1 })];
    image.applyFilters(); image.graphicColor = color;
  }

  async function assetUrl(assetId) {
    if (app.objectUrls.has(assetId)) return app.objectUrls.get(assetId);
    const blob = await app.assetStore.get(assetId); if (!blob) return null;
    const url = URL.createObjectURL(blob); app.objectUrls.set(assetId, url); return url;
  }
  async function loadFont(family) {
    if (!family || family === "Cairo") return document.fonts?.load(`16px "${family || "Cairo"}"`) || Promise.resolve();
    if (app.fontPromises.has(family)) return app.fontPromises.get(family);
    const promise = new Promise(resolve => {
      const link = document.createElement("link"); link.rel = "stylesheet";
      link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(family).replace(/%20/g, "+")}&display=swap`;
      link.onload = () => { document.fonts?.load(`16px "${family}"`).then(resolve).catch(resolve); }; link.onerror = resolve; document.head.appendChild(link);
    });
    app.fontPromises.set(family, promise); return promise;
  }
  async function modelToObject(model, zone) {
    const common = { left: zone.left + model.x * zone.width, top: zone.top + model.y * zone.height, angle: model.angle, flipX: model.flipX, flipY: model.flipY };
    if (model.kind === "image") {
      const url = await assetUrl(model.assetId); if (!url) return null;
      const ImageClass = fabric.FabricImage || fabric.Image, image = await ImageClass.fromURL(url, {}, common);
      image.set({ scaleX: (model.width * zone.width) / image.width, scaleY: (model.height * zone.height) / image.height, assetId: model.assetId });
      return configureObject(image, model);
    }
    if (model.kind === "graphic") {
      const record = APPROVED_GRAPHICS.get(model.graphicId); if (!record) return null;
      const image = await window.PALPRINTS_STUDIO_SVG.create(record, common);
      if (!image.width || !image.height) return null;
      const scale = (model.width * zone.width) / image.width;
      image.set({ scaleX: scale, scaleY: scale, graphicId: record.id });
      if (record.recolorable) recolorGraphic(image, model.color || record.defaultColor);
      return configureObject(image, model);
    }
    await loadFont(model.fontFamily || "Cairo");
    return configureObject(new fabric.Textbox(model.text || "", { ...common, width: Math.max(30, model.width * zone.width), fontSize: Math.max(8, model.fontSize * zone.height),
      scaleX: model.scaleX || 1, scaleY: model.scaleY || 1, fontFamily: model.fontFamily || "Cairo", fill: model.fill || "#0b1f3a",
      fontWeight: model.fontWeight || "normal", fontStyle: model.fontStyle || "normal", textAlign: model.textAlign || "center",
      charSpacing: model.charSpacing || 0, lineHeight: model.lineHeight || 1.2, direction: /[\u0600-\u06ff]/.test(model.text || "") ? "rtl" : "ltr" }), model);
  }
  async function restoreArea(areaId) {
    const token = ++app.areaSwitchToken, models = app.design.areas[areaId]?.objects || [];
    await Promise.all([...new Set(models.filter(item => item.kind === "text").map(item => item.fontFamily).filter(Boolean))].map(loadFont));
    const zone = getZoneRect(), objects = (await Promise.all(models.map(model => modelToObject(model, zone)))).filter(Boolean);
    if (token !== app.areaSwitchToken) return;
    app.suppressCanvasEvents = true; app.canvas.clear(); objects.forEach(object => app.canvas.add(object)); app.canvas.discardActiveObject(); app.canvas.requestRenderAll(); app.suppressCanvasEvents = false;
    clearSnapGuides(); updateSelectedObjectControls(); updatePrintZoneFeedback();
    app.viewportZone = { ...zone };
  }
  function reflowObjects(models) {
    const zone = getZoneRect(), byId = new Map(models.map(model => [model.id, model]));
    app.canvas.getObjects().forEach(object => {
      const model = byId.get(object.studioId); if (!model) return;
      const values = { left: zone.left + model.x * zone.width, top: zone.top + model.y * zone.height, angle: model.angle };
      if (model.kind === "image") Object.assign(values, { scaleX: (model.width * zone.width) / object.width, scaleY: (model.height * zone.height) / object.height });
      else if (model.kind === "graphic") Object.assign(values, { scaleX: (model.width * zone.width) / object.width, scaleY: (model.width * zone.width) / object.width });
      else Object.assign(values, { width: Math.max(30, model.width * zone.width), fontSize: Math.max(8, model.fontSize * zone.height), scaleX: model.scaleX, scaleY: model.scaleY });
      object.set(values); object.setCoords();
    }); app.canvas.requestRenderAll(); app.viewportZone = { ...zone }; updatePrintZoneFeedback();
  }

  function uniformFitObjects(models, oldZone) {
    const nextZone = getZoneRect(), byId = new Map(models.map(model => [model.id, model]));
    const fit = Math.min(nextZone.width / oldZone.width, nextZone.height / oldZone.height);
    app.canvas.getObjects().forEach(object => {
      const model = byId.get(object.studioId); if (!model) return;
      object.set({ left: nextZone.left + model.x * nextZone.width, top: nextZone.top + model.y * nextZone.height,
        scaleX: object.scaleX * fit, scaleY: object.scaleY * fit });
      object.setCoords();
    });
    app.canvas.requestRenderAll(); app.viewportZone = { ...nextZone }; updatePrintZoneFeedback();
  }

  function initializeLogicalStage() {
    if (app.logicalStage) return;
    app.logicalStage = { width: elements.stage.clientWidth, height: elements.stage.clientHeight };
    elements.coordinateSystem.style.width = `${app.logicalStage.width}px`;
    elements.coordinateSystem.style.height = `${app.logicalStage.height}px`;
  }
  function updateStageTransform() {
    if (!app.logicalStage) return;
    const width = elements.stage.clientWidth, height = elements.stage.clientHeight;
    const baseScale = Math.min(width / app.logicalStage.width, height / app.logicalStage.height);
    const scale = baseScale * app.viewZoom;
    const offsetX = (width - app.logicalStage.width * scale) / 2, offsetY = (height - app.logicalStage.height * scale) / 2;
    app.baseStageScale = baseScale; app.stageScale = scale;
    elements.coordinateSystem.style.transform = `translate3d(${offsetX}px, ${offsetY}px, 0) scale(${scale})`;
    refreshCanvasResolution();
    app.canvas?.calcOffset();
  }
  function updateZoomControls() {
    if (!elements.zoomValue) return;
    elements.zoomValue.textContent = `${Math.round(app.viewZoom * 100)}%`;
    elements.zoomOut.disabled = app.viewZoom <= VIEW_ZOOM.min + 0.001;
    elements.zoomIn.disabled = app.viewZoom >= VIEW_ZOOM.max - 0.001;
    elements.zoomFit.setAttribute("aria-pressed", String(Math.abs(app.viewZoom - 1) < 0.001));
  }
  function setViewZoom(value, shouldAnnounce = true) {
    const clamped = Math.min(VIEW_ZOOM.max, Math.max(VIEW_ZOOM.min, Number(value) || 1));
    app.viewZoom = Math.round(clamped * 10) / 10;
    updateStageTransform(); updateZoomControls(); clearSnapGuides();
    if (shouldAnnounce) announce(`نسبة عرض مساحة التصميم ${Math.round(app.viewZoom * 100)} بالمئة`);
  }
  function setupZoomControls() {
    elements.zoomOut?.addEventListener("click", () => setViewZoom(app.viewZoom - VIEW_ZOOM.step));
    elements.zoomIn?.addEventListener("click", () => setViewZoom(app.viewZoom + VIEW_ZOOM.step));
    elements.zoomFit?.addEventListener("click", () => setViewZoom(1));
    elements.coordinateSystem?.addEventListener("transitionend", event => {
      if (event.propertyName === "transform") app.canvas?.calcOffset();
    });
    updateZoomControls();
  }
  function refreshCanvasResolution() {
    if (!app.canvas) return;
    const ratio = Math.max(1, (window.devicePixelRatio || 1) * (app.stageScale || 1));
    if (Math.abs((app.renderPixelRatio || 0) - ratio) < 0.01) return;
    app.renderPixelRatio = ratio;
    app.canvas.setDimensions(app.logicalStage);
    app.canvas.getObjects().forEach(object => object.set({ dirty: true }));
    app.canvas.requestRenderAll();
  }
  function measureVisibleBounds() {
    const configured = app.area?.visibleBounds;
    if (configured && ["left", "top", "right", "bottom"].every(key => Number.isFinite(Number(configured[key])))
      && configured.left >= 0 && configured.top >= 0 && configured.right <= 1 && configured.bottom <= 1
      && configured.right > configured.left && configured.bottom > configured.top) return configured;
    const image = elements.productMockup, scale = Math.min(1, 512 / Math.max(image.naturalWidth, image.naturalHeight));
    const width = Math.max(1, Math.round(image.naturalWidth * scale)), height = Math.max(1, Math.round(image.naturalHeight * scale));
    const canvas = document.createElement("canvas"), context = canvas.getContext("2d", { willReadFrequently: true });
    if (!context) return { left: 0, top: 0, right: 1, bottom: 1 }; canvas.width = width; canvas.height = height;
    try {
      context.drawImage(image, 0, 0, width, height); const pixels = context.getImageData(0, 0, width, height).data; let minX = width, minY = height, maxX = -1, maxY = -1;
      for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) if (pixels[((y * width) + x) * 4 + 3] > 24) { minX = Math.min(minX, x); minY = Math.min(minY, y); maxX = Math.max(maxX, x); maxY = Math.max(maxY, y); }
      if (maxX >= minX) return { left: minX / width, top: minY / height, right: (maxX + 1) / width, bottom: (maxY + 1) / height };
    } catch (error) { /* cross-origin fallback */ }
    return { left: 0, top: 0, right: 1, bottom: 1 };
  }
  function fitProductToStage() {
    const image = elements.productMockup; if (!image.naturalWidth || !image.naturalHeight) return false;
    const style = getComputedStyle(elements.stage), stageWidth = app.logicalStage.width, stageHeight = app.logicalStage.height;
    const availableWidth = stageWidth - parseFloat(style.paddingInlineStart) - parseFloat(style.paddingInlineEnd);
    const bounds = measureVisibleBounds();
    const visibleWidth = image.naturalWidth * (bounds.right - bounds.left), visibleHeight = image.naturalHeight * (bounds.bottom - bounds.top);
    const desktop = matchMedia("(min-width: 901px)").matches;
    const targetHeight = stageHeight * (desktop ? DESKTOP_PRODUCT_HEIGHT_RATIO : COMPACT_PRODUCT_HEIGHT_RATIO);
    const scale = Math.min(targetHeight / visibleHeight, availableWidth / visibleWidth);
    const offsetX = ((image.naturalWidth / 2) - ((bounds.left + bounds.right) / 2) * image.naturalWidth) * scale;
    const offsetY = ((image.naturalHeight / 2) - ((bounds.top + bounds.bottom) / 2) * image.naturalHeight) * scale;
    const width = `${Math.floor(image.naturalWidth * scale)}px`, height = `${Math.floor(image.naturalHeight * scale)}px`;
    const left = `calc(50% + ${offsetX}px)`, top = `calc(50% + ${offsetY}px)`;
    elements.productCanvas.style.setProperty("--studio-product-ratio", `${image.naturalWidth} / ${image.naturalHeight}`);
    elements.productCanvas.style.width = width; elements.productCanvas.style.height = height;
    elements.productCanvas.style.left = left; elements.productCanvas.style.top = top;
    app.geometryReady = true; return true;
  }
  function waitForMockup(image) {
    if (image.complete && image.naturalWidth > 0) return Promise.resolve();
    return new Promise((resolve, reject) => {
      const loaded = () => { cleanup(); resolve(); }, failed = () => { cleanup(); reject(new Error("Mockup failed to load.")); };
      const cleanup = () => { image.removeEventListener("load", loaded); image.removeEventListener("error", failed); };
      image.addEventListener("load", loaded, { once: true }); image.addEventListener("error", failed, { once: true });
      // A cached/local image can finish between the initial check and listener registration.
      if (image.complete) (image.naturalWidth > 0 ? loaded : failed)();
    });
  }
  async function loadMockup(area, color) {
    elements.stage.classList.add("is-loading"); elements.stage.setAttribute("aria-busy", "true"); app.geometryReady = false;
    await prepareMockup(area, color);
    const mockup = resolveMockup(area, color);
    elements.productMockup.src = mockup; elements.productMockup.alt = `${app.product.studioTitle || app.product.name} — ${area.name}`;
    const zone = resolveZone(area, app.size); applyZoneToOverlay(zone);
    await waitForMockup(elements.productMockup);
    fitProductToStage(); updateStageTransform();
  }
  function revealStage() { elements.stage.classList.remove("is-loading"); elements.stage.setAttribute("aria-busy", "false"); }
  async function switchArea(areaId, initial = false) {
    const next = app.product.editor.printAreas.find(area => area.id === areaId); if (!next) return;
    if (!initial) { saveActiveArea(); commitDesign(); }
    app.canvas.discardActiveObject(); app.area = next; app.design.activeAreaId = next.id; elements.workspaceEyebrow.textContent = next.name;
    document.querySelectorAll("[data-area-id]").forEach(button => { const active = button.dataset.areaId === next.id; button.classList.toggle("is-active", active); button.setAttribute("aria-selected", String(active)); });
    await loadMockup(next, app.color); await restoreArea(next.id); revealStage(); renderToolPanel(app.activeTool); renderAreaCopyActions(); announce(`منطقة الطباعة الحالية: ${next.name}`);
  }

  function renderColors() {
    elements.colorOptions.innerHTML = ""; app.product.colors.forEach(color => {
      const button = document.createElement("button"); button.type = "button"; button.className = `studio-swatch${color.id === app.color.id ? " is-active" : ""}`;
      button.style.backgroundColor = color.value || "#fff"; button.setAttribute("aria-label", color.name); button.title = color.name;
      button.addEventListener("click", async () => {
        if (color.id === app.color.id) return; saveActiveArea(); const models = app.design.areas[app.area.id].objects;
        app.color = color; elements.summaryColor.textContent = color.name; elements.selectedColorName.textContent = color.name; renderColors();
        await loadMockup(app.area, color); reflowObjects(models); saveActiveArea(); commitDesign(); captureHistory(); revealStage();
      }); elements.colorOptions.appendChild(button);
    });
  }
  function renderSizes() {
    elements.sizeOptions.innerHTML = ""; app.product.sizes.forEach(size => {
      const button = document.createElement("button"); button.type = "button"; button.className = `studio-size${size.id === app.size.id ? " is-active" : ""}`; button.textContent = size.name;
      button.addEventListener("click", () => {
        if (size.id === app.size.id) return; const oldZone = resolveZone(app.area, app.size), nextZone = resolveZone(app.area, size);
        saveActiveArea(); const oldZoneRect = app.viewportZone || getZoneRect(), models = app.design.areas[app.area.id].objects, needsFit = zonesDiffer(oldZone, nextZone);
        app.size = size; elements.summarySize.textContent = size.name; renderSizes();
        if (needsFit) showNotice("تغيّر مقاس منطقة الطباعة لهذا المقاس، وتمت ملاءمة التصميم تناسبيًا دون تشويه.", "warning");
        applyZoneToOverlay(nextZone);
        if (needsFit) uniformFitObjects(models, oldZoneRect); else reflowObjects(models); saveActiveArea(); commitDesign(); captureHistory(); renderToolPanel(app.activeTool);
      }); elements.sizeOptions.appendChild(button);
    });
  }
  function renderAreas() {
    elements.areaOptions.innerHTML = ""; const areas = app.product.editor.printAreas; elements.areaCount.textContent = `${areas.length} ${areas.length === 1 ? "جهة" : "جهات"}`;
    areas.forEach(area => {
      const button = document.createElement("button"); button.type = "button"; button.className = `studio-area${area.id === app.area.id ? " is-active" : ""}`;
      button.dataset.areaId = area.id; button.setAttribute("role", "tab"); button.setAttribute("aria-selected", String(area.id === app.area.id));
      button.innerHTML = `<i class="${area.icon || "bi bi-bounding-box"}" aria-hidden="true"></i><span></span>`; button.querySelector("span").textContent = area.name;
      button.addEventListener("click", () => { void switchArea(area.id); }); elements.areaOptions.appendChild(button);
    });
  }
  function setActiveTool(name) {
    app.activeTool = name; document.querySelectorAll("[data-studio-tool]").forEach(tab => { const active = tab.dataset.studioTool === name; tab.classList.toggle("is-active", active); tab.setAttribute("aria-selected", String(active)); });
    elements.toolPanel.setAttribute("aria-labelledby", `${name}Tab`); renderToolPanel(name);
  }
  function setupToolTabs() { document.querySelectorAll("[data-studio-tool]").forEach(tab => tab.addEventListener("click", () => setActiveTool(tab.dataset.studioTool))); }
  function createEmptyState(icon, title, copy) {
    const box = document.createElement("div"); box.className = "studio-tool-empty"; box.innerHTML = `<div><i class="bi ${icon}" aria-hidden="true"></i><strong></strong><span></span></div>`;
    box.querySelector("strong").textContent = title; box.querySelector("span").textContent = copy; return box;
  }

  function uploadFingerprint(file, mimeType) {
    return [file.name, file.size, file.lastModified || 0, mimeType].join("::");
  }
  function hasUploadedFile(file, mimeType, fingerprint) {
    return app.assets.some(asset => asset.fingerprint === fingerprint
      || (!asset.fingerprint && asset.name === file.name && asset.size === file.size && asset.mimeType === mimeType));
  }

  async function sanitizeUploadedSvg(file) {
    const input = await file.text();
    if (input.length > 2000000 || /<!DOCTYPE|<!ENTITY/i.test(input)) throw new Error("Unsafe SVG declaration");
    const parsed = new DOMParser().parseFromString(input, "image/svg+xml");
    const root = parsed.documentElement;
    if (root.localName !== "svg" || root.namespaceURI !== "http://www.w3.org/2000/svg" || parsed.querySelector("parsererror")) {
      throw new Error("Invalid SVG upload");
    }
    const forbidden = new Set(["foreignObject", "iframe", "object", "embed", "audio", "video", "image", "animate", "animateMotion", "animateTransform", "set"]);
    const safeReference = value => {
      if (/javascript:|data:|https?:|file:|@import|expression\s*\(/i.test(value)) return false;
      for (const match of value.matchAll(/url\s*\(\s*([^)]*)\)/gi)) {
        if (!/^#[A-Za-z_][\w.-]*$/.test(match[1].trim().replace(/^['"]|['"]$/g, ""))) return false;
      }
      return true;
    };
    const walk = node => {
      for (const child of [...node.childNodes]) {
        if (child.nodeType === Node.PROCESSING_INSTRUCTION_NODE) throw new Error("SVG processing instruction rejected");
        if (child.nodeType !== Node.ELEMENT_NODE) continue;
        if (child.localName === "script" || child.localName === "metadata" || child.localName === "namedview") { child.remove(); continue; }
        if (child.namespaceURI !== "http://www.w3.org/2000/svg" || forbidden.has(child.localName)) {
          throw new Error(`Unsafe SVG element: ${child.localName}`);
        }
        for (const attribute of [...child.attributes]) {
          if (attribute.namespaceURI === "http://www.w3.org/2000/xmlns/") continue;
          if (/^on/i.test(attribute.localName) || attribute.localName === "base") { child.removeAttributeNode(attribute); continue; }
          if (!safeReference(attribute.value) || (["href", "src"].includes(attribute.localName) && !/^#[A-Za-z_][\w.-]*$/.test(attribute.value.trim()))) {
            throw new Error("Unsafe SVG resource reference");
          }
        }
        if (child.localName === "style" && !safeReference(child.textContent)) throw new Error("Unsafe SVG style");
        walk(child);
      }
    };
    walk(parsed);
    for (const attribute of [...root.attributes]) {
      if (attribute.namespaceURI === "http://www.w3.org/2000/xmlns/") continue;
      if (/^on/i.test(attribute.localName) || attribute.localName === "base") root.removeAttributeNode(attribute);
      else if (!safeReference(attribute.value)) throw new Error("Unsafe SVG root attribute");
    }
    const cleaned = new XMLSerializer().serializeToString(parsed);
    if (/<\s*script\b|\s+on[a-z]+\s*=|javascript:|data:/i.test(cleaned)) throw new Error("SVG did not sanitize cleanly");
    return new Blob([cleaned], { type: "image/svg+xml" });
  }

  async function readRasterDimensions(blob) {
    try {
      if (typeof createImageBitmap === "function") {
        const bitmap = await createImageBitmap(blob);
        const result = { pixelWidth: bitmap.width, pixelHeight: bitmap.height, sourceType: "raster" };
        bitmap.close?.(); return result;
      }
      const url = URL.createObjectURL(blob), image = new Image();
      try {
        await new Promise((resolve, reject) => { image.onload = resolve; image.onerror = reject; image.src = url; });
        return { pixelWidth: image.naturalWidth, pixelHeight: image.naturalHeight, sourceType: "raster" };
      } finally { URL.revokeObjectURL(url); }
    } catch (error) { return { pixelWidth: null, pixelHeight: null, sourceType: "raster" }; }
  }

  async function handleUploads(files) {
    let changed = false;
    for (const file of files) {
      const extension = file.name.split(".").pop()?.toLowerCase(), mime = file.type || (extension === "svg" ? "image/svg+xml" : extension === "png" ? "image/png" : "image/jpeg");
      if (!ALLOWED_UPLOADS.has(mime) || !["png", "jpg", "jpeg", "svg"].includes(extension)) { showNotice(`الملف «${file.name}» غير مدعوم.`, "error"); continue; }
      const fingerprint = uploadFingerprint(file, mime);
      if (app.pendingAssetFingerprints.has(fingerprint) || hasUploadedFile(file, mime, fingerprint)) continue;
      app.pendingAssetFingerprints.add(fingerprint);
      try {
        let blob = file;
        if (extension === "svg" || mime === "image/svg+xml") {
          try { blob = await sanitizeUploadedSvg(file); }
          catch (error) { showNotice(`ملف SVG «${file.name}» يحتوي على محتوى غير آمن.`, "error"); continue; }
        }
        const assetId = uid("asset"); await app.assetStore.put(assetId, blob);
        if (!hasUploadedFile(file, mime, fingerprint)) {
          const dimensions = mime === "image/svg+xml" ? { sourceType: "vector" } : await readRasterDimensions(blob);
          app.assets.push({ assetId, name: file.name, mimeType: mime, size: file.size, lastModified: file.lastModified || 0, fingerprint,
            pixelWidth: dimensions.pixelWidth || null, pixelHeight: dimensions.pixelHeight || null, sourceType: dimensions.sourceType, createdAt: new Date().toISOString() });
          changed = true;
        }
      } finally {
        app.pendingAssetFingerprints.delete(fingerprint);
      }
    }
    if (changed) commitDesign();
    if (app.activeTool === "upload") renderToolPanel("upload");
  }
  function assetUsageCount(assetId) { storeActiveDraft(); return Object.values(app.drafts).reduce((total, draft) => total
    + Object.values(draft.areas).reduce((count, area) => count + area.objects.filter(object => object.kind === "image" && object.assetId === assetId).length, 0), 0); }
  function confirmReferencedAssetDeletion(asset, usage, trigger) {
    if (elements.deleteDialog.open) return Promise.resolve(false);
    return new Promise(resolve => {
      const dialog = elements.deleteDialog; let settled = false;
      const finish = confirmed => {
        if (settled) return; settled = true;
        elements.cancelAssetDelete.removeEventListener("click", cancel); elements.confirmAssetDelete.removeEventListener("click", confirmDelete);
        dialog.removeEventListener("cancel", cancelEvent); dialog.removeEventListener("click", backdropClick);
        if (dialog.open) dialog.close(); else dialog.removeAttribute("open");
        trigger?.focus?.(); resolve(confirmed);
      };
      const cancel = () => finish(false), confirmDelete = () => finish(true);
      const cancelEvent = event => { event.preventDefault(); finish(false); };
      const backdropClick = event => { if (event.target === dialog) finish(false); };
      elements.deleteMessage.textContent = usage === 1
        ? "هذه الصورة مستخدمة في التصميم مرة واحدة. حذفها سيزيلها من التصميم أيضًا."
        : `هذه الصورة مستخدمة في التصميم ${usage} مرات. حذفها سيزيل جميع استخداماتها.`;
      elements.deleteThumbnail.alt = asset.name || "الصورة المراد حذفها"; elements.deleteThumbnail.removeAttribute("src");
      void assetUrl(asset.assetId).then(url => { if (!settled && url) elements.deleteThumbnail.src = url; });
      elements.cancelAssetDelete.addEventListener("click", cancel); elements.confirmAssetDelete.addEventListener("click", confirmDelete);
      dialog.addEventListener("cancel", cancelEvent); dialog.addEventListener("click", backdropClick);
      if (typeof dialog.showModal === "function") dialog.showModal(); else dialog.setAttribute("open", "");
      elements.cancelAssetDelete.focus();
    });
  }
  async function deleteAsset(asset, trigger) {
    saveActiveArea(); const usage = assetUsageCount(asset.assetId);
    if (usage && !await confirmReferencedAssetDeletion(asset, usage, trigger)) return;
    Object.values(app.drafts).forEach(draft => Object.values(draft.areas).forEach(area => { area.objects = area.objects.filter(object => object.assetId !== asset.assetId); }));
    Object.values(app.design.areas).forEach(area => { area.objects = area.objects.filter(object => object.assetId !== asset.assetId); });
    app.canvas.getObjects().filter(object => object.assetId === asset.assetId).forEach(object => app.canvas.remove(object)); app.assets = app.assets.filter(item => item.assetId !== asset.assetId); app.design.assets = app.assets;
    await app.assetStore.delete(asset.assetId); const url = app.objectUrls.get(asset.assetId); if (url) URL.revokeObjectURL(url); app.objectUrls.delete(asset.assetId); commitDesign();
    if (app.activeTool === "upload") renderToolPanel("upload");
  }
  async function addAssetToCanvas(asset) {
    const url = await assetUrl(asset.assetId); if (!url) { showNotice("تعذر استعادة ملف الصورة.", "error"); return; }
    const ImageClass = fabric.FabricImage || fabric.Image, image = await ImageClass.fromURL(url), zone = getZoneRect();
    if (asset.sourceType !== "vector" && (!asset.pixelWidth || !asset.pixelHeight)) {
      asset.pixelWidth = image.getElement?.()?.naturalWidth || image.width || null;
      asset.pixelHeight = image.getElement?.()?.naturalHeight || image.height || null;
      asset.sourceType = "raster"; commitDesign();
    }
    const scale = Math.min((zone.width * 0.55) / image.width, (zone.height * 0.55) / image.height);
    configureObject(image, { id: uid("object"), kind: "image" }); image.set({ left: zone.left + zone.width / 2, top: zone.top + zone.height / 2, scaleX: scale, scaleY: scale, assetId: asset.assetId });
    app.canvas.add(image); app.canvas.setActiveObject(image); image.setCoords(); app.canvas.requestRenderAll(); scheduleCommit();
    if (app.activeTool !== "upload") setActiveTool("upload");
  }
  function imageQuality(object) {
    if (!object || object.studioKind !== "image") return null;
    const asset = app.assets.find(item => item.assetId === object.assetId);
    if (!asset || asset.sourceType === "vector" || !asset.pixelWidth || !asset.pixelHeight) return null;
    const zone = resolveZone(app.area, app.size), viewport = app.viewportZone || getZoneRect();
    if (!zone?.widthCm || !zone?.heightCm || !viewport.width || !viewport.height) return null;
    const widthCm = Math.abs(object.getScaledWidth() / viewport.width) * zone.widthCm;
    const heightCm = Math.abs(object.getScaledHeight() / viewport.height) * zone.heightCm;
    if (!widthCm || !heightCm) return null;
    const dpiX = asset.pixelWidth / (widthCm / 2.54), dpiY = asset.pixelHeight / (heightCm / 2.54), dpi = Math.min(dpiX, dpiY);
    const state = dpi >= IMAGE_QUALITY_THRESHOLDS.excellent ? "excellent" : dpi >= IMAGE_QUALITY_THRESHOLDS.good ? "good" : "low";
    return { dpiX, dpiY, dpi, state, asset };
  }
  function qualityNotice(object) {
    const quality = imageQuality(object); if (!quality) return null;
    const labels = { excellent: "ممتازة", good: "جيدة", low: "جودة منخفضة" };
    const box = document.createElement("div"); box.className = `studio-quality is-${quality.state}`;
    box.innerHTML = `<i class="bi ${quality.state === "low" ? "bi-exclamation-triangle" : "bi-check-circle"}" aria-hidden="true"></i><span><strong>${labels[quality.state]}</strong><small></small></span>`;
    box.querySelector("small").textContent = `${Math.round(quality.dpi)} DPI فعّالة — حدود تقييم مؤقتة`;
    if (quality.state === "low") box.setAttribute("role", "status");
    return box;
  }
  function renderUploadPanel(renderToken) {
    const content = document.createDocumentFragment(), assets = uniqueAssetRecords(app.assets);
    const notice = qualityNotice(app.canvas?.getActiveObject()); if (notice) content.appendChild(notice);
    const uploader = document.createElement("label"); uploader.className = "studio-upload-drop";
    uploader.innerHTML = `<input type="file" accept=".png,.jpg,.jpeg,.svg,image/png,image/jpeg,image/svg+xml" multiple><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i><strong>رفع صورة</strong><span>PNG، JPG، SVG</span>`;
    const input = uploader.querySelector("input"); input.addEventListener("change", () => { void handleUploads([...input.files]); input.value = ""; });
    ["dragenter", "dragover"].forEach(name => uploader.addEventListener(name, event => { event.preventDefault(); uploader.classList.add("is-dragging"); }));
    ["dragleave", "drop"].forEach(name => uploader.addEventListener(name, event => { event.preventDefault(); uploader.classList.remove("is-dragging"); }));
    uploader.addEventListener("drop", event => { void handleUploads([...event.dataTransfer.files]); }); content.appendChild(uploader);
    if (assets.length) {
      const heading = document.createElement("div"); heading.className = "studio-assets-heading"; heading.innerHTML = `<strong>الصور المرفوعة</strong><span>${assets.length}</span>`; content.appendChild(heading);
      const grid = document.createElement("div"); grid.className = "studio-assets-grid";
      for (const asset of assets) {
        const card = document.createElement("div"); card.className = "studio-asset-card";
        card.dataset.assetId = asset.assetId;
        const add = document.createElement("button"); add.type = "button"; add.className = "studio-asset-add"; add.title = `إضافة ${asset.name}`;
        const image = document.createElement("img"); image.alt = asset.name; add.appendChild(image); add.addEventListener("click", () => { void addAssetToCanvas(asset); });
        void assetUrl(asset.assetId).then(url => { if (url) image.src = url; });
        const remove = document.createElement("button"); remove.type = "button"; remove.className = "studio-asset-delete"; remove.setAttribute("aria-label", `حذف ${asset.name}`); remove.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>'; remove.addEventListener("click", () => { void deleteAsset(asset, remove); });
        card.append(add, remove); grid.appendChild(card);
      }
      content.appendChild(grid);
    }
    if (renderToken !== app.panelRenderToken || app.activeTool !== "upload") return;
    elements.toolPanelContent.className = `studio-upload-panel${assets.length ? "" : " is-empty"}`;
    elements.toolPanelContent.replaceChildren(content);
  }

  function activeText() { const object = app.canvas?.getActiveObject(); return object?.studioKind === "text" ? object : null; }
  function applyTextProperty(property, value) { const text = activeText(); if (!text) return; text.set(property, value); text.setCoords(); app.canvas.requestRenderAll(); scheduleCommit(); }
  function fontPicker(selectedFamily) {
    const details = document.createElement("details"); details.className = "studio-font-picker";
    const summary = document.createElement("summary"); summary.textContent = selectedFamily; summary.style.fontFamily = `"${selectedFamily}", sans-serif`;
    const list = document.createElement("div"); list.className = "studio-font-list";
    FONT_FAMILIES.forEach(family => {
      const button = document.createElement("button"); button.type = "button"; button.textContent = family; button.dataset.font = family; button.style.fontFamily = `"${family}", sans-serif`;
      button.addEventListener("click", async () => { await loadFont(family); summary.textContent = family; summary.style.fontFamily = button.style.fontFamily; details.open = false; applyTextProperty("fontFamily", family); }); list.appendChild(button);
    });
    details.addEventListener("toggle", () => {
      if (!details.open) return;
      if (!("IntersectionObserver" in window)) { list.querySelectorAll("button").forEach(button => { void loadFont(button.dataset.font); }); return; }
      const observer = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) void loadFont(entry.target.dataset.font); }), { root: list });
      list.querySelectorAll("button").forEach(button => observer.observe(button));
    }, { once: true }); details.append(summary, list); return details;
  }
  function renderTextPanel() {
    const selected = activeText(); elements.toolPanelContent.innerHTML = ""; elements.toolPanelContent.className = "studio-text-panel";
    const form = document.createElement("div"); form.className = "studio-text-form";
    form.innerHTML = `<label class="studio-field studio-field-wide"><span>النص</span><textarea rows="3" placeholder="اكتب النص هنا"></textarea></label>
      <button type="button" class="studio-add-text"><i class="bi bi-plus-lg" aria-hidden="true"></i> إضافة مربع نص</button>
      <div class="studio-field studio-field-wide"><span>الخط</span><div data-font-picker></div></div>
      <label class="studio-field"><span>الحجم</span><input data-prop="fontSize" type="number" min="8" max="240"></label>
      <label class="studio-field"><span>اللون</span><input data-prop="fill" type="color"></label>
      <div class="studio-text-toggles"><button type="button" data-toggle="bold" aria-label="عريض"><i class="bi bi-type-bold"></i></button><button type="button" data-toggle="italic" aria-label="مائل"><i class="bi bi-type-italic"></i></button></div>
      <div class="studio-alignments"><button type="button" data-align="right" aria-label="يمين"><i class="bi bi-text-right"></i></button><button type="button" data-align="center" aria-label="وسط"><i class="bi bi-text-center"></i></button><button type="button" data-align="left" aria-label="يسار"><i class="bi bi-text-left"></i></button></div>
      <label class="studio-field"><span>تباعد الحروف</span><input data-prop="letterSpacing" type="number" min="-5" max="30" step="0.5"></label>
      <label class="studio-field"><span>ارتفاع السطر</span><input data-prop="lineHeight" type="number" min="0.7" max="3" step="0.1"></label>
      <label class="studio-field studio-field-wide"><span>الدوران</span><input data-prop="angle" type="range" min="-180" max="180" step="1"><output></output></label>`;
    const textarea = form.querySelector("textarea"), fontHost = form.querySelector("[data-font-picker]"); textarea.value = selected?.text || ""; fontHost.appendChild(fontPicker(selected?.fontFamily || "Cairo"));
    form.querySelector('[data-prop="fontSize"]').value = Math.round(selected?.fontSize || 36); form.querySelector('[data-prop="fill"]').value = typeof selected?.fill === "string" && selected.fill.startsWith("#") ? selected.fill : "#0b1f3a";
    form.querySelector('[data-prop="letterSpacing"]').value = selected ? ((selected.charSpacing * selected.fontSize) / 1000).toFixed(1) : 0; form.querySelector('[data-prop="lineHeight"]').value = selected?.lineHeight || 1.2;
    const angle = form.querySelector('[data-prop="angle"]'), output = form.querySelector("output"); angle.value = selected?.angle || 0; output.textContent = `${angle.value}°`;
    if (selected?.fontWeight === "bold") form.querySelector('[data-toggle="bold"]').classList.add("is-active"); if (selected?.fontStyle === "italic") form.querySelector('[data-toggle="italic"]').classList.add("is-active");
    form.querySelector(`[data-align="${selected?.textAlign || "center"}"]`)?.classList.add("is-active"); textarea.addEventListener("input", () => applyTextProperty("text", textarea.value));
    form.querySelector(".studio-add-text").addEventListener("click", async () => {
      const zone = getZoneRect(), value = textarea.value.trim() || "اكتب نصك هنا"; await loadFont("Cairo");
      const text = configureObject(new fabric.Textbox(value, { left: zone.left + zone.width / 2, top: zone.top + zone.height / 2, width: zone.width * 0.62,
        fontSize: Math.max(20, Math.min(44, zone.height * 0.13)), fontFamily: "Cairo", fill: "#0b1f3a", textAlign: "center", lineHeight: 1.2,
        direction: /[\u0600-\u06ff]/.test(value) ? "rtl" : "ltr" }), { id: uid("object"), kind: "text" });
      app.canvas.add(text); app.canvas.setActiveObject(text); app.canvas.requestRenderAll(); scheduleCommit(); renderTextPanel();
    });
    form.querySelector('[data-prop="fontSize"]').addEventListener("change", event => applyTextProperty("fontSize", Number(event.target.value)));
    form.querySelector('[data-prop="fill"]').addEventListener("input", event => applyTextProperty("fill", event.target.value));
    form.querySelector('[data-prop="letterSpacing"]').addEventListener("change", event => { const text = activeText(); if (text) applyTextProperty("charSpacing", Number(event.target.value) * 1000 / text.fontSize); });
    form.querySelector('[data-prop="lineHeight"]').addEventListener("change", event => applyTextProperty("lineHeight", Number(event.target.value)));
    angle.addEventListener("input", event => { output.textContent = `${event.target.value}°`; applyTextProperty("angle", Number(event.target.value)); });
    form.querySelectorAll("[data-toggle]").forEach(button => button.addEventListener("click", () => { const text = activeText(); if (!text) return; const prop = button.dataset.toggle === "bold" ? "fontWeight" : "fontStyle", value = button.dataset.toggle === "bold" ? "bold" : "italic"; applyTextProperty(prop, text[prop] === value ? "normal" : value); renderTextPanel(); }));
    form.querySelectorAll("[data-align]").forEach(button => button.addEventListener("click", () => { applyTextProperty("textAlign", button.dataset.align); renderTextPanel(); }));
    if (!selected) form.querySelectorAll("[data-prop], [data-toggle], [data-align]").forEach(control => { control.disabled = true; control.classList.add("is-disabled"); });
    elements.toolPanelContent.appendChild(form);
  }
  function activeGraphic() { const object = app.canvas?.getActiveObject(); return object?.studioKind === "graphic" ? object : null; }
  async function addGraphicToCanvas(record) {
    if (!app.canvas || !app.geometryReady || !APPROVED_GRAPHICS.has(record.id)) {
      console.warn("Graphic not added", { canvas: Boolean(app.canvas), geometryReady: app.geometryReady, approved: APPROVED_GRAPHICS.has(record.id), id: record.id });
      showNotice(!APPROVED_GRAPHICS.has(record.id) ? "هذا الرسم غير معتمد حاليًا." : "مساحة التصميم لم تجهز بعد، حاول مرة أخرى بعد لحظة.", "error");
      return;
    }
    const areaId = app.area.id, areaToken = app.areaSwitchToken;
    const model = { id: uid("object"), kind: "graphic", graphicId: record.id, color: record.recolorable ? record.defaultColor : null,
      x: 0.5, y: 0.5, width: 0.3, height: 0.4, angle: 0, flipX: false, flipY: false };
    try {
      const image = await window.PALPRINTS_STUDIO_SVG.create(record);
      if (app.area.id !== areaId || app.areaSwitchToken !== areaToken || !app.geometryReady) return;
      if (!image.width || !image.height) throw new Error("Empty graphic");
      const zone = getZoneRect();
      const scale = Math.min((zone.width * 0.45) / image.width, (zone.height * 0.55) / image.height);
      image.set({ left: zone.left + zone.width / 2, top: zone.top + zone.height / 2, scaleX: scale, scaleY: scale,
        graphicId: record.id });
      if (record.recolorable) recolorGraphic(image, record.defaultColor);
      configureObject(image, model); app.canvas.add(image); app.canvas.setActiveObject(image); image.setCoords();
      app.canvas.requestRenderAll(); scheduleCommit(); announce(`${record.nameAr} أضيف إلى التصميم`);
    } catch (error) { console.error(error); showNotice(error.message === "SVG_RASTER_CONTENT"
      ? "هذا الملف يحتوي صورة نقطية غير قابلة للتكبير بجودة عالية ويحتاج مراجعة."
      : "تعذر إضافة الرسم المختار.", "error"); }
  }
  function renderGraphicsPanel() {
    const panel = document.createElement("div"); panel.className = "studio-graphics-panel";
    const selected = activeGraphic(), selectedRecord = selected && APPROVED_GRAPHICS.get(selected.graphicId);
    if (selectedRecord?.recolorable) {
      const colorControl = document.createElement("label"); colorControl.className = "studio-graphic-color-control";
      colorControl.innerHTML = '<span><i class="bi bi-palette" aria-hidden="true"></i> لون الرسم</span><input type="color" aria-label="لون الرسم المحدد">';
      const input = colorControl.querySelector("input"); input.value = selected.graphicColor || selectedRecord.defaultColor || "#000000";
      input.addEventListener("input", () => {
        if (activeGraphic() !== selected) return;
        recolorGraphic(selected, input.value); app.canvas.requestRenderAll(); scheduleCommit();
      });
      panel.appendChild(colorControl);
    }
    const search = document.createElement("label"); search.className = "studio-graphics-search";
    search.innerHTML = '<i class="bi bi-search" aria-hidden="true"></i><input type="search" placeholder="ابحث عن رسم…" aria-label="ابحث في الرسومات">';
    const searchInput = search.querySelector("input"); searchInput.value = app.graphicsSearch; panel.appendChild(search);
    const categories = document.createElement("div"); categories.className = "studio-graphics-categories"; categories.setAttribute("role", "group"); categories.setAttribute("aria-label", "تصنيفات الرسومات");
    const grid = document.createElement("div"); grid.className = "studio-graphics-grid"; grid.setAttribute("aria-live", "polite");
    const more = document.createElement("button"); more.type = "button"; more.className = "studio-graphics-more"; more.textContent = "عرض المزيد";
    const renderGrid = (append = false) => {
      const query = app.graphicsSearch.trim().toLocaleLowerCase();
      const matches = (GRAPHICS_BY_CATEGORY.get(app.graphicsCategory) || []).filter(item =>
        !query || `${item.nameAr} ${item.nameEn}`.toLocaleLowerCase().includes(query));
      const start = append ? grid.querySelectorAll(".studio-graphic-card").length : 0;
      if (!append) grid.replaceChildren();
      for (const record of matches.slice(start, app.graphicsLimit)) {
        const card = document.createElement("button"); card.type = "button"; card.className = "studio-graphic-card";
        card.setAttribute("aria-label", `إضافة ${record.nameAr} إلى التصميم`);
        const thumb = document.createElement("img"); thumb.loading = "lazy"; thumb.decoding = "async"; thumb.src = record.assetPath; thumb.alt = "";
        const caption = document.createElement("span"); caption.textContent = record.nameAr;
        card.append(thumb, caption); card.addEventListener("click", () => { void addGraphicToCanvas(record); }); grid.appendChild(card);
      }
      if (!matches.length) { const empty = document.createElement("p"); empty.className = "studio-graphics-no-results"; empty.textContent = "لا توجد رسومات مطابقة"; grid.appendChild(empty); }
      more.hidden = matches.length <= app.graphicsLimit;
    };
    const renderCategories = () => {
      categories.replaceChildren();
      GRAPHICS.categories.map(category => [category, category]).forEach(([value, label]) => {
        const button = document.createElement("button"); button.type = "button"; button.textContent = label;
        button.className = `studio-graphics-category${app.graphicsCategory === value ? " is-active" : ""}`;
        button.setAttribute("aria-pressed", String(app.graphicsCategory === value));
        button.dataset.category = value;
        button.addEventListener("click", () => {
          app.graphicsCategory = value; app.graphicsLimit = GRAPHICS_PAGE_SIZE;
          categories.querySelectorAll("button").forEach(control => {
            const active = control.dataset.category === value;
            control.classList.toggle("is-active", active); control.setAttribute("aria-pressed", String(active));
          });
          renderGrid();
        }); categories.appendChild(button);
      });
    };
    searchInput.addEventListener("input", () => { app.graphicsSearch = searchInput.value; app.graphicsLimit = GRAPHICS_PAGE_SIZE; renderGrid(); });
    more.addEventListener("click", () => { app.graphicsLimit += GRAPHICS_PAGE_SIZE; renderGrid(true); });
    panel.append(categories, grid, more); renderCategories(); renderGrid();
    elements.toolPanelContent.className = "studio-graphics-content"; elements.toolPanelContent.replaceChildren(panel);
  }
  function renderToolPanel(name) {
    if (!app.design) return;
    const renderToken = ++app.panelRenderToken;
    if (name === "upload") void renderUploadPanel(renderToken); else if (name === "text") renderTextPanel();
    else renderGraphicsPanel();
  }

  function updateSelectedObjectControls() {
    const object = app.canvas?.getActiveObject(), visible = object && object.studioId;
    const buttons = elements.selectedObjectControls.querySelectorAll("[data-object-action]");
    elements.selectedObjectControls.hidden = false;
    if (!visible) {
      elements.selectedObjectName.textContent = "حدد عنصرًا";
      buttons.forEach(button => { button.disabled = true; });
      return;
    }
    const record = object.studioKind === "graphic" && APPROVED_GRAPHICS.get(object.graphicId);
    elements.selectedObjectName.textContent = record?.nameAr || (object.studioKind === "text" ? "نص" : "صورة");
    const stack = app.canvas.getObjects(), index = stack.indexOf(object);
    buttons.forEach(button => { button.disabled = false; });
    elements.selectedObjectControls.querySelector('[data-object-action="forward"]').disabled = index >= stack.length - 1;
    elements.selectedObjectControls.querySelector('[data-object-action="backward"]').disabled = index <= 0;
  }
  async function actOnSelectedObject(action) {
    const object = app.canvas?.getActiveObject(); if (!object?.studioId) return;
    if (action === "forward") app.canvas.bringObjectForward(object);
    if (action === "backward") app.canvas.sendObjectBackwards(object);
    if (action === "delete") { app.canvas.remove(object); app.canvas.discardActiveObject(); }
    if (action === "duplicate") {
      const zone = getZoneRect(), areaId = app.area.id, model = objectToModel(object, zone);
      model.id = uid("object"); model.x += 12 / zone.width; model.y += 12 / zone.height;
      try {
        const copy = await modelToObject(model, zone); if (!copy) throw new Error("Missing source");
        if (app.area.id !== areaId) return;
        app.canvas.add(copy); app.canvas.setActiveObject(copy); copy.setCoords();
      } catch (error) { console.error(error); showNotice("تعذر تكرار العنصر.", "error"); return; }
    }
    app.canvas.requestRenderAll(); scheduleCommit(); updateSelectedObjectControls();
    if (action === "delete" && app.activeTool === "graphics") renderGraphicsPanel();
  }
  function setupSelectedObjectActions() {
    elements.selectedObjectControls.querySelectorAll("[data-object-action]").forEach(button => {
      button.addEventListener("click", () => { void actOnSelectedObject(button.dataset.objectAction); });
    });
  }
  function clearSnapGuides() { elements.snapGuideVertical.hidden = true; elements.snapGuideHorizontal.hidden = true; }
  function scenePointer(event) {
    if (event?.scenePoint) return event.scenePoint;
    if (!event?.e) return null;
    return app.canvas.getScenePoint?.(event.e) || app.canvas.getPointer?.(event.e, false) || null;
  }
  function beginSnapDrag(event) {
    const object = event?.target, pointer = scenePointer(event);
    if (!object?.studioId || !pointer || !app.geometryReady) { app.snapDrag = null; return; }
    app.snapDrag = { object, pointerStart: { x: pointer.x, y: pointer.y }, objectStart: { left: object.left, top: object.top }, xTarget: null, yTarget: null };
  }
  function finishSnapDrag() { app.snapDrag = null; clearSnapGuides(); }
  function snapMovingObject(event) {
    const object = event?.target, pointer = scenePointer(event);
    if (!object?.studioId || !pointer || !app.geometryReady) return;
    if (!app.snapDrag || app.snapDrag.object !== object) beginSnapDrag(event);
    const drag = app.snapDrag; if (!drag) return;
    const rawLeft = drag.objectStart.left + pointer.x - drag.pointerStart.x;
    const rawTop = drag.objectStart.top + pointer.y - drag.pointerStart.y;
    object.set({ left: rawLeft, top: rawTop }); object.setCoords();

    const zone = getZoneRect(), bounds = object.getBoundingRect();
    const screenScale = Math.max((app.stageScale || 1) * (app.canvas.getZoom?.() || 1), 0.01);
    const engageThreshold = 3 / screenScale, releaseThreshold = 5 / screenScale;
    const xCandidates = [
      { id: "left-left", objectLine: bounds.left, zoneLine: zone.left },
      { id: "left-right", objectLine: bounds.left, zoneLine: zone.left + zone.width },
      { id: "right-left", objectLine: bounds.left + bounds.width, zoneLine: zone.left },
      { id: "right-right", objectLine: bounds.left + bounds.width, zoneLine: zone.left + zone.width },
      { id: "center-center", objectLine: bounds.left + bounds.width / 2, zoneLine: zone.left + zone.width / 2 }
    ];
    const yCandidates = [
      { id: "top-top", objectLine: bounds.top, zoneLine: zone.top },
      { id: "top-bottom", objectLine: bounds.top, zoneLine: zone.top + zone.height },
      { id: "bottom-top", objectLine: bounds.top + bounds.height, zoneLine: zone.top },
      { id: "bottom-bottom", objectLine: bounds.top + bounds.height, zoneLine: zone.top + zone.height },
      { id: "center-center", objectLine: bounds.top + bounds.height / 2, zoneLine: zone.top + zone.height / 2 }
    ];
    const resolveAxis = (candidates, targetKey) => {
      const activeId = drag[targetKey];
      if (activeId) {
        const active = candidates.find(candidate => candidate.id === activeId);
        if (active) {
          const delta = active.zoneLine - active.objectLine;
          if (Math.abs(delta) <= releaseThreshold) return { ...active, delta };
        }
        drag[targetKey] = null;
        return null;
      }
      const nearest = candidates.reduce((best, candidate) => {
        const delta = candidate.zoneLine - candidate.objectLine;
        return Math.abs(delta) <= engageThreshold && (!best || Math.abs(delta) < Math.abs(best.delta))
          ? { ...candidate, delta } : best;
      }, null);
      if (nearest) drag[targetKey] = nearest.id;
      return nearest;
    };
    const vertical = resolveAxis(xCandidates, "xTarget"), horizontal = resolveAxis(yCandidates, "yTarget");
    object.set({ left: rawLeft + (vertical?.delta || 0), top: rawTop + (horizontal?.delta || 0) }); object.setCoords();
    elements.snapGuideVertical.hidden = !vertical; elements.snapGuideHorizontal.hidden = !horizontal;
    if (vertical) Object.assign(elements.snapGuideVertical.style, { left: `${vertical.zoneLine}px`, top: `${zone.top}px`, height: `${zone.height}px` });
    if (horizontal) Object.assign(elements.snapGuideHorizontal.style, { left: `${zone.left}px`, top: `${horizontal.zoneLine}px`, width: `${zone.width}px` });
    updatePrintZoneFeedback();
  }

  function setupCanvasEvents() {
    const select = event => { if (app.suppressCanvasEvents) return; const object = event.selected?.[0] || app.canvas.getActiveObject();
      if (object) setActiveTool(object.studioKind === "text" ? "text" : object.studioKind === "graphic" ? "graphics" : "upload");
      updateSelectedObjectControls(); };
    app.canvas.on("selection:created", select); app.canvas.on("selection:updated", select);
    app.canvas.on("selection:cleared", () => { clearSnapGuides(); updateSelectedObjectControls();
      if (app.activeTool === "text") renderTextPanel(); if (app.activeTool === "graphics") renderGraphicsPanel(); });
    app.canvas.on("mouse:down", beginSnapDrag);
    app.canvas.on("object:moving", snapMovingObject);
    app.canvas.on("object:scaling", event => {
      const object = event.target;
      if (object?.studioKind === "graphic") object.set({ scaleY: object.scaleX });
      object?.setCoords(); updatePrintZoneFeedback(); app.canvas.requestRenderAll();
    });
    app.canvas.on("object:rotating", updatePrintZoneFeedback);
    app.canvas.on("mouse:up", finishSnapDrag);
    ["object:added", "object:removed", "object:modified", "text:changed"].forEach(name => app.canvas.on(name, () => {
      if (!app.suppressCanvasEvents) {
        clearSnapGuides(); scheduleCommit(); updateSelectedObjectControls(); updatePrintZoneFeedback(); renderAreaCopyActions();
        if (app.activeTool === "upload" && (name === "object:modified" || name === "object:added")) renderToolPanel("upload");
      }
    }));
  }
  function initCanvas() {
    initializeLogicalStage();
    app.canvas = new fabric.Canvas(elements.designCanvas, { preserveObjectStacking: true, selection: true, uniformScaling: true, controlsAboveOverlay: true, backgroundColor: "transparent" });
    // Browser/CSS zoom changes backing-store resolution, not document geometry.
    app.canvas.getRetinaScaling = () => Math.max(1, (window.devicePixelRatio || 1) * (app.stageScale || 1));
    app.canvas.on("before:render", () => {
      refreshCanvasResolution();
      app.canvas.getObjects().forEach(object => window.PALPRINTS_STUDIO_SVG.refresh(object, app.canvas));
    });
    window.addEventListener("resize", refreshCanvasResolution);
    window.visualViewport?.addEventListener("resize", refreshCanvasResolution);
    app.canvas.setDimensions(app.logicalStage); updateStageTransform(); setupCanvasEvents(); setupSelectedObjectActions();
  }
  function renderProductSummary() {
    const title = app.product.studioTitle || app.product.name, mockup = resolveMockup(app.area, app.color);
    elements.summaryImage.src = mockup; elements.summaryImage.alt = title; elements.summaryProductName.textContent = title; elements.summaryColor.textContent = app.color?.name || "—";
    elements.summarySize.textContent = app.size?.name || "—"; elements.summaryPrice.textContent = formatPrice(app.product.price); elements.selectedColorName.textContent = app.color?.name || "—";
  }
  function setupResizeObserver() {
    if (app.resizeObserver) return;
    app.resizeObserver = new ResizeObserver(() => {
      if (app.resizeFrame) cancelAnimationFrame(app.resizeFrame);
      app.resizeFrame = requestAnimationFrame(() => { app.resizeFrame = null; updateStageTransform(); });
    });
    app.resizeObserver.observe(elements.stage);
  }
  async function init() {
    setupSiteShell(); setupToolTabs(); if (!elements.designCanvas || !elements.printZone || !window.fabric?.Canvas) return showError("تعذر تجهيز مساحة التصميم. أعد تحميل الصفحة وحاول مرة أخرى.");
    app.selection = readSelection(); if (!app.selection) return showError("اختر منتجًا مهيأ من صفحة اختيار المنتجات أولًا.");
    const validation = validateProduct(app.selection.editorProduct); if (!validation.valid) return showError(validation.message);
    ensureDesignId(app.selection);
    const catalog = resolveEligibleProducts(app.selection);
    catalog.forEach(product => app.products.set(product.id, product));
    if (!app.products.has(app.selection.editorProduct.id)) {
      const fallbackProduct = catalog[0];
      if (!fallbackProduct) return showError("لا توجد منتجات صالحة ومتاحة حاليًا في استوديو التصميم.");
      app.selection = { ...app.selection, productId: fallbackProduct.id, editorProduct: fallbackProduct,
        colorId: fallbackProduct.defaultColor || fallbackProduct.colors?.[0]?.id,
        sizeId: fallbackProduct.sizes?.[0]?.id, printAreaIds: [fallbackProduct.editor.defaultAreaId] };
      sessionStorage.setItem(SELECTION_KEY, JSON.stringify(app.selection));
    }
    const documentState = loadDesignDocument(app.selection); app.assets = documentState.assets; app.drafts = documentState.drafts || {};
    const initialProduct = app.products.get(documentState.activeProductId) || app.selection.editorProduct;
    if (!app.drafts[initialProduct.id]) app.drafts[initialProduct.id] = createDraft(initialProduct, { colorId: app.selection.colorId, sizeId: app.selection.sizeId });
    activateDraft(initialProduct, app.drafts[initialProduct.id]); app.assetStore = new DesignAssetStore(app.selection.designId);
    renderProductSummary(); renderColors(); renderSizes(); renderAreas(); renderAreaCopyActions(); renderProductSelector(); setActiveTool("upload");
    initCanvas(); setupZoomControls();
    try {
      await switchArea(app.area.id, true); setupResizeObserver(); setupHistory(); setupProductSwap(); commitDesign(); captureHistory();
    }
    catch (error) { console.error(error); showError("تعذر تحميل مساحة المنتج المحدد."); }
  }

  addEventListener("beforeunload", () => { saveActiveArea(); commitDesign(); app.objectUrls.forEach(url => URL.revokeObjectURL(url)); });
  window.PALPRINTS_DESIGN_STUDIO_EXPORT = Object.freeze({ createPrintAreaCanvas: createPrintAreaExport });
  void init();
})(window, document);
