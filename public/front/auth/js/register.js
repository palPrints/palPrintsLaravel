(function () {
  "use strict";

  const form = document.getElementById("registerForm");
  if (!form) return;

  const roles = {
    customer: { label: "عميل" },
    designer: { label: "مصمم" },
    print_provider: { label: "مطبعة" }
  };
  const roleInputs = Array.from(form.querySelectorAll('input[name="account_type"]'));
  const submitLabel = form.querySelector("[type=submit] span");
  const formStatus = document.getElementById("formStatus");
  const terms = document.getElementById("terms");
  const termsError = document.getElementById("termsError");
  const password = document.getElementById("password");
  const passwordConfirm = document.getElementById("passwordConfirm");
  let submitting = false;

  function setRole(role) {
    submitLabel.textContent = "إنشاء حساب " + roles[role].label;
    formStatus.textContent = "";
  }

  function getFieldWrapper(field) {
    return field.closest(".form-field");
  }

  function clearFieldError(field) {
    const wrapper = getFieldWrapper(field);
    if (!wrapper) return;
    wrapper.classList.remove("is-invalid");
    field.removeAttribute("aria-invalid");
    const error = wrapper.querySelector(".field-error");
    if (error) error.textContent = "";
  }

  function showFieldError(field, message) {
    const wrapper = getFieldWrapper(field);
    if (!wrapper) return false;
    wrapper.classList.add("is-invalid");
    field.setAttribute("aria-invalid", "true");
    const error = wrapper.querySelector(".field-error");
    if (error) error.textContent = message;
    return false;
  }

  function validateField(field) {
    if (field.disabled || field.type === "radio" || field.type === "checkbox") return true;
    const value = field.value.trim();
    if (field.required && !value) return showFieldError(field, "هذا الحقل مطلوب.");
    if (field.type === "email" && value && !field.validity.valid) return showFieldError(field, "أدخل بريدًا إلكترونيًا صحيحًا.");
    if (field === password && value.length < 8) return showFieldError(field, "كلمة المرور يجب أن تكون 8 أحرف على الأقل.");
    if (field === passwordConfirm && value !== password.value) return showFieldError(field, "كلمتا المرور غير متطابقتين.");
    clearFieldError(field);
    return true;
  }

  roleInputs.forEach(function (input) {
    input.addEventListener("change", function () { setRole(input.value); });
  });

  form.querySelectorAll("input, select, textarea").forEach(function (field) {
    if (field.type !== "radio" && field.type !== "checkbox") {
      field.addEventListener("blur", function () { validateField(field); });
      field.addEventListener("input", function () {
        if (field.getAttribute("aria-invalid") === "true") validateField(field);
        if (field === password && passwordConfirm.value) validateField(passwordConfirm);
      });
    }
  });

  document.querySelectorAll(".password-toggle").forEach(function (button) {
    button.addEventListener("click", function () {
      const input = document.getElementById(button.dataset.target);
      const show = input.type === "password";
      input.type = show ? "text" : "password";
      button.setAttribute("aria-label", show ? "إخفاء كلمة المرور" : "إظهار كلمة المرور");
      button.querySelector("i").className = show ? "bi bi-eye-slash" : "bi bi-eye";
      input.focus();
    });
  });

  terms.addEventListener("change", function () {
    termsError.textContent = terms.checked ? "" : "يجب الموافقة على الشروط والأحكام.";
  });

  form.addEventListener("submit", function (event) {
    if (submitting) {
      event.preventDefault();
      return;
    }
    formStatus.textContent = "";
    const activeFields = Array.from(form.querySelectorAll("input:not(:disabled), select:not(:disabled), textarea:not(:disabled)"));
    let valid = true;
    activeFields.forEach(function (field) {
      if (field.type !== "radio" && field.type !== "checkbox" && !validateField(field)) valid = false;
    });
    if (!terms.checked) {
      termsError.textContent = "يجب الموافقة على الشروط والأحكام.";
      valid = false;
    }
    if (!valid) {
      event.preventDefault();
      const firstInvalid = form.querySelector('[aria-invalid="true"], .is-invalid input, .is-invalid select, .is-invalid textarea');
      if (firstInvalid) firstInvalid.focus();
      return;
    }

    /* Valid: let the browser post the form to Laravel. */
    submitting = true;
    const submitButton = form.querySelector("[type=submit]");
    if (submitButton) submitButton.disabled = true;
  });

  /* Back/forward cache restores the page with a disabled button. */
  window.addEventListener("pageshow", function () {
    submitting = false;
    const submitButton = form.querySelector("[type=submit]");
    if (submitButton) submitButton.disabled = false;
  });

  setRole(form.querySelector('input[name="account_type"]:checked').value);
})();
