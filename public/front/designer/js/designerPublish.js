"use strict";

/* Publish-details step for a designer's design. The design itself was made in the shared studio and previewed on the
   shared preview page, which left everything this step needs in sessionStorage ("palprintsDesignerPublish"):
   the product, colors, the picture of the design on the product, the layout and the artwork asset ids.
   Saving sends it all to the server in one multipart request; the artwork files come from the studio's IndexedDB. */
(function () {
  const $ = id => document.getElementById(id);
  const STASH_KEY = "palprintsDesignerPublish";
  const config = window.palPrintsPublish || {};

  function readStash() {
    try { return JSON.parse(sessionStorage.getItem(STASH_KEY) || "null"); } catch (error) { return null; }
  }
  function writeStash() {
    try { sessionStorage.setItem(STASH_KEY, JSON.stringify(stash)); } catch (error) { /* Optional. */ }
  }

  // The dialog and toast live on <body> so the page header and sidebar can never sit above their backdrop.
  ["successDialog", "toast"].forEach(id => { if ($(id)) document.body.appendChild($(id)); });

  const stash = readStash();
  if (!stash || !stash.preview || !stash.productCode) {
    $("publishMissing").hidden = false;
    return;
  }

  const basePrice = Number((config.basePrices || {})[String(stash.productCode).toUpperCase()] || 0);
  const state = {
    designName: stash.form?.designName ?? stash.designName ?? "",
    sellingPrice: Number(stash.form?.sellingPrice) || (basePrice + 16),
    rightsConfirmed: stash.form?.rightsConfirmed ?? false
  };

  function showToast(message, kind) {
    const toast = $("toast");
    toast.textContent = message;
    toast.classList.toggle("is-success", kind === "success");
    toast.classList.add("show");
    window.clearTimeout(showToast.timeout);
    showToast.timeout = window.setTimeout(() => toast.classList.remove("show"), 4500);
  }

  function remember() {
    stash.form = { designName: state.designName, sellingPrice: state.sellingPrice, rightsConfirmed: state.rightsConfirmed };
    writeStash();
  }

  function validationErrors() {
    const errors = [];
    if (!state.designName.trim()) errors.push("أدخل اسم التصميم.");
    if (state.sellingPrice < basePrice) errors.push("صحح سعر البيع.");
    if (!state.rightsConfirmed) errors.push("أكد امتلاك حقوق استخدام التصميم.");
    return errors;
  }

  function updateValidation() {
    const errors = validationErrors();
    $("publishButton").disabled = errors.length > 0;
    $("validationNote").textContent = errors[0] || "";
    return errors;
  }

  function updateProfit() {
    const field = $("sellingPrice"), digits = field.value.replace(/[^\d.]/g, "");
    if (field.value !== digits) field.value = digits; // numbers only, shown with ordinary digits
    state.sellingPrice = Number(digits) || 0;
    const profit = state.sellingPrice - basePrice;
    $("profitValue").textContent = Math.max(0, profit).toFixed(2);
    $("priceError").textContent = profit < 0 ? "يجب ألا يقل سعر البيع عن تكلفة المنتج." : "";
    remember();
    updateValidation();
  }

  /* The artwork the designer uploaded, from the studio's IndexedDB (same origin as this page). */
  function assetBlob(assetId) {
    return new Promise(resolve => {
      if (!window.indexedDB) return resolve(null);
      const open = indexedDB.open("palprintsStudioAssets", 1);
      open.onerror = open.onblocked = () => resolve(null);
      open.onsuccess = () => {
        try {
          const request = open.result.transaction("assets").objectStore("assets").get(stash.designId + ":" + assetId);
          request.onsuccess = () => resolve(request.result?.blob || null);
          request.onerror = () => resolve(null);
        } catch (error) { resolve(null); }
      };
    });
  }

  async function buildRequest(status) {
    const form = new FormData();
    form.append("data", JSON.stringify({
      status,
      productCode: stash.productCode,
      colorId: stash.colorId,
      sizeId: stash.sizeId,
      designName: state.designName.trim(),
      sellingPrice: state.sellingPrice,
      rightsConfirmed: state.rightsConfirmed,
      allowedColorIds: stash.allowedColorIds || [],
      category: stash.category || null,
      allowedSizeIds: stash.allowedSizeIds || [],
      mockup: stash.mockup,
      layout: stash.layout
    }));
    form.append("preview", await (await fetch(stash.preview)).blob(), "design-preview.png");

    const extensions = { "image/png": "png", "image/jpeg": "jpg", "image/webp": "webp", "image/svg+xml": "svg" };
    for (const id of stash.assetIds || []) {
      const blob = await assetBlob(id);
      if (!blob) continue;
      const record = (stash.assets || []).find(asset => asset.assetId === id);
      const base = String(record?.name || id).replace(/\.[a-z0-9]+$/i, "").slice(0, 80) || "design";
      form.append("files[]", blob, base + "." + (extensions[blob.type] || "png"));
      form.append("file_assets[]", id);
    }
    return form;
  }

  async function send(status) {
    const token = document.querySelector('meta[name="csrf-token"]');
    const response = await fetch(config.saveUrl, {
      method: "POST",
      headers: { "Accept": "application/json", "X-CSRF-TOKEN": token ? token.content : "" },
      body: await buildRequest(status)
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const first = data.errors ? Object.values(data.errors)[0][0] : data.message;
      throw new Error(first || "تعذر حفظ التصميم. حاول مرة أخرى.");
    }
    return data;
  }

  async function saveDraft() {
    const button = $("saveButton");
    button.disabled = true;
    try {
      if (!state.designName.trim()) { showToast("أدخل اسم التصميم أولًا."); return; }
      await send("draft");
      showToast("تم حفظ التصميم كمسودة", "success");
    } catch (error) {
      showToast(error.message);
    } finally { button.disabled = false; }
  }

  async function publish() {
    const errors = updateValidation();
    if (errors.length) { showToast(errors[0]); return; }
    const button = $("publishButton");
    button.disabled = true;
    button.setAttribute("aria-busy", "true");
    try {
      await send("submitted");
      sessionStorage.removeItem(STASH_KEY);
      showToast("تم إرسال تصميمك للمراجعة، وسنُعلمك عند الموافقة عليه.", "success");
      $("successDialog").hidden = false;
      $("closeDialog").focus();
    } catch (error) {
      showToast(error.message);
      button.disabled = false;
    } finally {
      button.removeAttribute("aria-busy");
    }
  }

  // Render
  $("publishLayout").hidden = false;
  $("publishFooter").hidden = false;
  $("publishPreview").src = stash.preview;
  $("productName").textContent = stash.productName || "";
  $("colorName").textContent = stash.colorName || "";
  $("allowedColors").textContent = (stash.allowedColorIds || []).length + " ألوان";
  const audience = (config.audiences || {})[stash.category];
  if (audience) {
    $("audienceName").textContent = audience.label;
    const names = audience.sizes.filter(size => (stash.allowedSizeIds || []).includes(size.id)).map(size => size.name);
    $("allowedSizes").textContent = names.length ? names.join("، ") : "—";
  }
  $("designName").value = state.designName;
  $("nameCount").textContent = state.designName.length;
  $("basePrice").textContent = basePrice.toFixed(2);
  $("sellingPrice").min = String(basePrice);
  $("sellingPrice").value = state.sellingPrice.toFixed(2);
  $("rightsCheck").checked = state.rightsConfirmed;
  updateProfit();

  $("designName").addEventListener("input", event => { state.designName = event.target.value; $("nameCount").textContent = state.designName.length; remember(); updateValidation(); });
  $("sellingPrice").addEventListener("input", updateProfit);
  $("rightsCheck").addEventListener("change", event => { state.rightsConfirmed = event.target.checked; remember(); updateValidation(); });
  $("saveButton").addEventListener("click", saveDraft);
  $("publishButton").addEventListener("click", publish);
  $("closeDialog").addEventListener("click", () => { window.location.href = config.designsUrl; });
})();
