"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const Core = window.PalProfile;

  if (!Core) return;

  Core.init({
    dictionary: {
      ar: {
        openSidebar: "فتح القائمة الجانبية",
        closeSidebar: "إغلاق القائمة الجانبية",
        logoutConfirm: "هل تريد تسجيل الخروج من حسابك؟"
      },
    }
  });

  const main = document.getElementById("designerMain");
  const tabs = Array.from(document.querySelectorAll("[data-settings-tab]"));
  const panels = Array.from(document.querySelectorAll("[data-settings-panel]"));

  function showTab(target) {
    tabs.forEach(function (tab) {
      const active = tab.dataset.settingsTab === target;
      tab.classList.toggle("active", active);
      tab.setAttribute("aria-selected", String(active));
    });

    panels.forEach(function (panel) {
      const active = panel.dataset.settingsPanel === target;
      panel.hidden = !active;
      panel.classList.toggle("active", active);
    });
  }

  tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      showTab(tab.dataset.settingsTab);
    });
  });

  if (main && main.dataset.initialTab) {
    showTab(main.dataset.initialTab);
  }

  /* Password confirmation is also validated on the server. */
  const securityForm = document.getElementById("securitySettingsForm");

  if (securityForm) {
    securityForm.addEventListener("submit", function (event) {
      const password = securityForm.elements.password;
      const confirmation = securityForm.elements.password_confirmation;
      const matches = password.value === confirmation.value;

      confirmation.closest(".profile-field").classList.toggle("has-error", !matches);

      if (!securityForm.reportValidity() || !matches) {
        event.preventDefault();
        if (!matches) confirmation.focus();
      }
    });
  }

  /* Theme and motion are per-device preferences (the site is Arabic only). */

  const themeToggle = document.getElementById("settingsThemeToggle");
  themeToggle.checked = document.documentElement.getAttribute("data-bs-theme") === "dark";
  themeToggle.addEventListener("change", function () {
    Core.applyTheme(themeToggle.checked ? "dark" : "light");
  });

  /* Delete account. The server still requires the current password. */
  const deleteDialog = document.getElementById("deleteAccountDialog");
  const deleteButton = document.getElementById("deleteAccountButton");
  const deleteForm = document.getElementById("deleteAccountForm");
  const deleteConfirmation = document.getElementById("deleteConfirmation");

  deleteButton.addEventListener("click", function () {
    deleteDialog.showModal();
    window.setTimeout(function () { deleteConfirmation.focus(); }, 0);
  });

  document.querySelectorAll("[data-delete-close]").forEach(function (button) {
    button.addEventListener("click", function () { deleteDialog.close(); });
  });

  deleteDialog.addEventListener("click", function (event) {
    if (event.target === deleteDialog) deleteDialog.close();
  });

  deleteForm.addEventListener("submit", function (event) {
    if (deleteConfirmation.value.trim() !== "حذف حسابي") {
      event.preventDefault();
      Core.toast("اكتب «حذف حسابي» للتأكيد.", "error");
    }
  });

  if (deleteDialog.hasAttribute("data-open-on-load")) {
    deleteDialog.showModal();
  }

  const flashMessages = {
    "account-updated": "تم حفظ معلومات الحساب بنجاح.",
    "password-updated": "تم تحديث كلمة المرور بنجاح."
  };

  if (window.designerSettingsFlash && flashMessages[window.designerSettingsFlash]) {
    Core.toast(flashMessages[window.designerSettingsFlash], "success");
  }
});
