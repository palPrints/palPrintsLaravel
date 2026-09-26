(function () {
  "use strict";

  const form = document.getElementById("resetPasswordForm");
  const password = document.getElementById("newPassword");
  const confirmation = document.getElementById("confirmPassword");

  if (!form || !password || !confirmation) return;

  function setError(input, message) {
    const field = input.closest(".form-field");
    const error = field.querySelector(".field-error");
    field.classList.toggle("is-invalid", Boolean(message));
    input.setAttribute("aria-invalid", String(Boolean(message)));
    error.textContent = message;
    return !message;
  }

  function validatePassword() {
    const value = password.value;
    if (!value) return setError(password, "يرجى إدخال كلمة المرور الجديدة.");
    if (value.length < 8) return setError(password, "كلمة المرور يجب أن تكون 8 أحرف على الأقل.");
    return setError(password, "");
  }

  function validateConfirmation() {
    if (!confirmation.value) return setError(confirmation, "يرجى تأكيد كلمة المرور.");
    if (confirmation.value !== password.value) return setError(confirmation, "كلمتا المرور غير متطابقتين.");
    return setError(confirmation, "");
  }

  [password, confirmation].forEach(function (input) {
    input.addEventListener("blur", input === password ? validatePassword : validateConfirmation);
    input.addEventListener("input", function () {
      if (input.getAttribute("aria-invalid") === "true") {
        (input === password ? validatePassword : validateConfirmation)();
      }
      if (input === password && confirmation.value) validateConfirmation();
    });
  });

  document.querySelectorAll(".password-toggle").forEach(function (button) {
    button.addEventListener("click", function () {
      const input = document.getElementById(button.dataset.target);
      const shouldShow = input.type === "password";
      input.type = shouldShow ? "text" : "password";
      button.setAttribute("aria-pressed", String(shouldShow));
      button.setAttribute("aria-label", shouldShow ? "إخفاء كلمة المرور" : "إظهار كلمة المرور");
      button.querySelector("i").className = shouldShow ? "bi bi-eye-slash" : "bi bi-eye";
      input.focus();
    });
  });

  let submitting = false;

  form.addEventListener("submit", function (event) {
    if (submitting) {
      event.preventDefault();
      return;
    }

    const validPassword = validatePassword();
    const validConfirmation = validateConfirmation();
    if (!validPassword || !validConfirmation) {
      event.preventDefault();
      const firstInvalid = form.querySelector('[aria-invalid="true"]');
      if (firstInvalid) firstInvalid.focus();
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
