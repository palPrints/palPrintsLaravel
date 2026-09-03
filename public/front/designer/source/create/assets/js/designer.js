const selection = JSON.parse(sessionStorage.getItem("palprintsDesignerSelection") || "null");

const catalog = {
    "product-001": {
        name: "تي شيرت كلاسيكي",
        price: 29,
        colorName: { white: "أبيض", black: "أسود", navy: "كحلي", red: "أحمر", green: "أخضر" },
        image: "assets/images/tshirt.webp",
        colors: [{ id: "white", value: "#fff" }, { id: "black", value: "#111" }, { id: "navy", value: "#173b87" }, { id: "red", value: "#d52a3c" }, { id: "green", value: "#2e9b42" }],
        sizes: ["S", "M", "L", "XL", "XXL"],
        areas: [{ id: "front", name: "الأمام", image: "assets/images/printing-areas/tshirt/tshirt-front-removebg-preview.png", dimensions: "28 × 36 سم" }, { id: "back", name: "الخلف", image: "assets/images/printing-areas/tshirt/tshirt-back-removebg-preview.png", dimensions: "28 × 36 سم" }, { id: "right-sleeve", name: "الكم الأيمن", image: "assets/images/printing-areas/tshirt/tshirt-rightSleeve-removebg-preview.png", dimensions: "10 × 12 سم" }, { id: "left-sleeve", name: "الكم الأيسر", image: "assets/images/printing-areas/tshirt/tshirt-leftSleeve-removebg-preview.png", dimensions: "10 × 12 سم" }]
    },
    "product-002": {
        name: "هودي بسيط", price: 79, colorName: { white: "أبيض", black: "أسود" }, colors: [{ id: "white", value: "#fff", image: "assets/images/hoodie.png" }, { id: "black", value: "#111", image: "assets/images/hoodie-black.png" }], sizes: ["S", "M", "L", "XL"], areas: [{ id: "front", name: "الأمام", image: "assets/images/printing-areas/hoodie/hoodie-front.png", dimensions: "28 × 36 سم" }, { id: "back", name: "الخلف", image: "assets/images/printing-areas/hoodie/hoodie-back.png", dimensions: "28 × 36 سم" }]
    },
    "product-003": { name: "كوب سيراميك", price: 19, colorName: { white: "أبيض" }, image: "assets/images/cup.webp", colors: [{ id: "white", value: "#fff" }], sizes: ["قياسي"], areas: [{ id: "front", name: "الواجهة", image: "assets/images/cup.webp", dimensions: "20 × 9 سم" }] },
    "product-004": { name: "حقيبة قماشية", price: 39, colorName: { white: "أبيض" }, image: "assets/images/bag.png", colors: [{ id: "white", value: "#fff" }], sizes: ["قياسي"], areas: [{ id: "front", name: "الأمام", image: "assets/images/bag.png", dimensions: "28 × 30 سم" }, { id: "back", name: "الخلف", image: "assets/images/bag.png", dimensions: "28 × 30 سم" }] },
    "product-005": {
        name: "تي شيرت ثقيل باهت",
        price: 49,
        colorName: { "faded-black": "أسود باهت", "faded-brown": "بني باهت", "faded-cream": "كريمي باهت", "faded-navy": "كحلي باهت" },
        colors: [
            { id: "faded-black", value: "#4a4a48", image: "assets/products/tshirt-2/tshirt-dyed-heavyweight-faded-black-removebg-preview.png" },
            { id: "faded-brown", value: "#9b816a", image: "assets/products/tshirt-2/tshirt-dyed-heavyweight-faded-brown-removebg-preview.png" },
            { id: "faded-cream", value: "#f1ebdd", image: "assets/products/tshirt-2/tshirt-dyed-heavyweight-faded-cream-removebg-preview.png" },
            { id: "faded-navy", value: "#345775", image: "assets/products/tshirt-2/tshirt-dyed-heavyweight-faded-navy-removebg-preview.png" }
        ],
        sizes: ["S", "M", "L", "XL", "XXL"],
        areas: [{ id: "front", name: "الأمام", image: "assets/images/printing-areas/tshirt/tshirt-front-removebg-preview.png", dimensions: "28 × 36 سم" }, { id: "back", name: "الخلف", image: "assets/images/printing-areas/tshirt/tshirt-back-removebg-preview.png", dimensions: "28 × 36 سم" }, { id: "right-sleeve", name: "الكم الأيمن", image: "assets/images/printing-areas/tshirt/tshirt-rightSleeve-removebg-preview.png", dimensions: "10 × 12 سم" }, { id: "left-sleeve", name: "الكم الأيسر", image: "assets/images/printing-areas/tshirt/tshirt-leftSleeve-removebg-preview.png", dimensions: "10 × 12 سم" }],
        canvasWidth: "min(56%, 455px)",
        canvasMobileWidth: "76%",
        printZone: { top: "31%", left: "32%", width: "36%", height: "36%" }
    },
    "product-006": {
        name: "قبعة كلاسيكية",
        price: 25,
        colorName: { black: "أسود", navy: "كحلي", storm: "رمادي فاتح", walnut: "جوزي" },
        colors: [
            { id: "black", value: "#171717", image: "assets/products/cap/cap-black-removebg-preview.png" },
            { id: "navy", value: "#1d1e2b", image: "assets/products/cap/cap-navy-removebg-preview.png" },
            { id: "storm", value: "#d3d3d3", image: "assets/products/cap/cap-storm-removebg-preview.png" },
            { id: "walnut", value: "#786551", image: "assets/products/cap/cap-wallnut-removebg-preview.png" }
        ],
        sizes: ["قياسي"],
        areas: [{ id: "front", name: "الواجهة", dimensions: "18 × 8 سم" }],
        canvasWidth: "min(56%, 455px)",
        canvasMobileWidth: "76%",
        printZone: { top: "29%", left: "31%", width: "38%", height: "20%" }
    }
};

const productId = selection?.productId || "product-001";
const product = catalog[productId] || catalog["product-001"];
const storedDesignerState = JSON.parse(sessionStorage.getItem("palprintsDesignerState") || "null");
const restoredState = storedDesignerState?.productId === productId ? storedDesignerState : null;
const requestedAreaId = restoredState?.areaId || selection?.printAreaIds?.[0] || "front";
const initialAreaId = product.areas.some(area => area.id === requestedAreaId) ? requestedAreaId : product.areas[0].id;
const state = {
    productId,
    colorId: restoredState?.colorId || selection?.colorId || product.colors[0].id,
    sizeId: restoredState?.sizeId || selection?.sizeId || product.sizes[0],
    areaId: initialAreaId,
    zoom: 1,
    tool: "upload",
    hasDesign: false,
    images: [],
    selectedImageId: null,
    nextImageId: 1,
    texts: [],
    selectedTextId: null,
    nextTextId: 1,
    zoneLabelHidden: false,
    designsByArea: restoredState?.designsByArea || {}
};
const $ = id => document.getElementById(id);
let fontSelectionRequest = 0;
let designerHistory = null;
let designerHistoryAdapter = null;

function emptyAreaDesign() {
    return { images: [], texts: [], nextImageId: 1, nextTextId: 1, zoneLabelHidden: false };
}

function activeAreaDesign() {
    return state.designsByArea[state.areaId] || (state.designsByArea[state.areaId] = emptyAreaDesign());
}

function normalizeLayerOrders(images = state.images, texts = state.texts) {
    const layers = [...images, ...texts].map((component, fallbackIndex) => {
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
    return layers.map(({ component }) => component);
}

function orderedComponents() {
    return [...state.images, ...state.texts].sort((first, second) => Number(first.layerOrder) - Number(second.layerOrder));
}

function nextLayerOrder() {
    return Math.max(0, ...orderedComponents().map(component => Number(component.layerOrder) || 0)) + 1;
}

function loadAreaDesign(areaId) {
    const design = state.designsByArea[areaId] || (state.designsByArea[areaId] = emptyAreaDesign());
    state.images = Array.isArray(design.images) ? design.images : [];
    state.texts = Array.isArray(design.texts) ? design.texts : [];
    state.nextImageId = Number(design.nextImageId) || 1;
    state.nextTextId = Number(design.nextTextId) || 1;
    state.zoneLabelHidden = Boolean(design.zoneLabelHidden);
    state.selectedImageId = null;
    state.selectedTextId = null;
    normalizeLayerOrders();
}

function syncActiveAreaDesign() {
    const design = activeAreaDesign();
    design.images = state.images;
    design.texts = state.texts;
    design.nextImageId = state.nextImageId;
    design.nextTextId = state.nextTextId;
    design.zoneLabelHidden = state.zoneLabelHidden;
}

function createStoredDesignerState() {
    syncActiveAreaDesign();
    Object.values(state.designsByArea).forEach(design => normalizeLayerOrders(design.images || [], design.texts || []));
    return {
        version: 3,
        productId: state.productId,
        colorId: state.colorId,
        sizeId: state.sizeId,
        areaId: state.areaId,
        product,
        designsByArea: state.designsByArea,
        updatedAt: new Date().toISOString()
    };
}

function persistDesignerState() {
    const storedState = createStoredDesignerState();
    try {
        sessionStorage.setItem("palprintsDesignerState", JSON.stringify(storedState));
        return storedState;
    } catch {
        showToast("تعذر حفظ التصميم؛ حجم الصور المرفوعة كبير جدًا");
        return null;
    }
}

loadAreaDesign(state.areaId);

const componentAssets = [
    { id: "heart", name: "قلب", svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="#ef476f" d="M50 88 15 54C-8 31 8 8 29 12c10 2 17 9 21 17 4-8 11-15 21-17 21-4 37 19 14 42Z"/></svg>' },
    { id: "star", name: "نجمة", svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="#ffbd2e" d="m50 7 13 27 30 4-22 21 6 30-27-14-27 14 6-30L7 38l30-4Z"/></svg>' },
    { id: "leaf", name: "ورقة نبات", svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="#49bd72" d="M86 12C47 14 20 30 17 58c-2 15 7 26 20 29 23 5 48-16 49-75Z"/><path fill="none" stroke="#167d45" stroke-width="6" stroke-linecap="round" d="M18 91c13-29 29-44 55-62"/></svg>' },
    { id: "coffee", name: "فنجان قهوة", svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="#6432f2" d="M15 31h58v31c0 16-13 27-29 27S15 78 15 62Z"/><path fill="none" stroke="#6432f2" stroke-width="9" d="M72 40h7c18 0 18 25 0 25h-7"/><path fill="none" stroke="#19c8d7" stroke-width="5" stroke-linecap="round" d="M32 23c-8-8 8-10 0-18m22 18c-8-8 8-10 0-18"/></svg>' },
    { id: "chef", name: "قبعة طاهٍ", svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="#fff" stroke="#142d70" stroke-width="5" d="M24 47C8 43 11 20 28 20c5-17 29-17 36-2 19-5 30 19 13 29v35H24Z"/><path fill="none" stroke="#6432f2" stroke-width="5" d="M24 64h53M38 47v17m24-17v17"/></svg>' },
    { id: "pizza", name: "بيتزا", svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="#ffcf5c" stroke="#d98b2b" stroke-width="6" d="M15 18c24-12 49-12 73 0L51 89Z"/><path fill="none" stroke="#a85d25" stroke-width="10" stroke-linecap="round" d="M15 18c24-12 49-12 73 0"/><circle fill="#ef476f" cx="42" cy="38" r="8"/><circle fill="#ef476f" cx="61" cy="59" r="7"/><circle fill="#49bd72" cx="64" cy="29" r="6"/></svg>' }
].map(asset => ({ ...asset, src: `data:image/svg+xml;charset=utf-8,${encodeURIComponent(asset.svg)}` }));

async function loadFont(family, weight = 800, size = 28, sample = "PalPrints تصميم") {
    if (!document.fonts) return;
    const descriptor = `${weight} ${size}px "${family}"`;
    await document.fonts.load(descriptor, sample);
    if (!document.fonts.check(descriptor, sample)) throw new Error(`Font failed to load: ${family}`);
}

async function revealDesignerAfterFontsLoad() {
    try {
        await Promise.allSettled([loadFont("Cairo", 400, 16), loadFont("Cairo", 800, 28)]);
        renderProduct();
    } finally {
        window.clearTimeout(window.palPrintsFontTimeout);
        document.documentElement.classList.remove("fonts-loading");
    }
}

function showToast(message) { const toast = $("toast"); toast.textContent = message; toast.classList.add("show"); setTimeout(() => toast.classList.remove("show"), 2200); }
function activeArea() { return product.areas.find(area => area.id === state.areaId) || product.areas[0]; }
function createImageLayer(image) {
    const layer = document.createElement("div");
    layer.className = "image-layer";
    layer.dataset.imageId = image.id;
    layer.innerHTML = `
        <img class="uploaded-image" alt="تصميم مرفوع" draggable="false">
        <button class="image-handle image-rotate-handle" type="button" data-image-action="rotate" aria-label="تدوير الصورة"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></button>
        <button class="image-handle image-resize-handle" type="button" data-image-action="resize" aria-label="تغيير حجم الصورة"><i class="bi bi-arrows-angle-expand" aria-hidden="true"></i></button>
        <button class="image-handle image-stretch-x-handle" type="button" data-image-action="stretch-x" aria-label="تمديد الصورة أفقياً"><i class="bi bi-arrows-expand" aria-hidden="true"></i></button>
        <button class="image-handle image-stretch-y-handle" type="button" data-image-action="stretch-y" aria-label="تمديد الصورة عمودياً"><i class="bi bi-arrows-expand-vertical" aria-hidden="true"></i></button>
        <button class="image-handle image-delete-handle" type="button" data-image-action="delete" aria-label="حذف الصورة"><i class="bi bi-trash3" aria-hidden="true"></i></button>
    `;
    $("imageLayers").appendChild(layer);
    return layer;
}
function renderImages() {
    const container = $("imageLayers");
    container.querySelectorAll(".image-layer").forEach(layer => {
        if (!state.images.some(image => String(image.id) === layer.dataset.imageId)) layer.remove();
    });
    state.images.forEach((image, index) => {
        const layer = container.querySelector(`[data-image-id="${image.id}"]`) || createImageLayer(image);
        const imageElement = layer.querySelector(".uploaded-image");
        if (imageElement.src !== image.url) imageElement.src = image.url;
        imageElement.alt = image.name || `تصميم مرفوع ${index + 1}`;
        layer.classList.toggle("selected", image.id === state.selectedImageId);
        layer.style.zIndex = String(Number(image.layerOrder) || 1);
        layer.style.left = `${image.x}%`;
        layer.style.top = `${image.y}%`;
        layer.style.width = `${image.width}%`;
        layer.style.height = `${image.height}%`;
        layer.style.transform = `translate(-50%, -50%) rotate(${image.rotation}deg)`;
        constrainImagePosition(image, layer);
        layer.style.left = `${image.x}%`;
        layer.style.top = `${image.y}%`;
    });
}
function updateImageOverlapIndicators(activeImageId = null) {
    const container = $("imageLayers");
    const layers = [...container.querySelectorAll(".image-layer")];
    container.classList.toggle("dragging", activeImageId !== null);
    layers.forEach(layer => layer.classList.remove("overlapping"));
    if (activeImageId === null) return;
    const activeLayer = layers.find(layer => Number(layer.dataset.imageId) === activeImageId);
    if (!activeLayer) return;
    const activeBounds = activeLayer.getBoundingClientRect();
    layers.forEach(layer => {
        if (layer === activeLayer) return;
        const bounds = layer.getBoundingClientRect();
        const overlaps = activeBounds.left < bounds.right && activeBounds.right > bounds.left && activeBounds.top < bounds.bottom && activeBounds.bottom > bounds.top;
        if (!overlaps) return;
        activeLayer.classList.add("overlapping");
        layer.classList.add("overlapping");
    });
}
function selectedText() {
    return state.texts.find(text => text.id === state.selectedTextId) || null;
}

function selectedImage() {
    return state.images.find(image => image.id === state.selectedImageId) || null;
}

function selectedComponent() {
    return selectedText() || selectedImage();
}

function syncLayerActionButtons() {
    const component = selectedComponent();
    const layers = orderedComponents();
    const selectedIndex = component ? layers.indexOf(component) : -1;
    $("bringForward").disabled = selectedIndex < 0 || selectedIndex === layers.length - 1;
    $("sendBackward").disabled = selectedIndex <= 0;
}

function moveSelectedLayer(offset) {
    const component = selectedComponent();
    if (!component) return;
    normalizeLayerOrders();
    const layers = orderedComponents();
    const selectedIndex = layers.indexOf(component);
    const targetIndex = selectedIndex + offset;
    if (selectedIndex < 0 || targetIndex < 0 || targetIndex >= layers.length) return;
    const target = layers[targetIndex];
    [component.layerOrder, target.layerOrder] = [target.layerOrder, component.layerOrder];
    renderProduct();
    showToast(offset > 0 ? "تم إرسال العنصر للأمام" : "تم إرسال العنصر للخلف");
}

function componentLayer(component = selectedComponent()) {
    if (!component) return null;
    if (state.texts.includes(component)) return $("textLayers").querySelector(`[data-text-id="${component.id}"]`);
    return $("imageLayers").querySelector(`[data-image-id="${component.id}"]`);
}

function createText(content = "") {
    const placements = [[50, 50], [36, 36], [64, 36], [36, 64], [64, 64]];
    const [x, y] = placements[state.texts.length % placements.length];
    return { id: state.nextTextId++, content, fontFamily: "Cairo", size: 28, color: "#6432f2", align: "center", letterSpacing: 0, lineHeight: 1.2, rotation: 0, x, y, layerOrder: nextLayerOrder() };
}

function ensureSelectedText() {
    let text = selectedText();
    if (text) return text;
    text = createText();
    state.texts.push(text);
    state.selectedTextId = text.id;
    state.selectedImageId = null;
    return text;
}

function createTextLayer(text) {
    const layer = document.createElement("div");
    layer.className = "text-layer";
    layer.dataset.textId = text.id;
    layer.innerHTML = `
        <span class="text-content" dir="auto"></span>
        <button class="text-handle text-rotate-handle" type="button" data-text-action="rotate" aria-label="تدوير النص"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></button>
    `;
    $("textLayers").appendChild(layer);
    return layer;
}

function fitTextLayer(text, layer) {
    if (!text.content) return;
    const zone = $("printZone");
    const content = layer.querySelector(".text-content");
    const styles = getComputedStyle(content);
    const measuringCanvas = fitTextLayer.measuringCanvas || (fitTextLayer.measuringCanvas = document.createElement("canvas"));
    const context = measuringCanvas.getContext("2d");
    context.font = `${styles.fontWeight} ${styles.fontSize} ${styles.fontFamily}`;
    const letterSpacing = Number(text.letterSpacing) || 0;
    const naturalWidth = Math.max(...text.content.split("\n").map(line => context.measureText(line || " ").width + Math.max(0, line.length - 1) * letterSpacing));
    const layerWidth = clamp(Math.ceil(naturalWidth + 22), 28, Math.max(28, zone.clientWidth - 8));
    layer.style.width = `${layerWidth}px`;
    layer.style.height = "auto";
    const layerStyles = getComputedStyle(layer);
    const verticalSpacing = Number.parseFloat(layerStyles.paddingTop) + Number.parseFloat(layerStyles.paddingBottom) + Number.parseFloat(layerStyles.borderTopWidth) + Number.parseFloat(layerStyles.borderBottomWidth);
    const contentHeight = Math.ceil(content.getBoundingClientRect().height);
    const layerHeight = Math.min(contentHeight + verticalSpacing + 2, Math.max(20, zone.clientHeight - 16));
    layer.style.height = `${layerHeight}px`;
    if (zone.clientWidth > 0) {
        text.previewMetrics = {
            widthPercent: layerWidth / zone.clientWidth * 100,
            fontSizePercent: Number(text.size) / zone.clientWidth * 100,
            letterSpacingPercent: letterSpacing / zone.clientWidth * 100
        };
    }
}

function renderTexts() {
    const container = $("textLayers");
    container.querySelectorAll(".text-layer").forEach(layer => {
        if (!state.texts.some(text => String(text.id) === layer.dataset.textId)) layer.remove();
    });
    state.texts.forEach(text => {
        const layer = container.querySelector(`[data-text-id="${text.id}"]`) || createTextLayer(text);
        const content = layer.querySelector(".text-content");
        layer.hidden = !text.content;
        if (!text.content) return;
        layer.classList.toggle("selected", text.id === state.selectedTextId);
        layer.style.zIndex = String(Number(text.layerOrder) || 1);
        content.textContent = text.content;
        layer.style.left = "0";
        layer.style.top = "0";
        layer.style.fontSize = `${text.size}px`;
        layer.style.fontFamily = `"${text.fontFamily}", sans-serif`;
        content.style.fontFamily = `"${text.fontFamily}", sans-serif`;
        layer.style.color = text.color;
        layer.style.textAlign = text.align;
        content.style.letterSpacing = `${Number(text.letterSpacing) || 0}px`;
        layer.style.lineHeight = text.lineHeight;
        layer.style.transform = "none";
        fitTextLayer(text, layer);
        constrainTextPosition(text, layer);
        layer.style.left = `calc(${text.x}% - ${layer.offsetWidth / 2}px)`;
        layer.style.top = `calc(${text.y}% - ${layer.offsetHeight / 2}px)`;
        layer.style.transform = `rotate(${text.rotation}deg)`;
    });
}
function renderProduct() {
    normalizeLayerOrders();
    $("productName").value = product.name;
    const productCanvas = $("productCanvas");
    if (product.canvasWidth) productCanvas.style.setProperty("--product-canvas-width", product.canvasWidth); else productCanvas.style.removeProperty("--product-canvas-width");
    if (product.canvasMobileWidth) productCanvas.style.setProperty("--product-canvas-mobile-width", product.canvasMobileWidth); else productCanvas.style.removeProperty("--product-canvas-mobile-width");
    const activeColor = product.colors.find(color => color.id === state.colorId) || product.colors[0];
    const productImage = $("productImage");
    const area = activeArea();
    productImage.src = (area.id === "front" && (activeColor.image || product.image)) || area.image || activeColor.image || product.image;
    productImage.alt = `${product.name} - ${area.name}`;
    if (productImage.complete && productImage.naturalWidth) updateProductAspectRatio();
    $("areaDimensions").textContent = area.dimensions;
    const printZone = $("printZone");
    printZone.style.top = product.printZone?.top || "";
    printZone.style.left = product.printZone?.left || "";
    printZone.style.width = product.printZone?.width || "";
    printZone.style.height = product.printZone?.height || "";
    state.hasDesign = Boolean(state.images.length || state.texts.some(text => text.content));
    document.querySelector(".zone-badge").hidden = state.hasDesign || state.zoneLabelHidden;
    renderImages();
    renderTexts();
    const hasSelection = state.selectedImageId !== null || state.selectedTextId !== null;
    $("selectionActions").hidden = !hasSelection;
    $("selectionActionLabel").textContent = state.selectedImageId !== null ? "الصورة المحددة" : "النص المحدد";
    syncLayerActionButtons();
    syncTransformControls();
}

function updateProductAspectRatio() {
    const image = $("productImage");
    if (!image.naturalWidth || !image.naturalHeight) return;
    const canvas = $("productCanvas");
    canvas.style.setProperty("--product-aspect-ratio", `${image.naturalWidth} / ${image.naturalHeight}`);
    fitProductToStage();
}

function fitProductToStage() {
    const image = $("productImage");
    const canvas = $("productCanvas");
    const stage = $("canvasStage");
    if (!image.naturalWidth || !image.naturalHeight || !stage.clientHeight) return;

    canvas.style.removeProperty("--product-render-width");
    const preferredWidth = canvas.offsetWidth;
    const stageStyles = getComputedStyle(stage);
    const availableHeight = stage.clientHeight
        - parseFloat(stageStyles.paddingTop)
        - parseFloat(stageStyles.paddingBottom);
    const heightFitWidth = availableHeight * image.naturalWidth / image.naturalHeight;
    canvas.style.setProperty("--product-render-width", `${Math.max(1, Math.min(preferredWidth, heightFitWidth))}px`);
}
function renderAreas() { const select = $("areaSelect"); select.innerHTML = product.areas.map(area => `<option value="${area.id}">${area.name}</option>`).join(""); select.value = state.areaId; }
function renderColors() { $("swatches").innerHTML = product.colors.map(color => `<button class="swatch ${color.id === state.colorId ? "active" : ""}" type="button" data-color="${color.id}" style="background:${color.value}" aria-label="${product.colorName[color.id]}"></button>`).join(""); document.querySelectorAll("[data-color]").forEach(button => button.addEventListener("click", () => { state.colorId = button.dataset.color; renderColors(); renderProduct(); showToast(`تم اختيار اللون ${product.colorName[state.colorId]}`); })); }
function renderSizes() { $("sizes").innerHTML = product.sizes.map(size => `<button class="size-button ${size === state.sizeId ? "active" : ""}" type="button" data-size="${size}">${size}</button>`).join(""); document.querySelectorAll("[data-size]").forEach(button => button.addEventListener("click", () => { state.sizeId = button.dataset.size; renderSizes(); renderProduct(); })); }

function renderComponentsLibrary() {
    $("componentsGrid").innerHTML = componentAssets.map(asset => `
        <button class="component-thumbnail" type="button" data-component-id="${asset.id}" aria-label="إضافة ${asset.name}" title="${asset.name}">
            <img src="${asset.src}" alt="">
        </button>
    `).join("");
}

function toggleComponentsLibrary(open) {
    const library = $("componentsLibrary");
    const button = $("elementsTool");
    const menu = button.closest(".tool-menu");
    library.hidden = !open;
    button.setAttribute("aria-expanded", String(open));
    menu.classList.toggle("open", open);
}

function addComponentAsset(asset) {
    if (!asset) return;
    const offset = (state.images.length % 5) * 3;
    const image = {
        id: state.nextImageId++,
        name: asset.name,
        componentId: asset.id,
        url: asset.src,
        x: clamp(50 + offset, 25, 75),
        y: clamp(50 + offset, 25, 75),
        width: 46,
        height: 46,
        rotation: 0,
        layerOrder: nextLayerOrder()
    };
    state.images.push(image);
    state.selectedImageId = image.id;
    state.selectedTextId = null;
    state.zoneLabelHidden = true;
    setDetailsMode("product");
    renderProduct();
    showToast(`تمت إضافة ${asset.name}`);
}

function syncTextControls(text = selectedText()) {
    const values = text || { content: "", fontFamily: "Cairo", size: 28, color: "#6432f2", align: "center", letterSpacing: 0, lineHeight: 1.2, rotation: 0, x: 50, y: 50 };
    $("textContent").value = values.content;
    $("fontFamily").value = values.fontFamily;
    $("fontFamily").style.setProperty("--selected-font", `"${values.fontFamily}"`);
    $("textSize").value = values.size;
    $("textSizeNumber").value = values.size;
    $("textColor").value = values.color;
    $("letterSpacing").value = values.letterSpacing;
    $("lineHeight").value = values.lineHeight;
    $("rotation").value = values.rotation;
    document.querySelectorAll("[data-align]").forEach(button => button.classList.toggle("active", button.dataset.align === values.align));
}

function openTextPanel(startNew = false) {
    if (startNew) {
        state.selectedTextId = null;
        state.selectedImageId = null;
        const pendingText = [...state.texts].reverse().find(text => !text.content);
        if (pendingText) state.selectedTextId = pendingText.id;
    } else if (!selectedText()) {
        const latestText = state.texts.at(-1);
        state.selectedTextId = latestText?.id || null;
        if (latestText) state.selectedImageId = null;
    }
    const text = ensureSelectedText();
    state.zoneLabelHidden = true;
    setDetailsMode("text");
    syncTextControls(text);
    renderProduct();
    const detailsPanel = document.querySelector(".details-panel");
    void detailsPanel.offsetHeight;
    detailsPanel.scrollTop = 0;
    requestAnimationFrame(() => { detailsPanel.scrollTop = 0; });
    $("textContent").focus({ preventScroll: true });
}

function setDetailsMode(mode) {
    const textMode = mode === "text";
    $("textPanel").hidden = !textMode;
    document.querySelector(".details-panel").classList.toggle("text-mode", textMode);
    document.querySelectorAll("[data-details-mode]").forEach(button => {
        const active = button.dataset.detailsMode === mode;
        button.classList.toggle("active", active);
        button.setAttribute("aria-selected", String(active));
    });
}

function closeTextPanel() { state.selectedTextId = null; state.zoneLabelHidden = state.texts.some(text => text.content); setDetailsMode("product"); renderProduct(); }

function removeUploadedImage(imageId = state.selectedImageId) {
    const imageIndex = state.images.findIndex(image => image.id === imageId);
    if (imageIndex < 0) return;
    state.images.splice(imageIndex, 1);
    state.selectedImageId = state.images.at(-1)?.id || null;
}

function removeText(textId = state.selectedTextId) {
    const textIndex = state.texts.findIndex(text => text.id === textId);
    if (textIndex < 0) return;
    state.texts.splice(textIndex, 1);
    state.selectedTextId = state.texts.at(-1)?.id || null;
    syncTextControls();
}

function duplicateSelectedDesign() {
    if (state.selectedImageId !== null) {
        const image = state.images.find(item => item.id === state.selectedImageId);
        if (!image) return;
        const duplicate = { ...image, id: state.nextImageId++, x: clamp(Number(image.x) + 4, 0, 100), y: clamp(Number(image.y) + 4, 0, 100), layerOrder: nextLayerOrder() };
        state.images.push(duplicate);
        state.selectedImageId = duplicate.id;
        state.selectedTextId = null;
        setDetailsMode("product");
        renderProduct();
        showToast("تم تكرار الصورة");
        return;
    }
    const text = selectedText();
    if (text) {
        const duplicate = { ...text, id: state.nextTextId++, x: clamp(Number(text.x) + 4, 0, 100), y: clamp(Number(text.y) + 4, 0, 100), layerOrder: nextLayerOrder() };
        state.texts.push(duplicate);
        state.selectedTextId = duplicate.id;
        syncTextControls(duplicate);
        renderProduct();
        showToast("تم تكرار النص");
        return;
    }
    showToast("حدد صورة أو نصاً أولاً");
}

function deleteSelectedDesign() {
    if (state.selectedImageId !== null) {
        removeUploadedImage();
        renderProduct();
        showToast("تم حذف الصورة");
        return;
    }
    if (state.selectedTextId !== null) {
        removeText();
        renderProduct();
        showToast("تم حذف النص");
        return;
    }
    showToast("حدد صورة أو نصاً أولاً");
}

document.querySelectorAll("[data-tool]").forEach(button => button.addEventListener("click", () => {
    const tool = button.dataset.tool;
    if (tool === "elements") {
        const willOpen = $("componentsLibrary").hidden;
        state.tool = willOpen ? tool : null;
        document.querySelectorAll("[data-tool]").forEach(item => item.classList.toggle("active", item === button && willOpen));
        toggleComponentsLibrary(willOpen);
        if (willOpen) closeTextPanel();
        return;
    }
    state.tool = tool;
    toggleComponentsLibrary(false);
    document.querySelectorAll("[data-tool]").forEach(item => item.classList.toggle("active", item === button));
    if (tool === "upload") {
        closeTextPanel();
        $("imageUpload").click();
    } else if (tool === "text") openTextPanel(true);
}));
$("componentsGrid").addEventListener("click", event => {
    const thumbnail = event.target.closest("[data-component-id]");
    if (!thumbnail) return;
    addComponentAsset(componentAssets.find(asset => asset.id === thumbnail.dataset.componentId));
});
document.querySelectorAll("[data-details-mode]").forEach(button => button.addEventListener("click", () => { if (button.dataset.detailsMode === "text") openTextPanel(); else closeTextPanel(); }));
$("textContent").addEventListener("input", event => { const text = ensureSelectedText(); text.content = event.target.value; renderProduct(); });
$("fontFamily").addEventListener("change", async event => {
    const select = event.currentTarget;
    const detailsPanel = select.closest(".details-panel");
    const preservedScrollTop = detailsPanel?.scrollTop || 0;
    const nextFontFamily = select.value;
    const text = ensureSelectedText();
    const textId = text.id;
    const requestId = ++fontSelectionRequest;
    select.setAttribute("aria-busy", "true");
    try {
        await loadFont(nextFontFamily, 800, text.size, text.content || "PalPrints تصميم");
        if (requestId !== fontSelectionRequest) return;
        const targetText = state.texts.find(item => item.id === textId);
        if (!targetText) return;
        targetText.fontFamily = nextFontFamily;
        if (state.selectedTextId === textId) select.style.setProperty("--selected-font", `"${nextFontFamily}"`);
        renderTexts();
        syncTransformControls();
        captureDesignerHistory();
    } catch {
        if (requestId !== fontSelectionRequest) return;
        select.value = selectedText()?.fontFamily || "Cairo";
        showToast("تعذر تحميل الخط المحدد");
    } finally {
        if (detailsPanel) {
            detailsPanel.scrollTop = preservedScrollTop;
            requestAnimationFrame(() => { detailsPanel.scrollTop = preservedScrollTop; });
        }
        if (requestId === fontSelectionRequest) select.removeAttribute("aria-busy");
    }
});
function updateTextSize(value) {
    const size = clamp(Number(value) || 12, 12, 64);
    ensureSelectedText().size = size;
    $("textSize").value = size;
    $("textSizeNumber").value = size;
    renderProduct();
}
$("textSize").addEventListener("input", event => updateTextSize(event.target.value));
$("textSizeNumber").addEventListener("input", event => updateTextSize(event.target.value));
$("textColor").addEventListener("input", event => { ensureSelectedText().color = event.target.value; renderProduct(); });
$("letterSpacing").addEventListener("input", event => { ensureSelectedText().letterSpacing = event.target.value; renderProduct(); });
$("lineHeight").addEventListener("input", event => { ensureSelectedText().lineHeight = event.target.value; renderProduct(); });
$("rotation").addEventListener("input", event => applyComponentRotation(event.target.value, true));
$("positionX").addEventListener("input", event => setComponentEdgeCoordinate("x", event.target.value));
$("positionY").addEventListener("input", event => setComponentEdgeCoordinate("y", event.target.value));
document.querySelectorAll("[data-align]").forEach(button => button.addEventListener("click", () => { const text = ensureSelectedText(); text.align = button.dataset.align; document.querySelectorAll("[data-align]").forEach(item => item.classList.toggle("active", item === button)); renderProduct(); }));
$("addTextButton").addEventListener("click", () => { const text = ensureSelectedText(); text.content = $("textContent").value.trim() || "تصميمك"; $("textContent").value = text.content; renderProduct(); showToast("تمت إضافة النص إلى التصميم"); });
$("bringForward").addEventListener("click", () => moveSelectedLayer(1));
$("sendBackward").addEventListener("click", () => moveSelectedLayer(-1));
$("duplicateText").addEventListener("click", duplicateSelectedDesign);
$("deleteText").addEventListener("click", deleteSelectedDesign);
$("imageUpload").addEventListener("change", event => {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith("image/")) { showToast("يرجى اختيار ملف صورة"); event.target.value = ""; return; }
    event.target.value = "";
    const reader = new FileReader();
    reader.onerror = () => showToast("تعذر قراءة الصورة");
    reader.onload = () => {
        const imageUrl = String(reader.result);
        const imageId = state.nextImageId++;
        const offset = (state.images.length % 4) * 4;
        const uploadedImage = { id: imageId, name: file.name, url: imageUrl, x: 50 + offset, y: 50 + offset, width: 70, height: 70, rotation: 0, layerOrder: nextLayerOrder() };
        state.images.push(uploadedImage);
        state.selectedImageId = imageId;
        state.selectedTextId = null;
        setDetailsMode("product");
        const imageProbe = new Image();
        imageProbe.onload = () => {
            const image = state.images.find(item => item.id === imageId && item.url === imageUrl);
            if (!image) return;
            const imageRatio = imageProbe.naturalWidth / imageProbe.naturalHeight;
            image.width = imageRatio >= 1 ? 70 : 70 * imageRatio;
            image.height = imageRatio >= 1 ? 70 / imageRatio : 70;
            image.naturalWidth = imageProbe.naturalWidth;
            image.naturalHeight = imageProbe.naturalHeight;
            renderProduct();
            captureDesignerHistory();
        };
        imageProbe.onerror = () => {
            if (!state.images.some(item => item.id === imageId && item.url === imageUrl)) return;
            removeUploadedImage(imageId);
            renderProduct();
            showToast("تعذر تحميل الصورة");
        };
        imageProbe.src = imageUrl;
        renderProduct();
        showToast("تمت إضافة الصورة");
    };
    reader.readAsDataURL(file);
});
$("areaSelect").addEventListener("change", event => {
    syncActiveAreaDesign();
    state.areaId = event.target.value;
    loadAreaDesign(state.areaId);
    syncTextControls();
    renderProduct();
});
$("productImage").addEventListener("load", updateProductAspectRatio);
window.addEventListener("resize", fitProductToStage);
$("zoomIn").addEventListener("click", () => { state.zoom = Math.min(1.4, state.zoom + .1); $("productCanvas").style.transform = `scale(${state.zoom})`; $("zoomValue").textContent = `${Math.round(state.zoom * 100)}%`; });
$("zoomOut").addEventListener("click", () => { state.zoom = Math.max(.7, state.zoom - .1); $("productCanvas").style.transform = `scale(${state.zoom})`; $("zoomValue").textContent = `${Math.round(state.zoom * 100)}%`; });
$("fitButton").addEventListener("click", () => { state.zoom = 1; $("productCanvas").style.transform = "scale(1)"; $("zoomValue").textContent = "100%"; });
$("undoButton").addEventListener("click", async () => {
    const changed = await designerHistory?.undo();
    showToast(changed ? "تم التراجع" : "لا توجد تغييرات للتراجع عنها");
});
$("redoButton").addEventListener("click", async () => {
    const changed = await designerHistory?.redo();
    showToast(changed ? "تمت إعادة التغيير" : "لا توجد تغييرات لإعادتها");
});
$("saveButton").addEventListener("click", () => { if (persistDesignerState()) showToast("تم حفظ التصميم"); });
$("previewButton").addEventListener("click", () => {
    if (!persistDesignerState()) return;
    window.location.href =
        window.palPrintsCreateRoutes.review;
});

function clamp(value, minimum, maximum) { return Math.min(maximum, Math.max(minimum, value)); }

function normalizeRotation(rotation) {
    const normalized = Number(rotation) % 360;
    return normalized < 0 ? normalized + 360 : normalized;
}

function rotatedLayerSize(layer, rotation) {
    const angle = normalizeRotation(rotation) * Math.PI / 180;
    const cosine = Math.abs(Math.cos(angle));
    const sine = Math.abs(Math.sin(angle));
    return {
        width: layer.offsetWidth * cosine + layer.offsetHeight * sine,
        height: layer.offsetWidth * sine + layer.offsetHeight * cosine
    };
}

function rotationFitsPrintZone(component, layer, rotation) {
    if (!component || !layer) return false;
    const zone = $("printZone");
    const bounds = rotatedLayerSize(layer, rotation);
    const centerX = Number(component.x) * zone.clientWidth / 100;
    const centerY = Number(component.y) * zone.clientHeight / 100;
    const tolerance = .25;
    return centerX - bounds.width / 2 >= -tolerance
        && centerX + bounds.width / 2 <= zone.clientWidth + tolerance
        && centerY - bounds.height / 2 >= -tolerance
        && centerY + bounds.height / 2 <= zone.clientHeight + tolerance;
}

function componentEdgeCoordinates(component, layer) {
    const zone = $("printZone");
    const bounds = rotatedLayerSize(layer, component.rotation);
    const rightGap = zone.clientWidth - (Number(component.x) * zone.clientWidth / 100 + bounds.width / 2);
    const bottomGap = zone.clientHeight - (Number(component.y) * zone.clientHeight / 100 + bounds.height / 2);
    return {
        x: Math.max(0, rightGap / zone.clientWidth * 100),
        y: Math.max(0, bottomGap / zone.clientHeight * 100),
        maxX: Math.max(0, 100 - bounds.width / zone.clientWidth * 100),
        maxY: Math.max(0, 100 - bounds.height / zone.clientHeight * 100)
    };
}

function syncTransformControls() {
    const component = selectedComponent();
    const panel = $("componentTransformPanel");
    const layer = componentLayer(component);
    panel.hidden = !component || !layer || layer.hidden;
    if (panel.hidden) return;
    const coordinates = componentEdgeCoordinates(component, layer);
    $("positionX").max = Math.floor(coordinates.maxX);
    $("positionY").max = Math.floor(coordinates.maxY);
    $("positionX").value = Math.round(coordinates.x);
    $("positionY").value = Math.round(coordinates.y);
    $("rotation").value = component.rotation;
}

function setComponentEdgeCoordinate(axis, value) {
    const component = selectedComponent();
    const layer = componentLayer(component);
    if (!component || !layer) return;
    const coordinates = componentEdgeCoordinates(component, layer);
    const gap = clamp(Number(value) || 0, 0, axis === "x" ? coordinates.maxX : coordinates.maxY);
    const zone = $("printZone");
    const bounds = rotatedLayerSize(layer, component.rotation);
    if (axis === "x") component.x = 100 - gap - bounds.width / zone.clientWidth * 50;
    else component.y = 100 - gap - bounds.height / zone.clientHeight * 50;
    renderProduct();
}

function applyComponentRotation(value, showBoundaryMessage = false) {
    const component = selectedComponent();
    const layer = componentLayer(component);
    const nextRotation = Number(value);
    if (!component || !layer || !Number.isFinite(nextRotation)) return;
    if (!rotationFitsPrintZone(component, layer, nextRotation)) {
        $("rotation").value = component.rotation;
        if (showBoundaryMessage) showToast("لا يمكن تدوير العنصر خارج حدود منطقة الأمان");
        return false;
    }
    component.rotation = nextRotation;
    renderProduct();
    return true;
}

function beginBoundedRotation(event, component, layerElement, afterRender = () => {}) {
    event.preventDefault();
    event.stopPropagation();
    const layer = layerElement.getBoundingClientRect();
    const centerX = layer.left + layer.width / 2;
    const centerY = layer.top + layer.height / 2;
    let previousPointerAngle = Math.atan2(event.clientY - centerY, event.clientX - centerX) * 180 / Math.PI;
    let blockedDirection = 0;
    const move = moveEvent => {
        const pointerAngle = Math.atan2(moveEvent.clientY - centerY, moveEvent.clientX - centerX) * 180 / Math.PI;
        let delta = pointerAngle - previousPointerAngle;
        if (delta > 180) delta -= 360;
        if (delta < -180) delta += 360;
        previousPointerAngle = pointerAngle;
        const direction = Math.sign(delta);
        if (!direction) return;
        if (blockedDirection && direction === blockedDirection) return;
        if (blockedDirection && direction !== blockedDirection) blockedDirection = 0;
        const candidate = normalizeRotation(Number(component.rotation) + delta);
        if (!rotationFitsPrintZone(component, layerElement, candidate)) {
            blockedDirection = direction;
            return;
        }
        component.rotation = Math.round(candidate);
        renderProduct();
        afterRender();
    };
    const stop = () => {
        window.removeEventListener("pointermove", move);
        window.removeEventListener("pointerup", stop);
    };
    window.addEventListener("pointermove", move);
    window.addEventListener("pointerup", stop);
    return stop;
}

function getPrintZoneBounds() {
    const zone = $("printZone");
    const bounds = zone.getBoundingClientRect();
    const scaleX = bounds.width / zone.offsetWidth;
    const scaleY = bounds.height / zone.offsetHeight;
    const left = bounds.left + zone.clientLeft * scaleX;
    const top = bounds.top + zone.clientTop * scaleY;
    const width = zone.clientWidth * scaleX;
    const height = zone.clientHeight * scaleY;
    return { left, top, width, height, right: left + width, bottom: top + height };
}

function constrainImagePosition(image, layer) {
    if (!image || !layer) return;
    const zone = $("printZone");
    const { width: rotatedWidth, height: rotatedHeight } = rotatedLayerSize(layer, image.rotation);
    const horizontalPadding = Math.min(50, (rotatedWidth / zone.clientWidth) * 50);
    const verticalPadding = Math.min(50, (rotatedHeight / zone.clientHeight) * 50);
    image.x = clamp(Number(image.x), horizontalPadding, 100 - horizontalPadding);
    image.y = clamp(Number(image.y), verticalPadding, 100 - verticalPadding);
}

function constrainTextPosition(text, layer) {
    if (!text || !layer) return;
    const zone = $("printZone");
    const { width: rotatedWidth, height: rotatedHeight } = rotatedLayerSize(layer, text.rotation);
    const horizontalPadding = Math.min(50, (rotatedWidth / zone.clientWidth) * 50);
    const verticalPadding = Math.min(50, (rotatedHeight / zone.clientHeight) * 50);
    text.x = clamp(Number(text.x), horizontalPadding, 100 - horizontalPadding);
    text.y = clamp(Number(text.y), verticalPadding, 100 - verticalPadding);
}

let imageDragOffset = null;
const imageLayers = $("imageLayers");
imageLayers.addEventListener("click", event => {
    const layer = event.target.closest(".image-layer");
    if (!layer) return;
    event.stopPropagation();
    const imageId = Number(layer.dataset.imageId);
    if (event.target.closest('[data-image-action="delete"]')) {
        removeUploadedImage(imageId);
        renderProduct();
        showToast("تم حذف الصورة");
        return;
    }
    state.selectedImageId = imageId;
    state.selectedTextId = null;
    setDetailsMode("product");
    renderProduct();
});
imageLayers.addEventListener("pointerdown", event => {
    const layer = event.target.closest(".image-layer");
    if (!layer) return;
    const image = state.images.find(item => item.id === Number(layer.dataset.imageId));
    if (!image) return;
    state.selectedImageId = image.id;
    state.selectedTextId = null;
    setDetailsMode("product");
    const action = event.target.closest("[data-image-action]")?.dataset.imageAction;
    if (action === "resize") return beginImageResize(event, "both", image, layer);
    if (action === "stretch-x") return beginImageResize(event, "horizontal", image, layer);
    if (action === "stretch-y") return beginImageResize(event, "vertical", image, layer);
    if (action === "rotate") return beginImageRotation(event, image, layer);
    if (action === "delete") return;
    event.preventDefault();
    event.stopPropagation();
    const zone = getPrintZoneBounds();
    const layerRect = layer.getBoundingClientRect();
    imageDragOffset = { imageId: image.id, x: event.clientX - (layerRect.left + layerRect.width / 2), y: event.clientY - (layerRect.top + layerRect.height / 2), zone, width: layerRect.width, height: layerRect.height };
    layer.setPointerCapture(event.pointerId);
    renderProduct();
    updateImageOverlapIndicators(image.id);
});
imageLayers.addEventListener("pointermove", event => {
    if (!imageDragOffset) return;
    const image = state.images.find(item => item.id === imageDragOffset.imageId);
    if (!image) return;
    const { zone, width, height } = imageDragOffset;
    const centerX = clamp(event.clientX - imageDragOffset.x, zone.left + width / 2, zone.right - width / 2);
    const centerY = clamp(event.clientY - imageDragOffset.y, zone.top + height / 2, zone.bottom - height / 2);
    image.x = ((centerX - zone.left) / zone.width) * 100;
    image.y = ((centerY - zone.top) / zone.height) * 100;
    renderProduct();
    updateImageOverlapIndicators(image.id);
});
function finishImageDrag(event) {
    const layer = event.target.closest(".image-layer");
    imageDragOffset = null;
    if (layer?.hasPointerCapture(event.pointerId)) layer.releasePointerCapture(event.pointerId);
    updateImageOverlapIndicators();
}
imageLayers.addEventListener("pointerup", finishImageDrag);
imageLayers.addEventListener("pointercancel", finishImageDrag);
function beginImageResize(event, mode, image, layerElement) {
    event.preventDefault();
    event.stopPropagation();
    const zone = getPrintZoneBounds();
    const layer = layerElement.getBoundingClientRect();
    const centerX = layer.left + layer.width / 2;
    const centerY = layer.top + layer.height / 2;
    const startX = event.clientX;
    const startY = event.clientY;
    const startWidth = image.width;
    const startHeight = image.height;
    const startDistance = Math.max(1, Math.hypot(startX - centerX, startY - centerY));
    const rotation = Number(image.rotation) * Math.PI / 180;
    updateImageOverlapIndicators(image.id);
    const move = moveEvent => {
        if (mode === "both") {
            const distance = Math.hypot(moveEvent.clientX - centerX, moveEvent.clientY - centerY);
            const minimumScale = Math.max(10 / startWidth, 10 / startHeight);
            const maximumScale = Math.min(100 / startWidth, 100 / startHeight);
            const scale = clamp(distance / startDistance, minimumScale, maximumScale);
            image.width = startWidth * scale;
            image.height = startHeight * scale;
        } else {
            const deltaX = moveEvent.clientX - startX;
            const deltaY = moveEvent.clientY - startY;
            const localX = deltaX * Math.cos(rotation) + deltaY * Math.sin(rotation);
            const localY = -deltaX * Math.sin(rotation) + deltaY * Math.cos(rotation);
            if (mode === "horizontal") image.width = clamp(startWidth + (localX / zone.width) * 100, 10, 100);
            if (mode === "vertical") image.height = clamp(startHeight + (localY / zone.height) * 100, 10, 100);
        }
        renderProduct();
        updateImageOverlapIndicators(image.id);
    };
    const stop = () => { updateImageOverlapIndicators(); window.removeEventListener("pointermove", move); window.removeEventListener("pointerup", stop); };
    window.addEventListener("pointermove", move);
    window.addEventListener("pointerup", stop);
}
function beginImageRotation(event, image, layerElement) {
    updateImageOverlapIndicators(image.id);
    const stopRotation = beginBoundedRotation(event, image, layerElement, () => updateImageOverlapIndicators(image.id));
    window.addEventListener("pointerup", () => { updateImageOverlapIndicators(); stopRotation(); }, { once: true });
}
document.addEventListener("keydown", event => {
    if ((state.selectedImageId === null && state.selectedTextId === null) || !["Delete", "Backspace"].includes(event.key) || event.target.closest("input, textarea, select")) return;
    event.preventDefault();
    deleteSelectedDesign();
});
window.addEventListener("beforeunload", () => {
    const historyUrls = typeof designerHistoryAssets === "undefined" ? [] : [...designerHistoryAssets.values()];
    new Set([...state.images.map(image => image.url), ...historyUrls].filter(url => url.startsWith("blob:"))).forEach(url => URL.revokeObjectURL(url));
});

let textDragOffset = null;
const textLayers = $("textLayers");
textLayers.addEventListener("click", event => {
    if (event.target.closest(".text-layer")) event.stopPropagation();
});

function selectableTextLayersAtPoint(clientX, clientY) {
    return [...textLayers.querySelectorAll(".text-layer:not([hidden])")].reverse().filter(layer => {
        const bounds = layer.getBoundingClientRect();
        return clientX >= bounds.left && clientX <= bounds.right && clientY >= bounds.top && clientY <= bounds.bottom;
    });
}

function textLayerForPointer(event, targetLayer) {
    if (event.target.closest("[data-text-action]")) return targetLayer;
    const candidates = selectableTextLayersAtPoint(event.clientX, event.clientY);
    if (candidates.length < 2) return targetLayer;
    const selectedIndex = candidates.findIndex(layer => Number(layer.dataset.textId) === state.selectedTextId);
    return selectedIndex < 0 ? targetLayer : candidates[(selectedIndex + 1) % candidates.length];
}

textLayers.addEventListener("pointerdown", event => {
    const targetLayer = event.target.closest(".text-layer");
    if (!targetLayer) return;
    const layer = textLayerForPointer(event, targetLayer);
    const text = state.texts.find(item => item.id === Number(layer.dataset.textId));
    if (!text) return;
    state.selectedTextId = text.id;
    state.selectedImageId = null;
    syncTextControls(text);
    setDetailsMode("text");
    renderProduct();
    if (event.target.closest('[data-text-action="rotate"]')) return beginTextRotation(event, text, layer);
    if (!event.target.closest(".text-content")) return;
    event.preventDefault();
    event.stopPropagation();
    const zone = getPrintZoneBounds();
    const layerRect = layer.getBoundingClientRect();
    textDragOffset = { textId: text.id, x: event.clientX - (layerRect.left + layerRect.width / 2), y: event.clientY - (layerRect.top + layerRect.height / 2), zone };
    layer.setPointerCapture(event.pointerId);
});
document.addEventListener("pointerdown", event => {
    let shouldRender = false;
    if (!event.target.closest(".image-layer") && !event.target.closest(".details-mode-toggle") && !event.target.closest("#componentTransformPanel") && !event.target.closest("#selectionActions") && state.selectedImageId !== null) {
        state.selectedImageId = null;
        shouldRender = true;
    }
    if (!event.target.closest(".text-layer") && !event.target.closest(".details-mode-toggle") && !event.target.closest("#textPanel") && !event.target.closest("#componentTransformPanel") && !event.target.closest("#selectionActions") && state.selectedTextId !== null) {
        state.selectedTextId = null;
        shouldRender = true;
    }
    if (shouldRender) renderProduct();
});
textLayers.addEventListener("pointermove", event => {
    if (!textDragOffset) return;
    const text = state.texts.find(item => item.id === textDragOffset.textId);
    const layer = textLayers.querySelector(`[data-text-id="${textDragOffset.textId}"]`);
    if (!text || !layer) return;
    const zone = textDragOffset.zone;
    const layerBounds = layer.getBoundingClientRect();
    const rotatedWidth = layerBounds.width;
    const rotatedHeight = layerBounds.height;
    const centerX = clamp(event.clientX - textDragOffset.x, zone.left + rotatedWidth / 2, zone.right - rotatedWidth / 2);
    const centerY = clamp(event.clientY - textDragOffset.y, zone.top + rotatedHeight / 2, zone.bottom - rotatedHeight / 2);
    text.x = ((centerX - zone.left) / zone.width) * 100;
    text.y = ((centerY - zone.top) / zone.height) * 100;
    renderProduct();
});
function finishTextDrag(event) {
    const layer = event.target.closest(".text-layer");
    textDragOffset = null;
    if (layer?.hasPointerCapture(event.pointerId)) layer.releasePointerCapture(event.pointerId);
}
textLayers.addEventListener("pointerup", finishTextDrag);
textLayers.addEventListener("pointercancel", finishTextDrag);
function beginTextRotation(event, text, layerElement) {
    beginBoundedRotation(event, text, layerElement);
}

const designerHistoryAssets = new Map();
const designerHistoryAssetKeys = new Map();
let nextDesignerHistoryAssetId = 1;

function historyAssetKey(url) {
    if (!url) return "";
    if (designerHistoryAssetKeys.has(url)) return designerHistoryAssetKeys.get(url);
    const key = `asset-${nextDesignerHistoryAssetId++}`;
    designerHistoryAssetKeys.set(url, key);
    designerHistoryAssets.set(key, url);
    return key;
}

function serializeDesignerHistoryState() {
    syncActiveAreaDesign();
    const designsByArea = Object.fromEntries(Object.entries(state.designsByArea).map(([areaId, design]) => [areaId, {
        images: (design.images || []).map(image => {
            const { url, ...imageState } = image;
            return { ...imageState, historyAssetKey: historyAssetKey(url) };
        }),
        texts: (design.texts || []).map(text => {
            const { previewMetrics, ...textState } = text;
            return textState;
        }),
        nextImageId: Number(design.nextImageId) || 1,
        nextTextId: Number(design.nextTextId) || 1,
        zoneLabelHidden: Boolean(design.zoneLabelHidden)
    }]));

    return {
        colorId: state.colorId,
        sizeId: state.sizeId,
        areaId: state.areaId,
        designsByArea
    };
}

function restoreDesignerHistoryState(snapshot) {
    state.colorId = product.colors.some(color => color.id === snapshot.colorId) ? snapshot.colorId : product.colors[0].id;
    state.sizeId = product.sizes.includes(snapshot.sizeId) ? snapshot.sizeId : product.sizes[0];
    state.areaId = product.areas.some(area => area.id === snapshot.areaId) ? snapshot.areaId : product.areas[0].id;
    state.designsByArea = Object.fromEntries(Object.entries(snapshot.designsByArea || {}).map(([areaId, design]) => [areaId, {
        images: (design.images || []).map(image => {
            const { historyAssetKey: assetKey, ...imageState } = image;
            return { ...imageState, url: designerHistoryAssets.get(assetKey) || "" };
        }),
        texts: (design.texts || []).map(text => ({ ...text })),
        nextImageId: Number(design.nextImageId) || 1,
        nextTextId: Number(design.nextTextId) || 1,
        zoneLabelHidden: Boolean(design.zoneLabelHidden)
    }]));

    loadAreaDesign(state.areaId);
    renderAreas();
    renderColors();
    renderSizes();
    setDetailsMode("product");
    syncTextControls();
    renderProduct();
    return Promise.resolve();
}

function createDesignerHistoryAdapter() {
    const listeners = new Map();
    return {
        on(eventName, handler) {
            if (!listeners.has(eventName)) listeners.set(eventName, new Set());
            listeners.get(eventName).add(handler);
            return () => listeners.get(eventName)?.delete(handler);
        },
        off(eventName, handler) {
            listeners.get(eventName)?.delete(handler);
        },
        fire(eventName, payload) {
            listeners.get(eventName)?.forEach(handler => handler(payload));
        },
        toJSON: serializeDesignerHistoryState,
        loadFromJSON: restoreDesignerHistoryState,
        discardActiveObject() {
            state.selectedImageId = null;
            state.selectedTextId = null;
        },
        requestRenderAll: renderProduct
    };
}

function syncHistoryButtons(historyState) {
    const undoButton = $("undoButton");
    const redoButton = $("redoButton");
    undoButton.disabled = !historyState.canUndo;
    redoButton.disabled = !historyState.canRedo;
    undoButton.classList.toggle("muted", !historyState.canUndo);
    redoButton.classList.toggle("muted", !historyState.canRedo);
}

function captureDesignerHistory(eventName = "object:modified") {
    if (!designerHistoryAdapter || designerHistory?.isRespondingToHistory) return;
    designerHistoryAdapter.fire(eventName);
}

function queueDesignerHistoryCapture(eventName = "object:modified") {
    queueMicrotask(() => captureDesignerHistory(eventName));
}

function initializeDesignerHistory() {
    if (typeof CanvasHistoryManager !== "function") {
        console.error("CanvasHistoryManager failed to load.");
        return;
    }

    designerHistoryAdapter = createDesignerHistoryAdapter();
    designerHistory = new CanvasHistoryManager(designerHistoryAdapter, {
        maxHistory: 60,
        textDebounceMs: 250,
        serialize: serializeDesignerHistoryState,
        onChange: syncHistoryButtons
    });

    document.addEventListener("click", event => {
        if (event.target.closest?.("#undoButton, #redoButton, #saveButton, #previewButton")) return;
        queueDesignerHistoryCapture();
    }, true);
    document.addEventListener("input", () => queueDesignerHistoryCapture("text:changed"), true);
    document.addEventListener("change", event => {
        if (event.target === $("imageUpload")) return;
        queueDesignerHistoryCapture();
    }, true);
    document.addEventListener("pointerup", () => queueDesignerHistoryCapture(), true);
    document.addEventListener("keydown", event => {
        if (!["Delete", "Backspace"].includes(event.key) || event.target.closest?.("input, textarea, select")) return;
        queueDesignerHistoryCapture();
    }, true);
}

renderAreas(); renderColors(); renderSizes(); renderComponentsLibrary(); renderProduct(); initializeDesignerHistory(); revealDesignerAfterFontsLoad();
