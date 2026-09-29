"use strict";

document.addEventListener("DOMContentLoaded", () => {
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

  document.getElementById("startChatButton")?.addEventListener("click", () => {
    showToast("سيتم ربطك بأحد موظفي الدعم الآن.");
  });

  const fileInput = document.querySelector(".support-upload input");
  const attachmentName = document.getElementById("attachmentName");
  fileInput?.addEventListener("change", () => {
    attachmentName.textContent = fileInput.files[0] ? fileInput.files[0].name : "اختيار ملف";
  });

  const form = document.getElementById("supportTicketForm");
  form?.addEventListener("reset", () => {
    window.setTimeout(() => { attachmentName.textContent = "اختيار ملف"; }, 0);
  });
  form?.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    showToast("تم إرسال طلب الدعم برقم #SUP-1043.");
    form.reset();
  });
});
