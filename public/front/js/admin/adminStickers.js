(function () {
  "use strict";

  const config = window.PalPrintsAdminStickers || {};
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  let categories = config.categories || [];
  let activeCategory = "all";
  let editingCategory = null;
  let editingSticker = null;
  let settings = config.settings || {};

  const $ = (id) => document.getElementById(id);
  const els = {
    bar: $("categoriesBar"),
    grid: $("stickersGrid"),
    empty: $("stickersEmpty"),
    categoryDialog: $("categoryDialog"),
    categoryForm: $("categoryForm"),
    categoryTitle: $("categoryDialogTitle"),
    categoryName: $("categoryNameInput"),
    categoryOrder: $("categoryOrderInput"),
    categoryActive: $("categoryActiveInput"),
    categoryError: $("categoryError"),
    stickerDialog: $("stickerDialog"),
    stickerForm: $("stickerForm"),
    stickerTitle: $("stickerDialogTitle"),
    stickerImage: $("stickerImageInput"),
    stickerName: $("stickerNameInput"),
    stickerCategory: $("stickerCategoryInput"),
    stickerOrder: $("stickerOrderInput"),
    stickerActive: $("stickerActiveInput"),
    stickerError: $("stickerError"),
    settingsForm: $("settingsForm"),
    sizesInput: $("sizesInput"),
    preview: $("adminA4Preview")
  };

  function toast(message) {
    if (window.PalAdmin?.toast) window.PalAdmin.toast(message);
    else alert(message);
  }

  function route(template, id) {
    return template.replace("__ID__", id);
  }

  async function request(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        "Accept": "application/json",
        "X-CSRF-TOKEN": csrf,
        "X-Requested-With": "XMLHttpRequest",
        ...(options.headers || {})
      }
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const message = Object.values(data.errors || {})[0]?.[0] || data.message || "تعذر حفظ التغيير.";
      throw new Error(message);
    }
    return data;
  }

  function allStickers() {
    return categories.flatMap((category) => (category.stickers || []).map((item) => ({ ...item, category_name: category.name })));
  }

  function renderBar() {
    const stickers = allStickers();
    const items = [{ id: "all", name: "الكل", count: stickers.length }].concat(categories.map((category) => ({
      id: String(category.id),
      name: category.name,
      count: (category.stickers || []).length,
      category
    })));
    els.bar.replaceChildren(...items.map((item) => {
      const wrap = document.createElement("span");
      wrap.className = `sticker-cat ${activeCategory === item.id ? "is-active" : ""}`;
      const pill = document.createElement("button");
      pill.className = "summary-pill";
      pill.type = "button";
      pill.dataset.category = item.id;
      pill.innerHTML = `<b>${item.count}</b> ${item.name}`;
      wrap.append(pill);
      if (item.category) {
        const tools = document.createElement("span");
        tools.className = "sticker-cat-tools";
        tools.innerHTML = `<button type="button" data-edit-category="${item.id}" aria-label="تعديل"><i class="bi bi-pencil-square"></i></button><button type="button" data-delete-category="${item.id}" aria-label="حذف"><i class="bi bi-trash3"></i></button>`;
        wrap.append(tools);
      }
      return wrap;
    }));
  }

  function renderGrid() {
    const stickers = allStickers().filter((item) => activeCategory === "all" || String(item.sticker_category_id) === activeCategory);
    els.grid.replaceChildren(...stickers.map((sticker) => {
      const card = document.createElement("article");
      card.className = "sticker-card";
      card.innerHTML = `
        <div class="sticker-visual"><img src="${sticker.image_url}" alt="${sticker.name}"></div>
        <div class="sticker-info"><h3 title="${sticker.name}">${sticker.name}</h3><p>${sticker.category_name} · ترتيب ${sticker.sort_order}</p></div>
        <div class="sticker-actions">
          <button class="edit-action" type="button" data-edit-sticker="${sticker.id}"><i class="bi bi-pencil-square"></i> تعديل</button>
          <button class="delete-action" type="button" data-delete-sticker="${sticker.id}"><i class="bi bi-trash3"></i></button>
        </div>`;
      return card;
    }));
    els.empty.hidden = stickers.length > 0;
  }

  function render() {
    if (activeCategory !== "all" && !categories.some((category) => String(category.id) === activeCategory)) activeCategory = "all";
    renderBar();
    renderGrid();
    renderStickerCategoryOptions();
  }

  function renderStickerCategoryOptions() {
    els.stickerCategory.replaceChildren(...categories.map((category) => {
      const option = document.createElement("option");
      option.value = category.id;
      option.textContent = category.name;
      return option;
    }));
  }

  function openCategory(category = null) {
    editingCategory = category;
    els.categoryTitle.textContent = category ? "تعديل تصنيف" : "إضافة تصنيف";
    els.categoryName.value = category?.name || "";
    els.categoryOrder.value = category?.sort_order || 0;
    els.categoryActive.checked = category ? Boolean(category.is_active) : true;
    els.categoryError.hidden = true;
    els.categoryDialog.showModal();
  }

  function openSticker(sticker = null) {
    if (!categories.length) {
      toast("أضف تصنيفا أولا.");
      openCategory();
      return;
    }
    editingSticker = sticker;
    els.stickerTitle.textContent = sticker ? "تعديل ملصق" : "إضافة ملصق";
    els.stickerForm.reset();
    els.stickerName.value = sticker?.name || "";
    els.stickerCategory.value = sticker?.sticker_category_id || (activeCategory !== "all" ? activeCategory : categories[0].id);
    els.stickerOrder.value = sticker?.sort_order || 0;
    els.stickerActive.checked = sticker ? Boolean(sticker.is_active) : true;
    els.stickerError.hidden = true;
    els.stickerDialog.showModal();
  }

  function setError(node, error) {
    node.textContent = error;
    node.hidden = !error;
  }

  function initDialogs() {
    $("addCategoryButton").addEventListener("click", () => openCategory());
    $("addStickerButton").addEventListener("click", () => openSticker());
    document.querySelectorAll("[data-dialog-close]").forEach((button) => button.addEventListener("click", () => button.closest("dialog").close()));

    els.categoryForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      const form = new FormData();
      form.append("name", els.categoryName.value.trim());
      form.append("sort_order", els.categoryOrder.value || "0");
      form.append("is_active", els.categoryActive.checked ? "1" : "0");
      if (!form.get("name")) return setError(els.categoryError, "أدخل اسم التصنيف.");
      try {
        const url = editingCategory ? route(config.routes.categoryUpdate, editingCategory.id) : config.routes.categoryStore;
        if (editingCategory) form.append("_method", "PATCH");
        const data = await request(url, { method: "POST", body: form });
        categories = data.categories;
        els.categoryDialog.close();
        render();
        toast("تم حفظ التصنيف.");
      } catch (error) {
        setError(els.categoryError, error.message);
      }
    });

    els.stickerForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      const form = new FormData();
      form.append("name", els.stickerName.value.trim());
      form.append("sticker_category_id", els.stickerCategory.value);
      form.append("sort_order", els.stickerOrder.value || "0");
      form.append("is_active", els.stickerActive.checked ? "1" : "0");
      if (els.stickerImage.files[0]) form.append("image", els.stickerImage.files[0]);
      if (!form.get("name")) return setError(els.stickerError, "أدخل اسم الملصق.");
      if (!editingSticker && !els.stickerImage.files[0]) return setError(els.stickerError, "اختر صورة الملصق.");
      try {
        const url = editingSticker ? route(config.routes.itemUpdate, editingSticker.id) : config.routes.itemStore;
        if (editingSticker) form.append("_method", "PATCH");
        const data = await request(url, { method: "POST", body: form });
        categories = data.categories;
        activeCategory = String(form.get("sticker_category_id"));
        els.stickerDialog.close();
        render();
        toast("تم حفظ الملصق.");
      } catch (error) {
        setError(els.stickerError, error.message);
      }
    });
  }

  function initGridActions() {
    els.bar.addEventListener("click", async (event) => {
      const tab = event.target.closest("[data-category]");
      if (tab) {
        activeCategory = tab.dataset.category;
        render();
        return;
      }
      const edit = event.target.closest("[data-edit-category]");
      if (edit) openCategory(categories.find((category) => String(category.id) === edit.dataset.editCategory));
      const del = event.target.closest("[data-delete-category]");
      if (del && confirm("حذف التصنيف سيحذف ملصقاته. هل أنت متأكد؟")) {
        const data = await request(route(config.routes.categoryDestroy, del.dataset.deleteCategory), { method: "POST", body: methodForm("DELETE") });
        categories = data.categories;
        render();
      }
    });

    els.grid.addEventListener("click", async (event) => {
      const stickers = allStickers();
      const edit = event.target.closest("[data-edit-sticker]");
      if (edit) openSticker(stickers.find((item) => String(item.id) === edit.dataset.editSticker));
      const del = event.target.closest("[data-delete-sticker]");
      if (del && confirm("حذف الملصق؟")) {
        const data = await request(route(config.routes.itemDestroy, del.dataset.deleteSticker), { method: "POST", body: methodForm("DELETE") });
        categories = data.categories;
        render();
      }
    });
  }

  function methodForm(method) {
    const form = new FormData();
    form.append("_method", method);
    return form;
  }

  function get(path) {
    return path.split(".").reduce((carry, key) => carry?.[key], settings);
  }

  function set(path, value) {
    const keys = path.split(".");
    let target = settings;
    keys.slice(0, -1).forEach((key) => {
      target[key] = target[key] || {};
      target = target[key];
    });
    target[keys[keys.length - 1]] = value;
  }

  function fillSettings() {
    els.settingsForm.querySelectorAll("[name]").forEach((input) => {
      input.value = get(input.name) ?? "";
    });
    els.sizesInput.value = (settings.layout?.sizes || []).map((size) => `${size.key},${size.label},${size.width_mm},${size.height_mm}`).join("\n");
    renderA4Preview();
  }

  function collectSettings() {
    els.settingsForm.querySelectorAll("[name]").forEach((input) => {
      const value = input.type === "color" ? input.value : Number(input.value);
      set(input.name, value);
    });
    settings.layout.sizes = els.sizesInput.value.split(/\n+/).map((line) => {
      const [key, label, width, height] = line.split(",").map((part) => part.trim());
      return { key, label, width_mm: Number(width), height_mm: Number(height) };
    }).filter((size) => size.key && size.label && size.width_mm && size.height_mm);
  }

  function renderA4Preview() {
    const layout = settings.layout || {};
    const pageW = Number(layout.page_width_mm || 210);
    const pageH = Number(layout.page_height_mm || 297);
    const size = (layout.sizes || [])[0] || { width_mm: 50, height_mm: 50 };
    els.preview.innerHTML = `<div class="admin-a4-sheet" style="aspect-ratio:${pageW}/${pageH}">
      <span style="inset:${(layout.margin_top_mm / pageH) * 100}% ${(layout.margin_right_mm / pageW) * 100}% ${(layout.margin_bottom_mm / pageH) * 100}% ${(layout.margin_left_mm / pageW) * 100}%"></span>
      <b style="width:${(size.width_mm / pageW) * 100}%;height:${(size.height_mm / pageH) * 100}%;border-color:${layout.cut_line_color || "#00A3A3"}"></b>
    </div>`;
  }

  function initSettings() {
    fillSettings();
    els.settingsForm.addEventListener("input", () => {
      collectSettings();
      renderA4Preview();
    });
    els.settingsForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      collectSettings();
      const response = await request(config.routes.settings, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(settings)
      });
      settings = response.settings;
      fillSettings();
      toast("تم حفظ إعدادات الملصقات.");
    });
  }

  function init() {
    render();
    initDialogs();
    initGridActions();
    initSettings();
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init, { once: true });
  else init();
})();
