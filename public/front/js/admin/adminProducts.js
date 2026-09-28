/* Admin products page: search, filters, add / edit dialog with image upload, pause and delete. */
(function (window, document) {
  "use strict";

  function initialize() {
    const searchInput = document.getElementById("dashboardSearch");
    const productsGrid = document.getElementById("productsGrid");
    const emptyState = document.getElementById("emptyState");
    const dialog = document.getElementById("productDialog");
    const form = document.getElementById("productForm");
    const dialogTitle = document.getElementById("productDialogTitle");
    const nameInput = document.getElementById("productNameInput");
    const codeInput = document.getElementById("productCodeInput");
    const categoryInput = document.getElementById("productCategoryInput");
    const descriptionInput = document.getElementById("productDescriptionInput");
    const imageInput = document.getElementById("productImageInput");
    const imageUpload = document.getElementById("productImageUpload");
    const imagePreview = document.getElementById("productImagePreviewImg");
    const imageUploadTitle = document.getElementById("productImageUploadTitle");
    const imageError = document.getElementById("productImageError");
    const csrf = document.querySelector('meta[name="csrf-token"]');
    const deleteDialog = document.getElementById("deleteDialog");
    const deleteName = document.getElementById("deleteDialogName");
    const deleteCancel = document.getElementById("deleteCancel");
    const deleteConfirm = document.getElementById("deleteConfirm");
    let pendingDelete = null;
    let editingCard = null;
    let activeFilter = "all";

    function toast(message) {
      if (window.PalAdmin) window.PalAdmin.toast(message);
    }

    function normalize(value) {
      return String(value || "").trim().toLocaleLowerCase("ar")
        .replace(/[أإآ]/g, "ا").replace(/ة/g, "ه").replace(/ى/g, "ي");
    }

    function request(url, method, body) {
      const headers = { "Accept": "application/json", "X-CSRF-TOKEN": csrf ? csrf.content : "" };
      if (body && !(body instanceof FormData)) {
        headers["Content-Type"] = "application/json";
        body = JSON.stringify(body);
      }

      return window.fetch(url, { method: method, headers: headers, body: body })
        .then(function (response) {
          return response.json().then(function (data) { return { ok: response.ok, data: data }; });
        })
        .then(function (result) {
          if (!result.ok) {
            const errors = result.data.errors ? Object.values(result.data.errors)[0] : null;
            throw new Error((errors && errors[0]) || result.data.message || "تعذّر تنفيذ الإجراء.");
          }
          return result.data;
        });
    }

    /* ---------- Image field ---------- */
    function resetImageField() {
      imageInput.value = "";
      imagePreview.removeAttribute("src");
      imagePreview.hidden = true;
      imagePreview.previousElementSibling.hidden = false;
      imageUploadTitle.textContent = "اختر صورة للمنتج";
      imageError.hidden = true;
      imageError.textContent = "";
      imageUpload.classList.remove("has-error", "is-dragging");
    }

    function showImageError(message) {
      imageError.textContent = message;
      imageError.hidden = false;
      imageUpload.classList.add("has-error");
    }

    function previewImage(source, label) {
      imagePreview.src = source;
      imagePreview.hidden = false;
      imagePreview.previousElementSibling.hidden = true;
      imageUploadTitle.textContent = label;
      imageError.hidden = true;
      imageUpload.classList.remove("has-error");
    }

    function useImageFile(file) {
      if (!file) return;

      if (!["image/png", "image/jpeg", "image/webp"].includes(file.type)) {
        imageInput.value = "";
        showImageError("اختر صورة بصيغة PNG أو JPG أو WEBP.");
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        imageInput.value = "";
        showImageError("حجم الصورة أكبر من 5MB. اختر صورة أصغر.");
        return;
      }

      const reader = new FileReader();
      reader.addEventListener("load", function () { previewImage(reader.result, file.name); });
      reader.addEventListener("error", function () { showImageError("تعذّر قراءة الصورة. حاول اختيار ملف آخر."); });
      reader.readAsDataURL(file);
    }

    imageInput.addEventListener("change", function () { useImageFile(imageInput.files[0]); });

    ["dragenter", "dragover"].forEach(function (name) {
      imageUpload.addEventListener(name, function (event) {
        event.preventDefault();
        imageUpload.classList.add("is-dragging");
      });
    });

    ["dragleave", "drop"].forEach(function (name) {
      imageUpload.addEventListener(name, function (event) {
        event.preventDefault();
        imageUpload.classList.remove("is-dragging");
      });
    });

    imageUpload.addEventListener("drop", function (event) {
      const file = event.dataTransfer.files[0];
      if (!file) return;
      const transfer = new DataTransfer();
      transfer.items.add(file);
      imageInput.files = transfer.files;
      useImageFile(file);
    });

    /* ---------- Summary, search and filters ---------- */
    function updateSummary() {
      const cards = Array.from(productsGrid.querySelectorAll(".product-card"));
      const active = cards.filter(function (card) { return card.dataset.status === "active"; }).length;
      document.getElementById("totalProducts").textContent = cards.length;
      document.getElementById("activeProducts").textContent = active;
      document.getElementById("pausedProducts").textContent = cards.length - active;
    }

    function filterProducts() {
      const query = normalize(searchInput ? searchInput.value : "");
      let visible = 0;

      productsGrid.querySelectorAll(".product-card").forEach(function (card) {
        const matches = (!query || normalize(card.dataset.productName + " " + card.dataset.productId).includes(query))
          && (activeFilter === "all" || card.dataset.status === activeFilter);
        card.hidden = !matches;
        if (matches) visible += 1;
      });

      const noProducts = productsGrid.querySelectorAll(".product-card").length === 0;
      document.getElementById("emptyStateTitle").textContent = noProducts ? "لا توجد منتجات بعد" : "لا توجد نتائج مطابقة";
      document.getElementById("emptyStateText").textContent = noProducts ? "أضف أول منتج ليظهر للعملاء." : "جرّب البحث باسم منتج أو رقمه.";
      emptyState.hidden = visible !== 0;
    }

    if (searchInput) searchInput.addEventListener("input", filterProducts);

    document.querySelectorAll("[data-product-filter]").forEach(function (button) {
      button.addEventListener("click", function () {
        activeFilter = button.dataset.productFilter;
        document.querySelectorAll("[data-product-filter]").forEach(function (item) {
          item.setAttribute("aria-pressed", String(item === button));
        });
        filterProducts();
      });
    });

    /* ---------- Add / edit dialog ---------- */
    function openDialog(card) {
      editingCard = card || null;
      form.reset();
      resetImageField();
      dialogTitle.textContent = card ? "تعديل المنتج" : "إضافة منتج";

      if (card) {
        nameInput.value = card.dataset.productName;
        codeInput.value = card.dataset.productId;
        categoryInput.value = card.dataset.categoryId || "";
        descriptionInput.value = card.dataset.description || "";
        if (card.dataset.image) previewImage(card.dataset.image, "الصورة الحالية (اختر صورة لتغييرها)");
      }

      dialog.showModal();
      nameInput.focus();
    }

    document.getElementById("addProductButton").addEventListener("click", function () { openDialog(); });

    dialog.querySelectorAll("[data-dialog-close]").forEach(function (button) {
      button.addEventListener("click", function () { dialog.close(); });
    });
    dialog.addEventListener("click", function (event) {
      if (event.target === dialog) dialog.close();
    });

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      if (!form.reportValidity()) return;

      if (!editingCard && !imageInput.files[0]) {
        showImageError("صورة المنتج مطلوبة.");
        return;
      }

      const data = new FormData(form);
      const submit = form.querySelector('[type="submit"]');
      let url = form.dataset.storeUrl;

      if (editingCard) {
        url = editingCard.dataset.updateUrl;
        data.append("_method", "PATCH");
      }

      submit.disabled = true;
      request(url, "POST", data)
        .then(function (result) {
          dialog.close();
          toast(result.message);
          window.setTimeout(function () { window.location.reload(); }, 700);
        })
        .catch(function (error) {
          toast(error.message);
        })
        .finally(function () {
          submit.disabled = false;
        });
    });

    /* ---------- Card actions ---------- */
    productsGrid.addEventListener("click", function (event) {
      const button = event.target.closest("button[data-action]");
      if (!button) return;

      const card = button.closest(".product-card");
      const name = card.dataset.productName;

      if (button.dataset.action === "edit") {
        openDialog(card);
        return;
      }

      if (button.dataset.action === "toggle") {
        request(card.dataset.toggleUrl, "POST", { _method: "PATCH" })
          .then(function (result) {
            card.dataset.status = result.active ? "active" : "paused";
            card.classList.toggle("is-paused", !result.active);
            button.innerHTML = result.active
              ? '<i class="bi bi-pause-circle"></i>إيقاف'
              : '<i class="bi bi-play-circle"></i>تفعيل';
            button.className = result.active ? "danger-action" : "success-action";

            const label = card.querySelector(".paused-label");
            if (!result.active && !label) {
              card.querySelector(".product-visual").insertAdjacentHTML("afterbegin", '<span class="paused-label">موقوف</span>');
            }
            if (result.active && label) label.remove();

            updateSummary();
            filterProducts();
            toast(result.message);
          })
          .catch(function (error) { toast(error.message); });
        return;
      }

      if (button.dataset.action === "delete") {
        deleteName.textContent = name;
        pendingDelete = card;
        deleteDialog.showModal();
      }
    });

    deleteCancel.addEventListener("click", function () { deleteDialog.close(); });
    deleteDialog.addEventListener("click", function (event) {
      if (event.target === deleteDialog) deleteDialog.close();
    });
    deleteDialog.addEventListener("close", function () { pendingDelete = null; });
    deleteConfirm.addEventListener("click", function () {
      const card = pendingDelete;
      if (!card) return;
      deleteConfirm.disabled = true;
      request(card.dataset.deleteUrl, "POST", { _method: "DELETE" })
        .then(function (result) {
          card.remove();
          updateSummary();
          filterProducts();
          toast(result.message);
        })
        .catch(function (error) { toast(error.message); })
        .finally(function () {
          deleteConfirm.disabled = false;
          deleteDialog.close();
        });
    });

    updateSummary();
    filterProducts();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(window, document);
