"use strict";

/*
 * The new-ticket form is hidden until it is asked for: by any [data-new-ticket] button on the page, or by arriving
 * with #newTicket in the address (the "new ticket" button of a ticket's chat page). It closes with its X button.
 * The form checks itself here (light messages under the fields) instead of the browser's own pop-up bubbles.
 */
document.addEventListener("DOMContentLoaded", () => {
  const panel = document.getElementById("newTicket");
  if (!panel) return;
  const form = panel.querySelector("form");

  function open() {
    panel.hidden = false;
    panel.scrollIntoView({ behavior: "smooth", block: "start" });
    window.setTimeout(() => panel.querySelector("select, input, textarea")?.focus({ preventScroll: true }), 350);
  }

  function close() {
    panel.hidden = true;
    clearErrors();
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  document.querySelectorAll("[data-new-ticket]").forEach((trigger) => {
    trigger.addEventListener("click", (event) => { event.preventDefault(); open(); });
  });
  panel.querySelector("[data-close-new-ticket]")?.addEventListener("click", close);
  if (window.location.hash === "#newTicket") open();

  if (!form) return;
  form.noValidate = true;

  function wrapOf(field) { return field.closest("label") || field.parentElement; }

  function clearError(field) {
    const wrap = wrapOf(field);
    wrap.classList.remove("has-error");
    wrap.querySelector(":scope > .pp-field-error")?.remove();
  }

  function clearErrors() { form.querySelectorAll("input, select, textarea").forEach(clearError); }

  function showError(field, message) {
    clearError(field);
    const wrap = wrapOf(field);
    wrap.classList.add("has-error");
    const note = document.createElement("small");
    note.className = "pp-field-error";
    note.innerHTML = '<i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>';
    note.append(document.createTextNode(message));
    wrap.append(note);
  }

  function problem(field) {
    const value = field.value.trim();
    if (field.required && !value) return field.tagName === "SELECT" ? "اختر نوع المشكلة." : "هذا الحقل مطلوب.";
    if (field.minLength > 0 && value && value.length < field.minLength) return "اكتب " + field.minLength + " أحرف على الأقل.";
    return "";
  }

  // Runs before the page's own submit handler; an invalid form stops here.
  form.addEventListener("submit", (event) => {
    let first = null;
    form.querySelectorAll("input, select, textarea").forEach((field) => {
      const message = problem(field);
      if (message) { showError(field, message); first = first || field; } else { clearError(field); }
    });
    if (first) {
      event.preventDefault();
      event.stopImmediatePropagation();
      first.focus();
    }
  });

  form.addEventListener("input", (event) => { if (event.target.matches("input, select, textarea")) clearError(event.target); });
  form.addEventListener("change", (event) => { if (event.target.matches("select")) clearError(event.target); });
  form.addEventListener("reset", clearErrors);
});
