(() => {
  "use strict";

  const root = document.getElementById("registerRoot");
  const form = document.getElementById("registerForm");
  if (!root || !form) return;

  const roleStep = form.querySelector('[data-register-step="role"]');
  const detailsStep = form.querySelector('[data-register-step="details"]');
  const roleInputs = Array.from(form.querySelectorAll('input[name="account_type"]'));
  const continueButton = document.getElementById("continueButton");
  const backButton = document.getElementById("backButton");
  const progressRole = Array.from(root.querySelectorAll('[data-progress="role"]'));
  const progressDetails = Array.from(root.querySelectorAll('[data-progress="details"]'));
  const progressLine = Array.from(root.querySelectorAll(".register-progress__line"));
  const selectedRoleLabel = document.getElementById("selectedRoleLabel");
  const detailsSubtitle = document.getElementById("detailsSubtitle");
  const showcaseTitle = document.getElementById("registerShowcaseTitle");
  const showcaseDescription = document.getElementById("registerShowcaseDescription");
  const showcaseContent = root.querySelector(".register-showcase__content");
  const visualImages = Array.from(root.querySelectorAll("[data-visual-image]"));
  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const showcaseBenefits = [
    document.getElementById("registerBenefitOne"),
    document.getElementById("registerBenefitTwo"),
    document.getElementById("registerBenefitThree")
  ];
  const submitLabel = document.getElementById("submitLabel");
  const password = document.getElementById("password");
  const passwordConfirmation = document.getElementById("password_confirmation");
  const terms = form.querySelector('input[name="terms"]');
  const socialRegisterLinks = Array.from(form.querySelectorAll("[data-social-register]"));
  let showcaseUpdateTimer = 0;

  const roleCopy = {
    customer: {
      ar: ["حساب عميل", "أنشئ حسابك وابدأ باختيار المنتجات والتصاميم التي تناسبك.", "إنشاء حساب العميل"],
      en: ["Customer account", "Create your account and choose the products and designs that suit you.", "Create customer account"]
    },
    designer: {
      ar: ["حساب مصمم", "أكمل ملفك واعرض أعمالك لتبدأ بيع تصاميمك على المنصة.", "إنشاء حساب المصمم"],
      en: ["Designer account", "Complete your profile and showcase your work to start selling designs.", "Create designer account"]
    },
    print_provider: {
      ar: ["حساب مطبعة", "أضف خدمات مطبعتك واستقبل طلبات الطباعة التي تتوافق مع إمكاناتك.", "إنشاء حساب المطبعة"],
      en: ["Print shop account", "Add your print services and receive orders that match your capabilities.", "Create print shop account"]
    }
  };

  const showcaseCopy = {
    ar: [
      "كل فكرة تستحق أن تُطبع باحتراف",
      "انضم إلى PalPrints واكتشف تجربة تجمع التصميم والطباعة والمنتجات في مكان واحد.",
      ["تصميمات مبتكرة", "طباعة احترافية", "منتجات متنوعة"]
    ],
    en: [
      "Every idea deserves to be printed professionally",
      "Join PalPrints and discover an experience that brings design, printing, and products together in one place.",
      ["Creative designs", "Professional printing", "Varied products"]
    ]
  };

  const roleVisualCopy = {
    customer: {
      ar: ["اطبع فكرتك… والباقي علينا", "اختر المنتجات والتصاميم التي تناسبك، ودع PalPrints تهتم بتحويلها إلى منتجات مطبوعة بجودة عالية.", ["منتجات متنوعة", "طباعة موثوقة", "تجربة شراء سهلة"]],
      en: ["Print your idea—we'll handle the rest", "Choose the products and designs that suit you, and let PalPrints turn them into high-quality printed products.", ["Varied products", "Reliable printing", "Easy shopping experience"]]
    },
    designer: {
      ar: ["حوّل إبداعك إلى منتجات تُباع", "اعرض تصاميمك وحوّلها إلى منتجات مطبوعة تصل إلى العملاء بسهولة.", ["اعرض تصاميمك", "اكسب من مبيعاتك", "نحن نهتم بالطباعة"]],
      en: ["Turn your creativity into products that sell", "Showcase your designs and turn them into printed products that reach customers easily.", ["Show your designs", "Earn from sales", "We handle printing"]]
    },
    print_provider: {
      ar: ["طلبات أكثر، وإدارة أسهل لمطبعتك", "أضف خدمات طباعتك واستقبل الطلبات التي تتوافق مع إمكاناتك من خلال PalPrints.", ["استقبل الطلبات", "حدد إمكاناتك", "أدر أعمالك بسهولة"]],
      en: ["More orders, simpler print-shop management", "Add your printing services and receive orders that match your capabilities through PalPrints.", ["Receive orders", "Set your capabilities", "Manage your business easily"]]
    }
  };

  const validationCopy = {
    ar: {
      roleRequired: "اختر نوع الحساب أولًا للمتابعة.",
      nameRequired: "أدخل الاسم الكامل.",
      nameShort: "يجب أن يتكون الاسم من 3 أحرف على الأقل.",
      emailRequired: "أدخل البريد الإلكتروني.",
      emailInvalid: "أدخل بريدًا إلكترونيًا صحيحًا مثل name@example.com.",
      passwordRequired: "أدخل كلمة المرور.",
      passwordShort: "يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.",
      confirmationRequired: "أعد كتابة كلمة المرور.",
      confirmationMismatch: "كلمتا المرور غير متطابقتين.",
      termsRequired: "يجب الموافقة على الشروط وسياسة الخصوصية.",
    },
    en: {
      roleRequired: "Choose an account type to continue.",
      nameRequired: "Enter your full name.",
      nameShort: "Your name must be at least 3 characters.",
      emailRequired: "Enter your email address.",
      emailInvalid: "Enter a valid email such as name@example.com.",
      passwordRequired: "Enter a password.",
      passwordShort: "Your password must be at least 8 characters.",
      confirmationRequired: "Re-enter your password.",
      confirmationMismatch: "Passwords do not match.",
      termsRequired: "You must accept the terms and privacy policy.",
    }
  };

  const fieldRules = [
    {
      input: document.getElementById("name"),
      error: document.getElementById("name_client_error"),
      validate(value) {
        const clean = value.trim();
        if (!clean) return "nameRequired";
        return clean.length < 3 ? "nameShort" : "";
      }
    },
    {
      input: document.getElementById("email"),
      error: document.getElementById("email_client_error"),
      validate(value) {
        const clean = value.trim();
        if (!clean) return "emailRequired";
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clean) ? "" : "emailInvalid";
      }
    },
    {
      input: password,
      error: document.getElementById("password_client_error"),
      validate(value) {
        if (!value) return "passwordRequired";
        return value.length < 8 ? "passwordShort" : "";
      }
    },
    {
      input: passwordConfirmation,
      error: document.getElementById("password_confirmation_client_error"),
      validate(value) {
        if (!value) return "confirmationRequired";
        return value === password.value ? "" : "confirmationMismatch";
      }
    }
  ];

  roleInputs.forEach((input) => {
    input.addEventListener("change", () => {
      updateSelectedRole(input.value);
      root.dataset.selectedRole = input.value;
      showRoleShowcase(input.value);
      continueButton.disabled = false;
      setValidationError(document.getElementById("account_type_client_error"), "", null);
    });
  });

  continueButton?.addEventListener("click", () => {
    const role = selectedRole();
    if (!role) {
      setValidationError(document.getElementById("account_type_client_error"), "roleRequired", null);
      roleInputs[0]?.focus();
      return;
    }
    updateSelectedRole(role);
    showStep("details");
    requestAnimationFrame(() => document.getElementById("name")?.focus());
  });

  backButton?.addEventListener("click", () => {
    showStep("role");
    requestAnimationFrame(() => roleInputs.find((input) => input.checked)?.focus());
  });

  fieldRules.forEach((rule) => {
    rule.input?.addEventListener("blur", () => validateRule(rule));
    rule.input?.addEventListener("input", () => {
      if (rule.input.getAttribute("aria-invalid") === "true") validateRule(rule);
      if (rule.input === password && passwordConfirmation?.value) {
        validateRule(fieldRules[3]);
      }
    });
  });

  terms?.addEventListener("change", () => {
    if (terms.checked) {
      setValidationError(document.getElementById("terms_client_error"), "", terms);
    }
  });

  socialRegisterLinks.forEach((link) => {
    link.addEventListener("click", (event) => {
      event.preventDefault();
      const role = selectedRole();

      if (!role) {
        showStep("role");
        setValidationError(document.getElementById("account_type_client_error"), "roleRequired", null);
        roleInputs[0]?.focus();
        return;
      }

      if (!terms?.checked) {
        setValidationError(document.getElementById("terms_client_error"), "termsRequired", terms);
        terms?.focus();
        return;
      }

      const destination = new URL(link.href, window.location.origin);
      destination.searchParams.set("source", "register");
      destination.searchParams.set("account_type", role);
      destination.searchParams.set("terms", "1");
      window.location.assign(destination.toString());
    });
  });

  form.addEventListener("submit", (event) => {
    if (!selectedRole()) {
      event.preventDefault();
      showStep("role");
      setValidationError(document.getElementById("account_type_client_error"), "roleRequired", null);
      roleInputs[0]?.focus();
      return;
    }

    const fieldsValid = fieldRules.map(validateRule).every(Boolean);
    const termsValid = Boolean(terms?.checked);
    setValidationError(
      document.getElementById("terms_client_error"),
      termsValid ? "" : "termsRequired",
      terms
    );

    if (!fieldsValid || !termsValid) {
      event.preventDefault();
      const firstInvalid = fieldRules.find((rule) => rule.input?.getAttribute("aria-invalid") === "true")?.input;
      (firstInvalid || terms)?.focus();
    }
  });

  document.addEventListener("palprints:language-changed", () => {
    const role = selectedRole();
    if (role) updateSelectedRole(role);
    renderShowcasePhrase();
    document.querySelectorAll("[data-validation-key]").forEach((element) => {
      element.textContent = validationText(element.dataset.validationKey);
    });
  });

  const initialRole = root.dataset.initialRole;
  if (initialRole && roleCopy[initialRole]) {
    const input = roleInputs.find((item) => item.value === initialRole);
    if (input) input.checked = true;
    root.dataset.selectedRole = initialRole;
    continueButton.disabled = false;
    updateSelectedRole(initialRole);
  }

  showStep(root.dataset.initialStep === "details" && selectedRole() ? "details" : "role", false);
  renderShowcasePhrase(false);

  function selectedRole() {
    return roleInputs.find((input) => input.checked)?.value || "";
  }

  function currentLanguage() {
    return document.documentElement.lang === "en" ? "en" : "ar";
  }

  function validationText(key) {
    return key ? validationCopy[currentLanguage()][key] || "" : "";
  }

  function updateSelectedRole(role) {
    const copy = roleCopy[role]?.[currentLanguage()];
    if (!copy) return;
    selectedRoleLabel.textContent = copy[0];
    detailsSubtitle.textContent = copy[1];
    submitLabel.textContent = copy[2];
  }

  function renderShowcasePhrase(animate = true) {
    const role = selectedRole();
    if (role) {
      showRoleShowcase(role, animate);
      return;
    }
    const phrase = showcaseCopy[currentLanguage()];
    setShowcaseCopy(phrase[0], phrase[1], phrase[2], "general", animate);
  }

  function showRoleShowcase(role, animate = true) {
    const copy = roleVisualCopy[role]?.[currentLanguage()];
    if (!copy) return;
    setShowcaseCopy(copy[0], copy[1], copy[2], role, animate);
  }

  function setShowcaseCopy(title, description, benefits = [], visualRole = "general", animate = true) {
    if (!showcaseTitle || !showcaseDescription) return;
    window.clearTimeout(showcaseUpdateTimer);
    const shouldAnimate = animate && !prefersReducedMotion;
    showcaseContent?.classList.toggle("is-changing", shouldAnimate);
    showcaseUpdateTimer = window.setTimeout(() => {
      showcaseTitle.textContent = title;
      showcaseDescription.textContent = description;
      showcaseBenefits.forEach((element, index) => {
        if (element && benefits[index]) element.textContent = benefits[index];
      });
      root.dataset.visualRole = visualRole;
      visualImages.forEach((image) => {
        image.classList.toggle("is-active", image.dataset.visualImage === visualRole);
      });
      requestAnimationFrame(() => showcaseContent?.classList.remove("is-changing"));
    }, shouldAnimate ? 150 : 0);
  }

  function validateRule(rule) {
    if (!rule?.input) return true;
    const key = rule.validate(rule.input.value);
    setValidationError(rule.error, key, rule.input);
    return !key;
  }

  function setValidationError(element, key, input) {
    if (element) {
      element.dataset.validationKey = key;
      element.textContent = validationText(key);
      if (!key) element.removeAttribute("data-validation-key");
    }
    input?.setAttribute("aria-invalid", key ? "true" : "false");
  }

  function showStep(step, animate = true) {
    const showDetails = step === "details";
    root.dataset.currentStep = showDetails ? "details" : "role";
    roleStep.hidden = showDetails;
    detailsStep.hidden = !showDetails;
    const active = showDetails ? detailsStep : roleStep;
    active.classList.remove("is-entering");
    if (animate) {
      void active.offsetWidth;
      active.classList.add("is-entering");
    }
    progressRole.forEach((item) => {
      item.classList.toggle("is-active", !showDetails);
      item.classList.toggle("is-complete", showDetails);
    });
    progressDetails.forEach((item) => item.classList.toggle("is-active", showDetails));
    progressLine.forEach((item) => item.classList.toggle("is-complete", showDetails));
  }
})();
