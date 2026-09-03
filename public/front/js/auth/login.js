(() => {
  "use strict";

  const form = document.getElementById("loginForm");
  const emailInput = document.getElementById("loginEmail");
  const passwordInput = document.getElementById("loginPassword");
  const localeInput = document.getElementById("loginLocale");

  if (!form || !emailInput || !passwordInput) return;

  const validationCopy = {
    ar: {
      emailRequired: "أدخل البريد الإلكتروني.",
      emailInvalid: "أدخل بريدًا إلكترونيًا صحيحًا، مثل name@example.com.",
      passwordRequired: "أدخل كلمة المرور."
    },
    en: {
      emailRequired: "Enter your email address.",
      emailInvalid: "Enter a valid email address, such as name@example.com.",
      passwordRequired: "Enter your password."
    }
  };

  const fieldRules = [
    {
      input: emailInput,
      error: document.getElementById("loginEmailError"),
      validate(value) {
        const email = value.trim();
        if (!email) return "emailRequired";
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? "" : "emailInvalid";
      }
    },
    {
      input: passwordInput,
      error: document.getElementById("loginPasswordError"),
      validate(value) {
        return value ? "" : "passwordRequired";
      }
    }
  ];

  const emailFromQuery = new URLSearchParams(window.location.search).get("email");
  if (emailFromQuery && !emailInput.value) {
    emailInput.value = emailFromQuery;
  }

  syncLocale();
  renderTranslatedServerErrors();

  fieldRules.forEach((rule) => {
    rule.input.addEventListener("blur", () => validateRule(rule));
    rule.input.addEventListener("input", () => {
      if (rule.input.getAttribute("aria-invalid") === "true") validateRule(rule);
    });
  });

  form.addEventListener("submit", (event) => {
    syncLocale();
    const isValid = fieldRules.map(validateRule).every(Boolean);

    if (!isValid) {
      event.preventDefault();
      fieldRules.find((rule) => rule.input.getAttribute("aria-invalid") === "true")?.input.focus();
    }
  });

  document.querySelectorAll("#googleLoginButton, #appleLoginButton").forEach((link) => {
    link.addEventListener("click", () => {
      const destination = new URL(link.href, window.location.origin);
      destination.searchParams.set("locale", currentLanguage());
      link.href = destination.toString();
    });
  });

  document.addEventListener("palprints:language-changed", () => {
    syncLocale();
    renderTranslatedServerErrors();
    document.querySelectorAll("[data-validation-key]").forEach((element) => {
      element.textContent = validationText(element.dataset.validationKey);
    });
  });

  function currentLanguage() {
    return document.documentElement.lang === "en" ? "en" : "ar";
  }

  function syncLocale() {
    if (localeInput) localeInput.value = currentLanguage();
  }

  function validationText(key) {
    return key ? validationCopy[currentLanguage()][key] || "" : "";
  }

  function validateRule(rule) {
    const key = rule.validate(rule.input.value);
    setFieldError(rule, key);
    return !key;
  }

  function setFieldError(rule, key) {
    if (rule.error) {
      delete rule.error.dataset.errorAr;
      delete rule.error.dataset.errorEn;

      if (key) {
        rule.error.dataset.validationKey = key;
        rule.error.textContent = validationText(key);
      } else {
        delete rule.error.dataset.validationKey;
        rule.error.textContent = "";
      }
    }

    rule.input.setAttribute("aria-invalid", key ? "true" : "false");
  }

  function renderTranslatedServerErrors() {
    const language = currentLanguage();

    document.querySelectorAll("[data-error-ar][data-error-en]").forEach((element) => {
      element.textContent = language === "en"
        ? element.dataset.errorEn
        : element.dataset.errorAr;
    });
  }
})();
