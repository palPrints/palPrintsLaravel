(function (window, document) {
  "use strict";

  const STORAGE_KEY = "palprints:print-shop-services:v1";
  const IMAGE_BASE = "/front/assets/images/customer/";
  const colors = [
    { id: "white", label: "أبيض", value: "#ffffff" },
    { id: "black", label: "أسود", value: "#141414" },
    { id: "red", label: "أحمر", value: "#ef233c" },
    { id: "blue", label: "أزرق", value: "#1677ff" },
    { id: "yellow", label: "أصفر", value: "#ffdc32" },
    { id: "purple", label: "بنفسجي", value: "#9333ea" },
    { id: "pink", label: "وردي", value: "#f472b6" },
    { id: "green", label: "أخضر", value: "#16a34a" }
  ];
  const apparelCategories = ["رجال", "نساء", "أطفال", "أوفر سايز"];
  const apparelSizes = ["XS", "S", "M", "L", "XL", "XXL", "3XL"];
  const catalog = [
    { id: "tshirt", name: "تيشيرت قطن", category: "ملابس", image: "products/1.png", categories: apparelCategories, sizes: apparelSizes, colors: colors, defaults: { price: 8, days: 2, capacity: 100, categories: apparelCategories, sizes: ["S", "M", "L", "XL", "XXL"], colors: colors.map(function (color) { return color.id; }) } },
    { id: "hoodie", name: "هودي", category: "ملابس", image: "hoodie-black.png", categories: apparelCategories, sizes: apparelSizes, colors: colors.slice(0, 6), defaults: { price: 12, days: 4, capacity: 50, categories: apparelCategories, sizes: ["S", "M", "L", "XL"], colors: colors.slice(0, 6).map(function (color) { return color.id; }) } },
    { id: "mug", name: "كوب سيراميك", category: "أكواب ومستلزمات للشرب", image: "products/7.png", categories: ["أكواب"], sizes: ["مقاس واحد"], colors: colors.slice(0, 4), defaults: { price: 4.5, days: 2, capacity: 100 } },
    { id: "stickers", name: "ستيكرات مخصصة", category: "مطبوعات ورقية", art: "stickers", categories: ["ورقية", "فينيل"], sizes: ["صغير", "متوسط", "كبير"], colors: colors.slice(0, 4), defaults: { price: 0.5, days: 1, capacity: 500 } },
    { id: "bag", name: "شنطة قماش", category: "مطبوعات وإكسسوارات", image: "products/4.png", categories: ["قماش قطني", "كانفاس"], sizes: ["صغير", "كبير"], colors: colors.slice(0, 4), defaults: { price: 5, days: 3, capacity: 100 } },
    { id: "cap", name: "كاب", category: "ملابس وإكسسوارات", image: "products/3.png", categories: ["رجال", "نساء", "أطفال"], sizes: ["مقاس واحد"], colors: colors.slice(0, 4), defaults: { price: 4, days: 2, capacity: 100 } },
    { id: "phone", name: "كفر جوال", category: "إكسسوارات", image: "products/6.png", categories: ["iPhone", "Samsung"], sizes: ["صغير", "كبير"], colors: colors.slice(0, 2), defaults: { price: 6, days: 2, capacity: 100 } },
    { id: "mousepad", name: "باد ماوس", category: "إكسسوارات", art: "mousepad", categories: ["إكسسوارات مكتبية"], sizes: ["صغير", "كبير"], colors: colors.slice(0, 2), defaults: { price: 3, days: 2, capacity: 100 } },
    { id: "poster", name: "طباعة بوسترات", category: "مطبوعات ورقية", art: "poster", categories: ["ورق مطفي", "ورق لامع"], sizes: ["A5", "A4", "A3", "A2", "A1"], colors: colors.slice(0, 4), defaults: { price: 3, days: 3, capacity: 200 } },
    { id: "cards", name: "كروت شخصية", category: "مطبوعات ورقية", image: "products/8.png", categories: ["ورق مطفي", "ورق لامع"], sizes: ["مقاس قياسي"], colors: colors.slice(0, 4), defaults: { price: 0.25, days: 2, capacity: 500 } }
  ];

  function initialize() {
    const products = document.getElementById("servicesProducts");
    if (!products) return;
    const catalogDialog = document.getElementById("catalogDialog");
    const settingsDialog = document.getElementById("settingsDialog");
    const form = document.getElementById("productSettingsForm");
    const search = document.getElementById("dashboardSearch");
    const warning = document.getElementById("storageWarning");
    const error = document.getElementById("settingsError");
    const toast = document.getElementById("servicesToast");
    let toastTimer;
    let draft = null;
    let dialogOrigin = null;
    let returnToCatalog = false;
    let pendingCatalogFocus = null;
    let shop = loadShop();

    function element(tag, className, text) {
      const node = document.createElement(tag);
      if (className) node.className = className;
      if (text !== undefined) node.textContent = text;
      return node;
    }

    function icon(name) { const node = element("i", "bi bi-" + name); node.setAttribute("aria-hidden", "true"); return node; }
    function definition(id) { return catalog.find(function (product) { return product.id === id; }); }
    function copy(value) { return JSON.parse(JSON.stringify(value)); }

    function defaults(product) {
      return Object.assign({ active: true, categories: product.categories.slice(), sizes: product.sizes.slice(), colors: product.colors.map(function (color) { return color.id; }) }, copy(product.defaults));
    }

    function defaultShop() {
      return ["poster", "stickers", "tshirt", "hoodie", "mug"].map(function (id) {
        return Object.assign({ id: id }, defaults(definition(id)), { active: id !== "poster" });
      });
    }

    function validSettings(value, product) {
      if (!value || typeof value.active !== "boolean") return false;
      if (!Number.isFinite(value.price) || value.price < 0.01 || value.price > 1000000 || Math.abs(value.price * 100 - Math.round(value.price * 100)) > 0.00001) return false;
      if (!Number.isInteger(value.days) || value.days < 1 || value.days > 365) return false;
      if (!Number.isInteger(value.capacity) || value.capacity < 1 || value.capacity > 1000000) return false;
      return ["categories", "sizes", "colors"].every(function (group) {
        const allowed = group === "colors" ? product.colors.map(function (color) { return color.id; }) : product[group];
        return Array.isArray(value[group]) && value[group].length > 0 && new Set(value[group]).size === value[group].length && value[group].every(function (item) { return allowed.includes(item); });
      });
    }

    function storageWarning(message) { warning.textContent = message; warning.hidden = false; }

    function loadShop() {
      try {
        const stored = window.localStorage.getItem(STORAGE_KEY);
        if (!stored) return defaultShop();
        const data = JSON.parse(stored);
        if (data.version !== 1 || !Array.isArray(data.products) || new Set(data.products.map(function (product) { return product && product.id; })).size !== data.products.length || !data.products.every(function (value) { const product = value && definition(value.id); return product && validSettings(value, product); })) throw new Error("Invalid stored settings");
        return data.products.map(function (value) { return Object.assign({ id: value.id, active: value.active, price: value.price, days: value.days, capacity: value.capacity }, { categories: value.categories.slice(), sizes: value.sizes.slice(), colors: value.colors.slice() }); });
      } catch (exception) {
        storageWarning("تعذّر قراءة البيانات المحفوظة؛ تم عرض المنتجات الافتراضية. ستستمر التغييرات داخل هذه الجلسة إذا تعذّر التخزين.");
        return defaultShop();
      }
    }

    function persist() {
      try { window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ version: 1, products: shop })); warning.hidden = true; }
      catch (exception) { storageWarning("تعذّر الحفظ الدائم في المتصفح. تغييراتك متاحة داخل هذه الجلسة فقط."); }
    }

    function showToast(message) {
      window.clearTimeout(toastTimer);
      toast.textContent = message;
      toast.classList.add("is-visible");
      toastTimer = window.setTimeout(function () { toast.classList.remove("is-visible"); }, 3000);
    }

    function artwork(product) {
      const art = element("div", "services-product-art services-art--" + product.id);
      art.setAttribute("role", "img");
      art.setAttribute("aria-label", product.name);
      if (product.image) {
        const photo = element("div", "services-product-photo");
        const image = element("img"); image.src = IMAGE_BASE + product.image; image.alt = ""; image.decoding = "async";
        image.addEventListener("load", function () { photo.style.setProperty("--product-ratio", String(image.naturalWidth / image.naturalHeight)); }, { once: true });
        const brand = element("img", "services-brand"); brand.src = IMAGE_BASE + "palprints-wordmark-transparent.png"; brand.alt = "";
        photo.append(image, brand);
        art.append(photo);
      } else if (product.art === "stickers") {
        const stickers = element("div", "services-stickers");
        ["CREATE", "PALPRINTS", "YOUR WAY"].forEach(function (text) { stickers.append(element("span", "", text)); }); art.append(stickers);
      } else if (product.art === "poster") {
        const poster = element("div", "services-poster"); poster.append(element("div", "", "GOOD"), element("div", "", "IDEAS"), element("em", "", "BRIGHT"), element("div", "", "PRINTS")); art.append(poster);
      } else {
        const pad = element("div", "services-mousepad"); const brand = element("img"); brand.src = IMAGE_BASE + "palprints-wordmark-transparent.png"; brand.alt = ""; pad.append(brand); art.append(pad);
      }
      return art;
    }

    function normalize(value) { return String(value).trim().toLocaleLowerCase("ar").replace(/[أإآ]/g, "ا").replace(/ة/g, "ه").replace(/ى/g, "ي"); }

    function renderProducts() {
      const query = normalize(search.value);
      products.replaceChildren();
      let visible = 0;
      shop.forEach(function (settings) {
        const product = definition(settings.id);
        if (query && !normalize(product.name + " " + product.category).includes(query)) return;
        visible += 1;
        const card = element("article", "services-product"); card.dataset.productId = product.id;
        const top = element("div", "services-product-top");
        top.append(element("span", "services-badge" + (settings.active ? "" : " services-badge--paused"), settings.active ? "نشط" : "موقوف مؤقتًا"));
        const toggle = element("button", "services-switch"); toggle.type = "button"; toggle.dataset.toggleProduct = product.id; toggle.setAttribute("role", "switch"); toggle.setAttribute("aria-checked", String(settings.active)); toggle.setAttribute("aria-label", "تشغيل " + product.name); top.append(toggle);
        card.append(top, artwork(product), element("h3", "", product.name), element("p", "services-category", product.category));
        const details = element("ul", "services-details");
        const money = element("li", "services-money");
        const amount = element("span", "", "$" + settings.price.toFixed(2));
        amount.dir = "ltr";
        money.append(amount);
        details.append(money);
        [["clock", settings.days + (settings.days === 1 ? " يوم" : " أيام")], ["box", settings.capacity + " قطعة يوميًا"], ["palette", settings.colors.length + " ألوان"], ["grid", settings.sizes.length === 1 ? settings.sizes[0] : settings.sizes.length + " مقاسات"]].forEach(function (detail) { const item = element("li"); item.append(icon(detail[0]), element("span", "", detail[1])); details.append(item); });
        const edit = element("button", "services-button services-edit"); edit.type = "button"; edit.dataset.editProduct = product.id; edit.setAttribute("aria-label", "تعديل إعدادات " + product.name); edit.append(icon("pencil-square"), element("span", "", "تعديل الإعدادات"));
        card.append(details, edit); products.append(card);
      });
      document.getElementById("servicesEmpty").hidden = visible !== 0;
      document.getElementById("addedCount").textContent = String(shop.length);
      const summary = document.getElementById("servicesSummary");
      summary.replaceChildren(document.createTextNode("عدد المنتجات المفعلة: "), element("strong", "", String(shop.length) + " من أصل " + catalog.length), document.createTextNode(" منتجًا في كتالوج المنصة"));
    }

    function renderCatalog() {
      const grid = document.getElementById("catalogProducts"); grid.replaceChildren();
      catalog.forEach(function (product) {
        const card = element("article", "services-catalog-card"); card.append(artwork(product), element("h3", "", product.name), element("p", "services-category", product.category));
        if (shop.some(function (value) { return value.id === product.id; })) { const added = element("span", "services-added", "مفعل عندك"); added.prepend(icon("check-lg"), document.createTextNode(" ")); card.append(added); }
        else { const activate = element("button", "services-button services-button--primary", "تفعيل"); activate.type = "button"; activate.dataset.addProduct = product.id; activate.setAttribute("aria-label", "تفعيل " + product.name); card.append(activate); }
        grid.append(card);
      });
    }

    function renderOptions(product, settings) {
      const container = document.getElementById("settingsOptions"); container.replaceChildren();
      [["categories", "التصنيفات المتاحة"], ["sizes", "المقاسات المتاحة"], ["colors", "الألوان المتاحة"]].forEach(function (group) {
        const fieldset = element("fieldset", "services-options"); fieldset.dataset.optionGroup = group[0];
        const legend = element("legend", "", group[1] + " "); legend.append(element("span", "services-required", "*")); fieldset.append(legend);
        product[group[0]].forEach(function (option) {
          const value = group[0] === "colors" ? option.id : option;
          const label = element("label", "services-choice");
          const input = element("input"); input.type = "checkbox"; input.name = group[0]; input.value = value; input.checked = settings[group[0]].includes(value);
          if (group[0] === "colors") { input.setAttribute("aria-label", option.label); label.title = option.label; const swatch = element("span", "services-color"); swatch.style.setProperty("--swatch", option.value); swatch.setAttribute("aria-hidden", "true"); label.append(swatch, input); }
          else label.append(input, element("span", "", option));
          fieldset.append(label);
        });
        container.append(fieldset);
      });
    }

    function openSettings(id, origin, fromCatalog) {
      const product = definition(id);
      const existing = shop.find(function (value) { return value.id === id; });
      draft = Object.assign({ id: id }, existing ? copy(existing) : defaults(product));
      dialogOrigin = fromCatalog ? document.getElementById("openCatalog") : origin;
      returnToCatalog = fromCatalog;
      pendingCatalogFocus = fromCatalog ? id : null;
      if (catalogDialog.open) catalogDialog.close();
      const preview = document.getElementById("settingsPreview"); preview.replaceChildren(artwork(product), element("h3", "", product.name), element("p", "services-category", product.category), element("p", "", "لا يمكن تغيير اسم المنتج أو صورته."));
      form.elements.price.value = draft.price; form.elements.days.value = draft.days; form.elements.capacity.value = draft.capacity;
      error.hidden = true; renderOptions(product, draft); settingsDialog.showModal();
      form.elements.capacity.focus();
    }

    function closeSettings() {
      const reopen = returnToCatalog;
      settingsDialog.close(); draft = null; returnToCatalog = false;
      if (reopen) {
        renderCatalog(); catalogDialog.showModal();
        const target = document.querySelector('[data-add-product="' + pendingCatalogFocus + '"]');
        if (target) target.focus();
      } else if (dialogOrigin && dialogOrigin.isConnected) dialogOrigin.focus();
    }

    document.getElementById("openCatalog").addEventListener("click", function () { renderCatalog(); dialogOrigin = this; catalogDialog.showModal(); });
    products.addEventListener("click", function (event) {
      const toggle = event.target.closest("[data-toggle-product]");
      if (toggle) { const settings = shop.find(function (value) { return value.id === toggle.dataset.toggleProduct; }); settings.active = !settings.active; persist(); renderProducts(); const target = products.querySelector('[data-toggle-product="' + settings.id + '"]'); if (target) target.focus(); showToast(settings.active ? "تم تشغيل المنتج" : "تم إيقاف المنتج مؤقتًا"); return; }
      const edit = event.target.closest("[data-edit-product]");
      if (edit) { openSettings(edit.dataset.editProduct, edit, false); }
    });
    document.getElementById("catalogProducts").addEventListener("click", function (event) { const button = event.target.closest("[data-add-product]"); if (button) openSettings(button.dataset.addProduct, button, true); });
    search.addEventListener("input", renderProducts);
    document.querySelectorAll("[data-dialog-close]").forEach(function (button) { button.addEventListener("click", function () { if (button.closest("dialog") === settingsDialog) closeSettings(); else { catalogDialog.close(); document.getElementById("openCatalog").focus(); } }); });
    settingsDialog.addEventListener("cancel", function (event) { event.preventDefault(); closeSettings(); });
    catalogDialog.addEventListener("cancel", function (event) { event.preventDefault(); catalogDialog.close(); document.getElementById("openCatalog").focus(); });
    [catalogDialog, settingsDialog].forEach(function (dialog) {
      dialog.addEventListener("keydown", function (event) {
        if (event.key !== "Tab") return;
        const focusable = Array.from(dialog.querySelectorAll('button:not([disabled]), input:not([disabled]), [tabindex="0"]')).filter(function (node) { return node.getClientRects().length > 0; });
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (!first) { event.preventDefault(); return; }
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      });
    });
    [catalogDialog, settingsDialog].forEach(function (dialog) { dialog.addEventListener("click", function (event) { const rect = dialog.getBoundingClientRect(); if (event.target !== dialog || (event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom)) return; if (dialog === settingsDialog) closeSettings(); else { dialog.close(); document.getElementById("openCatalog").focus(); } }); });
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      if (!draft || !form.reportValidity()) return;
      const next = Object.assign({}, draft, { price: Number(form.elements.price.value), days: Number(form.elements.days.value), capacity: Number(form.elements.capacity.value) });
      const groups = ["categories", "sizes", "colors"];
      let emptyGroup = null;
      groups.forEach(function (group) { next[group] = Array.from(form.querySelectorAll('input[name="' + group + '"]:checked')).map(function (input) { return input.value; }); if (!next[group].length && !emptyGroup) emptyGroup = group; });
      if (emptyGroup) { error.textContent = "اختر خيارًا واحدًا على الأقل من كل مجموعة متاحة."; error.hidden = false; form.querySelector('input[name="' + emptyGroup + '"]').focus(); return; }
      if (!validSettings(next, definition(next.id))) { error.textContent = "يرجى مراجعة القيم المدخلة."; error.hidden = false; return; }
      const index = shop.findIndex(function (value) { return value.id === next.id; });
      if (index === -1) shop.push(next); else shop[index] = next;
      const savedId = next.id;
      persist(); renderProducts(); returnToCatalog = false; settingsDialog.close(); draft = null;
      const target = products.querySelector('[data-edit-product="' + savedId + '"]');
      if (target) target.focus(); else document.getElementById("openCatalog").focus();
      showToast(index === -1 ? "تمت إضافة المنتج وحفظ إعداداته" : "تم حفظ إعدادات المنتج");
    });

    renderProducts();

    if (new URLSearchParams(window.location.search).get("openCatalog")) {
      renderCatalog();
      dialogOrigin = document.getElementById("openCatalog");
      catalogDialog.showModal();
      window.history.replaceState(null, "", window.location.pathname);
    }
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initialize, { once: true });
  else initialize();
})(window, document);
