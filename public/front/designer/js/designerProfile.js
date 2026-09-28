"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const Core = window.PalProfile;
  const form = document.getElementById("designerProfileForm");

  if (!form) {
    return;
  }

  const view = document.getElementById("designerProfileView");
  const edit = document.getElementById("designerProfileEdit");
  const actions = document.getElementById("designerFormActions");
  const editButton = document.getElementById("editDesignerProfile");
  const cancelButton = document.getElementById("cancelDesignerEdit");
  const description = document.getElementById("designerPageDescription");
  const portfolioField = document.getElementById("profilePortfolio");
  const portfolioClearButton = document.getElementById("portfolioClearButton");

  let submitting = false;

  function setEditing(isEditing) {
    view.hidden = isEditing;
    edit.hidden = !isEditing;
    actions.hidden = !isEditing;
    editButton.hidden = isEditing;
    editButton.setAttribute("aria-expanded", String(isEditing));

    if (description) {
      description.textContent = isEditing
        ? description.dataset.editText
        : description.dataset.viewText;
    }

    if (isEditing) {
      const first = document.getElementById("profileFullName");
      if (first) first.focus();
    }
  }

  editButton.addEventListener("click", function () {
    setEditing(true);
  });

  cancelButton.addEventListener("click", function () {
    /* Restores the values rendered by the server. */
    form.reset();
    form.querySelectorAll("[aria-invalid]").forEach(function (field) {
      field.removeAttribute("aria-invalid");
    });
    setEditing(false);
  });

  if (portfolioClearButton && portfolioField) {
    portfolioClearButton.addEventListener("click", function () {
      portfolioField.value = "";
      portfolioField.focus();
    });
  }

  form.addEventListener("submit", function (event) {
    if (submitting) {
      event.preventDefault();
      return;
    }

    if (!form.reportValidity()) {
      event.preventDefault();

      const invalid = form.querySelector(":invalid");
      if (Core && invalid) {
        Core.toast(
          invalid.validationMessage || "راجع الحقول المطلوبة قبل الحفظ.",
          "error"
        );
      }
      return;
    }

    submitting = true;

    const submitButton = form.querySelector("[type=submit]");
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.setAttribute("aria-busy", "true");
    }
  });

  /* Back/forward cache restores the page with a disabled button. */
  window.addEventListener("pageshow", function () {
    submitting = false;

    const submitButton = form.querySelector("[type=submit]");
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.removeAttribute("aria-busy");
    }
  });

  if (window.designerProfileFlash === "updated" && Core) {
    Core.toast("تم حفظ بيانات الملف الشخصي بنجاح.", "success");
  }
});
