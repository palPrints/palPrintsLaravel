"use strict";

/* Print provider settings and support pages. */
document.addEventListener("DOMContentLoaded", function () {
  const settings = document.getElementById("accountSettings");
  const toast = document.getElementById("dashboardToast");
  let toastTimer = 0;

  function showToast(message) {
    if (!toast) return;
    window.clearTimeout(toastTimer);
    toast.textContent = message;
    toast.classList.add("is-visible");
    toastTimer = window.setTimeout(function () { toast.classList.remove("is-visible"); }, 2600);
  }

  function readStorage(key) {
    try { return window.localStorage.getItem(key); } catch (error) { return null; }
  }

  function writeStorage(key, value) {
    try { window.localStorage.setItem(key, value); } catch (error) { /* storage unavailable */ }
  }

  /* Notification switches are per-device preferences. */
  document.querySelectorAll("[data-preference]").forEach(function (input) {
    const key = "palprints-preference-" + input.dataset.preference;
    const stored = readStorage(key);

    if (stored !== null) input.checked = stored === "true";

    input.addEventListener("change", function () {
      writeStorage(key, String(input.checked));
      showToast((input.dataset.preferenceLabel || "") + " — " + (input.checked ? "تم التفعيل" : "تم الإيقاف"));
    });
  });

  /* Dark mode is shared with the other account areas through the same storage key. */
  const themeToggle = document.getElementById("settingsThemeToggle");

  if (themeToggle) {
    themeToggle.checked = document.documentElement.getAttribute("data-bs-theme") === "dark";
    themeToggle.addEventListener("change", function () {
      const theme = themeToggle.checked ? "dark" : "light";
      document.documentElement.setAttribute("data-bs-theme", theme);
      writeStorage("palprints-theme", theme);
    });
  }

  const chatButton = document.getElementById("startChatButton");

  if (chatButton) {
    chatButton.addEventListener("click", function () { showToast("سيتم ربطك بأحد موظفي الدعم الآن."); });
  }

  if (settings) {
    const tabs = Array.from(settings.querySelectorAll("[data-acct-tab]"));
    const panels = Array.from(settings.querySelectorAll("[data-acct-panel]"));

    function showTab(target) {
      tabs.forEach(function (tab) {
        const active = tab.dataset.acctTab === target;
        tab.classList.toggle("active", active);
        tab.setAttribute("aria-selected", String(active));
      });

      panels.forEach(function (panel) {
        const active = panel.dataset.acctPanel === target;
        panel.hidden = !active;
        panel.classList.toggle("active", active);
      });
    }

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () { showTab(tab.dataset.acctTab); });
    });

    if (settings.dataset.initialTab) showTab(settings.dataset.initialTab);

    /* The server validates the password confirmation as well. */
    const securityForm = document.getElementById("securityForm");

    if (securityForm) {
      securityForm.addEventListener("submit", function (event) {
        const confirmation = securityForm.elements.password_confirmation;
        const matches = securityForm.elements.password.value === confirmation.value;

        confirmation.closest(".acct-field").classList.toggle("has-error", !matches);

        if (!securityForm.reportValidity() || !matches) {
          event.preventDefault();
          if (!matches) confirmation.focus();
        }
      });
    }

    /* Delete account: the server still requires the current password. */
    const deleteDialog = document.getElementById("deleteAccountDialog");
    const deleteButton = document.getElementById("deleteAccountButton");
    const deleteForm = document.getElementById("deleteAccountForm");
    const deleteConfirmation = document.getElementById("deleteConfirmation");

    if (deleteDialog && deleteButton && deleteForm) {
      deleteButton.addEventListener("click", function () {
        deleteDialog.showModal();
        window.setTimeout(function () { deleteConfirmation.focus(); }, 0);
      });

      deleteDialog.querySelectorAll("[data-delete-close]").forEach(function (button) {
        button.addEventListener("click", function () { deleteDialog.close(); });
      });

      deleteDialog.addEventListener("click", function (event) {
        if (event.target === deleteDialog) deleteDialog.close();
      });

      deleteForm.addEventListener("submit", function (event) {
        if (deleteConfirmation.value.trim() !== "حذف حسابي") {
          event.preventDefault();
          deleteConfirmation.setCustomValidity("اكتب «حذف حسابي» للتأكيد.");
          deleteConfirmation.reportValidity();
          deleteConfirmation.setCustomValidity("");
        }
      });

      if (deleteDialog.hasAttribute("data-open-on-load")) deleteDialog.showModal();
    }
  }

  const fileInput = document.getElementById("supportAttachmentInput");
  const fileLabel = document.getElementById("attachmentName");

  if (fileInput && fileLabel) {
    fileInput.addEventListener("change", function () {
      fileLabel.textContent = fileInput.files[0] ? fileInput.files[0].name : "اختيار ملف";
    });
  }
});
