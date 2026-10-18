"use strict";

/*
 * Email / phone channels of the help centre. On a phone the links keep their job (open the mail app, start the call).
 * On a computer neither does anything useful, so the button copies the address or number instead.
 */
document.addEventListener("DOMContentLoaded", () => {
  const isPhone = window.matchMedia("(hover: none) and (pointer: coarse)").matches;
  if (isPhone) return;

  function copy(text) {
    if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
    return new Promise((resolve, reject) => {
      const area = document.createElement("textarea");
      area.value = text;
      area.setAttribute("readonly", "");
      area.style.cssText = "position:fixed;opacity:0;top:0;left:0";
      document.body.append(area);
      area.select();
      const ok = document.execCommand("copy");
      area.remove();
      ok ? resolve() : reject(new Error("copy failed"));
    });
  }

  document.querySelectorAll("a[data-copy]").forEach((link) => {
    const label = link.querySelector(":scope > b");
    if (label) label.textContent = link.dataset.copyLabel || "نسخ";

    link.addEventListener("click", (event) => {
      event.preventDefault();
      copy(link.dataset.copy)
        .then(() => {
          if (!label) return;
          label.textContent = "تم النسخ ✓";
          window.setTimeout(() => { label.textContent = link.dataset.copyLabel || "نسخ"; }, 1800);
        })
        .catch(() => { if (label) label.textContent = link.dataset.copy; });
    });
  });
});
