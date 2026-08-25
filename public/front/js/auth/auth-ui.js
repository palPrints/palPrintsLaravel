(() => {
  "use strict";

  const STORAGE_KEYS = {
    language: "palprints-language",
    theme: "palprints-theme"
  };

  const translations = {
    ar: {
      registerPageTitle: "إنشاء حساب | PALPRINTS",
      loginPageTitle: "تسجيل الدخول | PALPRINTS",
      forgotPageTitle: "نسيت كلمة المرور | PALPRINTS",
      resetPageTitle: "إعادة تعيين كلمة المرور | PALPRINTS",
      statusPageTitle: "حالة الحساب | PALPRINTS",
      verifyEmailPageTitle: "التحقق من البريد | PALPRINTS",
      confirmPasswordPageTitle: "تأكيد كلمة المرور | PALPRINTS",
      skipToContent: "انتقل إلى المحتوى الرئيسي",
      headerNavLabel: "خيارات الحساب والواجهة",
      legalLinksLabel: "روابط قانونية",
      languageToggleLabel: "تغيير اللغة إلى الإنجليزية",
      activateDarkTheme: "تفعيل الوضع الليلي",
      activateLightTheme: "تفعيل الوضع الفاتح",
      themeToggleText: "تبديل المظهر",
      allRightsReserved: "جميع الحقوق محفوظة.",
      privacyPolicy: "سياسة الخصوصية",
      termsAndConditions: "الشروط والأحكام",
      hasAccount: "لديك حساب بالفعل؟",
      needAccount: "ليس لديك حساب؟",
      signIn: "تسجيل الدخول",
      createAccount: "إنشاء حساب",
      registerKicker: "ابدأ رحلتك",
      registerRoleTitle: "كيف تريد استخدام PALPRINTS؟",
      registerRoleSubtitle: "اختر نوع الحساب أولًا، ثم أدخل بيانات إنشاء الحساب.",
      roleCustomer: "عميل",
      roleCustomerDescription: "تسوّق واطلب منتجات مطبوعة بتصاميم تناسب ذوقك.",
      roleDesigner: "مصمم",
      roleDesignerDescription: "اعرض تصاميمك وحوّل إبداعك إلى مصدر دخل.",
      rolePrinter: "مطبعة",
      rolePrinterDescription: "استقبل طلبات طباعة تتوافق مع خدمات وإمكانات مطبعتك.",
      registerShowcaseKicker: "مرحبًا بك في",
      registerShowcaseTitle: "PalPrints",
      registerShowcaseDescription: "منصة الطباعة حسب الطلب التي تمكّنك من تحويل أفكارك إلى واقع ملموس.",
      registerVisualEyebrow: "من الفكرة إلى منتج مطبوع",
      registerBenefitsLabel: "مزايا الحساب المحدد",
      visualTshirt: "قميص مطبوع",
      visualMug: "كوب مطبوع",
      visualPackaging: "تغليف",
      visualProductPreview: "معاينة المنتج",
      visualReadyToPrint: "جاهز للطباعة",
      visualColors: "الألوان",
      visualReadyProducts: "منتجات جاهزة للطلب",
      visualDesignCanvas: "لوحة التصميم",
      visualDesign: "تصميم",
      visualProduct: "منتج",
      visualSale: "بيع",
      visualNewSale: "عملية بيع جديدة",
      visualPrintOrder: "طلب طباعة",
      visualPrintedProducts: "منتجات مطبوعة",
      visualPiecesCount: "24 قطعة",
      visualPrintingStatus: "قيد الطباعة",
      visualNewOrders: "3 طلبات جديدة",
      roleStep: "نوع الحساب",
      detailsStep: "بياناتك",
      continue: "متابعة لإنشاء الحساب",
      changeRole: "تغيير نوع الحساب",
      registrationWelcome: "أهلًا بك، أنت تنشئ",
      detailsTitle: "أدخل بياناتك الأساسية",
      detailsDefaultSubtitle: "لن يستغرق الأمر أكثر من دقيقة.",
      fullName: "الاسم الكامل",
      fullNamePlaceholder: "مثال: أحمد محمد",
      email: "البريد الإلكتروني",
      emailPlaceholder: "name@example.com",
      password: "كلمة المرور",
      passwordPlaceholder: "8 أحرف على الأقل",
      confirmPassword: "تأكيد كلمة المرور",
      confirmPasswordPlaceholder: "أعد كتابة كلمة المرور",
      passwordHint: "استخدم حروفًا وأرقامًا لزيادة الأمان.",
      agreeTo: "أوافق على",
      and: "و",
      dataPrivacyNote: "نحفظ بياناتك بأمان ولن نشاركها مع أي طرف غير مخوّل.",
      customerAccount: "حساب عميل",
      designerAccount: "حساب مصمم",
      printerAccount: "حساب مطبعة",
      customerDetailsSubtitle: "أنشئ حسابك لتبدأ الطلب واختيار المنتجات والتصاميم.",
      designerDetailsSubtitle: "بعد إنشاء الحساب ستتمكن من استكمال ملف المصمم وإرسال أعمالك للمراجعة.",
      printerDetailsSubtitle: "بعد إنشاء الحساب ستستكمل بيانات المطبعة والخدمات ومستندات الاعتماد.",
      createCustomerAccount: "إنشاء حساب العميل",
      createDesignerAccount: "إنشاء حساب المصمم",
      createPrinterAccount: "إنشاء حساب المطبعة",
      loginVisualKicker: "منصة الطباعة حسب الطلب",
      loginVisualTitle: "كل فكرة يمكن أن تصبح شيئًا حقيقيًا",
      loginVisualDescription: "ادخل إلى مساحة تجمع التصميم والطباعة والإبداع في تجربة واحدة.",
      loginVisualBenefitsLabel: "مزايا تسجيل الدخول إلى PALPRINTS",
      loginBenefitOne: "وصول سريع إلى حسابك",
      loginBenefitTwo: "متابعة الطلبات والحالة",
      loginBenefitThree: "تجربة موحدة لكل أنواع الحسابات",
      welcomeBack: "مرحبًا بعودتك",
      loginSubtitle: "أدخل بيانات حسابك للمتابعة إلى PALPRINTS.",
      logoAlt: "شعار PALPRINTS",
      productsImageAlt: "مجموعة منتجات مطبوعة من PALPRINTS",
      rememberMe: "تذكرني",
      forgotPassword: "نسيت كلمة المرور؟",
      or: "أو",
      continueWithGoogle: "المتابعة باستخدام Google",
      continueWithApple: "المتابعة باستخدام Apple",
      socialUnavailable: "تسجيل الدخول الاجتماعي غير مربوط بالـ backend بعد.",
      showPassword: "إظهار كلمة المرور",
      hidePassword: "إخفاء كلمة المرور",
      accountRecovery: "استعادة الحساب",
      forgotTitle: "نسيت كلمة المرور؟",
      forgotSubtitle: "أدخل بريدك الإلكتروني وسنرسل لك رابطًا آمنًا لاختيار كلمة مرور جديدة.",
      sendResetLink: "إرسال رابط الاستعادة",
      backToLogin: "العودة إلى تسجيل الدخول",
      resetKicker: "حماية حسابك",
      resetTitle: "إعادة تعيين كلمة المرور",
      resetSubtitle: "أدخل البريد وكلمة المرور الجديدة ثم أكدها.",
      newPassword: "كلمة المرور الجديدة",
      savePassword: "حفظ كلمة المرور",
      accountStatus: "حالة الحساب",
      pendingReview: "بانتظار مراجعة حسابك",
      pendingDescription: "تم استلام طلبك، وستظهر هنا تفاصيل المراجعة عند تفعيل نظام اعتماد الحسابات.",
      requestReceived: "تم استلام الطلب",
      underReview: "قيد المراجعة",
      accountActivation: "تفعيل الحساب",
      accountType: "نوع الحساب",
      requestReference: "رقم الطلب",
      browseAsGuest: "تصفح المنصة كزائر",
      customer: "عميل",
      designer: "مصمم",
      printProvider: "مطبعة",
      verifyEmailTitle: "تحقق من بريدك الإلكتروني",
      verifyEmailSubtitle: "افتح الرسالة التي أرسلناها إلى بريدك واضغط رابط التحقق للمتابعة.",
      resendVerification: "إعادة إرسال رابط التحقق",
      logout: "تسجيل الخروج",
      confirmPasswordTitle: "تأكيد كلمة المرور",
      confirmPasswordSubtitle: "هذه منطقة آمنة. أكد كلمة المرور قبل المتابعة.",
      confirm: "تأكيد"
    },
    en: {
      registerPageTitle: "Create account | PALPRINTS",
      loginPageTitle: "Sign in | PALPRINTS",
      forgotPageTitle: "Forgot password | PALPRINTS",
      resetPageTitle: "Reset password | PALPRINTS",
      statusPageTitle: "Account status | PALPRINTS",
      verifyEmailPageTitle: "Verify email | PALPRINTS",
      confirmPasswordPageTitle: "Confirm password | PALPRINTS",
      skipToContent: "Skip to main content",
      headerNavLabel: "Account and interface options",
      legalLinksLabel: "Legal links",
      languageToggleLabel: "Switch language to Arabic",
      activateDarkTheme: "Enable dark mode",
      activateLightTheme: "Enable light mode",
      themeToggleText: "Toggle appearance",
      allRightsReserved: "All rights reserved.",
      privacyPolicy: "Privacy policy",
      termsAndConditions: "Terms and conditions",
      hasAccount: "Already have an account?",
      needAccount: "Don't have an account?",
      signIn: "Sign in",
      createAccount: "Create account",
      registerKicker: "Start your journey",
      registerRoleTitle: "How will you use PALPRINTS?",
      registerRoleSubtitle: "Choose your account type first, then enter your account details.",
      roleCustomer: "Customer",
      roleCustomerDescription: "Shop and order printed products with designs that match your style.",
      roleDesigner: "Designer",
      roleDesignerDescription: "Showcase your designs and turn creativity into a source of income.",
      rolePrinter: "Print shop",
      rolePrinterDescription: "Receive print orders that match your print shop services and capabilities.",
      registerShowcaseKicker: "Welcome to",
      registerShowcaseTitle: "PalPrints",
      registerShowcaseDescription: "The print-on-demand platform that turns your ideas into tangible products.",
      registerVisualEyebrow: "From an idea to a printed product",
      registerBenefitsLabel: "Benefits of the selected account",
      visualTshirt: "Printed T-shirt",
      visualMug: "Printed mug",
      visualPackaging: "Packaging",
      visualProductPreview: "Product preview",
      visualReadyToPrint: "Ready to print",
      visualColors: "Colors",
      visualReadyProducts: "Products ready to order",
      visualDesignCanvas: "Design canvas",
      visualDesign: "Design",
      visualProduct: "Product",
      visualSale: "Sale",
      visualNewSale: "New sale",
      visualPrintOrder: "Print order",
      visualPrintedProducts: "Printed products",
      visualPiecesCount: "24 pieces",
      visualPrintingStatus: "Printing",
      visualNewOrders: "3 new orders",
      roleStep: "Account type",
      detailsStep: "Your details",
      continue: "Continue to create account",
      changeRole: "Change account type",
      registrationWelcome: "Welcome, you are creating",
      detailsTitle: "Enter your basic details",
      detailsDefaultSubtitle: "This will take less than a minute.",
      fullName: "Full name",
      fullNamePlaceholder: "Example: Alex Smith",
      email: "Email address",
      emailPlaceholder: "name@example.com",
      password: "Password",
      passwordPlaceholder: "At least 8 characters",
      confirmPassword: "Confirm password",
      confirmPasswordPlaceholder: "Re-enter your password",
      passwordHint: "Use letters and numbers for better security.",
      agreeTo: "I agree to",
      and: "and",
      dataPrivacyNote: "We protect your data and never share it with unauthorized parties.",
      customerAccount: "Customer account",
      designerAccount: "Designer account",
      printerAccount: "Print shop account",
      customerDetailsSubtitle: "Create your account to start ordering products and designs.",
      designerDetailsSubtitle: "After signup, you can complete your designer profile and submit work for review.",
      printerDetailsSubtitle: "After signup, you can complete your business, services, and verification details.",
      createCustomerAccount: "Create customer account",
      createDesignerAccount: "Create designer account",
      createPrinterAccount: "Create print shop account",
      loginVisualKicker: "Print-on-demand platform",
      loginVisualTitle: "Every idea can become something real",
      loginVisualDescription: "Enter a space that brings design, printing, and creativity together.",
      loginVisualBenefitsLabel: "Benefits of signing in to PALPRINTS",
      loginBenefitOne: "Quick access to your account",
      loginBenefitTwo: "Track orders and status",
      loginBenefitThree: "One experience for every account type",
      welcomeBack: "Welcome back",
      loginSubtitle: "Enter your account details to continue to PALPRINTS.",
      logoAlt: "PALPRINTS logo",
      productsImageAlt: "A collection of printed PALPRINTS products",
      rememberMe: "Remember me",
      forgotPassword: "Forgot password?",
      or: "or",
      continueWithGoogle: "Continue with Google",
      continueWithApple: "Continue with Apple",
      socialUnavailable: "Social sign-in is not connected to the backend yet.",
      showPassword: "Show password",
      hidePassword: "Hide password",
      accountRecovery: "Account recovery",
      forgotTitle: "Forgot your password?",
      forgotSubtitle: "Enter your email and we'll send a secure link to choose a new password.",
      sendResetLink: "Send reset link",
      backToLogin: "Back to sign in",
      resetKicker: "Protect your account",
      resetTitle: "Reset password",
      resetSubtitle: "Enter your email, new password, and confirmation.",
      newPassword: "New password",
      savePassword: "Save password",
      accountStatus: "Account status",
      pendingReview: "Your account is pending review",
      pendingDescription: "Your request was received. Review details will appear here when account approval is enabled.",
      requestReceived: "Request received",
      underReview: "Under review",
      accountActivation: "Account activation",
      accountType: "Account type",
      requestReference: "Request reference",
      browseAsGuest: "Browse as guest",
      customer: "Customer",
      designer: "Designer",
      printProvider: "Print shop",
      verifyEmailTitle: "Verify your email address",
      verifyEmailSubtitle: "Open the email we sent and click the verification link to continue.",
      resendVerification: "Resend verification link",
      logout: "Log out",
      confirmPasswordTitle: "Confirm password",
      confirmPasswordSubtitle: "This is a secure area. Confirm your password to continue.",
      confirm: "Confirm"
    }
  };

  let language = safeGet(STORAGE_KEYS.language) === "en" ? "en" : "ar";
  let theme = safeGet(STORAGE_KEYS.theme) === "dark" ? "dark" : "light";

  applyLanguage();
  applyTheme();
  bindControls();
  bindPasswordToggles();
  bindSocialPlaceholders();
  bindFormLoading();
  hydrateStatusPage();
  document.querySelectorAll("[data-current-year]").forEach((element) => {
    element.textContent = String(new Date().getFullYear());
  });

  function t(key) {
    return translations[language][key] || translations.ar[key] || key;
  }

  function applyLanguage() {
    document.documentElement.lang = language;
    document.documentElement.dir = language === "en" ? "ltr" : "rtl";

    document.querySelectorAll("[data-i18n]").forEach((element) => {
      const value = translations[language][element.dataset.i18n];
      if (value) element.textContent = value;
    });

    document.querySelectorAll("[data-i18n-placeholder]").forEach((element) => {
      const value = translations[language][element.dataset.i18nPlaceholder];
      if (value) element.setAttribute("placeholder", value);
    });

    document.querySelectorAll("[data-i18n-aria-label]").forEach((element) => {
      const value = translations[language][element.dataset.i18nAriaLabel];
      if (value) element.setAttribute("aria-label", value);
    });
    document.querySelectorAll("[data-i18n-alt]").forEach((element) => {
      element.alt = t(element.dataset.i18nAlt);
    });

    const titleElement = document.querySelector("[data-i18n-document-title]");
    if (titleElement) document.title = t(titleElement.dataset.i18nDocumentTitle);

    const languageText = document.getElementById("languageToggleText");
    if (languageText) languageText.textContent = language === "ar" ? "EN" : "AR";

    updatePasswordLabels();
    updateThemeLabel();
    document.dispatchEvent(new CustomEvent("palprints:language-changed", { detail: { language } }));
  }

  function applyTheme() {
    document.documentElement.dataset.theme = theme;
    document.body.classList.toggle("dark-mode", theme === "dark");
    const button = document.getElementById("themeToggle");
    button?.setAttribute("aria-pressed", String(theme === "dark"));
    const icon = document.getElementById("themeToggleIcon");
    if (icon) {
      icon.classList.remove("bi-moon-stars", "bi-sun");
      icon.classList.add(theme === "dark" ? "bi-sun" : "bi-moon-stars");
    }
    updateThemeLabel();
  }

  function bindControls() {
    document.getElementById("languageToggle")?.addEventListener("click", () => {
      language = language === "ar" ? "en" : "ar";
      safeSet(STORAGE_KEYS.language, language);
      applyLanguage();
    });

    document.getElementById("themeToggle")?.addEventListener("click", () => {
      theme = theme === "dark" ? "light" : "dark";
      safeSet(STORAGE_KEYS.theme, theme);
      applyTheme();
    });
  }

  function updateThemeLabel() {
    const button = document.getElementById("themeToggle");
    if (!button) return;
    button.setAttribute("aria-label", t(theme === "dark" ? "activateLightTheme" : "activateDarkTheme"));
  }

  function bindPasswordToggles() {
    document.querySelectorAll("[data-password-target]").forEach((button) => {
      button.addEventListener("click", () => {
        const input = document.getElementById(button.dataset.passwordTarget);
        if (!input) return;
        const show = input.type === "password";
        input.type = show ? "text" : "password";
        button.classList.toggle("is-visible", show);
        updatePasswordLabels();
      });
    });
  }

  function updatePasswordLabels() {
    document.querySelectorAll("[data-password-target]").forEach((button) => {
      const input = document.getElementById(button.dataset.passwordTarget);
      button.setAttribute("aria-label", t(input?.type === "text" ? "hidePassword" : "showPassword"));
    });
  }

  function bindSocialPlaceholders() {
    document.querySelectorAll("[data-social-unavailable]").forEach((button) => {
      button.addEventListener("click", () => {
        const status = document.querySelector("[data-social-status]")
          || document.getElementById("loginFormStatus");
        if (!status) return;
        status.textContent = t("socialUnavailable");
        status.hidden = false;
        status.style.display = "block";
      });
    });
  }

  function bindFormLoading() {
    document.querySelectorAll("form[data-native-auth-form]").forEach((form) => {
      form.addEventListener("submit", () => {
        if (!form.checkValidity()) return;
        const button = form.querySelector('button[type="submit"]');
        if (button) {
          button.setAttribute("aria-busy", "true");
          button.disabled = true;
        }
      });
    });
  }

  function hydrateStatusPage() {
    const roleElement = document.querySelector("[data-status-role]");
    if (!roleElement) return;
    const params = new URLSearchParams(window.location.search);
    const role = params.get("role") || "customer";
    const roleKey = role === "designer" ? "designer" : role === "print_provider" || role === "printer" ? "printProvider" : "customer";
    roleElement.dataset.roleKey = roleKey;
    roleElement.textContent = t(roleKey);

    const referenceElement = document.querySelector("[data-status-reference]");
    if (referenceElement) referenceElement.textContent = params.get("reference") || "—";

    document.addEventListener("palprints:language-changed", () => {
      roleElement.textContent = t(roleElement.dataset.roleKey);
    });
  }

  function safeGet(key) {
    try { return localStorage.getItem(key); } catch (_) { return null; }
  }

  function safeSet(key, value) {
    try { localStorage.setItem(key, value); } catch (_) { /* The UI still works without storage. */ }
  }
})();
