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
    form.noValidate = true; // mistakes are shown by our own messages (see the submit handler), not the browser bubble
    const search = document.getElementById("dashboardSearch");
    const error = document.getElementById("settingsError");
    // Whenever an error is shown, bring the top of the dialog into view so it is not missed.
    new MutationObserver(function () { if (!error.hidden) error.scrollIntoView({ block: "nearest" }); }).observe(error, { attributes: true, attributeFilter: ["hidden"] });
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

    function showToast(message, isError) {
      if (!message) return;
      window.clearTimeout(toastTimer);
      toast.textContent = message;
      toast.classList.toggle("is-error", Boolean(isError));
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

    /* What the card of the paper product says: its paper types, print colours and sizes (it has no areas or printing methods). */
    function paperRows(product, settings) {
      const paper = product.paper || { types: [] };
      const on = paper.types.filter(function (type) { return type.enabled; });
      const colors = ["bw", "color"].filter(function (mode) { return on.some(function (type) { return type.ink && type.ink[mode]; }); }).length;
      return [
        ["clock", settings.days + (settings.days === 1 ? " يوم" : " أيام")],
        ["box", settings.capacity + " طلب يوميًا"],
        ["stack", on.length ? on.length + (on.length === 1 ? " نوع ورق" : " أنواع ورق") : "لم تحدد أنواع الورق بعد"],
        ["droplet", colors ? colors + (colors === 1 ? " لون طباعة" : " ألوان طباعة") : "لم تحدد ألوان الطباعة بعد"],
        ["grid", settings.sizes.length === 1 ? (product.sizes.find(function (size) { return size.id === settings.sizes[0]; }) || {}).label : settings.sizes.length + " مقاسات"]
      ];
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
        if (product.code === "STICKER-CUSTOM") money.append(element("span", "", " قص لكل ورقة A4"));
        details.append(money);
        const rows = settings.configured
          ? (product.code === "PAPER-PRINT" ? paperRows(product, settings) : [["clock", settings.days + (settings.days === 1 ? " يوم" : " أيام")], ["box", settings.capacity + " قطعة يوميًا"], ["palette", settings.colors.length + " ألوان"], ["printer", settings.methods.length + (settings.methods.length === 1 ? " طريقة طباعة" : " طرق طباعة")], ["bounding-box", settings.areas.length + (settings.areas.length === 1 ? " منطقة طباعة" : " مناطق طباعة")], ["grid", settings.sizes.length === 1 ? (product.sizes.find(function (size) { return size.id === settings.sizes[0]; }) || {}).label : settings.sizes.length + " مقاسات"]])
          : [["info-circle", "أكمل السعر ومدة الإنتاج والسعة ليظهر للعملاء"]];
        const visibleRows = product.code === "STICKER-CUSTOM" && settings.configured
          ? [["clock", settings.days + " أيام"], ["files", settings.capacity + " ورقة A4 يوميًا"], ["scissors", "قص يدوي"], ["file-earmark-pdf", "ملف A4 جاهز"]] : rows;
        visibleRows.forEach(function (detail) { const item = element("li"); item.append(icon(detail[0]), element("span", "", detail[1])); details.append(item); });
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
      if (product.code === "STICKER-CUSTOM") {
        const materials = settings.sticker_materials || { paper: [
          { key: "photo-sticker", label: "فوتو لاصق", available: true, price_per_sheet: 0 },
          { key: "photo-non-adhesive", label: "فوتو غير لاصق", available: true, price_per_sheet: 0 }
        ], protection: { label: "طبقة حماية", available: false, price_per_sheet: 0 } };
        const fieldset = element("fieldset", "services-options sticker-material-options");
        fieldset.append(element("legend", "", "مواد الملصقات لكل ورقة A4"));
        (materials.paper || []).forEach(function (paper, index) {
          const row = element("div", "sticker-material-row");
          const available = element("input"); available.type = "checkbox"; available.checked = Boolean(paper.available); available.dataset.material = "paper"; available.dataset.index = index;
          const label = element("input"); label.type = "text"; label.value = paper.label || "نوع ورق"; label.dataset.material = "paper-label"; label.dataset.index = index; label.maxLength = 100;
          const price = element("input"); price.type = "number"; price.min = 0; price.step = "0.01"; price.value = paper.price_per_sheet || 0; price.dataset.material = "paper-price"; price.dataset.index = index;
          row.append(available, element("span", "", "متاح"), label, element("span", "", "₪ / ورقة"), price); fieldset.append(row);
        });
        const protection = materials.protection || {};
        const prow = element("div", "sticker-material-row sticker-protection-row");
        const pavailable = element("input"); pavailable.type = "checkbox"; pavailable.checked = Boolean(protection.available); pavailable.dataset.material = "protection";
        const plabel = element("input"); plabel.type = "text"; plabel.value = protection.label || "طبقة حماية"; plabel.dataset.material = "protection-label"; plabel.maxLength = 100;
        const pprice = element("input"); pprice.type = "number"; pprice.min = 0; pprice.step = "0.01"; pprice.value = protection.price_per_sheet || 0; pprice.dataset.material = "protection-price";
        prow.append(pavailable, element("span", "", "الحماية متاحة"), plabel, element("span", "", "₪ / ورقة"), pprice); fieldset.append(prow);
        const add = element("button", "services-button services-button--secondary", "إضافة نوع ورق"); add.type = "button"; add.addEventListener("click", function () { const row = element("div", "sticker-material-row"); row.innerHTML = '<input type="checkbox" data-material="paper" data-index="new" checked><span>متاح</span><input type="text" data-material="paper-label" data-index="new" value="نوع ورق جديد"><span>₪ / ورقة</span><input type="number" min="0" step="0.01" data-material="paper-price" data-index="new" value="0">'; fieldset.insertBefore(row, add); }); fieldset.append(add); container.append(fieldset); return;
      }
      [["colors", "الألوان المتاحة"], ["sizes", "المقاسات المتاحة"]].forEach(function (group) {
        const fieldset = element("fieldset", "services-options"); fieldset.dataset.optionGroup = group[0];
        if (product.code === "PAPER-PRINT" && group[0] === "colors") fieldset.hidden = true;
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

      // Paper is not printed on areas and has no printing methods: it sells paper types, print colours and bindings.
      if (product.code === "PAPER-PRINT") { container.append(paperFieldset(product)); return; }

      // Print areas: the ones this shop prints on, each with the largest size (cm) it can print there.
      const areas = element("fieldset", "services-options services-methods services-areas"); areas.dataset.optionGroup = "areas";
      const areaTitle = element("legend", "", "مناطق الطباعة المتاحة عندك "); areaTitle.append(element("span", "services-required", "*")); areas.append(areaTitle);
      areas.append(element("p", "services-methods-hint", "اختر المناطق التي تطبع عليها هذه القطعة، وحدد أكبر مقاس تقدر على طباعته فيها. لا يمكن أن يتجاوز مقاس المنطقة الذي يصمم عليه الزبون."));
      product.areas.forEach(function (option) {
        const saved = (settings.areas || []).find(function (item) { return item.code === option.code; });
        const row = element("div", "services-method");
        const label = element("label", "services-method-name");
        const box = element("input"); box.type = "checkbox"; box.name = "areas"; box.value = option.code; box.checked = Boolean(saved);
        label.append(box, element("span", "", option.name), element("small", "services-area-limit", "الحد " + option.widthCm + " × " + option.heightCm + " سم"));
        const sizes = element("div", "services-method-prices");
        function sizeField(caption, key, value, max) {
          const field = element("label", "services-method-price");
          field.append(element("small", "", caption));
          const wrap = element("span", "services-method-input");
          const input = element("input"); input.type = "number"; input.min = "1"; input.step = "0.1"; input.max = String(max); input.dataset[key] = option.code;
          input.value = saved ? value : ""; input.disabled = !saved; input.required = Boolean(saved); input.setAttribute("aria-label", caption + " — " + option.name);
          wrap.append(input, element("b", "", "سم"));
          field.append(wrap);
          return field;
        }
        sizes.append(sizeField("أقصى عرض", "areaWidth", saved && saved.widthCm, option.widthCm), sizeField("أقصى ارتفاع", "areaHeight", saved && saved.heightCm, option.heightCm));
        box.addEventListener("change", function () {
          sizes.querySelectorAll("input").forEach(function (input) {
            input.disabled = !box.checked; input.required = box.checked;
            if (box.checked && input.value === "") input.value = input.dataset.areaWidth !== undefined ? option.widthCm : option.heightCm;
          });
        });
        row.append(label, sizes);
        areas.append(row);
      });
      container.append(areas);

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

    /* The paper section of the settings. A print shop prices the way it talks: "every 100 sheets cost 15 ₪", "every 10 pages
       cost 5 ₪". For each paper type it has: the price of the blank paper in every size, and the price of printing on it
       in black and white and/or colour (printing costs differ from one paper to another). Then the bindings it offers.
       A size that is not ticked above has no price to enter, so its fields are switched off. */
    function paperFieldset(product) {
      const paper = product.paper || { types: [], bindings: [] };
      const wrapper = element("div", "services-paper");
      const sync = [];
      const inks = [{ key: "bw", label: "أبيض وأسود" }, { key: "color", label: "ملوّن" }];

      function numberField(min, max, step, value, label) {
        const input = element("input"); input.type = "number"; input.min = String(min); input.max = String(max); input.step = String(step);
        input.value = value === null || value === undefined ? "" : value; input.setAttribute("aria-label", label);
        return input;
      }

      // "every [count] <unit> cost [price] ₪"
      function packRow(caption, unit, pack, label) {
        const row = element("div", "services-pack");
        row.append(element("span", "services-pack-name", caption));
        const line = element("span", "services-pack-line");
        const count = numberField(1, 100000, 1, pack && pack.count, "العدد — " + label);
        const price = numberField(0.01, 100000, 0.01, pack && pack.amount, "السعر — " + label);
        const money = element("span", "services-pack-money"); money.append(price, element("b", "", "₪"));
        line.append(element("span", "", "كل"), count, element("span", "", unit), element("span", "", "بـ"), money);
        row.append(line);
        if (pack && pack.since) row.append(element("small", "services-paper-since", "آخر تحديث " + pack.since));
        return { row: row, inputs: [count, price], count: count, price: price };
      }

      function section(title, hint, required) {
        const fieldset = element("fieldset", "services-options services-methods");
        const legend = element("legend", "", title + " ");
        if (required) legend.append(element("span", "services-required", "*"));
        fieldset.append(legend, element("p", "services-methods-hint", hint));
        wrapper.append(fieldset);
        return fieldset;
      }

      // 1. The paper types, each with the blank paper per size and the printing on it per colour.
      const typesBox = section("أنواع الورق المتوفرة وأسعارها", "فعّل أنواع الورق التي عندك. لكل نوع اكتب سعر الطباعة عليه (مثلًا: كل 10 ورقات مطبوعة بـ 5 ₪) أبيض وأسود و/أو ملوّن، وسعر الطباعة بيختلف من نوع ورق لنوع. السعر يبقى ساريًا حتى تغيّره، وتقدر تحدّثه أي يوم، والأسعار القديمة تبقى محفوظة.", true);
      paper.types.forEach(function (type) {
        const card = element("div", "services-method services-paper-type");
        const head = element("label", "services-method-name");
        const typeBox = element("input"); typeBox.type = "checkbox"; typeBox.name = "paper_types"; typeBox.value = type.key; typeBox.checked = type.enabled;
        head.append(typeBox, element("span", "", type.label));

        const printing = element("div", "services-paper-block");
        printing.append(element("strong", "services-paper-title", "سعر الطباعة على هذا الورق"));
        const inkRows = inks.map(function (mode) {
          const saved = type.ink ? type.ink[mode.key] : null;
          const line = element("div", "services-paper-ink");
          const label = element("label", "services-method-name");
          const box = element("input"); box.type = "checkbox"; box.checked = Boolean(saved);
          label.append(box, element("span", "", mode.label));
          const made = packRow("", "ورقة مطبوعة", saved, type.label + " — طباعة " + mode.label);
          made.inputs.forEach(function (input, index) { input.dataset.paperField = "ink"; input.dataset.paperType = type.key; input.dataset.paperKey = mode.key; input.dataset.paperPart = index === 0 ? "count" : "price"; });
          line.append(label, made.row);
          printing.append(line);
          return { box: box, inputs: made.inputs };
        });

        sync.push(function (ticked) {
          inkRows.forEach(function (row) { row.inputs.forEach(function (input) { const active = typeBox.checked && row.box.checked; input.disabled = !active; input.required = active; }); row.box.disabled = !typeBox.checked; });
        });

        card.append(head, printing);
        typesBox.append(card);
      });

      // 2. The bindings: one price each (optional).
      const bindingsBox = section("التغليف المتوفر وسعره", "فعّل أنواع التغليف التي تقدمها واكتب سعر كل واحد. «بدون تغليف» متاح دائمًا ومجاني. إذا ما فعّلت شيئًا فالزبون ما بيشوف أي تغليف عندك.", false);
      paper.bindings.forEach(function (item) {
        const row = element("div", "services-method");
        const label = element("label", "services-method-name");
        const check = element("input"); check.type = "checkbox"; check.checked = item.enabled;
        label.append(check, element("span", "", item.label));
        const prices = element("div", "services-method-prices");
        const field = element("label", "services-method-price");
        field.append(element("small", "", "سعر التغليف"));
        const wrap = element("span", "services-method-input");
        const input = numberField(0, 1000, 0.01, item.price && item.price.amount, "سعر التغليف — " + item.label);
        input.dataset.paperBinding = item.key;
        wrap.append(input, element("b", "", "₪"));
        field.append(wrap);
        if (item.price && item.price.since) field.append(element("small", "services-paper-since", "آخر تحديث " + item.price.since));
        prices.append(field);
        sync.push(function () { input.disabled = !check.checked; input.required = check.checked; });
        check.addEventListener("change", function () { if (check.checked && input.value === "") input.focus(); });
        row.append(label, prices);
        bindingsBox.append(row);
      });

      function refresh() {
        const ticked = new Set(Array.from(form.querySelectorAll('input[name="sizes"]:checked')).map(function (input) { return input.value; }));
        sync.forEach(function (apply) { apply(ticked); });
      }
      wrapper.addEventListener("change", refresh);
      form.addEventListener("change", function (event) { if (event.target.name === "sizes") refresh(); });
      refresh();
      return wrapper;
    }

    function openSettings(id, origin, fromCatalog) {
      const product = find(id);
      const existing = product.settings;
      draft = { id: id, price: existing && existing.price, days: existing && existing.days, capacity: existing && existing.capacity,
        sticker_materials: existing && existing.sticker_materials,
        areas: existing ? existing.areas.map(function (area) { return { code: area.code, widthCm: area.widthCm, heightCm: area.heightCm }; }) : product.areas.map(function (area) { return { code: area.code, widthCm: area.widthCm, heightCm: area.heightCm }; }),
        methods: existing ? existing.methods.map(function (method) { return { id: method.id, price: method.price, rate: method.rate }; }) : [],
        colors: existing ? existing.colors.slice() : product.colors.map(function (color) { return color.id; }),
        sizes: existing ? existing.sizes.slice() : product.sizes.map(function (size) { return size.id; }) };
      dialogOrigin = fromCatalog ? document.getElementById("openCatalog") : origin;
      returnToCatalog = fromCatalog;
      pendingCatalogFocus = fromCatalog ? id : null;
      if (catalogDialog.open) catalogDialog.close();
      const preview = document.getElementById("settingsPreview"); preview.replaceChildren(artwork(product), element("h3", "", product.name), element("p", "services-category", product.category), element("p", "", "لا يمكن تغيير اسم المنتج أو صورته."));
      form.elements.price.value = draft.price == null ? "" : draft.price; form.elements.days.value = draft.days == null ? "" : draft.days; form.elements.capacity.value = draft.capacity == null ? "" : draft.capacity;
      // For paper the price field is the least a customer pays for one order (the rest comes from the paper, colour and binding prices).
      document.getElementById("productPriceLabel").textContent = product.code === "PAPER-PRINT" ? "أقل مبلغ للطلب" : "سعر المنتج الأساسي";
      if (product.code === "STICKER-CUSTOM") document.getElementById("productPriceLabel").textContent = "سعر قص ورقة A4 واحدة";
      document.getElementById("productCapacityLabel").textContent = product.code === "STICKER-CUSTOM" ? "السعة اليومية (أوراق A4)" : "السعة الإنتاجية اليومية";
      error.hidden = true; renderOptions(product, draft); clearSectionErrors(); settingsDialog.showModal();
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
        }).catch(function (exception) { toggle.disabled = false; showToast(exception.message, true); });
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

    // A mistake is shown above the section it belongs to (colours, sizes, areas, methods, one paper type...), not at the bottom.
    function clearSectionErrors() { form.querySelectorAll(".services-section-error").forEach(function (node) { node.remove(); }); }
    function sectionError(message, field, before) {
      const place = before || field.closest("fieldset") || field.parentElement;
      const note = element("p", "services-form-error services-section-error", message); note.setAttribute("role", "alert");
      place.before(note);
      showSectionError(note);
      field.focus({ preventScroll: true });
    }
    // Take the shop to the first mistake: scroll the dialog so the message is at the top, and flash it.
    function showSectionError(note) {
      window.requestAnimationFrame(function () {
        note.scrollIntoView({ block: "start", behavior: "smooth" });
        note.classList.remove("is-flashing"); void note.offsetWidth; note.classList.add("is-flashing");
      });
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      clearSectionErrors(); error.hidden = true;
      if (!draft) return;
      // The browser's own little bubble is not used: a required or wrong field gets our message above its row.
      const bad = form.querySelector("input:invalid:not(:disabled)");
      if (bad) {
        const name = bad.getAttribute("aria-label") || (bad.closest("label") && bad.closest("label").firstChild && bad.closest("label").firstChild.textContent.trim()) || "";
        const what = bad.validity.valueMissing ? "هذا الحقل مطلوب، اكتب قيمته." : bad.validity.stepMismatch || bad.validity.badInput ? "اكتب رقمًا صحيحًا في هذا الحقل." : bad.validity.rangeUnderflow || bad.validity.rangeOverflow ? "القيمة خارج المسموح (من " + bad.min + " إلى " + bad.max + ")." : "قيمة هذا الحقل غير صحيحة.";
        sectionError((name ? "«" + name + "»: " : "") + what, bad, bad.closest(".services-paper-ink, .services-number-fields") || bad.closest("fieldset") || bad.parentElement);
        return;
      }
      const body = { price: Number(form.elements.price.value), days: Number(form.elements.days.value), capacity: Number(form.elements.capacity.value) };
      let emptyGroup = null;
      const isSticker = find(draft.id).code === "STICKER-CUSTOM";
      (isSticker ? [] : ["colors", "sizes"]).forEach(function (group) { body[group] = Array.from(form.querySelectorAll('input[name="' + group + '"]:checked')).map(function (input) { return input.value; }); if (!body[group].length && !emptyGroup) emptyGroup = group; });
      if (emptyGroup) { sectionError("اختر خيارًا واحدًا على الأقل من هذه المجموعة.", form.querySelector('input[name="' + emptyGroup + '"]')); return; }
      if (find(draft.id).code === "PAPER-PRINT") {
        // Paper: for every type that is on, "every N sheets cost X" per size and "every N pages cost X" per colour; and the bindings.
        body.paper_types = Array.from(form.querySelectorAll('input[name="paper_types"]:checked')).map(function (input) { return input.value; });
        if (!body.paper_types.length) { sectionError("فعّل نوع ورق واحدًا على الأقل.", form.querySelector('input[name="paper_types"]')); return; }
        body.paper_prices = {}; body.paper_ink = {}; body.paper_bindings = {};
        const target = { sheet: body.paper_prices, ink: body.paper_ink };
        form.querySelectorAll("input[data-paper-field]:not(:disabled)").forEach(function (input) {
          const group = target[input.dataset.paperField];
          const pack = (group[input.dataset.paperType] = group[input.dataset.paperType] || {});
          (pack[input.dataset.paperKey] = pack[input.dataset.paperKey] || {})[input.dataset.paperPart] = Number(input.value);
        });
        form.querySelectorAll("input[data-paper-binding]:not(:disabled)").forEach(function (input) { body.paper_bindings[input.dataset.paperBinding] = Number(input.value); });
        const noPrinting = body.paper_types.filter(function (type) { return !body.paper_ink[type]; });
        if (noPrinting.length) {
          // one message above the printing prices of every paper type that has none
          noPrinting.forEach(function (type) {
            const box = form.querySelector('input[name="paper_types"][value="' + type + '"]');
            const block = box.closest(".services-paper-type").querySelector(".services-paper-block");
            const note = element("p", "services-form-error services-section-error", "فعّل طباعة أبيض وأسود أو ملوّن واكتب سعرها، أو أطفئ هذا النوع من الورق."); note.setAttribute("role", "alert");
            block.before(note);
          });
          showSectionError(form.querySelector(".services-section-error"));
          return;
        }
      } else if (isSticker) {
        const paper = [];
        form.querySelectorAll('[data-material="paper"]').forEach(function (input) {
          const index = input.dataset.index;
          const label = form.querySelector('[data-material="paper-label"][data-index="' + index + '"]');
          const price = form.querySelector('[data-material="paper-price"][data-index="' + index + '"]');
          if (label && price) paper.push({ key: index === 'new' ? 'custom-' + paper.length : (index === '0' ? 'photo-sticker' : index === '1' ? 'photo-non-adhesive' : 'paper-' + index), label: label.value.trim(), available: input.checked, price_per_sheet: Number(price.value || 0) });
        });
        const protection = { available: Boolean(form.querySelector('[data-material="protection"]')?.checked), label: form.querySelector('[data-material="protection-label"]')?.value.trim() || 'طبقة حماية', price_per_sheet: Number(form.querySelector('[data-material="protection-price"]')?.value || 0) };
        body.sticker_materials = { paper: paper, protection: protection };
      } else {
        body.areas = Array.from(form.querySelectorAll('input[name="areas"]:checked')).map(function (input) {
          return { code: input.value, width_cm: Number(form.querySelector('[data-area-width="' + input.value + '"]').value), height_cm: Number(form.querySelector('[data-area-height="' + input.value + '"]').value) };
        });
        if (!body.areas.length) { sectionError("اختر منطقة طباعة واحدة على الأقل.", form.querySelector('input[name="areas"]')); return; }
        body.methods = Array.from(form.querySelectorAll('input[name="methods"]:checked')).map(function (input) {
          const price = form.querySelector('[data-method-price="' + input.value + '"]'), rate = form.querySelector('[data-method-rate="' + input.value + '"]');
          return { id: Number(input.value), price: Number(price.value), rate: Number(rate.value || 0) };
        });
        if (!body.methods.length) { sectionError("اختر طريقة طباعة واحدة على الأقل وحدد سعرها.", form.querySelector('input[name="methods"]')); return; }
      }
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
