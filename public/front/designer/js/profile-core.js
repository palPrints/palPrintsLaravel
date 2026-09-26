"use strict";

/* =========================================================
   PalPrints — Profile Core
   السلوك المشترك لصفحات الملف الشخصي الثلاث
   (العميل — المصمم — المطبعة)

   File: assets/js/pages/profile-core.js

   يوفّر كائن PalProfile ويحتوي على:
   - القائمة الجانبية (ارتفاع، تمرير، فتح وإغلاق)
   - زر الرجوع
   - الوضع الليلي
   - تبديل اللغة ودعم RTL / LTR
   - الحالات الأساسية: Loading / Empty / Error / Success
   - رسائل النجاح والخطأ
   - إخفاء البيانات الحساسة
   - رفع الصورة الشخصية
   ========================================================= */

(function (window, document) {
  /* =======================================================
     مفاتيح التخزين
     ======================================================= */

  const STORAGE = {
    language: "palprints-language",
    theme: "palprints-theme",
    sidebar: "palprints-sidebar-collapsed"
  };

  /* =======================================================
     تخزين آمن — لا يكسر الصفحة إذا كان معطلًا
     ======================================================= */

  function readStorage(key) {
    try {
      return window.localStorage.getItem(key);
    } catch (error) {
      return null;
    }
  }

  function writeStorage(key, value) {
    try {
      window.localStorage.setItem(key, value);
    } catch (error) {
      /* المتصفح يمنع التخزين — نتجاهل بهدوء */
    }
  }

  /* =======================================================
     أدوات عامة
     ======================================================= */

  function resolve(target) {
    if (!target) {
      return null;
    }

    if (typeof target === "string") {
      return document.querySelector(target);
    }

    return target;
  }

  function each(list, callback) {
    Array.prototype.forEach.call(list || [], callback);
  }

  const reducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  );

  const mobileScreen = window.matchMedia("(max-width: 991.98px)");

  /* يمكن للصفحات الجديدة تخصيص بداية القائمة دون تغيير السلوك
     الافتراضي لصفحات الملف الشخصي الحالية. */
  const sidebarSettings = {
    desktopInitial: "stored",
    persist: true
  };

  /* =======================================================
     الترجمة
     =======================================================
     كل صفحة تسجّل قاموسها الخاص عبر:
     PalProfile.registerDictionary({ ar: {...}, en: {...} })
     ======================================================= */

  const dictionaries = { ar: {}, en: {} };

  const languageListeners = [];

  function registerDictionary(dictionary) {
    if (!dictionary) {
      return;
    }

    ["ar", "en"].forEach(function (language) {
      Object.assign(
        dictionaries[language],
        dictionary[language] || {}
      );
    });
  }

  function getLanguage() {
    return document.documentElement.getAttribute("lang") === "en"
      ? "en"
      : "ar";
  }

  function translate(key) {
    const language = getLanguage();
    const table = dictionaries[language] || {};

    if (Object.prototype.hasOwnProperty.call(table, key)) {
      return table[key];
    }

    /* الرجوع إلى العربية عند غياب الترجمة */
    const fallback = dictionaries.ar || {};

    return Object.prototype.hasOwnProperty.call(fallback, key)
      ? fallback[key]
      : "";
  }

  function applyTranslations() {
    const language = getLanguage();

    /* النصوص */
    each(document.querySelectorAll("[data-i18n]"), function (element) {
      const value = translate(element.getAttribute("data-i18n"));

      if (value) {
        element.textContent = value;
      }
    });

    /* تسميات الوصول */
    each(
      document.querySelectorAll("[data-i18n-aria]"),
      function (element) {
        const value = translate(element.getAttribute("data-i18n-aria"));

        if (value) {
          element.setAttribute("aria-label", value);
        }
      }
    );

    /* التلميحات */
    each(
      document.querySelectorAll("[data-i18n-title]"),
      function (element) {
        const value = translate(element.getAttribute("data-i18n-title"));

        if (value) {
          element.setAttribute("title", value);
        }
      }
    );

    /* النصوص البديلة للصور */
    each(
      document.querySelectorAll("[data-i18n-alt]"),
      function (element) {
        const value = translate(element.getAttribute("data-i18n-alt"));

        if (value) {
          element.setAttribute("alt", value);
        }
      }
    );

    /* النصوص الإرشادية داخل الحقول */
    each(
      document.querySelectorAll("[data-i18n-placeholder]"),
      function (element) {
        const value = translate(
          element.getAttribute("data-i18n-placeholder")
        );

        if (value) {
          element.setAttribute("placeholder", value);
        }
      }
    );

    /* عنوان الصفحة ووصفها */
    const pageTitle = translate("documentTitle");

    if (pageTitle) {
      document.title = pageTitle;
    }

    const description = document.querySelector('meta[name="description"]');
    const descriptionText = translate("documentDescription");

    if (description && descriptionText) {
      description.setAttribute("content", descriptionText);
    }

    /* اتجاه سهم الرجوع يتبع اتجاه الصفحة */
    each(
      document.querySelectorAll("[data-back-icon]"),
      function (icon) {
        icon.classList.remove("bi-arrow-left", "bi-arrow-right");

        icon.classList.add(
          language === "ar" ? "bi-arrow-left" : "bi-arrow-right"
        );
      }
    );

    /* اتجاه سهم القائمة الجانبية يتبع اتجاه الصفحة */
    sidebarApi.sync();

    languageListeners.forEach(function (listener) {
      try {
        listener(language);
      } catch (error) {
        console.warn("خطأ أثناء تحديث الترجمة:", error);
      }
    });
  }

  function applyLanguage(language) {
    const next = language === "en" ? "en" : "ar";

    document.documentElement.setAttribute("lang", next);
    document.documentElement.setAttribute(
      "dir",
      next === "ar" ? "rtl" : "ltr"
    );

    writeStorage(STORAGE.language, next);

    const toggleText = document.getElementById("languageToggleText");

    if (toggleText) {
      toggleText.textContent = next === "ar" ? "EN" : "AR";
    }

    applyTranslations();
  }

  function onLanguageChange(listener) {
    if (typeof listener === "function") {
      languageListeners.push(listener);
    }
  }

  /* =======================================================
     الوضع الليلي
     ======================================================= */

  function applyTheme(theme) {
    const next = theme === "dark" ? "dark" : "light";

    document.documentElement.setAttribute("data-bs-theme", next);

    writeStorage(STORAGE.theme, next);

    const icon = document.getElementById("themeToggleIcon");

    if (icon) {
      icon.classList.remove("bi-moon-stars", "bi-sun");
      icon.classList.add(next === "dark" ? "bi-sun" : "bi-moon-stars");
    }

    const button = document.getElementById("themeToggleButton");

    if (button) {
      button.setAttribute(
        "aria-label",
        translate(next === "dark" ? "switchToLight" : "switchToDark") ||
          button.getAttribute("aria-label") ||
          ""
      );
    }
  }

  function currentTheme() {
    return document.documentElement.getAttribute("data-bs-theme") === "dark"
      ? "dark"
      : "light";
  }

  /* =======================================================
     القائمة الجانبية
     ======================================================= */

  /* على سطح المكتب تُطوى القائمة جانبًا،
     وعلى الشاشات الصغيرة تعمل كقائمة منزلقة. */

  const sidebarApi = {
    app: null,
    sidebar: null,
    backdrop: null,

    isMobile: function () {
      return mobileScreen.matches;
    },

    isOpen: function () {
      if (!this.app) {
        return false;
      }

      if (this.isMobile()) {
        return this.app.classList.contains("sidebar-open");
      }

      return !this.app.classList.contains("sidebar-collapsed");
    },

    open: function () {
      if (!this.app) {
        return;
      }

      if (this.isMobile()) {
        this.app.classList.add("sidebar-open");
        document.body.style.overflow = "hidden";
      } else {
        this.app.classList.remove("sidebar-collapsed");

        if (sidebarSettings.persist) {
          writeStorage(STORAGE.sidebar, "false");
        }
      }

      this.sync();
    },

    close: function () {
      if (!this.app) {
        return;
      }

      if (this.isMobile()) {
        this.app.classList.remove("sidebar-open");
        document.body.style.overflow = "";
      } else {
        this.app.classList.add("sidebar-collapsed");

        if (sidebarSettings.persist) {
          writeStorage(STORAGE.sidebar, "true");
        }
      }

      this.sync();
    },

    toggle: function () {
      if (this.isOpen()) {
        this.close();
      } else {
        this.open();
      }
    },

    /* مزامنة الزر واتجاه السهم وحالات ARIA
       مع الحالة الفعلية للقائمة */
    sync: function () {
      const button = document.getElementById("sidebarToggleButton");
      const icon = document.getElementById("sidebarToggleIcon");
      const open = this.isOpen();

      if (button) {
        button.setAttribute("aria-expanded", open ? "true" : "false");

        /* المفتاح يتغيّر ليتبع الترجمة عند تبديل اللغة */
        const key = open ? "closeSidebar" : "openSidebar";

        button.setAttribute("data-i18n-aria", key);

        const label = translate(key);

        if (label) {
          button.setAttribute("aria-label", label);
        }
      }

      if (icon) {
        icon.classList.remove(
          "bi-arrow-left",
          "bi-arrow-right",
          "bi-list",
          "bi-x-lg"
        );
        icon.classList.add(open ? "bi-x-lg" : "bi-list");
      }

      if (this.sidebar) {
        /* على الهاتف فقط تُخفى القائمة عن قارئ الشاشة عند إغلاقها */
        if (this.isMobile() && !open) {
          this.sidebar.setAttribute("aria-hidden", "true");
        } else {
          this.sidebar.removeAttribute("aria-hidden");
        }
      }
    },

    /* عند تغيّر حجم الشاشة نعيد ضبط الحالة */
    syncWithScreen: function () {
      if (!this.app) {
        return;
      }

      this.app.classList.remove("sidebar-open");
      document.body.style.overflow = "";

      if (this.isMobile()) {
        /* على الهاتف لا يوجد طيّ — القائمة منزلقة */
        this.app.classList.remove("sidebar-collapsed");
      } else if (sidebarSettings.desktopInitial === "open") {
        this.app.classList.remove("sidebar-collapsed");
      } else if (readStorage(STORAGE.sidebar) === "true") {
        this.app.classList.add("sidebar-collapsed");
      } else {
        this.app.classList.remove("sidebar-collapsed");
      }

      this.sync();
    }
  };

  function setupSidebar() {
    sidebarApi.app = document.querySelector(".profile-app");
    sidebarApi.sidebar = document.getElementById("profileSidebar");
    sidebarApi.backdrop = document.getElementById("sidebarBackdrop");

    if (!sidebarApi.app) {
      return;
    }

    const toggleButton = document.getElementById("sidebarToggleButton");
    const closeButton = document.getElementById("sidebarCloseButton");

    if (toggleButton) {
      toggleButton.addEventListener("click", function () {
        sidebarApi.toggle();
      });
    }

    if (closeButton) {
      closeButton.addEventListener("click", function () {
        sidebarApi.close();
      });
    }

    if (sidebarApi.backdrop) {
      sidebarApi.backdrop.addEventListener("click", function () {
        sidebarApi.close();
      });
    }

    /* إغلاق القائمة على الهاتف بعد اختيار رابط */
    if (sidebarApi.sidebar) {
      each(
        sidebarApi.sidebar.querySelectorAll(".profile-sidebar-link"),
        function (link) {
          link.addEventListener("click", function () {
            if (sidebarApi.isMobile()) {
              sidebarApi.close();
            }
          });
        }
      );
    }

    document.addEventListener("keydown", function (event) {
      if (
        event.key === "Escape" &&
        sidebarApi.isMobile() &&
        sidebarApi.isOpen()
      ) {
        sidebarApi.close();
      }
    });

    if (typeof mobileScreen.addEventListener === "function") {
      mobileScreen.addEventListener("change", function () {
        sidebarApi.syncWithScreen();
      });
    } else if (typeof mobileScreen.addListener === "function") {
      mobileScreen.addListener(function () {
        sidebarApi.syncWithScreen();
      });
    }

    sidebarApi.syncWithScreen();
  }

  /* =======================================================
     تسجيل الخروج
     ======================================================= */

  function setupLogout() {
    const button = document.getElementById("profileLogoutButton");

    if (!button) {
      return;
    }

    button.addEventListener("click", function (event) {
      event.preventDefault();

      const message =
        translate("logoutConfirm") ||
        "هل تريد تسجيل الخروج من حسابك؟";

      if (window.confirm(message)) {
        window.location.href =
          button.getAttribute("data-href") || "login.html";
      }
    });
  }

  /* =======================================================
     الحالات: Loading / Empty / Error / Ready
     ======================================================= */

  const VALID_STATES = ["loading", "ready", "empty", "error"];

  function setState(target, state) {
    const element = resolve(target);

    if (!element) {
      return;
    }

    const next = VALID_STATES.indexOf(state) === -1 ? "ready" : state;

    element.setAttribute("data-state", next);
    element.setAttribute("aria-busy", next === "loading" ? "true" : "false");
  }

  function getState(target) {
    const element = resolve(target);

    return element ? element.getAttribute("data-state") : null;
  }

  /* =======================================================
     محاكاة تحميل البيانات
     =======================================================
     تُستبدل لاحقًا بنداء API حقيقي.
     loader يجب أن يعيد Promise.
     ======================================================= */

  function loadSection(target, loader, options) {
    const element = resolve(target);
    const settings = options || {};

    if (!element) {
      return Promise.resolve(null);
    }

    setState(element, "loading");

    return Promise.resolve()
      .then(function () {
        return typeof loader === "function" ? loader() : loader;
      })
      .then(function (result) {
        const isEmpty =
          typeof settings.isEmpty === "function"
            ? settings.isEmpty(result)
            : Array.isArray(result) && result.length === 0;

        if (typeof settings.render === "function") {
          settings.render(result);
        }

        setState(element, isEmpty ? "empty" : "ready");

        return result;
      })
      .catch(function (error) {
        console.warn("تعذر تحميل القسم:", error);

        setState(element, "error");

        return null;
      });
  }

  /* إعادة المحاولة من داخل حالة الخطأ */

  function setupRetryButtons() {
    each(
      document.querySelectorAll("[data-retry-for]"),
      function (button) {
        button.addEventListener("click", function () {
          const selector = button.getAttribute("data-retry-for");
          const section = document.getElementById(selector);

          if (!section) {
            return;
          }

          const handler = section.__palRetry;

          if (typeof handler === "function") {
            handler();
          }
        });
      }
    );
  }

  function onRetry(target, handler) {
    const element = resolve(target);

    if (element) {
      element.__palRetry = handler;
    }
  }

  /* =======================================================
     رسائل النجاح والخطأ
     ======================================================= */

  let toastTimer = null;

  function toast(message, type) {
    const element = document.getElementById("profileToast");

    if (!element || !message) {
      return;
    }

    const text = document.getElementById("profileToastMessage");
    const icon = element.querySelector(".profile-toast-icon i");

    if (text) {
      text.textContent = message;
    }

    element.classList.remove("is-error", "is-info");

    if (type === "error") {
      element.classList.add("is-error");
    } else if (type === "info") {
      element.classList.add("is-info");
    }

    if (icon) {
      icon.className =
        "bi " +
        (type === "error"
          ? "bi-exclamation-triangle"
          : type === "info"
          ? "bi-info-circle"
          : "bi-check2");
    }

    element.classList.add("is-visible");

    window.clearTimeout(toastTimer);

    toastTimer = window.setTimeout(function () {
      element.classList.remove("is-visible");
    }, 3200);
  }

  /* =======================================================
     البيانات الحساسة
     =======================================================
     ملاحظة مهمة للـ Backend:
     يجب أن تُرجع الـ API القيم مخفية افتراضيًا
     (مثال: "•••• 1121") ولا تُرجع الرقم الكامل
     إلا ضمن إجراء آمن ومصرح به وبعد تحقق إضافي.
     الدوال التالية للعرض فقط ولا تُستخدم لإخفاء
     بيانات وصلت كاملة من الخادم.
     ======================================================= */

  function maskAccountNumber(value) {
    const digits = String(value || "").replace(/\s+/g, "");

    if (digits.length <= 4) {
      return "•••• " + digits;
    }

    return "•••• " + digits.slice(-4);
  }

  function maskIban(value) {
    const clean = String(value || "").replace(/\s+/g, "");

    if (clean.length <= 8) {
      return clean;
    }

    return (
      clean.slice(0, 4) + " •••• •••• " + clean.slice(-4)
    );
  }

  /* =======================================================
     رفع الصورة الشخصية
     ======================================================= */

  function setupAvatarUpload(options) {
    const settings = options || {};

    const input = document.getElementById(
      settings.inputId || "profileAvatarInput"
    );

    const image = document.getElementById(
      settings.imageId || "profileAvatarImage"
    );

    const icon = document.getElementById(
      settings.iconId || "profileAvatarIcon"
    );

    if (!input || !image) {
      return;
    }

    const MAXIMUM_SIZE = 2 * 1024 * 1024;

    input.addEventListener("change", function () {
      const file = input.files && input.files[0];

      if (!file) {
        return;
      }

      if (!/^image\/(png|jpeg|webp)$/.test(file.type)) {
        toast(
          translate("avatarTypeError") ||
            "يُسمح بصور PNG أو JPG أو WEBP فقط.",
          "error"
        );

        input.value = "";
        return;
      }

      if (file.size > MAXIMUM_SIZE) {
        toast(
          translate("avatarSizeError") ||
            "حجم الصورة يجب أن يكون أقل من 2 ميجابايت.",
          "error"
        );

        input.value = "";
        return;
      }

      const reader = new FileReader();

      reader.addEventListener("load", function () {
        image.src = String(reader.result);
        image.hidden = false;

        if (icon) {
          icon.hidden = true;
        }

        toast(
          translate("avatarSuccess") || "تم تحديث الصورة بنجاح.",
          "success"
        );
      });

      reader.addEventListener("error", function () {
        toast(
          translate("avatarReadError") || "تعذر قراءة الصورة.",
          "error"
        );
      });

      reader.readAsDataURL(file);
    });
  }

  /* =======================================================
     تنسيق الأرقام والعملة
     ======================================================= */

  function formatNumber(value) {
    const number = Number(value);

    if (!isFinite(number)) {
      return String(value);
    }

    try {
      return number.toLocaleString(
        getLanguage() === "ar" ? "ar-u-nu-latn" : "en-US"
      );
    } catch (error) {
      return String(number);
    }
  }

  /* =======================================================
     نوافذ منبثقة بسيطة
     ======================================================= */

  function setupDialog(dialogId, options) {
    const dialog = document.getElementById(dialogId);

    if (!dialog) {
      return null;
    }

    const settings = options || {};

    function open() {
      if (typeof settings.onOpen === "function") {
        settings.onOpen(dialog);
      }

      if (typeof dialog.showModal === "function") {
        dialog.showModal();
      } else {
        dialog.setAttribute("open", "");
      }
    }

    function close() {
      if (typeof dialog.close === "function") {
        dialog.close();
      } else {
        dialog.removeAttribute("open");
      }
    }

    each(
      dialog.querySelectorAll("[data-dialog-close]"),
      function (button) {
        button.addEventListener("click", function (event) {
          event.preventDefault();
          close();
        });
      }
    );

    /* الإغلاق عند الضغط خارج النافذة */
    dialog.addEventListener("click", function (event) {
      if (event.target === dialog) {
        close();
      }
    });

    each(
      document.querySelectorAll('[data-dialog-open="' + dialogId + '"]'),
      function (trigger) {
        trigger.addEventListener("click", function (event) {
          event.preventDefault();
          open();
        });
      }
    );

    return { element: dialog, open: open, close: close };
  }

  /* =======================================================
     التهيئة العامة
     ======================================================= */

  function setupHeaderPopovers() {
    const topbar = document.querySelector(".profile-topbar");

    if (!topbar || topbar.dataset.popoversReady === "true") {
      return;
    }

    const triggers = [
      {
        selector: '[data-i18n-aria="shoppingCart"]',
        type: "cart",
        icon: "bi-cart3"
      },
      {
        selector: '[data-i18n-aria="notifications"]',
        type: "notifications",
        icon: "bi-bell"
      }
    ];

    const copy = {
      ar: {
        cartTitle: "سلة المشتريات",
        cartCount: "منتجان",
        cartItemOne: "تيشيرت بتصميم عصري",
        cartItemTwo: "كوب بطباعة مخصصة",
        total: "الإجمالي",
        viewCart: "عرض السلة",
        notificationsTitle: "الإشعارات",
        notificationsCount: "3 جديدة",
        notificationOne: "تم تأكيد طلبك بنجاح",
        notificationOneTime: "منذ 5 دقائق",
        notificationTwo: "تصميمك أصبح جاهزًا للطباعة",
        notificationTwoTime: "منذ ساعة",
        notificationThree: "تمت إضافة أرباح جديدة إلى محفظتك",
        notificationThreeTime: "أمس",
        viewNotifications: "عرض كل الإشعارات"
      },
      en: {
        cartTitle: "Shopping cart",
        cartCount: "2 items",
        cartItemOne: "Modern design T-shirt",
        cartItemTwo: "Custom printed mug",
        total: "Total",
        viewCart: "View cart",
        notificationsTitle: "Notifications",
        notificationsCount: "3 new",
        notificationOne: "Your order has been confirmed",
        notificationOneTime: "5 minutes ago",
        notificationTwo: "Your design is ready for printing",
        notificationTwoTime: "1 hour ago",
        notificationThree: "New earnings were added to your wallet",
        notificationThreeTime: "Yesterday",
        viewNotifications: "View all notifications"
      }
    };

    const entries = [];

    function closeAll(except) {
      entries.forEach(function (entry) {
        if (entry === except) return;
        entry.trigger.setAttribute("aria-expanded", "false");
        entry.popover.hidden = true;
      });
    }

    function render(entry) {
      const text = copy[getLanguage()] || copy.ar;
      const originalHref = entry.trigger.getAttribute("href") || "#";

      if (entry.type === "cart") {
        entry.popover.setAttribute("aria-label", text.cartTitle);
        entry.popover.innerHTML =
          '<div class="profile-header-popover-head"><span class="profile-header-popover-title"><i class="bi bi-cart3" aria-hidden="true"></i>' + text.cartTitle + '</span><span class="profile-header-popover-count">' + text.cartCount + '</span></div>' +
          '<div class="profile-header-popover-list"><div class="profile-header-popover-item"><span class="profile-header-popover-item-icon is-purple"><i class="bi bi-bag" aria-hidden="true"></i></span><span><strong>' + text.cartItemOne + '</strong><small dir="ltr">24.00 ₪</small></span></div><div class="profile-header-popover-item"><span class="profile-header-popover-item-icon is-blue"><i class="bi bi-cup-hot" aria-hidden="true"></i></span><span><strong>' + text.cartItemTwo + '</strong><small dir="ltr">12.00 ₪</small></span></div></div>' +
          '<div class="profile-header-popover-total"><span>' + text.total + '</span><strong dir="ltr">36.00 ₪</strong></div>' +
          '<a class="profile-header-popover-footer" href="' + originalHref + '">' + text.viewCart + '<i class="bi bi-arrow-left" aria-hidden="true"></i></a>';
      } else {
        entry.popover.setAttribute("aria-label", text.notificationsTitle);
        entry.popover.innerHTML =
          '<div class="profile-header-popover-head"><span class="profile-header-popover-title"><i class="bi bi-bell" aria-hidden="true"></i>' + text.notificationsTitle + '</span><span class="profile-header-popover-count">' + text.notificationsCount + '</span></div>' +
          '<div class="profile-header-popover-list"><div class="profile-header-popover-item is-unread"><span class="profile-header-popover-item-icon is-green"><i class="bi bi-check2" aria-hidden="true"></i></span><span><strong>' + text.notificationOne + '</strong><small>' + text.notificationOneTime + '</small></span></div><div class="profile-header-popover-item is-unread"><span class="profile-header-popover-item-icon is-blue"><i class="bi bi-printer" aria-hidden="true"></i></span><span><strong>' + text.notificationTwo + '</strong><small>' + text.notificationTwoTime + '</small></span></div><div class="profile-header-popover-item"><span class="profile-header-popover-item-icon is-purple"><i class="bi bi-wallet2" aria-hidden="true"></i></span><span><strong>' + text.notificationThree + '</strong><small>' + text.notificationThreeTime + '</small></span></div></div>' +
          '<a class="profile-header-popover-footer" href="' + originalHref + '">' + text.viewNotifications + '<i class="bi bi-arrow-left" aria-hidden="true"></i></a>';
      }
    }

    triggers.forEach(function (config, index) {
      const trigger = topbar.querySelector(config.selector);

      if (!trigger) return;

      const wrapper = document.createElement("div");
      const popover = document.createElement("div");
      const popoverId = "profileHeaderPopover" + index;
      const entry = { trigger: trigger, popover: popover, type: config.type };

      wrapper.className = "profile-header-action";
      popover.className = "profile-header-popover";
      popover.id = popoverId;
      popover.hidden = true;
      popover.setAttribute("role", "dialog");

      trigger.parentNode.insertBefore(wrapper, trigger);
      wrapper.appendChild(trigger);
      wrapper.appendChild(popover);

      trigger.setAttribute("aria-haspopup", "dialog");
      trigger.setAttribute("aria-controls", popoverId);
      trigger.setAttribute("aria-expanded", "false");

      render(entry);
      entries.push(entry);

      trigger.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();

        const willOpen = popover.hidden;
        closeAll(entry);
        popover.hidden = !willOpen;
        trigger.setAttribute("aria-expanded", String(willOpen));
      });

      popover.addEventListener("click", function (event) {
        event.stopPropagation();
      });
    });

    document.addEventListener("click", function () {
      closeAll();
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") return;
      const openEntry = entries.find(function (entry) { return !entry.popover.hidden; });
      if (!openEntry) return;
      closeAll();
      openEntry.trigger.focus();
    });

    onLanguageChange(function () {
      entries.forEach(render);
      closeAll();
    });

    topbar.dataset.popoversReady = "true";
  }

  function init(options) {
    const settings = options || {};

    if (settings.sidebar) {
      sidebarSettings.desktopInitial =
        settings.sidebar.desktopInitial === "open" ? "open" : "stored";
      sidebarSettings.persist = settings.sidebar.persist !== false;
    } else {
      sidebarSettings.desktopInitial = "stored";
      sidebarSettings.persist = true;
    }

    if (settings.dictionary) {
      registerDictionary(settings.dictionary);
    }

    /* اللغة والوضع الليلي قبل أي شيء لتفادي الوميض */
    applyLanguage(readStorage(STORAGE.language) || "ar");
    applyTheme(readStorage(STORAGE.theme) || "light");

    setupSidebar();
    setupLogout();
    setupRetryButtons();
    setupAvatarUpload(settings.avatar);
    setupHeaderPopovers();

    const themeButton = document.getElementById("themeToggleButton");

    if (themeButton) {
      themeButton.addEventListener("click", function () {
        applyTheme(currentTheme() === "dark" ? "light" : "dark");
      });
    }

    const languageButton = document.getElementById("languageToggleButton");

    if (languageButton) {
      languageButton.addEventListener("click", function () {
        applyLanguage(getLanguage() === "ar" ? "en" : "ar");
      });
    }

    /* تفعيل مفاتيح التفضيلات مع رسالة تأكيد */
    each(
      document.querySelectorAll("[data-preference]"),
      function (input) {
        const storageKey =
          "palprints-preference-" + input.getAttribute("data-preference");

        const stored = readStorage(storageKey);

        if (stored !== null) {
          input.checked = stored === "true";
        }

        input.addEventListener("change", function () {
          writeStorage(storageKey, input.checked ? "true" : "false");

          const label =
            input.getAttribute("data-preference-label") ||
            translate("preferenceLabel") ||
            "";

          const status = input.checked
            ? translate("preferenceOn") || "تم التفعيل"
            : translate("preferenceOff") || "تم الإيقاف";

          toast(label ? label + " — " + status : status, "success");
        });
      }
    );
  }

  /* =======================================================
     الواجهة العامة
     ======================================================= */

  window.PalProfile = {
    init: init,

    /* الترجمة */
    registerDictionary: registerDictionary,
    applyLanguage: applyLanguage,
    applyTranslations: applyTranslations,
    onLanguageChange: onLanguageChange,
    getLanguage: getLanguage,
    translate: translate,

    /* المظهر */
    applyTheme: applyTheme,
    currentTheme: currentTheme,

    /* القائمة الجانبية */
    sidebar: sidebarApi,

    /* الحالات */
    setState: setState,
    getState: getState,
    loadSection: loadSection,
    onRetry: onRetry,

    /* الرسائل */
    toast: toast,

    /* البيانات الحساسة */
    maskAccountNumber: maskAccountNumber,
    maskIban: maskIban,

    /* أدوات */
    formatNumber: formatNumber,
    setupDialog: setupDialog,
    reducedMotion: reducedMotion
  };
})(window, document);
