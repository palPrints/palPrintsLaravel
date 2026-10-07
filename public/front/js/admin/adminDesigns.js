/* Admin designs page: filters, search, details dialog and approve / reject. */
(function (window, document) {
  "use strict";

  function initialize() {
    const rows = function () { return Array.from(document.querySelectorAll("[data-design-row]")); };
    const search = document.getElementById("dashboardSearch");
    const tableEmpty = document.getElementById("tableEmpty");
    const dialog = document.getElementById("designDialog");
    const dialogActions = document.getElementById("dialogActions");
    const csrf = document.querySelector('meta[name="csrf-token"]');
    let activeCategory = "all";
    let activeStatus = "all";
    let activeRow = null;

    const toast = function (message, type) {
      if (window.PalAdmin) window.PalAdmin.toast(message, type);
    };

    const normalize = function (value) {
      return String(value || "").trim().toLocaleLowerCase("ar")
        .replace(/[أإآ]/g, "ا").replace(/ة/g, "ه").replace(/ى/g, "ي");
    };

    const setText = function (id, value) {
      document.getElementById(id).textContent = value;
    };

    function updateSummary() {
      const all = rows();
      const count = function (status) {
        return all.filter(function (row) { return row.dataset.status === status; }).length;
      };
      const pending = count("pending");

      setText("totalDesigns", all.length);
      setText("pendingDesigns", pending);
      setText("approvedDesigns", count("approved"));
      setText("rejectedDesigns", count("rejected"));
      setText("totalTabCount", all.length);
      setText("pendingTabCount", pending);
      setText("approvedTabCount", count("approved"));
      setText("rejectedTabCount", count("rejected"));

      const badge = document.querySelector('.admin-sidebar a[href$="/admin/designs"] .admin-nav-badge');
      if (badge) {
        badge.textContent = pending;
        badge.hidden = pending === 0;
      }
    }

    function filterRows() {
      const query = normalize(search ? search.value : "");
      let visible = 0;

      rows().forEach(function (row) {
        const text = normalize([row.dataset.code, row.dataset.title, row.dataset.designer, row.dataset.categoryLabel].join(" "));
        const matches = (!query || text.includes(query))
          && (activeCategory === "all" || row.dataset.category === activeCategory)
          && (activeStatus === "all" || row.dataset.status === activeStatus);
        row.hidden = !matches;
        if (matches) visible += 1;
      });

      tableEmpty.hidden = visible !== 0;
    }

    function bindFilter(selector, attribute, apply) {
      document.querySelectorAll(selector).forEach(function (button) {
        button.addEventListener("click", function () {
          apply(button.dataset[attribute]);
          document.querySelectorAll(selector).forEach(function (item) {
            item.setAttribute("aria-pressed", String(item === button));
          });
          filterRows();
        });
      });
    }

    bindFilter("[data-category-filter]", "categoryFilter", function (value) { activeCategory = value; });
    bindFilter("[data-status-filter]", "statusFilter", function (value) { activeStatus = value; });

    if (search) search.addEventListener("input", filterRows);

    const stateLabels = { pending: "قيد المراجعة", approved: "معتمد", rejected: "مرفوض" };

    function readDetails(row) {
      try { return JSON.parse(row.dataset.details || "{}"); } catch (error) { return {}; }
    }

    /* Colours as a coloured dot and a name, not a bare name. */
    function fillSwatches(id, colors) {
      const target = document.getElementById(id);
      target.replaceChildren();
      const list = document.createElement("ul");
      list.className = "design-swatches";
      colors.forEach(function (color) {
        const item = document.createElement("li");
        item.className = "design-swatch";
        const dot = document.createElement("i");
        dot.style.backgroundColor = color.hex || "#cccccc";
        item.append(dot, document.createTextNode(color.name));
        list.appendChild(item);
      });
      target.appendChild(list);
    }

    /* The design on every print area: the studio's own product picture and print zone, with what the designer placed
       there. A colour without its own photo is painted onto the white garment, as in the studio. */
    const studioBase = window.palPrintsStudioBase || "";
    const studioUrl = function (path) { return /^(https?:)?\/\//.test(path) || path.charAt(0) === "/" ? path : studioBase + path; };

    /* The colours the admin can try the design on: the designer's own preview colour first, then the approved ones. */
    function previewColors(details) {
      const seen = new Set();
      return [details.previewColor].concat(details.colors || []).filter(function (color) {
        if (!color || seen.has(color.id)) return false;
        seen.add(color.id);
        return true;
      });
    }

    function renderColorPicker(row) {
      const details = readDetails(row);
      const picker = document.getElementById("dialogColorPicker");
      const colors = previewColors(details);
      picker.replaceChildren();
      picker.hidden = colors.length < 2;

      colors.forEach(function (color, index) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "design-color";
        button.setAttribute("role", "radio");
        button.setAttribute("aria-checked", String(index === 0));
        button.title = index === 0 && details.previewColor ? "لون المصمم" : color.name;
        const dot = document.createElement("i");
        dot.style.backgroundColor = color.hex || "#cccccc";
        const name = document.createElement("span");
        name.textContent = color.name;
        button.append(dot, name);
        button.addEventListener("click", function () {
          picker.querySelectorAll(".design-color").forEach(function (item) { item.setAttribute("aria-checked", String(item === button)); });
          renderAreas(row, color);
        });
        picker.appendChild(button);
      });

      return colors[0] || null;
    }

    function renderAreas(row, colorEntry) {
      const details = readDetails(row);
      const section = document.getElementById("dialogAreasSection");
      const grid = document.getElementById("dialogAreas");
      const note = document.getElementById("dialogAreasNote");
      const products = (window.PALPRINTS_PRODUCT_CATALOG && window.PALPRINTS_PRODUCT_CATALOG.products) || [];
      const product = products.find(function (item) { return item.categoryId === details.kind; });
      grid.replaceChildren();
      section.hidden = !product;
      if (!product) return;

      const offered = (details.areas || []).map(function (area) { return area.id === "wrap" ? "front" : area.id; });
      const areas = product.printAreas.filter(function (area) { return !offered.length || offered.indexOf(area.id) !== -1; });
      const layout = details.layout || {};
      const files = details.files || {};
      const graphics = new Map(((window.PALPRINTS_STUDIO_GRAPHICS && window.PALPRINTS_STUDIO_GRAPHICS.items) || []).map(function (item) { return [item.id, item]; }));
      const chosen = colorEntry || details.previewColor;
      const previewId = chosen ? String(chosen.id).toLowerCase() : product.defaultColor;
      const known = product.colors.find(function (item) { return item.id === previewId; });
      const base = known || product.colors.find(function (item) { return item.id === "white"; }) || product.colors[0];
      const isWhite = function (value) {
        const match = /^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(value || "");
        return !match || (0.299 * parseInt(match[1], 16) + 0.587 * parseInt(match[2], 16) + 0.114 * parseInt(match[3], 16)) / 255 > 0.95;
      };
      // A colour without its own photo of an area (every colour lacks the sleeves; new colours lack them all) is painted
      // onto the white garment, as the studio does.
      const ownPhoto = function (area) {
        return Boolean(known && ((known.areaMockups && known.areaMockups[area.id]) || (["front", "primary"].indexOf(area.role) !== -1 && known.image)));
      };
      const paintHex = known ? known.value : (chosen ? chosen.hex : null);


      let withDesign = 0;
      areas.forEach(function (area) {
        const objects = ((layout[area.id] || {}).objects) || [];
        if (objects.length) withDesign += 1;

        const figure = document.createElement("figure");
        figure.className = "design-area " + (objects.length ? "has-design" : "is-empty");
        const stage = document.createElement("div");
        stage.className = "design-area__stage";
        const hasPhoto = ownPhoto(area);
        const mockup = studioUrl(hasPhoto
          ? ((base.areaMockups && base.areaMockups[area.id]) || base.image)
          : (["front", "primary"].indexOf(area.role) !== -1 && base.image) || area.mockup);
        const picture = document.createElement("img");
        picture.src = mockup;
        picture.alt = area.name;
        stage.appendChild(picture);

        const hex = !hasPhoto && !isWhite(paintHex) ? paintHex : null;
        if (hex) {
          const tint = document.createElement("span");
          tint.className = "design-area__tint";
          tint.style.backgroundColor = hex;
          tint.style.webkitMaskImage = tint.style.maskImage = "url('" + mockup + "')";
          stage.appendChild(tint);
          // A colour that has photos is painted with the colour those photos show, so the sleeves match the front and back.
          const white = product.colors.find(function (item) { return item.id === "white"; });
          if (known && known.image && white && white.image && window.PALPRINTS_PHOTO_TINT) {
            window.PALPRINTS_PHOTO_TINT(studioUrl(known.image), studioUrl(white.image)).then(function (photoHex) {
              if (photoHex) tint.style.backgroundColor = photoHex;
            });
          }
        }

        const zone = document.createElement("div");
        zone.className = "design-area__zone";
        Object.assign(zone.style, { left: area.printZone.leftPct + "%", top: area.printZone.topPct + "%", width: area.printZone.widthPct + "%", height: area.printZone.heightPct + "%" });
        stage.appendChild(zone);
        figure.appendChild(stage);

        const caption = document.createElement("figcaption");
        const label = document.createElement("span");
        label.textContent = area.name;
        const state = document.createElement("small");
        state.textContent = objects.length ? objects.length + " عنصر" : "بلا تصميم";
        caption.append(label, state);
        figure.appendChild(caption);
        grid.appendChild(figure);

        figure.__draw = function () {
          const zoneHeight = zone.clientHeight;
          objects.forEach(function (object) {
            const placement = { left: (Number(object.x) || 0) * 100 + "%", top: (Number(object.y) || 0) * 100 + "%",
              transform: "translate(-50%, -50%) rotate(" + (Number(object.angle) || 0) + "deg) scale(" + (object.flipX ? -1 : 1) + "," + (object.flipY ? -1 : 1) + ")" };

            if (object.kind === "text") {
              const text = document.createElement("span");
              text.className = "design-area__text";
              text.textContent = object.text || "";
              Object.assign(text.style, placement, {
                width: (Number(object.width) || 0.7) * (Number(object.scaleX) || 1) * 100 + "%",
                fontSize: Math.max(6, zoneHeight * (Number(object.fontSize) || 0.1) * (Number(object.scaleY) || 1)) + "px",
                fontFamily: (object.fontFamily || "Cairo") + ", sans-serif", color: object.fill || "#0b1f3a",
                fontWeight: object.fontWeight || "normal", fontStyle: object.fontStyle || "normal",
                textAlign: object.textAlign || "center", lineHeight: String(Number(object.lineHeight) || 1.2),
              });
              zone.appendChild(text);
              return;
            }

            let source = null;
            let paint = null;
            if (object.kind === "image") source = files[object.assetId] || null;
            if (object.kind === "graphic") {
              const graphic = graphics.get(object.graphicId);
              source = graphic ? studioUrl(graphic.assetPath) : null;
              paint = graphic && graphic.recolorable ? (object.color || graphic.defaultColor) : null;
            }
            if (!source) return;

            const item = document.createElement("span");
            item.className = "design-area__item";
            item.setAttribute("role", "img");
            Object.assign(item.style, placement, { width: (Number(object.width) || 0.2) * 100 + "%", height: (Number(object.height) || 0.2) * 100 + "%" });
            if (paint) {
              item.style.backgroundColor = paint;
              item.style.webkitMaskImage = item.style.maskImage = "url('" + source + "')";
              item.style.webkitMaskSize = item.style.maskSize = "contain";
              item.style.webkitMaskRepeat = item.style.maskRepeat = "no-repeat";
              item.style.webkitMaskPosition = item.style.maskPosition = "center";
            } else {
              item.style.backgroundImage = "url('" + source + "')";
            }
            zone.appendChild(item);
          });
        };
      });

      note.textContent = "المناطق التي تقدمها المطابع: " + areas.length + (chosen ? " · بلون " + chosen.name : "")
        + " · " + (withDesign ? "التصميم على " + withDesign + " منها" : "لا يوجد تصميم على أي منطقة");
      // The stages are measured once the dialog is on screen (text sizes are a share of the zone's height).
      requestAnimationFrame(function () {
        Array.from(grid.children).forEach(function (figure) { if (figure.__draw) figure.__draw(); });
      });
    }

    function openDetails(row) {
      const data = row.dataset;
      const image = document.getElementById("dialogDesignImage");
      const empty = document.getElementById("dialogDesignEmpty");
      const status = document.getElementById("dialogStatus");

      activeRow = row;
      image.hidden = !data.image;
      empty.hidden = Boolean(data.image);
      if (data.image) image.src = data.image;
      image.alt = "معاينة تصميم " + data.title;

      setText("dialogDesignTitle", data.title);
      setText("dialogDesignId", data.code);
      setText("dialogCategoryPreview", data.categoryLabel);
      setText("dialogCategory", data.categoryLabel);
      document.getElementById("dialogCategory").className = "category " + (data.categoryClass || "");
      setText("dialogDesigner", data.designer);
      setText("dialogProduct", data.product || "—");
      setText("dialogDate", data.date);
      [["audience", "dialogAudience"], ["sizes", "dialogSizes"]].forEach(function (pair) {
        setText(pair[1], data[pair[0]] || "");
        dialog.querySelector('[data-detail-row="' + pair[0] + '"]').hidden = !data[pair[0]];
      });
      const details = readDetails(row);
      const previewColors = details.previewColor ? [details.previewColor] : [];
      fillSwatches("dialogPreviewColor", previewColors);
      dialog.querySelector('[data-detail-row="previewColor"]').hidden = !previewColors.length;
      dialog.scrollTop = 0;
      setText("dialogBasePrice", data.basePrice);
      setText("dialogSellingPrice", data.sellingPrice);
      setText("dialogProfit", data.profit);
      status.className = "design-status " + data.status;
      status.textContent = stateLabels[data.status];

      document.getElementById("dialogReasonRow").hidden = !(data.status === "rejected" && data.reason);
      setText("dialogReason", data.reason || "");
      dialogActions.hidden = data.status !== "pending";
      dialog.showModal();
      const firstColor = renderColorPicker(row);
      renderAreas(row, firstColor);
      // When the areas are drawn, they replace the single picture the designer sent.
      document.getElementById("dialogDesignPreview").hidden = !document.getElementById("dialogAreasSection").hidden;
    }

    document.getElementById("designTableBody").addEventListener("click", function (event) {
      const button = event.target.closest("[data-details]");
      if (button) openDetails(button.closest("[data-design-row]"));
    });

    dialog.querySelectorAll("[data-dialog-close]").forEach(function (button) {
      button.addEventListener("click", function () { dialog.close(); });
    });
    dialog.addEventListener("click", function (event) {
      if (event.target === dialog) dialog.close();
    });

    function reviewDesign(action, reason) {
      if (!activeRow) return;
      reason = reason === undefined ? null : reason;

      const row = activeRow;
      const buttons = dialogActions.querySelectorAll("button");
      buttons.forEach(function (button) { button.disabled = true; });

      window.fetch(row.dataset.reviewUrl, {
        method: "POST",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf ? csrf.content : "",
        },
        body: JSON.stringify({ action: action, reason: reason }),
      })
        .then(function (response) {
          return response.json().then(function (body) { return { ok: response.ok, body: body }; });
        })
        .then(function (result) {
          if (!result.ok) throw new Error(result.body.message || "تعذّر تنفيذ الإجراء.");

          const state = action === "approve" ? "approved" : "rejected";
          const badge = row.querySelector(".design-status");
          row.dataset.status = state;
          row.dataset.reason = reason || "";
          badge.className = "design-status " + state;
          badge.textContent = stateLabels[state];
          dialog.close();
          updateSummary();
          filterRows();
          toast(result.body.message, action === "approve" ? "success" : "warning");
        })
        .catch(function (error) {
          toast(error.message || "تعذّر تنفيذ الإجراء.", "error");
        })
        .finally(function () {
          buttons.forEach(function (button) { button.disabled = false; });
        });
    }

    document.getElementById("approveDesign").addEventListener("click", function () { reviewDesign("approve"); });
    const rejectDialog = document.getElementById("rejectDialog"), rejectReason = document.getElementById("rejectReason");
    rejectReason.addEventListener("input", function () { setText("rejectCount", String(rejectReason.value.length)); });
    document.getElementById("rejectDesign").addEventListener("click", function () {
      rejectReason.value = ""; setText("rejectCount", "0");
      rejectDialog.showModal(); rejectReason.focus();
    });
    document.getElementById("rejectCancel").addEventListener("click", function () { rejectDialog.close(); });
    rejectDialog.addEventListener("click", function (event) { if (event.target === rejectDialog) rejectDialog.close(); });
    document.getElementById("rejectForm").addEventListener("submit", function (event) {
      event.preventDefault();
      const reason = rejectReason.value.trim();
      rejectDialog.close();
      reviewDesign("reject", reason);
    });

    updateSummary();
    filterRows();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(window, document);
