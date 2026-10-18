(function () {
  "use strict";

  const config = window.PalPrintsStickers || {};
  const settings = config.settings || {};
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  const state = { category: "all", lines: [], layout: null, page: 1, busy: false };
  const el = {
    size: document.getElementById("stickerSizeSelect"),
    quantity: document.getElementById("stickerQuantityInput"),
    cut: document.getElementById("stickerCutSelect"),
    upload: document.getElementById("stickerUploadInput"),
    tabs: document.getElementById("stickerTabs"),
    grid: document.getElementById("stickerGrid"),
    empty: document.getElementById("stickersEmpty"),
    count: document.getElementById("libraryCount"),
    sheet: document.getElementById("a4Sheet"),
    a4Empty: document.getElementById("a4Empty"),
    prev: document.getElementById("prevStickerPage"),
    next: document.getElementById("nextStickerPage"),
    pageLabel: document.getElementById("stickerPageLabel"),
    summaryQuantity: document.getElementById("summaryQuantity"),
    summaryPages: document.getElementById("summaryPages"),
    summaryPrice: document.getElementById("summaryPrice"),
    warning: document.getElementById("stickerWarning"),
    add: document.getElementById("addStickerBundle"),
    clear: document.getElementById("clearStickerBundle")
  };

  const categories = config.categories || [];
  const stickers = categories.flatMap((category) => (category.stickers || []).map((item) => ({ ...item, category_id: String(category.id), category_name: category.name })));
  const sizes = settings.layout?.sizes || [];

  function toast(message, icon = "info-circle") {
    if (window.PalPrintNotifications?.add) window.PalPrintNotifications.add(message, icon);
    else alert(message);
  }

  function currentChoice() {
    return {
      quantity: Math.max(1, Math.min(999, Number(el.quantity.value) || 1)),
      size_key: el.size.value || sizes[0]?.key || "5x5",
      cut_type: el.cut.value || "circle"
    };
  }

  function linePayload(line) {
    return {
      client_id: line.client_id,
      source: line.source,
      library_item_id: line.library_item_id || null,
      upload_key: line.upload_key || null,
      label: line.label,
      quantity: line.quantity,
      size_key: line.size_key,
      cut_type: line.cut_type,
      image_width_px: line.image_width_px || null,
      image_height_px: line.image_height_px || null,
      image_url: line.image_url || null
    };
  }

  function localArrange() {
    const page = settings.layout || {};
    const pageWidth = Number(page.page_width_mm || 210);
    const pageHeight = Number(page.page_height_mm || 297);
    const marginTop = Number(page.margin_top_mm || 10);
    const marginRight = Number(page.margin_right_mm || 10);
    const marginBottom = Number(page.margin_bottom_mm || 10);
    const marginLeft = Number(page.margin_left_mm || 10);
    const gap = Number(page.gap_mm || 3);
    const sizeMap = new Map(sizes.map((size) => [size.key, size]));
    const pages = [[]];
    let pageIndex = 0;
    let x = pageWidth - marginRight;
    let y = marginTop;
    let rowHeight = 0;

    state.lines.forEach((line) => {
      const size = sizeMap.get(line.size_key) || sizes[0] || { width_mm: 50, height_mm: 50 };
      for (let copy = 1; copy <= line.quantity; copy += 1) {
        const w = Number(size.width_mm);
        const h = Number(size.height_mm);
        if (x - w < marginLeft) {
          x = pageWidth - marginRight;
          y += rowHeight + gap;
          rowHeight = 0;
        }
        if (y + h > pageHeight - marginBottom) {
          pageIndex += 1;
          pages[pageIndex] = [];
          x = pageWidth - marginRight;
          y = marginTop;
          rowHeight = 0;
        }
        pages[pageIndex].push({
          ...line,
          page: pageIndex + 1,
          x_mm: +(x - w).toFixed(2),
          y_mm: +y.toFixed(2),
          width_mm: w,
          height_mm: h,
          copy
        });
        x -= w + gap;
        rowHeight = Math.max(rowHeight, h);
      }
    });

    return {
      page: {
        width_mm: pageWidth,
        height_mm: pageHeight,
        cut_line: {
          enabled: page.cut_line_enabled !== false,
          width_mm: Number(page.cut_line_width_mm || 0.25),
          color: page.cut_line_color || "#00A3A3"
        }
      },
      pages: pages.map((items, index) => ({ number: index + 1, items })),
      page_count: pages.length
    };
  }

  function priceLocal() {
    const pricing = settings.pricing || {};
    const total = state.lines.reduce((sum, line) => sum + line.quantity, 0);
    if (!total) return { quantity: 0, total: 0, warnings: [] };
    const subtotal = Number(pricing.base_price || 10) + Math.max(0, total - Number(pricing.base_quantity || 20)) * Number(pricing.extra_unit_price || 1);
    const contour = state.lines.filter((line) => line.cut_type === "contour").length;
    const contourFee = contour ? Math.ceil(contour / Number(pricing.contour_group_size || 3)) * Number(pricing.contour_group_price || 1) : 0;
    const warnings = [];
    if (total < Number(pricing.min_quantity || 20)) warnings.push("below_minimum");
    if (total > Number(pricing.warning_quantity || 300)) warnings.push("large_quantity");
    return { quantity: total, total: subtotal + contourFee, warnings };
  }

  function renderSheet(jumpFrom) {
    const layout = state.layout || localArrange();
    const pageCount = Math.max(1, layout.page_count || layout.pages.length || 1);
    state.page = Math.min(Math.max(1, state.page), pageCount);
    const page = layout.pages.find((item) => Number(item.number) === state.page) || layout.pages[0] || { items: [] };
    const width = Number(layout.page?.width_mm || 210);
    const height = Number(layout.page?.height_mm || 297);
    const cut = layout.page?.cut_line || {};

    el.sheet.style.setProperty("--a4-ratio", `${width} / ${height}`);
    el.sheet.querySelectorAll(".a4-sticker").forEach((node) => node.remove());
    el.a4Empty.hidden = state.lines.length > 0;

    (page.items || []).forEach((item) => {
      const node = document.createElement("div");
      node.className = "a4-sticker";
      node.style.left = `${(Number(item.x_mm) / width) * 100}%`;
      node.style.top = `${(Number(item.y_mm) / height) * 100}%`;
      node.style.width = `${(Number(item.width_mm) / width) * 100}%`;
      node.style.height = `${(Number(item.height_mm) / height) * 100}%`;
      node.style.borderColor = cut.color || "#00A3A3";
      node.style.borderWidth = `${Math.max(1, Number(cut.width_mm || 0.25) * 2)}px`;
      const img = document.createElement("img");
      img.src = item.image_url || item.local_url || "";
      img.alt = item.label || "Sticker";
      node.append(img);
      el.sheet.append(node);
    });

    el.pageLabel.textContent = `${state.page} / ${pageCount}`;
    el.prev.disabled = state.page <= 1;
    el.next.disabled = state.page >= pageCount;

    if (jumpFrom) animateJump(jumpFrom);
  }

  function renderSummary(serverQuote) {
    const quote = serverQuote || priceLocal();
    const layout = state.layout || localArrange();
    el.summaryQuantity.textContent = String(quote.quantity || 0);
    el.summaryPages.textContent = String(layout.page_count || 1);
    el.summaryPrice.textContent = `${Number(quote.total || 0).toFixed(0)} شيكل`;
    const warnings = quote.warnings || [];
    if (warnings.includes("below_minimum")) {
      el.warning.hidden = false;
      el.warning.textContent = `الحد الأدنى ${settings.pricing?.min_quantity || 20} ملصق. أضيفي المزيد قبل السلة.`;
    } else if (warnings.includes("large_quantity")) {
      el.warning.hidden = false;
      el.warning.textContent = "الكمية كبيرة، سنعرضها للمطبعة بوضوح داخل ملف الطباعة.";
    } else {
      el.warning.hidden = true;
      el.warning.textContent = "";
    }
  }

  let previewTimer = null;
  function requestPreview() {
    window.clearTimeout(previewTimer);
    state.layout = localArrange();
    renderSheet();
    renderSummary();
    previewTimer = window.setTimeout(async () => {
      if (!state.lines.length) return;
      try {
        const response = await fetch(config.routes.preview, {
          method: "POST",
          headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf, "X-Requested-With": "XMLHttpRequest" },
          body: JSON.stringify({ lines: state.lines.map(linePayload) })
        });
        if (!response.ok) return;
        const data = await response.json();
        state.layout = data.layout_snapshot;
        renderSheet();
        renderSummary(data.quote);
      } catch (_) {}
    }, 220);
  }

  function renderLibrary() {
    const visible = stickers.filter((item) => state.category === "all" || item.category_id === state.category);
    el.grid.replaceChildren(...visible.map((item) => {
      const card = document.createElement("article");
      card.className = "sticker-pick-card";
      card.innerHTML = `<button type="button" data-sticker="${item.id}"><span><img src="${item.image_url}" alt="${item.name}" loading="lazy"></span><strong>${item.name}</strong><small>${item.category_name}</small></button>`;
      return card;
    }));
    el.empty.hidden = visible.length > 0;
    el.count.textContent = `${visible.length} ملصق`;
  }

  function addLine(line, sourceNode) {
    state.lines.push(line);
    state.page = 9999;
    requestPreview();
    renderSheet(sourceNode);
  }

  function animateJump(sourceNode) {
    if (!sourceNode || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    const image = sourceNode.querySelector("img");
    const target = el.sheet.querySelector(".a4-sticker:last-child");
    if (!image || !target) return;
    const from = image.getBoundingClientRect();
    const to = target.getBoundingClientRect();
    const ghost = image.cloneNode(true);
    ghost.className = "sticker-jump-ghost";
    ghost.style.left = `${from.left}px`;
    ghost.style.top = `${from.top}px`;
    ghost.style.width = `${from.width}px`;
    ghost.style.height = `${from.height}px`;
    document.body.append(ghost);
    ghost.animate([
      { transform: "translate(0, 0) scale(1)", opacity: 1 },
      { transform: `translate(${to.left - from.left}px, ${to.top - from.top}px) scale(${Math.max(0.2, to.width / from.width)})`, opacity: 0.25 }
    ], { duration: 520, easing: "cubic-bezier(.2,.8,.2,1)" }).finished.finally(() => ghost.remove());
  }

  function initControls() {
    el.size.replaceChildren(...sizes.map((size) => {
      const option = document.createElement("option");
      option.value = size.key;
      option.textContent = size.label;
      return option;
    }));
    if (sizes[1]) el.size.value = sizes[1].key;

    [el.size, el.quantity, el.cut].forEach((input) => input.addEventListener("change", requestPreview));

    el.tabs.addEventListener("click", (event) => {
      const button = event.target.closest("[data-category]");
      if (!button) return;
      state.category = button.dataset.category;
      el.tabs.querySelectorAll("button").forEach((item) => item.classList.toggle("is-active", item === button));
      renderLibrary();
    });

    el.grid.addEventListener("click", (event) => {
      const button = event.target.closest("[data-sticker]");
      if (!button) return;
      const sticker = stickers.find((item) => String(item.id) === button.dataset.sticker);
      if (!sticker) return;
      const choice = currentChoice();
      addLine({
        client_id: `library-${sticker.id}-${Date.now()}`,
        source: "library",
        library_item_id: sticker.id,
        label: sticker.name,
        image_url: sticker.image_url,
        ...choice
      }, button);
    });

    el.upload.addEventListener("change", () => {
      const file = el.upload.files[0];
      el.upload.value = "";
      if (!file) return;
      if (!["image/png", "image/jpeg"].includes(file.type)) {
        toast("الصيغة غير مدعومة. استخدمي PNG أو JPG.", "exclamation-triangle");
        return;
      }
      const key = `upload-${Date.now()}`;
      const localUrl = URL.createObjectURL(file);
      const probe = new Image();
      probe.onload = () => {
        const choice = currentChoice();
        addLine({
          client_id: key,
          source: "upload",
          upload_key: key,
          label: file.name.replace(/\.[^.]+$/, ""),
          image_url: localUrl,
          local_url: localUrl,
          file,
          image_width_px: probe.naturalWidth,
          image_height_px: probe.naturalHeight,
          ...choice
        }, document.querySelector(".upload-drop"));
      };
      probe.onerror = () => {
        URL.revokeObjectURL(localUrl);
        toast("تعذر قراءة الصورة.", "exclamation-triangle");
      };
      probe.src = localUrl;
    });

    el.prev.addEventListener("click", () => { state.page -= 1; renderSheet(); });
    el.next.addEventListener("click", () => { state.page += 1; renderSheet(); });
    el.clear.addEventListener("click", () => {
      state.lines.forEach((line) => { if (line.file && line.image_url) URL.revokeObjectURL(line.image_url); });
      state.lines = [];
      state.layout = null;
      state.page = 1;
      requestPreview();
    });

    document.querySelectorAll("[data-mobile-view]").forEach((button) => {
      button.addEventListener("click", () => {
        document.querySelectorAll("[data-mobile-view]").forEach((item) => item.classList.toggle("is-active", item === button));
        document.body.classList.toggle("stickers-mobile-preview", button.dataset.mobileView === "preview");
      });
    });

    el.add.addEventListener("click", submitBundle);
  }

  async function submitBundle() {
    if (!state.lines.length) {
      toast("اختاري ملصقا واحدا على الأقل.", "exclamation-triangle");
      return;
    }
    if (!config.user?.authenticated) {
      window.location.href = config.routes.login;
      return;
    }
    if (state.busy) return;
    state.busy = true;
    el.add.disabled = true;
    const form = new FormData();
    state.lines.map(linePayload).forEach((line, index) => {
      Object.entries(line).forEach(([key, value]) => {
        if (value !== null && value !== undefined) form.append(`lines[${index}][${key}]`, value);
      });
    });
    state.lines.forEach((line) => {
      if (line.file) form.append(`files[${line.upload_key}]`, line.file);
    });

    try {
      const response = await fetch(config.routes.store, {
        method: "POST",
        headers: { "Accept": "application/json", "X-CSRF-TOKEN": csrf, "X-Requested-With": "XMLHttpRequest" },
        body: form
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        const message = Object.values(data.errors || {})[0]?.[0] || data.message || "تعذر إضافة الحزمة للسلة.";
        throw new Error(message);
      }
      window.location.href = data.redirect;
    } catch (error) {
      toast(error.message, "exclamation-triangle");
    } finally {
      state.busy = false;
      el.add.disabled = false;
    }
  }

  function init() {
    renderLibrary();
    initControls();
    requestPreview();
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init, { once: true });
  else init();
})();
