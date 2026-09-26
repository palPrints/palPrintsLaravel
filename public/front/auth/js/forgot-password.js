(function () {
  "use strict";

  const form = document.getElementById("recoveryForm");
  const email = document.getElementById("recoveryEmail");
  const error = document.getElementById("emailError");
  const successState = document.getElementById("successState");

  if (!form || !email || !error || !successState) return;

  function validateEmail() {
    const value = email.value.trim();
    let message = "";
    if (!value) message = "يرجى إدخال البريد الإلكتروني.";
    else if (!email.validity.valid) message = "يرجى إدخال بريد إلكتروني صحيح.";

    email.closest(".form-field").classList.toggle("is-invalid", Boolean(message));
    email.setAttribute("aria-invalid", String(Boolean(message)));
    error.textContent = message;
    return !message;
  }

  email.addEventListener("blur", validateEmail);
  email.addEventListener("input", function () {
    if (email.getAttribute("aria-invalid") === "true") validateEmail();
  });

  let submitting = false;

  form.addEventListener("submit", function (event) {
    if (submitting || !validateEmail()) {
      event.preventDefault();
      if (!submitting) email.focus();
      return;
    }

    /* Valid: let the browser post the form to Laravel. */
    submitting = true;
    const submitButton = form.querySelector("[type=submit]");
    if (submitButton) submitButton.disabled = true;
  });

  window.addEventListener("pageshow", function () {
    submitting = false;
    const submitButton = form.querySelector("[type=submit]");
    if (submitButton) submitButton.disabled = false;
  });
})();
