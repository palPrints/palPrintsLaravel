(function (window, document) {
  "use strict";

  /* The shop's products page. Everything comes from the database (window.palPrintsServices, filled by the server) and
     every change is saved back through the page's own endpoints; nothing is kept in the browser. */
  const config = window.palPrintsServices || { products: [] };
  const csrf = document.querySelector('meta[name="csrf-token"]');

  function initialize() {
    const products = document.getElementById("servicesProducts");
    if (!products) return;
    const catalogDialog = document.getElementById("catalogDialog");
    const settingsDialog = document.getElementById("settingsDialog");
    const form = document.getElementById("productSettingsForm");
    const search = document.getElementById("dashboardSearch");
    const error = document.getElementById("settingsError");
    const toast = document.getElementById("servicesToast");
    const submit = form.querySelector('[type="submit"]');
    let toastTimer;
    let draft = null;
    let dialogOrigin = null;
    let returnToCatalog = false;
    let pendingCatalogFocus = null;
    let catalog = Array.isArray(config.products) ? config.products : [];

    function element(tag, className, text) {
      const node = document.createElement(tag);
      if (className) node.className = className;
      if (text !== undefined) node.textContent = text;
      return node;
    }

    function icon(name) { const node = element("i", "bi bi-" + name); node.setAttribute("aria-hidden", "true"); return node; }
    function find(id) { return catalog.find(function (product) { return product.id === id; }); }
    function offered() { return catalog.filter(function (product) { return product.settings; }); }
    function url(template, id) { return template.replace("__ID__", String(id)); }
    function closeDropdowns() {
      ["accountDropdown", "notificationDropdown"].forEach(function (id) {
        const popup = document.getElementById(id);
        if (popup) { popup.hidden = true; popup.classList.remove("open"); }
      });
    }

    function showToast(message) {
      window.clearTimeout(toastTimer);
      toast.textContent = message;
      toast.classList.add("is-visible");
      toastTimer = window.setTimeout(function () { toast.classList.remove("is-visible"); }, 3500);
    }

    function request(method, address, body) {
      return window.fetch(address, {
        method: method,
        headers: { "Accept": "application/json", "Content-Type": "application/json", "X-CSRF-TOKEN": csrf ? csrf.content : "" },
        body: body ? JSON.stringify(body) : undefined
      }).then(function (response) {
        return response.json().catch(function () { return {}; }).then(function (data) {
          if (!response.ok) {
            const first = data.errors ? Object.values(data.errors)[0][0] : data.message;
            throw new Error(first || "تعذّر تنفيذ الإجراء. حاول مرة أخرى.");
          }
          return data;
        });
      });
    }

    function replace(product) {
      const index = catalog.findIndex(function (item) { return item.id === product.id; });
      if (index !== -1) catalog[index] = product;
    }

    function artwork(product) {
      const art = element("div", "services-product-art");
      art.setAttribute("role", "img");
      art.setAttribute("aria-label", product.name);
      if (product.image) {
        const photo = element("div", "services-product-photo");
        const image = element("img"); image.src = product.image; image.alt = ""; image.decoding = "async";
        image.addEventListener("load", function () { photo.style.setProperty("--product-ratio", String(image.naturalWidth / image.naturalHeight)); }, { once: true });
        photo.append(image);
        art.append(photo);
      } else {
        art.append(icon("box-seam"));
      }
      return art;
    }

    function normalize(value) { return String(value).trim().toLocaleLowerCase("ar").replace(/[أإآ]/g, "ا").replace(/ة/g, "ه").replace(/ى/g, "ي"); }

    function status(settings) {
      if (!settings.configured) return { text: "بانتظار إكمال الإعدادات", paused: true };
      return settings.active ? { text: "نشط", paused: false } : { text: "موقوف مؤقتًا", paused: true };
    }

    function renderProducts() {
      const query = normalize(search ? search.value : "");
      products.replaceChildren();
      let visible = 0;
      const mine = offered();
      mine.forEach(function (product) {
        const settings = product.settings;
        if (query && !normalize(product.name + " " + product.category).includes(query)) return;
        visible += 1;
        const state = status(settings);
        const card = element("article", "services-product"); card.dataset.productId = product.id;
        const top = element("div", "services-product-top");
        top.append(element("span", "services-badge" + (state.paused ? " services-badge--paused" : ""), state.text));
        const toggle = element("button", "services-switch"); toggle.type = "button"; toggle.dataset.toggleProduct = product.id; toggle.setAttribute("role", "switch"); toggle.setAttribute("aria-checked", String(settings.active)); toggle.setAttribute("aria-label", "تشغيل " + product.name); top.append(toggle);
        card.append(top, artwork(product), element("h3", "", product.name), element("p", "services-category", product.category));
        const details = element("ul", "services-details");
        const money = element("li", "services-money");
        const amount = element("span", "", settings.configured ? settings.price.toFixed(2) + " ₪" : "السعر غير محدد");
        amount.dir = "ltr";
        money.append(amount);
        details.append(money);
        const rows = settings.configured
          ? [["clock", settings.days + (settings.days === 1 ? " يوم" : " أيام")], ["box", settings.capacity + " قطعة يوميًا"], ["palette", settings.colors.length + " ألوان"], ["printer", settings.methods.length + (settings.methods.length === 1 ? " طريقة طباعة" : " طرق طباعة")], ["grid", settings.sizes.length === 1 ? (product.sizes.find(function (size) { return size.id === settings.sizes[0]; }) || {}).label : settings.sizes.length + " مقاسات"]]
          : [["info-circle", "أكمل السعر ومدة الإنتاج والسعة ليظهر للعملاء"]];
        rows.forEach(function (detail) { const item = element("li"); item.append(icon(detail[0]), element("span", "", detail[1])); details.append(item); });
        const edit = element("button", "services-button services-edit"); edit.type = "button"; edit.dataset.editProduct = product.id; edit.setAttribute("aria-label", "تعديل إعدادات " + product.name); edit.append(icon("pencil-square"), element("span", "", settings.configured ? "تعديل الإعدادات" : "إكمال الإعدادات"));
        card.append(details, edit); products.append(card);
      });
      document.getElementById("servicesEmpty").hidden = visible !== 0;
      document.getElementById("addedCount").textContent = String(mine.length);
      const summary = document.getElementById("servicesSummary");
      summary.replaceChildren(document.createTextNode("عدد المنتجات المفعلة: "), element("strong", "", String(mine.length) + " من أصل " + catalog.length), document.createTextNode(" منتجًا في كتالوج المنصة"));
    }

    function renderCatalog() {
      const grid = document.getElementById("catalogProducts"); grid.replaceChildren();
      catalog.forEach(function (product) {
        const card = element("article", "services-catalog-card"); card.append(artwork(product), element("h3", "", product.name), element("p", "services-category", product.category));
        if (product.settings) { const added = element("span", "services-added", "مفعل عندك"); added.prepend(icon("check-lg"), document.createTextNode(" ")); card.append(added); }
        else { const activate = element("button", "services-button services-button--primary", "تفعيل"); activate.type = "button"; activate.dataset.addProduct = product.id; activate.setAttribute("aria-label", "تفعيل " + product.name); card.append(activate); }
        grid.append(card);
      });
    }

    function renderOptions(product, settings) {
      const container = document.getElementById("settingsOptions"); container.replaceChildren();
      [["colors", "الألوان المتاحة"], ["sizes", "المقاسات المتاحة"]].forEach(function (group) {
        const fieldset = element("fieldset", "services-options"); fieldset.dataset.optionGroup = group[0];
        const legend = element("legend", "", group[1] + " "); legend.append(element("span", "services-required", "*")); fieldset.append(legend);
        product[group[0]].forEach(function (option) {
          const label = element("label", "services-choice");
          const input = element("input"); input.type = "checkbox"; input.name = group[0]; input.value = option.id; input.checked = settings[group[0]].includes(option.id);
          if (group[0] === "colors") { input.setAttribute("aria-label", option.label); label.title = option.label; const swatch = element("span", "services-color"); swatch.style.setProperty("--swatch", option.value); swatch.setAttribute("aria-hidden", "true"); label.append(swatch, input); }
          else label.append(input, element("span", "", option.label));
          fieldset.append(label);
        });
        container.append(fieldset);
      });

      // Printing methods: a method is offered when ticked, with its own price on top of the product price.
      const methods = element("fieldset", "services-options services-methods"); methods.dataset.optionGroup = "methods";
      const title = element("legend", "", "طرق الطباعة وسعر كل طريقة "); title.append(element("span", "services-required", "*")); methods.append(title);
      methods.append(element("p", "services-methods-hint", "عند اختيار الزبون لهذه الطريقة: السعر = السعر الأساسي + الرسوم الثابتة + (مساحة التصميم ÷ 100 × السعر لكل 100 سم²). مثال: تصميم 400 سم² والسعر 2 ₪ يضيف 8 ₪."));
      product.methods.forEach(function (method) {
        const saved = (settings.methods || []).find(function (item) { return item.id === method.id; });
        const row = element("div", "services-method");
        const label = element("label", "services-method-name");
        const box = element("input"); box.type = "checkbox"; box.name = "methods"; box.value = method.id; box.checked = Boolean(saved);
        label.append(box, element("span", "", method.name));
        const prices = element("div", "services-method-prices");
        function moneyField(caption, key, max, required) {
          const field = element("label", "services-method-price");
          field.append(element("small", "", caption));
          const wrap = element("span", "services-method-input");
          const input = element("input"); input.type = "number"; input.min = "0"; input.step = "0.01"; input.max = String(max); input.dataset[key] = method.id;
          input.value = saved && saved[key === "methodPrice" ? "price" : "rate"] ? saved[key === "methodPrice" ? "price" : "rate"] : (saved ? "0" : "");
          input.disabled = !saved; input.required = Boolean(saved) && required; input.setAttribute("aria-label", caption + " — " + method.name);
          wrap.append(input, element("b", "", "₪"));
          field.append(wrap);
          return field;
        }
        prices.append(moneyField("رسوم ثابتة للقطعة", "methodPrice", 100000, true), moneyField("السعر لكل 100 سم²", "methodRate", 1000, false));
        box.addEventListener("change", function () {
          prices.querySelectorAll("input").forEach(function (input) {
            input.disabled = !box.checked;
            input.required = box.checked && input.dataset.methodPrice !== undefined;
            if (box.checked && input.value === "") input.value = input.dataset.methodRate !== undefined ? "0" : "";
          });
          if (box.checked) prices.querySelector("input").focus();
        });
        row.append(label, prices);
        methods.append(row);
      });
      container.append(methods);
    }

    function openSettings(id, origin, fromCatalog) {
      const product = find(id);
      const existing = product.settings;
      draft = { id: id, price: existing && existing.price, days: existing && existing.days, capacity: existing && existing.capacity,
        methods: existing ? existing.methods.map(function (method) { return { id: method.id, price: method.price, rate: method.rate }; }) : [],
        colors: existing ? existing.colors.slice() : product.colors.map(function (color) { return color.id; }),
        sizes: existing ? existing.sizes.slice() : product.sizes.map(function (size) { return size.id; }) };
      dialogOrigin = fromCatalog ? document.getElementById("openCatalog") : origin;
      returnToCatalog = fromCatalog;
      pendingCatalogFocus = fromCatalog ? id : null;
      if (catalogDialog.open) catalogDialog.close();
      const preview = document.getElementById("settingsPreview"); preview.replaceChildren(artwork(product), element("h3", "", product.name), element("p", "services-category", product.category), element("p", "", "لا يمكن تغيير اسم المنتج أو صورته."));
      form.elements.price.value = draft.price == null ? "" : draft.price; form.elements.days.value = draft.days == null ? "" : draft.days; form.elements.capacity.value = draft.capacity == null ? "" : draft.capacity;
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

    document.getElementById("openCatalog").addEventListener("click", function () { closeDropdowns(); renderCatalog(); dialogOrigin = this; catalogDialog.showModal(); });
    products.addEventListener("click", function (event) {
      const toggle = event.target.closest("[data-toggle-product]");
      if (toggle) {
        const id = Number(toggle.dataset.toggleProduct);
        toggle.disabled = true;
        request("PATCH", url(config.toggleUrl, id)).then(function (data) {
          replace(data.product); renderProducts(); showToast(data.message);
          const target = products.querySelector('[data-toggle-product="' + id + '"]'); if (target) target.focus();
        }).catch(function (exception) { toggle.disabled = false; showToast(exception.message); });
        return;
      }
      const edit = event.target.closest("[data-edit-product]");
      if (edit) { closeDropdowns(); openSettings(Number(edit.dataset.editProduct), edit, false); }
    });
    document.getElementById("catalogProducts").addEventListener("click", function (event) { const button = event.target.closest("[data-add-product]"); if (button) openSettings(Number(button.dataset.addProduct), button, true); });
    if (search) search.addEventListener("input", renderProducts);
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
      const body = { price: Number(form.elements.price.value), days: Number(form.elements.days.value), capacity: Number(form.elements.capacity.value) };
      let emptyGroup = null;
      ["colors", "sizes"].forEach(function (group) { body[group] = Array.from(form.querySelectorAll('input[name="' + group + '"]:checked')).map(function (input) { return input.value; }); if (!body[group].length && !emptyGroup) emptyGroup = group; });
      if (emptyGroup) { error.textContent = "اختر خيارًا واحدًا على الأقل من كل مجموعة متاحة."; error.hidden = false; form.querySelector('input[name="' + emptyGroup + '"]').focus(); return; }
      body.methods = Array.from(form.querySelectorAll('input[name="methods"]:checked')).map(function (input) {
        const price = form.querySelector('[data-method-price="' + input.value + '"]'), rate = form.querySelector('[data-method-rate="' + input.value + '"]');
        return { id: Number(input.value), price: Number(price.value), rate: Number(rate.value || 0) };
      });
      if (!body.methods.length) { error.textContent = "اختر طريقة طباعة واحدة على الأقل وحدد سعرها."; error.hidden = false; form.querySelector('input[name="methods"]').focus(); return; }
      const id = draft.id, isNew = !find(id).settings;
      submit.disabled = true;
      request("PUT", url(config.saveUrl, id), body).then(function (data) {
        replace(data.product); returnToCatalog = false; settingsDialog.close(); draft = null; renderProducts();
        const target = products.querySelector('[data-edit-product="' + id + '"]'); if (target) target.focus(); else document.getElementById("openCatalog").focus();
        showToast(isNew ? "تمت إضافة المنتج وحفظ إعداداته" : data.message);
      }).catch(function (exception) { error.textContent = exception.message; error.hidden = false; }).then(function () { submit.disabled = false; });
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
