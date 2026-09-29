"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const tabs = Array.from(document.querySelectorAll("[data-settings-tab]"));
  const panels = Array.from(document.querySelectorAll("[data-settings-panel]"));

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const target = tab.dataset.settingsTab;
      tabs.forEach((item) => {
        const active = item === tab;
        item.classList.toggle("active", active);
        item.setAttribute("aria-selected", String(active));
      });
      panels.forEach((panel) => {
        const active = panel.dataset.settingsPanel === target;
        panel.hidden = !active;
        panel.classList.toggle("active", active);
      });
    });
  });

  const toast = document.getElementById("toast");
  function showToast(message) {
    if (!toast) return;
    const span = toast.querySelector("span");
    if (span) span.textContent = message;
    toast.hidden = false;
    requestAnimationFrame(() => toast.classList.add("show"));
    window.setTimeout(() => {
      toast.classList.remove("show");
      window.setTimeout(() => { toast.hidden = true; }, 300);
    }, 2600);
  }

  const saveNotifications = document.getElementById("saveNotificationSettings");
  saveNotifications?.addEventListener("click", () => showToast("تم حفظ تفضيلات الإشعارات."));

  const downloadData = document.getElementById("downloadDataButton");
  downloadData?.addEventListener("click", () => showToast("تم إرسال طلب تجهيز نسخة بياناتك."));

  const darkModeToggle = document.getElementById("darkModeToggle");
  if (darkModeToggle) {
    const stored = localStorage.getItem("palprints-pp-dark") === "1";
    darkModeToggle.checked = stored;
    document.body.classList.toggle("pp-dark-mode", stored);
    darkModeToggle.addEventListener("change", () => {
      document.body.classList.toggle("pp-dark-mode", darkModeToggle.checked);
      localStorage.setItem("palprints-pp-dark", darkModeToggle.checked ? "1" : "0");
    });
  }

  const reduceMotionToggle = document.getElementById("reduceMotionToggle");
  if (reduceMotionToggle) {
    const stored = localStorage.getItem("palprints-reduce-motion") === "1";
    reduceMotionToggle.checked = stored;
    document.body.classList.toggle("pp-reduce-motion", stored);
    reduceMotionToggle.addEventListener("change", () => {
      document.body.classList.toggle("pp-reduce-motion", reduceMotionToggle.checked);
      localStorage.setItem("palprints-reduce-motion", reduceMotionToggle.checked ? "1" : "0");
    });
  }

  const deleteDialog = document.getElementById("deleteAccountDialog");
  const deleteButton = document.getElementById("deleteAccountButton");
  if (deleteDialog && deleteButton) {
    deleteButton.addEventListener("click", () => {
      deleteDialog.showModal();
      window.setTimeout(() => document.getElementById("delete_password")?.focus(), 0);
    });
    deleteDialog.querySelectorAll("[data-delete-close]").forEach((button) => {
      button.addEventListener("click", () => deleteDialog.close());
    });
    deleteDialog.addEventListener("click", (event) => {
      if (event.target === deleteDialog) deleteDialog.close();
    });
  }
});
