/* Admin stickers page: add / rename / delete categories and add / delete sticker images (front end only, nothing is saved yet). */
(function (window, document) {
  "use strict";

  function initialize() {
    const MAX_IMAGE_BYTES = 2 * 1024 * 1024;
    const IMAGE_TYPES = ["image/png", "image/jpeg", "image/webp", "image/svg+xml"];

    const bar = document.getElementById("categoriesBar");
    const grid = document.getElementById("stickersGrid");
    const empty = document.getElementById("stickersEmpty");
    const emptyTitle = document.getElementById("stickersEmptyTitle");
    const emptyText = document.getElementById("stickersEmptyText");

    const categoryDialog = document.getElementById("categoryDialog");
    const categoryForm = document.getElementById("categoryForm");
    const categoryTitle = document.getElementById("categoryDialogTitle");
    const categoryName = document.getElementById("categoryNameInput");
    const categoryError = document.getElementById("categoryError");

    const stickerDialog = document.getElementById("stickerDialog");
    const stickerForm = document.getElementById("stickerForm");
    const stickerImage = document.getElementById("stickerImageInput");
    const stickerPreview = document.getElementById("stickerPreviewImg");
    const stickerUploadTitle = document.getElementById("stickerUploadTitle");
    const stickerName = document.getElementById("stickerNameInput");
    const stickerCategory = document.getElementById("stickerCategoryInput");
    const stickerError = document.getElementById("stickerError");
    const stickerTitle = document.getElementById("stickerDialogTitle");
    const stickerSubmit = document.getElementById("stickerSubmitButton");

    let nextId = 1;
    let categories = [];
    let stickers = [];
    let activeCategory = "all";
    let editingCategory = null;
    let pendingImage = null;
    let editingSticker = null;

    function toast(message) {
      if (window.PalAdmin) window.PalAdmin.toast(message);
    }

    function el(tag, className, text) {
      const node = document.createElement(tag);
      if (className) node.className = className;
      if (text != null) node.textContent = text;
      return node;
    }

    function iconButton(icon, label, dataName, dataValue) {
      const button = el("button");
      button.type = "button";
      button.dataset[dataName] = dataValue;
      button.setAttribute("aria-label", label);
      const i = el("i", "bi " + icon);
      i.setAttribute("aria-hidden", "true");
      button.append(i);
      return button;
    }

    function showError(node, message) {
      node.textContent = message;
      node.hidden = !message;
    }

    function countIn(categoryId) {
      return stickers.filter(function (sticker) { return sticker.categoryId === categoryId; }).length;
    }

    function nameTaken(name, exceptId) {
      const wanted = name.trim().toLowerCase();
      return categories.some(function (category) { return category.id !== exceptId && category.name.trim().toLowerCase() === wanted; });
    }

    /* ---------- Rendering ---------- */
    function renderBar() {
      const items = [{ id: "all", name: "كل الستيكرات", count: stickers.length }].concat(categories.map(function (category) {
        return { id: category.id, name: category.name, count: countIn(category.id), tools: true };
      }));

      bar.replaceChildren(...items.map(function (item) {
        const wrap = el("span", "sticker-cat" + (item.id === activeCategory ? " is-active" : ""));
        const pill = el("button", "summary-pill");
        pill.type = "button";
        pill.dataset.category = item.id;
        pill.setAttribute("aria-pressed", String(item.id === activeCategory));
        pill.append(el("b", null, String(item.count)), " " + item.name);
        wrap.append(pill);

        if (item.tools) {
          const tools = el("span", "sticker-cat-tools");
          tools.append(
            iconButton("bi-pencil-square", "تعديل التصنيف " + item.name, "rename", item.id),
            iconButton("bi-trash3", "حذف التصنيف " + item.name, "removeCategory", item.id)
          );
          wrap.append(tools);
        }
        return wrap;
      }));
    }

    function renderGrid() {
      const visible = stickers.filter(function (sticker) { return activeCategory === "all" || sticker.categoryId === activeCategory; });

      grid.replaceChildren(...visible.map(function (sticker) {
        const category = categories.find(function (item) { return item.id === sticker.categoryId; });
        const card = el("article", "sticker-card");
        const visual = el("div", "sticker-visual");
        const img = el("img");
        img.src = sticker.url;
        img.alt = sticker.name;
        visual.append(img);

        const info = el("div", "sticker-info");
        const title = el("h3", null, sticker.name);
        title.title = sticker.name;
        info.append(title, el("p", null, category ? category.name : ""));

        const actions = el("div", "sticker-actions");
        const edit = el("button", "edit-action");
        edit.type = "button";
        edit.dataset.editSticker = sticker.id;
        edit.setAttribute("aria-label", "تعديل " + sticker.name);
        const editIcon = el("i", "bi bi-pencil-square");
        editIcon.setAttribute("aria-hidden", "true");
        edit.append(editIcon, " تعديل");
        const remove = iconButton("bi-trash3", "حذف " + sticker.name, "removeSticker", sticker.id);
        remove.className = "delete-action";
        actions.append(edit, remove);

        card.append(visual, info, actions);
        return card;
      }));

      empty.hidden = visible.length > 0;
      if (visible.length === 0) {
        if (categories.length === 0) {
          emptyTitle.textContent = "ابدأ بإضافة تصنيف";
          emptyText.textContent = "أنشئ تصنيفًا أولًا (مثل: فلسطين)، ثم أضف إليه صور الستيكرات.";
        } else if (activeCategory === "all") {
          emptyTitle.textContent = "لا توجد ستيكرات بعد";
          emptyText.textContent = "اضغط «إضافة ستيكر» لرفع أول صورة.";
        } else {
          emptyTitle.textContent = "لا توجد ستيكرات في هذا التصنيف";
          emptyText.textContent = "أضف ستيكرًا وسيظهر هنا.";
        }
      }
    }

    function render() {
      if (activeCategory !== "all" && !categories.some(function (category) { return category.id === activeCategory; })) activeCategory = "all";
      renderBar();
      renderGrid();
    }

    /* ---------- Categories ---------- */
    function openCategoryDialog(category) {
      editingCategory = category || null;
      categoryTitle.textContent = category ? "تعديل التصنيف" : "إضافة تصنيف";
      categoryName.value = category ? category.name : "";
      showError(categoryError, "");
      categoryDialog.showModal();
      categoryName.focus();
    }

    // method="dialog" would close the dialog on every submit, so the form is handled by hand and closed only when valid.
    categoryForm.addEventListener("submit", function (event) {
      event.preventDefault();
      const name = categoryName.value.trim();
      if (!name) { showError(categoryError, "أدخل اسم التصنيف."); return; }
      if (nameTaken(name, editingCategory ? editingCategory.id : null)) { showError(categoryError, "يوجد تصنيف بهذا الاسم."); return; }

      if (editingCategory) {
        editingCategory.name = name;
        toast("تم تعديل التصنيف.");
      } else {
        const category = { id: "c" + nextId++, name: name };
        categories.push(category);
        activeCategory = category.id;
        toast("تمت إضافة التصنيف.");
      }
      categoryDialog.close();
      render();
    });

    document.getElementById("addCategoryButton").addEventListener("click", function () { openCategoryDialog(); });

    bar.addEventListener("click", function (event) {
      const rename = event.target.closest("[data-rename]");
      if (rename) {
        openCategoryDialog(categories.find(function (category) { return category.id === rename.dataset.rename; }));
        return;
      }

      const remove = event.target.closest("[data-remove-category]");
      if (remove) {
        const category = categories.find(function (item) { return item.id === remove.dataset.removeCategory; });
        const total = countIn(category.id);
        PalAlert.confirm({
          title: "حذف التصنيف",
          text: total > 0
            ? "سيتم حذف التصنيف «" + category.name + "» مع " + total + " ستيكر بداخله. هل أنت متأكد؟"
            : "هل تريد حذف التصنيف «" + category.name + "»؟",
          confirmText: "نعم، احذف",
          danger: true
        }).then(function (ok) {
          if (!ok) return;
          stickers.filter(function (sticker) { return sticker.categoryId === category.id; }).forEach(function (sticker) { URL.revokeObjectURL(sticker.url); });
          categories = categories.filter(function (item) { return item.id !== category.id; });
          stickers = stickers.filter(function (sticker) { return sticker.categoryId !== category.id; });
          toast("تم حذف التصنيف.");
          render();
        });
        return;
      }

      const pill = event.target.closest("[data-category]");
      if (pill) {
        activeCategory = pill.dataset.category;
        render();
      }
    });

    /* ---------- Stickers ---------- */
    function resetImageField() {
      if (pendingImage) URL.revokeObjectURL(pendingImage.url);
      pendingImage = null;
      stickerImage.value = "";
      stickerPreview.hidden = true;
      stickerPreview.removeAttribute("src");
      stickerUploadTitle.textContent = "اختر صورة للستيكر";
    }

    function openStickerDialog(sticker) {
      if (categories.length === 0) {
        toast("أضف تصنيفًا أولًا.");
        openCategoryDialog();
        return;
      }
      editingSticker = sticker || null;
      stickerForm.reset();
      resetImageField();
      showError(stickerError, "");
      stickerTitle.textContent = sticker ? "تعديل الستيكر" : "إضافة ستيكر";
      stickerSubmit.textContent = sticker ? "حفظ التعديلات" : "حفظ الستيكر";
      stickerCategory.replaceChildren(...categories.map(function (category) {
        const option = el("option", null, category.name);
        option.value = category.id;
        return option;
      }));

      if (sticker) {
        stickerName.value = sticker.name;
        stickerCategory.value = sticker.categoryId;
        stickerPreview.src = sticker.url;
        stickerPreview.hidden = false;
        stickerUploadTitle.textContent = "الصورة الحالية (اختر صورة لتغييرها)";
      } else if (activeCategory !== "all") {
        stickerCategory.value = activeCategory;
      }
      stickerDialog.showModal();
    }

    stickerImage.addEventListener("change", function () {
      const file = stickerImage.files[0];
      if (!file) return;
      if (IMAGE_TYPES.indexOf(file.type) === -1) { resetImageField(); showError(stickerError, "الصيغة غير مدعومة. استخدم PNG أو JPG أو WEBP أو SVG."); return; }
      if (file.size > MAX_IMAGE_BYTES) { resetImageField(); showError(stickerError, "حجم الصورة أكبر من 2MB."); return; }

      showError(stickerError, "");
      if (pendingImage) URL.revokeObjectURL(pendingImage.url);
      pendingImage = { file: file, url: URL.createObjectURL(file) };
      stickerPreview.src = pendingImage.url;
      stickerPreview.hidden = false;
      stickerUploadTitle.textContent = file.name;
      if (!stickerName.value.trim()) stickerName.value = file.name.replace(/\.[^.]+$/, "");
    });

    stickerForm.addEventListener("submit", function (event) {
      event.preventDefault();
      const name = stickerName.value.trim();
      let problem = "";
      if (!pendingImage && !editingSticker) problem = "اختر صورة الستيكر.";
      else if (!name) problem = "أدخل اسم الستيكر.";
      else if (!stickerCategory.value) problem = "اختر التصنيف.";

      if (problem) {
        showError(stickerError, problem);
        return;
      }

      if (editingSticker) {
        editingSticker.name = name;
        editingSticker.categoryId = stickerCategory.value;
        if (pendingImage) {
          URL.revokeObjectURL(editingSticker.url);
          editingSticker.url = pendingImage.url;
          editingSticker.file = pendingImage.file;
          pendingImage = null; // the preview URL now belongs to the sticker
        }
        toast("تم تعديل الستيكر.");
      } else {
        stickers.push({ id: "s" + nextId++, name: name, categoryId: stickerCategory.value, url: pendingImage.url, file: pendingImage.file });
        pendingImage = null; // the preview URL now belongs to the sticker
        toast("تمت إضافة الستيكر.");
      }
      activeCategory = stickerCategory.value;
      stickerDialog.close();
      render();
    });

    document.getElementById("addStickerButton").addEventListener("click", function () { openStickerDialog(); });

    grid.addEventListener("click", function (event) {
      const edit = event.target.closest("[data-edit-sticker]");
      if (edit) {
        openStickerDialog(stickers.find(function (item) { return item.id === edit.dataset.editSticker; }));
        return;
      }

      const remove = event.target.closest("[data-remove-sticker]");
      if (!remove) return;
      const sticker = stickers.find(function (item) { return item.id === remove.dataset.removeSticker; });
      if (!sticker) return;
      PalAlert.confirm({
        title: "حذف الستيكر",
        text: "هل تريد حذف الستيكر «" + sticker.name + "»؟",
        confirmText: "نعم، احذف",
        danger: true
      }).then(function (ok) {
        if (!ok) return;
        URL.revokeObjectURL(sticker.url);
        stickers = stickers.filter(function (item) { return item.id !== sticker.id; });
        toast("تم حذف الستيكر.");
        render();
      });
    });

    /* ---------- Dialog closing ---------- */
    [categoryDialog, stickerDialog].forEach(function (dialog) {
      dialog.querySelectorAll("[data-dialog-close]").forEach(function (button) {
        button.addEventListener("click", function () { dialog.close(); });
      });
      dialog.addEventListener("click", function (event) { if (event.target === dialog) dialog.close(); });
    });
    stickerDialog.addEventListener("close", function () { if (pendingImage) resetImageField(); });

    render();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(window, document);
