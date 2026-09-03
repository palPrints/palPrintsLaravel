"use strict";

/* =========================================================
   PalPrints — Interactions
   حركات الأزرار وظهور العناصر — مشتركة بين كل الصفحات

   File: assets/js/interactions.js
   يرافق: assets/css/interactions.css

   لا يعتمد على أي ملف آخر، ولا يغيّر سلوك أي زر،
   وكل ما يفعله بصري بحت:

   - موجة ضغط تنطلق من موضع الإصبع أو المؤشر
   - انكماش عند الضغط وبروز خفيف عند الإفلات
   - ظهور متدرّج للبطاقات أثناء التمرير
   - عدّ تصاعدي لأرقام الإحصائيات
   - دخول متتابع لعناصر القائمة الجانبية والشريط العلوي

   يحترم إعداد «تقليل الحركة» في نظام المستخدم.
   ========================================================= */

(function (window, document) {
  /* =======================================================
     إعدادات عامة
     ======================================================= */

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

  function prefersReducedMotion() {
    return reducedMotion.matches;
  }

  /* العناصر التي تستحق حركة ضغط */

  const PRESSABLE = [
    ".profile-button",
    ".profile-action-button",
    ".profile-sidebar-link",
    ".profile-sidebar-logout",
    ".profile-sidebar-close",
    ".profile-sidebar-toggle",
    ".profile-dialog-close",
    ".profile-card-link",
    ".profile-avatar-upload",
    ".customer-address-action",
    ".customer-library-item",
    ".designer-portfolio-link",
    ".btn",
    ".btn-brand",
    ".btn-brand-outline",
    "button",
    "[role='button']"
  ].join(",");

  /* استثناءات: خلفية القائمة الجانبية زر يغطي الشاشة،
     ولا معنى لموجة ضغط فيه */

  const PRESSABLE_SKIP = [
    ".profile-sidebar-backdrop",
    "[data-no-press]"
  ].join(",");

  /* العناصر التي تظهر تدريجياً أثناء التمرير

     عنوان الصفحة ومسار التنقل (.profile-page-heading)
     مستثنيان عمداً: يبقيان ثابتين تماماً في كل الصفحات
     بلا ظهور تدريجي ولا ارتفاع عند مرور الماوس. */

  const REVEAL = [
    ".designer-approval-alert",
    ".profile-card",
    ".app-card"
  ].join(",");

  const REVEAL_STEP = 70;
  const REVEAL_MAX_DELAY = 280;

  const COUNTER_TIME = 900;

  /* =======================================================
     أدوات صغيرة
     ======================================================= */

  function each(list, callback) {
    Array.prototype.forEach.call(list || [], callback);
  }

  function matches(element, selector) {
    return (
      element &&
      element.nodeType === 1 &&
      typeof element.matches === "function" &&
      element.matches(selector)
    );
  }

  /* =======================================================
     تهيئة العناصر القابلة للضغط
     ======================================================= */

  function preparePressable(element) {
    if (!element || element.classList.contains("pal-pressable")) {
      return;
    }

    if (matches(element, PRESSABLE_SKIP)) {
      return;
    }

    element.classList.add("pal-pressable");

    /* الموجة تحتاج مرجع تموضع داخل العنصر نفسه،
       ولا نلمس العناصر التي تملك تموضعاً أصلاً
       حتى لا نكسر مكانها في الصفحة */

    let position = "static";

    try {
      position = window.getComputedStyle(element).position;
    } catch (error) {
      position = "static";
    }

    if (position === "static") {
      element.classList.add("pal-anchor");
    }
  }

  function scanPressable(root) {
    const scope = root && root.querySelectorAll ? root : document;

    if (matches(scope, PRESSABLE)) {
      preparePressable(scope);
    }

    each(scope.querySelectorAll(PRESSABLE), preparePressable);
  }

  /* =======================================================
     موجة الضغط
     ======================================================= */

  function spawnRipple(element, pointX, pointY) {
    if (prefersReducedMotion()) {
      return;
    }

    const rect = element.getBoundingClientRect();

    if (!rect.width || !rect.height) {
      return;
    }

    /* نصف القطر يغطي أبعد زاوية عن نقطة الضغط */

    const x = typeof pointX === "number" ? pointX - rect.left : rect.width / 2;
    const y = typeof pointY === "number" ? pointY - rect.top : rect.height / 2;

    const far = Math.max(
      Math.hypot(x, y),
      Math.hypot(rect.width - x, y),
      Math.hypot(x, rect.height - y),
      Math.hypot(rect.width - x, rect.height - y)
    );

    const size = far * 2;

    const ripple = document.createElement("span");

    ripple.className = "pal-ripple";
    ripple.style.width = size + "px";
    ripple.style.height = size + "px";
    ripple.style.left = x - far + "px";
    ripple.style.top = y - far + "px";

    ripple.addEventListener("animationend", function () {
      if (ripple.parentNode) {
        ripple.parentNode.removeChild(ripple);
      }
    });

    element.appendChild(ripple);

    /* شبكة أمان لو لم يصل حدث النهاية */

    window.setTimeout(function () {
      if (ripple.parentNode) {
        ripple.parentNode.removeChild(ripple);
      }
    }, 1200);
  }

  /* =======================================================
     الضغط والإفلات
     ======================================================= */

  let pressedElement = null;

  function isDisabled(element) {
    return (
      element.disabled === true ||
      element.getAttribute("aria-disabled") === "true"
    );
  }

  function press(element) {
    if (pressedElement && pressedElement !== element) {
      release(pressedElement);
    }

    pressedElement = element;

    element.classList.remove("is-popped");
    element.classList.add("is-pressed");
  }

  function release(element) {
    if (!element) {
      return;
    }

    if (!element.classList.contains("is-pressed")) {
      return;
    }

    element.classList.remove("is-pressed");

    if (prefersReducedMotion()) {
      return;
    }

    /* إعادة تشغيل حركة البروز في كل ضغطة */

    element.classList.remove("is-popped");

    void element.offsetWidth;

    element.classList.add("is-popped");

    window.setTimeout(function () {
      element.classList.remove("is-popped");
    }, 400);
  }

  function findPressable(target) {
    if (!target || typeof target.closest !== "function") {
      return null;
    }

    const element = target.closest(".pal-pressable");

    if (!element || isDisabled(element)) {
      return null;
    }

    return element;
  }

  function setupPressEvents() {
    document.addEventListener(
      "pointerdown",
      function (event) {
        /* زر الفأرة الأيسر واللمس والقلم فقط */
        if (event.button && event.button !== 0) {
          return;
        }

        const element = findPressable(event.target);

        if (!element) {
          return;
        }

        press(element);
        spawnRipple(element, event.clientX, event.clientY);
      },
      true
    );

    /* الإفلات في أي مكان ينهي حالة الضغط
       — pointerleave مستثنى لأنه يُطلق عند التنقل بين
       أبناء الزر نفسه فينهي الضغط قبل أوانه */

    ["pointerup", "pointercancel", "blur"].forEach(
      function (name) {
        document.addEventListener(
          name,
          function () {
            release(pressedElement);
            pressedElement = null;
          },
          true
        );
      }
    );

    /* التفعيل بلوحة المفاتيح: مسافة أو Enter */

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Enter" && event.key !== " ") {
        return;
      }

      if (event.repeat) {
        return;
      }

      const element = findPressable(document.activeElement);

      if (!element) {
        return;
      }

      press(element);
      spawnRipple(element);
    });

    document.addEventListener("keyup", function (event) {
      if (event.key !== "Enter" && event.key !== " ") {
        return;
      }

      release(pressedElement);
      pressedElement = null;
    });
  }

  /* =======================================================
     العدّ التصاعدي للأرقام
     ======================================================= */

  function animateCounter(element) {
    if (element.getAttribute("data-counter-done") === "true") {
      return;
    }

    element.setAttribute("data-counter-done", "true");

    const raw = String(element.getAttribute("data-counter") || "").trim();
    const target = Number(raw);

    if (!isFinite(target)) {
      return;
    }

    if (prefersReducedMotion()) {
      element.textContent = raw;
      return;
    }

    const dot = raw.indexOf(".");
    const decimals = dot === -1 ? 0 : raw.length - dot - 1;

    const start =
      typeof window.performance !== "undefined" &&
      typeof window.performance.now === "function"
        ? window.performance.now()
        : null;

    if (start === null) {
      element.textContent = raw;
      return;
    }

    function step(now) {
      const progress = Math.min((now - start) / COUNTER_TIME, 1);

      /* تباطؤ في النهاية ليبدو العدّ طبيعياً */
      const eased = 1 - Math.pow(1 - progress, 3);

      element.textContent = (target * eased).toFixed(decimals);

      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        element.textContent = raw;
      }
    }

    element.textContent = (0).toFixed(decimals);

    window.requestAnimationFrame(step);
  }

  function runCounters(scope) {
    each(scope.querySelectorAll("[data-counter]"), animateCounter);
  }

  /* =======================================================
     الظهور أثناء التمرير
     ======================================================= */

  function revealNow(element) {
    element.classList.add("is-revealed");

    runCounters(element);
  }

  function setupReveal() {
    /* العناصر داخل النوافذ المنبثقة تظهر مع النافذة نفسها */

    const targets = [];

    each(document.querySelectorAll(REVEAL), function (element) {
      if (element.closest("dialog")) {
        return;
      }

      targets.push(element);
    });

    if (!targets.length) {
      return;
    }

    const supported =
      typeof window.IntersectionObserver === "function" &&
      !prefersReducedMotion();

    if (!supported) {
      targets.forEach(revealNow);
      return;
    }

    /* تأخير متدرّج داخل كل صف حتى تتتابع البطاقات */

    targets.forEach(function (element) {
      const siblings = element.parentNode
        ? element.parentNode.children
        : [element];

      const index = Array.prototype.indexOf.call(siblings, element);

      const delay = Math.min(index * REVEAL_STEP, REVEAL_MAX_DELAY);

      element.style.setProperty("--pal-delay", delay + "ms");

      element.classList.add("pal-reveal", "pal-hover-lift");
    });

    const observer = new window.IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }

          revealNow(entry.target);

          observer.unobserve(entry.target);
        });
      },
      {
        rootMargin: "0px 0px -8% 0px",
        threshold: 0.08
      }
    );

    targets.forEach(function (element) {
      observer.observe(element);
    });
  }

  /* =======================================================
     دخول الصفحة
     ======================================================= */

  function setupEntrance() {
    if (prefersReducedMotion()) {
      return;
    }

    /* روابط القائمة الجانبية تتتابع من الأعلى للأسفل */

    each(
      document.querySelectorAll(".profile-sidebar-nav > *"),
      function (element, index) {
        element.style.setProperty("--pal-index", String(index));
        element.classList.add("pal-enter");
      }
    );

    /* أزرار الشريط العلوي */

    each(
      document.querySelectorAll(".profile-topbar-actions > *"),
      function (element, index) {
        element.style.setProperty("--pal-index", String(index));
        element.classList.add("pal-enter-fade");
      }
    );
  }

  /* =======================================================
     المحتوى الذي يُبنى بالجافاسكربت
     ======================================================= */

  /* قائمة العناوين وبطاقات المهارات تُرسم بعد التحميل،
     فنراقب الصفحة لنمنح أزرارها الجديدة الحركة نفسها */

  function watchNewContent() {
    if (typeof window.MutationObserver !== "function") {
      return;
    }

    const observer = new window.MutationObserver(function (records) {
      records.forEach(function (record) {
        each(record.addedNodes, function (node) {
          if (node.nodeType !== 1) {
            return;
          }

          scanPressable(node);
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  /* =======================================================
     التهيئة
     ======================================================= */

  function init() {
    scanPressable(document);
    setupPressEvents();
    setupEntrance();
    setupReveal();
    watchNewContent();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(window, document);
