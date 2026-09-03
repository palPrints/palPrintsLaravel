const $ = id => document.getElementById(id);

function readSession(key, fallback = null) {
    try { return JSON.parse(sessionStorage.getItem(key) || "null") ?? fallback; }
    catch { return fallback; }
}

const fallbackProduct = {
    name: "تي شيرت كلاسيكي",
    price: 29,
    colorName: { white: "أبيض" },
    image: "assets/images/tshirt.webp",
    colors: [{ id: "white", value: "#fff" }],
    sizes: ["M"],
    areas: [
        { id: "front", name: "الأمام", image: "assets/images/printing-areas/tshirt/tshirt-front-removebg-preview.png", dimensions: "28 × 36 سم" },
        { id: "back", name: "الخلف", image: "assets/images/printing-areas/tshirt/tshirt-back-removebg-preview.png", dimensions: "28 × 36 سم" }
    ]
};

const designerState = readSession("palprintsDesignerState", {});
const selection = readSession("palprintsDesignerSelection", {});
const savedReview = readSession("palprintsReviewState", {});
const product = designerState.product || fallbackProduct;
const validAreaIds = new Set(product.areas.map(area => area.id));
const state = {
    areaId: validAreaIds.has(designerState.areaId) ? designerState.areaId : product.areas[0].id,
    zoom: 1,
    designName: savedReview.designName || `تصميم ${product.name}`,
    sellingPrice: Number(savedReview.sellingPrice) || Number(product.price || 0) + 16,
    rightsConfirmed: savedReview.rightsConfirmed ?? true,
    warnings: designerState.warnings || selection.warnings || []
};

async function loadReviewFonts() {
    if (!document.fonts) return;

    const fontSamples = new Map();
    Object.values(designerState.designsByArea || {}).forEach(design => {
        (design.texts || []).forEach(text => {
            const content = String(text.content || "").trim();
            if (content) fontSamples.set(text.fontFamily || "Cairo", content);
        });
    });

    const requests = [
        document.fonts.load('400 16px "Cairo"', "PalPrints تصميم"),
        document.fonts.load('800 28px "Cairo"', "PalPrints تصميم"),
        ...Array.from(fontSamples, ([family, sample]) =>
            document.fonts.load(`800 28px "${family}"`, sample)
        )
    ];
    await Promise.allSettled(requests);
}

async function revealReviewAfterFontsLoad() {
    try {
        await loadReviewFonts();
        renderMockup();
    } finally {
        window.clearTimeout(window.palPrintsFontTimeout);
        document.documentElement.classList.remove("fonts-loading");
    }
}

const repository = {
    async send(endpointName, payload) {
        const endpoint = window.PALPRINTS_API?.[endpointName];
        if (!endpoint) return { ok: true, mode: "session", data: payload };
        const response = await fetch(endpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify(payload)
        });
        if (!response.ok) throw new Error(`Request failed with status ${response.status}`);
        return { ok: true, mode: "api", data: await response.json() };
    }
};

function activeArea() {
    return product.areas.find(area => area.id === state.areaId) || product.areas[0];
}

function activeColor() {
    return product.colors.find(color => color.id === designerState.colorId) || product.colors[0];
}

function areaDesign(areaId = state.areaId) {
    return designerState.designsByArea?.[areaId] || { images: [], texts: [] };
}

function hasContent(design) {
    return Boolean(design.images?.length || design.texts?.some(text => String(text.content || "").trim()));
}

function hasAnyDesign() {
    return product.areas.some(area => hasContent(areaDesign(area.id)));
}

function showToast(message) {
    const toast = $("toast");
    toast.textContent = message;
    toast.classList.add("show");
    window.clearTimeout(showToast.timeout);
    showToast.timeout = window.setTimeout(() => toast.classList.remove("show"), 2300);
}

function normalizePreviewLayerOrders(design) {
    const layers = [...(design.images || []), ...(design.texts || [])].map((component, fallbackIndex) => {
        const explicitOrder = Number(component.layerOrder);
        const hasExplicitOrder = component.layerOrder !== undefined
            && component.layerOrder !== null
            && Number.isFinite(explicitOrder);
        return { component, fallbackIndex, explicitOrder, hasExplicitOrder };
    });
    layers.sort((first, second) => {
        if (first.hasExplicitOrder && second.hasExplicitOrder && first.explicitOrder !== second.explicitOrder) {
            return first.explicitOrder - second.explicitOrder;
        }
        if (first.hasExplicitOrder !== second.hasExplicitOrder) return first.hasExplicitOrder ? -1 : 1;
        return first.fallbackIndex - second.fallbackIndex;
    });
    layers.forEach(({ component }, index) => { component.layerOrder = index + 1; });
}

function renderAreaOptions() {
    $("areaSelect").innerHTML = product.areas.map(area => `<option value="${area.id}">${area.name}</option>`).join("");
    $("areaSelect").value = state.areaId;
}

function renderImages(images) {
    $("imageLayers").innerHTML = images.map((image, index) => `
        <div class="preview-image-layer" style="z-index:${Number(image.layerOrder) || index + 1};left:${Number(image.x) || 50}%;top:${Number(image.y) || 50}%;width:${Number(image.width) || 65}%;height:${Number(image.height) || 65}%;transform:translate(-50%,-50%) rotate(${Number(image.rotation) || 0}deg)">
            <img src="${escapeAttribute(image.url)}" alt="${escapeAttribute(image.name || `عنصر التصميم ${index + 1}`)}">
        </div>
    `).join("");
}

function renderTexts(texts) {
    const zoneWidth = $("printZone").clientWidth;
    $("textLayers").innerHTML = texts.filter(text => String(text.content || "").trim()).map((text, index) => `
        <div class="preview-text-layer" dir="auto" style="z-index:${Number(text.layerOrder) || index + 1};left:${Number(text.x) || 50}%;top:${Number(text.y) || 50}%;${previewTextMetrics(text, zoneWidth)}font-family:'${text.fontFamily || "Cairo"}',sans-serif;color:${text.color || "#142d70"};text-align:${text.align || "center"};line-height:${Number(text.lineHeight) || 1.2};transform:translate(-50%,-50%) rotate(${Number(text.rotation) || 0}deg)">${escapeHtml(text.content)}</div>
    `).join("");
}

function previewTextMetrics(text, zoneWidth) {
    const metrics = text.previewMetrics;
    if (!metrics || !zoneWidth) {
        return `width:max-content;font-size:${Number(text.size) || 28}px;letter-spacing:${Number(text.letterSpacing) || 0}px;`;
    }
    const width = Math.min(100, Math.max(0, Number(metrics.widthPercent) || 0));
    const fontSize = Number(metrics.fontSizePercent) * zoneWidth / 100;
    const letterSpacing = Number(metrics.letterSpacingPercent) * zoneWidth / 100;
    return `width:${width}%;font-size:${fontSize}px;letter-spacing:${letterSpacing}px;`;
}

function escapeHtml(value) {
    const element = document.createElement("span");
    element.textContent = String(value);
    return element.innerHTML;
}

function escapeAttribute(value) {
    return String(value || "").replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

function renderMockup() {
    const area = activeArea();
    const color = activeColor();
    const image = $("productImage");
    image.src = (area.id === "front" && (color.image || product.image)) || area.image || color.image || product.image;
    image.alt = `${product.name} - ${area.name}`;
    const canvas = $("previewCanvas");
    if (product.canvasWidth) canvas.style.setProperty("--product-canvas-width", product.canvasWidth);
    if (product.canvasMobileWidth) canvas.style.setProperty("--product-canvas-mobile-width", product.canvasMobileWidth);
    const zone = $("printZone");
    zone.style.top = product.printZone?.top || "";
    zone.style.left = product.printZone?.left || "";
    zone.style.width = product.printZone?.width || "";
    zone.style.height = product.printZone?.height || "";
    const design = areaDesign();
    normalizePreviewLayerOrders(design);
    renderImages(design.images || []);
    renderTexts(design.texts || []);
    $("emptyArea").hidden = hasContent(design);
    renderWarnings();
}

function updateCanvasRatio() {
    const image = $("productImage");
    if (!image.naturalWidth || !image.naturalHeight) return;
    $("previewCanvas").style.setProperty("--product-aspect-ratio", `${image.naturalWidth} / ${image.naturalHeight}`);
    renderTexts(areaDesign().texts || []);
}

function analyzedWarnings() {
    const warnings = [...state.warnings];
    const design = areaDesign();
    if (!hasContent(design)) warnings.push({ severity: "info", message: `منطقة ${activeArea().name} لا تحتوي على تصميم.` });
    (design.images || []).forEach(image => {
        if (image.naturalWidth && image.naturalHeight && Math.min(image.naturalWidth, image.naturalHeight) < 600) {
            warnings.push({ severity: "warning", message: `دقة الصورة «${image.name || "المرفوعة"}» منخفضة وقد تؤثر في جودة الطباعة.` });
        }
    });
    return warnings.map(warning => typeof warning === "string" ? { severity: "warning", message: warning } : warning);
}

function renderWarnings() {
    const warnings = analyzedWarnings();
    const card = $("warningsCard");
    card.classList.toggle("has-warnings", warnings.length > 0);
    card.querySelector(".warning-icon i").className = warnings.length ? "bi bi-exclamation-triangle" : "bi bi-shield-check";
    $("warningsSummary").textContent = warnings.length ? `${warnings.length} ملاحظة من نظام فحص الطباعة` : "التصميم ضمن منطقة الطباعة وجاهز للمتابعة";
    $("warningsList").innerHTML = warnings.map(warning => `<li>${escapeHtml(warning.message || "تحذير من نظام الطباعة")}</li>`).join("");
}

function renderProductDetails() {
    const color = activeColor();
    $("productName").textContent = product.name;
    $("colorName").textContent = product.colorName?.[color.id] || color.name || color.id;
    $("colorDot").style.background = color.value || "#fff";
    $("sizeName").textContent = designerState.sizeId || selection.sizeId || product.sizes[0];
    $("basePrice").textContent = Number(product.price || 0).toFixed(2);
}

function renderPublicationForm() {
    $("designName").value = state.designName;
    $("nameCount").textContent = state.designName.length;
    $("sellingPrice").min = String(product.price || 0);
    $("sellingPrice").value = state.sellingPrice.toFixed(2);
    $("rightsCheck").checked = state.rightsConfirmed;
    updateProfit();
}

function updateProfit() {
    state.sellingPrice = Number($("sellingPrice").value) || 0;
    const profit = state.sellingPrice - Number(product.price || 0);
    $("profitValue").textContent = Math.max(0, profit).toFixed(2);
    $("priceError").textContent = profit < 0 ? "يجب ألا يقل سعر البيع عن تكلفة المنتج." : "";
    updateValidation();
}

function validationErrors() {
    const errors = [];
    if (!state.designName.trim()) errors.push("أدخل اسم التصميم.");
    if (state.sellingPrice < Number(product.price || 0)) errors.push("صحح سعر البيع.");
    if (!state.rightsConfirmed) errors.push("أكد امتلاك حقوق استخدام التصميم.");
    if (!hasAnyDesign()) errors.push("أضف تصميمًا في منطقة طباعة واحدة على الأقل.");
    if (analyzedWarnings().some(warning => warning.severity === "error")) errors.push("عالج أخطاء الطباعة قبل المتابعة.");
    return errors;
}

function updateValidation() {
    const errors = validationErrors();
    $("publishButton").disabled = errors.length > 0;
    $("validationNote").textContent = errors[0] || "";
    return errors;
}

function publicationPayload(status) {
    return {
        status,
        productId: designerState.productId || selection.productId || "product-001",
        colorId: designerState.colorId || selection.colorId,
        sizeId: designerState.sizeId || selection.sizeId,
        designName: state.designName.trim(),
        pricing: {
            basePrice: Number(product.price || 0),
            sellingPrice: state.sellingPrice,
            profit: Math.max(0, state.sellingPrice - Number(product.price || 0))
        },
        rightsConfirmed: state.rightsConfirmed,
        printAreas: product.areas.map(area => ({ areaId: area.id, design: areaDesign(area.id) })).filter(item => hasContent(item.design)),
        warnings: analyzedWarnings(),
        designerVersion: designerState.version || 1,
        submittedAt: new Date().toISOString()
    };
}

function saveReviewLocally() {
    const reviewState = { designName: state.designName, sellingPrice: state.sellingPrice, rightsConfirmed: state.rightsConfirmed, updatedAt: new Date().toISOString() };
    sessionStorage.setItem("palprintsReviewState", JSON.stringify(reviewState));
    return reviewState;
}

async function saveDraft() {
    const button = $("saveButton");
    button.disabled = true;
    try {
        saveReviewLocally();
        const payload = publicationPayload("draft");
        sessionStorage.setItem("palprintsPublishDraft", JSON.stringify(payload));
        await repository.send("saveDraft", payload);
        showToast("تم حفظ التصميم كمسودة");
    } catch {
        showToast("تعذر حفظ المسودة، حاول مجددًا");
    } finally { button.disabled = false; }
}

async function publishDesign() {
    const errors = updateValidation();
    if (errors.length) { showToast(errors[0]); return; }
    const button = $("publishButton");
    button.disabled = true;
    button.setAttribute("aria-busy", "true");
    try {
        saveReviewLocally();
        const payload = publicationPayload("submitted");
        sessionStorage.setItem("palprintsPublishPayload", JSON.stringify(payload));
        await repository.send("publish", payload);
        $("successDialog").hidden = false;
        $("closeDialog").focus();
    } catch {
        showToast("تعذر إرسال التصميم، تحقق من الاتصال وحاول مجددًا");
    } finally {
        button.removeAttribute("aria-busy");
        updateValidation();
    }
}

function setZoom(value) {
    state.zoom = Math.min(1.5, Math.max(.7, value));
    $("previewCanvas").style.setProperty("--zoom", state.zoom);
    $("zoomValue").textContent = `${Math.round(state.zoom * 100)}%`;
}

$("areaSelect").addEventListener("change", event => { state.areaId = event.target.value; renderMockup(); });
$("productImage").addEventListener("load", updateCanvasRatio);
window.addEventListener("resize", () => renderTexts(areaDesign().texts || []));
$("zoomIn").addEventListener("click", () => setZoom(state.zoom + .1));
$("zoomOut").addEventListener("click", () => setZoom(state.zoom - .1));
$("fitButton").addEventListener("click", () => setZoom(1));
$("backToEditor").addEventListener("click", () => {
    saveReviewLocally();
    window.location.href =
        window.palPrintsCreateRoutes.editor;
});
$("designName").addEventListener("input", event => { state.designName = event.target.value; $("nameCount").textContent = state.designName.length; updateValidation(); });
$("sellingPrice").addEventListener("input", updateProfit);
$("rightsCheck").addEventListener("change", event => { state.rightsConfirmed = event.target.checked; updateValidation(); });
$("saveButton").addEventListener("click", saveDraft);
$("publishButton").addEventListener("click", publishDesign);
$("closeDialog").addEventListener("click", () => { $("successDialog").hidden = true; });
$("successDialog").addEventListener("click", event => { if (event.target === $("successDialog")) $("successDialog").hidden = true; });
document.addEventListener("keydown", event => { if (event.key === "Escape" && !$("successDialog").hidden) $("successDialog").hidden = true; });

renderAreaOptions();
renderProductDetails();
renderPublicationForm();
renderMockup();
setZoom(1);
revealReviewAfterFontsLoad();
