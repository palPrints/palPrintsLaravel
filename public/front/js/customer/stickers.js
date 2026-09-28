(function () {
  "use strict";

  const PAGE_SIZE = 8;
  const MAX_FILE_SIZE = 10 * 1024 * 1024;
  const CART_STORAGE_KEY = "stickerCart";
  const FAVORITES_STORAGE_KEY = "palprints-sticker-favorites";
  const ALLOWED_TYPES = new Set(["image/png", "image/jpeg", "image/webp", "image/svg+xml"]);
  const ALLOWED_EXTENSIONS = new Set(["png", "jpg", "jpeg", "webp", "svg"]);

  const categoryMeta = {
    quotes: { label: "خطوط وعبارات", title: "تصاميم خطوط وعبارات جاهزة" },
    colorful: { label: "ملوّنة ومعبّأة", title: "تصاميم ملوّنة ومعبّأة جاهزة" },
    illustrated: { label: "مخططة ومرسومة", title: "تصاميم مخططة ومرسومة جاهزة" },
    simple: { label: "بسيطة وأيقونات", title: "تصاميم بسيطة وأيقونات جاهزة" },
    "3d": { label: "3D", title: "تصاميم ملصقات 3D جاهزة" }
  };

  const createDesign = (id, category, title, image, alt) => ({ id, category, title, image, alt });

  const catalog = {
    quotes: [
      createDesign("quotes-love", "quotes", "حب", "assets/images/stickers/quotes/love.svg", "ملصق عربي بكلمة حب باللون الأحمر"),
      createDesign("quotes-hope", "quotes", "أمل", "assets/images/stickers/quotes/hope.svg", "ملصق عربي بكلمة أمل وزخرفة نباتية"),
      createDesign("quotes-palestine", "quotes", "فلسطين", "assets/images/stickers/quotes/palestine.svg", "ملصق عربي بكلمة فلسطين وألوان العلم الفلسطيني"),
      createDesign("quotes-be-you", "quotes", "كن أنت", "assets/images/stickers/quotes/be-you.svg", "ملصق عربي بعبارة كن أنت"),
      createDesign("quotes-gaza", "quotes", "غزة", "assets/images/stickers/quotes/gaza.svg", "ملصق عربي بكلمة غزة وزخرفة نباتية"),
      createDesign("quotes-peace", "quotes", "سلام", "assets/images/stickers/quotes/peace.svg", "ملصق عربي بكلمة سلام ورمز حمامة"),
      createDesign("quotes-passion", "quotes", "شغف", "assets/images/stickers/quotes/passion.svg", "ملصق عربي بكلمة شغف"),
      createDesign("quotes-dream", "quotes", "حلم", "assets/images/stickers/quotes/dream.svg", "ملصق عربي بكلمة حلم وزخرفة سحاب"),
      createDesign("quotes-smile", "quotes", "ابتسم", "assets/images/stickers/quotes/smile.svg", "ملصق عربي بكلمة ابتسم ووجه مبتسم"),
      createDesign("quotes-difference", "quotes", "اصنع فرقًا", "assets/images/stickers/quotes/make-a-difference.svg", "ملصق عربي بعبارة اصنع فرقًا"),
      createDesign("quotes-never-give-up", "quotes", "لا تستسلم", "assets/images/stickers/quotes/never-give-up.svg", "ملصق عربي بعبارة لا تستسلم"),
      createDesign("quotes-possible", "quotes", "كل شيء ممكن", "assets/images/stickers/quotes/everything-is-possible.svg", "ملصق عربي بعبارة كل شيء ممكن"),
      createDesign("quotes-you-can", "quotes", "أنت تستطيع", "assets/images/stickers/quotes/you-can.svg", "ملصق عربي بعبارة أنت تستطيع"),
      createDesign("quotes-be-kind", "quotes", "كن لطيفًا", "assets/images/stickers/quotes/be-kind.svg", "ملصق عربي بعبارة كن لطيفًا"),
      createDesign("quotes-step", "quotes", "خطوة بخطوة", "assets/images/stickers/quotes/step-by-step.svg", "ملصق عربي بعبارة خطوة بخطوة"),
      createDesign("quotes-freedom", "quotes", "حرية", "assets/images/stickers/quotes/freedom.svg", "ملصق عربي بكلمة حرية"),
      createDesign("quotes-create", "quotes", "أبدع", "assets/images/stickers/quotes/create.svg", "ملصق عربي بكلمة أبدع"),
      createDesign("quotes-good-morning", "quotes", "صباح الخير", "assets/images/stickers/quotes/good-morning.svg", "ملصق عربي بعبارة صباح الخير"),
    ],
    colorful: [
      createDesign("colorful-rainbow", "colorful", "قوس قزح", "assets/images/stickers/colorful/rainbow.svg", "ملصق قوس قزح ملوّن"),
      createDesign("colorful-butterfly", "colorful", "فراشة زرقاء", "assets/images/stickers/colorful/blue-butterfly.svg", "ملصق فراشة زرقاء ملوّنة"),
      createDesign("colorful-sunflower", "colorful", "عباد الشمس", "assets/images/stickers/colorful/sunflower.svg", "ملصق زهرة عباد الشمس"),
      createDesign("colorful-watermelon", "colorful", "بطيخ صيفي", "assets/images/stickers/colorful/watermelon.svg", "ملصق شريحة بطيخ صيفية"),
      createDesign("colorful-strawberry", "colorful", "فراولة مرحة", "assets/images/stickers/colorful/strawberry.svg", "ملصق حبة فراولة مرحة"),
      createDesign("colorful-cupcake", "colorful", "كب كيك", "assets/images/stickers/colorful/cupcake.svg", "ملصق كب كيك ملوّن"),
      createDesign("colorful-ice-cream", "colorful", "آيس كريم", "assets/images/stickers/colorful/ice-cream.svg", "ملصق آيس كريم ملوّن"),
      createDesign("colorful-balloons", "colorful", "بالونات", "assets/images/stickers/colorful/balloons.svg", "ملصق بالونات احتفالية ملوّنة"),
      createDesign("colorful-flamingo", "colorful", "فلامنغو", "assets/images/stickers/colorful/flamingo.svg", "ملصق طائر فلامنغو وردي"),
      createDesign("colorful-cactus", "colorful", "صبار مزهر", "assets/images/stickers/colorful/cactus.svg", "ملصق صبار أخضر مزهر"),
      createDesign("colorful-mushroom", "colorful", "فطر ملوّن", "assets/images/stickers/colorful/mushroom.svg", "ملصق فطر ملوّن"),
      createDesign("colorful-planet", "colorful", "كوكب وحلقات", "assets/images/stickers/colorful/ringed-planet.svg", "ملصق كوكب ملوّن تحيط به حلقات"),
      createDesign("colorful-fish", "colorful", "سمكة استوائية", "assets/images/stickers/colorful/tropical-fish.svg", "ملصق سمكة استوائية ملوّنة"),
      createDesign("colorful-parrot", "colorful", "ببغاء", "assets/images/stickers/colorful/parrot.svg", "ملصق ببغاء ملوّن"),
      createDesign("colorful-bicycle", "colorful", "دراجة ملوّنة", "assets/images/stickers/colorful/bicycle.svg", "ملصق دراجة ملوّنة"),
      createDesign("colorful-lemon", "colorful", "ليمون", "assets/images/stickers/colorful/lemon.svg", "ملصق حبة ليمون صفراء"),
      createDesign("colorful-cherries", "colorful", "كرز", "assets/images/stickers/colorful/cherries.svg", "ملصق حبات كرز حمراء"),
      createDesign("colorful-palette", "colorful", "لوحة ألوان", "assets/images/stickers/colorful/artist-palette.svg", "ملصق لوحة ألوان فنية"),
    ],
    illustrated: [
      createDesign("illustrated-coffee", "illustrated", "فنجان قهوة", "assets/images/stickers/illustrated/coffee-cup.svg", "ملصق خطي لفنجان قهوة"),
      createDesign("illustrated-book", "illustrated", "كتاب وزهرة", "assets/images/stickers/illustrated/open-book.svg", "ملصق مرسوم لكتاب مفتوح وزهرة"),
      createDesign("illustrated-pen", "illustrated", "ريشة حبر", "assets/images/stickers/illustrated/ink-pen.svg", "ملصق خطي لريشة حبر"),
      createDesign("illustrated-camera", "illustrated", "كاميرا قديمة", "assets/images/stickers/illustrated/vintage-camera.svg", "ملصق مرسوم لكاميرا قديمة"),
      createDesign("illustrated-cassette", "illustrated", "كاسيت", "assets/images/stickers/illustrated/cassette.svg", "ملصق خطي لشريط كاسيت"),
      createDesign("illustrated-skate", "illustrated", "حذاء تزلج", "assets/images/stickers/illustrated/roller-skate.svg", "ملصق مرسوم لحذاء تزلج"),
      createDesign("illustrated-luggage", "illustrated", "حقيبة سفر", "assets/images/stickers/illustrated/luggage.svg", "ملصق خطي لحقيبة سفر"),
      createDesign("illustrated-camping", "illustrated", "خيمة وجبل", "assets/images/stickers/illustrated/camping.svg", "ملصق مرسوم لخيمة وجبل"),
      createDesign("illustrated-telescope", "illustrated", "تلسكوب", "assets/images/stickers/illustrated/telescope.svg", "ملصق خطي لتلسكوب"),
      createDesign("illustrated-wave", "illustrated", "موجة وقمر", "assets/images/stickers/illustrated/wave-moon.svg", "ملصق مرسوم لموجة وقمر"),
      createDesign("illustrated-plant", "illustrated", "نبتة منزلية", "assets/images/stickers/illustrated/potted-plant.svg", "ملصق خطي لنبتة منزلية"),
      createDesign("illustrated-cat", "illustrated", "قطة نائمة", "assets/images/stickers/illustrated/sleeping-cat.svg", "ملصق مرسوم لقطة نائمة"),
      createDesign("illustrated-dog", "illustrated", "كلب مرح", "assets/images/stickers/illustrated/happy-dog.svg", "ملصق مرسوم لكلب مرح"),
      createDesign("illustrated-bike", "illustrated", "دراجة كلاسيكية", "assets/images/stickers/illustrated/classic-bike.svg", "ملصق خطي لدراجة كلاسيكية"),
      createDesign("illustrated-shell", "illustrated", "صدفة بحرية", "assets/images/stickers/illustrated/shell.svg", "ملصق مرسوم لصدفة بحرية"),
      createDesign("illustrated-bulb", "illustrated", "مصباح فكرة", "assets/images/stickers/illustrated/idea-bulb.svg", "ملصق خطي لمصباح فكرة"),
      createDesign("illustrated-kite", "illustrated", "طائرة ورقية", "assets/images/stickers/illustrated/kite.svg", "ملصق مرسوم لطائرة ورقية"),
      createDesign("illustrated-map", "illustrated", "خريطة كنز", "assets/images/stickers/illustrated/treasure-map.svg", "ملصق مرسوم لخريطة كنز"),
    ],
    simple: [
      createDesign("simple-house", "simple", "منزل", "assets/images/stickers/simple/house.svg", "أيقونة منزل بسيطة"),
      createDesign("simple-user", "simple", "مستخدم", "assets/images/stickers/simple/user.svg", "أيقونة مستخدم بسيطة"),
      createDesign("simple-heart", "simple", "قلب", "assets/images/stickers/simple/heart.svg", "أيقونة قلب بسيطة"),
      createDesign("simple-bell", "simple", "جرس", "assets/images/stickers/simple/bell.svg", "أيقونة جرس بسيطة"),
      createDesign("simple-cart", "simple", "سلة تسوق", "assets/images/stickers/simple/cart.svg", "أيقونة سلة تسوق بسيطة"),
      createDesign("simple-settings", "simple", "إعدادات", "assets/images/stickers/simple/settings.svg", "أيقونة إعدادات بسيطة"),
      createDesign("simple-calendar", "simple", "تقويم", "assets/images/stickers/simple/calendar.svg", "أيقونة تقويم بسيطة"),
      createDesign("simple-message", "simple", "رسالة", "assets/images/stickers/simple/message.svg", "أيقونة رسالة بسيطة"),
      createDesign("simple-music", "simple", "نغمة", "assets/images/stickers/simple/music-note.svg", "أيقونة نغمة موسيقية بسيطة"),
      createDesign("simple-check", "simple", "علامة صح", "assets/images/stickers/simple/check.svg", "أيقونة علامة صح بسيطة"),
      createDesign("simple-star", "simple", "نجمة", "assets/images/stickers/simple/star.svg", "أيقونة نجمة بسيطة"),
      createDesign("simple-sun", "simple", "شمس", "assets/images/stickers/simple/sun.svg", "أيقونة شمس بسيطة"),
      createDesign("simple-moon", "simple", "قمر", "assets/images/stickers/simple/moon.svg", "أيقونة قمر بسيطة"),
      createDesign("simple-cloud", "simple", "غيمة", "assets/images/stickers/simple/cloud.svg", "أيقونة غيمة بسيطة"),
      createDesign("simple-leaf", "simple", "ورقة نبات", "assets/images/stickers/simple/leaf.svg", "أيقونة ورقة نبات بسيطة"),
      createDesign("simple-lightning", "simple", "برق", "assets/images/stickers/simple/lightning.svg", "أيقونة برق بسيطة"),
      createDesign("simple-lock", "simple", "قفل", "assets/images/stickers/simple/lock.svg", "أيقونة قفل بسيطة"),
      createDesign("simple-location", "simple", "موقع", "assets/images/stickers/simple/location.svg", "أيقونة موقع بسيطة"),
    ],
    "3d": [
      createDesign("3d-rocket", "3d", "صاروخ", "assets/images/stickers/3d/rocket.png", "ملصق ثلاثي الأبعاد لصاروخ"),
      createDesign("3d-controller", "3d", "يد تحكم", "assets/images/stickers/3d/game-controller.png", "ملصق ثلاثي الأبعاد ليد تحكم"),
      createDesign("3d-sunglasses", "3d", "نظارة شمسية", "assets/images/stickers/3d/sunglasses.png", "ملصق ثلاثي الأبعاد لنظارة شمسية"),
      createDesign("3d-headphones", "3d", "سماعات", "assets/images/stickers/3d/headphones.png", "ملصق ثلاثي الأبعاد لسماعات"),
      createDesign("3d-camera", "3d", "كاميرا", "assets/images/stickers/3d/camera.png", "ملصق ثلاثي الأبعاد لكاميرا"),
      createDesign("3d-donut", "3d", "دونات", "assets/images/stickers/3d/donut.png", "ملصق ثلاثي الأبعاد لحبة دونات"),
      createDesign("3d-globe", "3d", "كرة أرضية", "assets/images/stickers/3d/globe.png", "ملصق ثلاثي الأبعاد لكرة أرضية"),
      createDesign("3d-astronaut", "3d", "رائد فضاء", "assets/images/stickers/3d/astronaut.png", "ملصق ثلاثي الأبعاد لرائد فضاء"),
      createDesign("3d-robot", "3d", "روبوت", "assets/images/stickers/3d/robot.png", "ملصق ثلاثي الأبعاد لروبوت"),
      createDesign("3d-gift", "3d", "هدية", "assets/images/stickers/3d/gift.png", "ملصق ثلاثي الأبعاد لهدية"),
      createDesign("3d-trophy", "3d", "كأس", "assets/images/stickers/3d/trophy.png", "ملصق ثلاثي الأبعاد لكأس"),
      createDesign("3d-fire", "3d", "شعلة", "assets/images/stickers/3d/fire.png", "ملصق ثلاثي الأبعاد لشعلة"),
      createDesign("3d-crown", "3d", "تاج", "assets/images/stickers/3d/crown.png", "ملصق ثلاثي الأبعاد لتاج"),
      createDesign("3d-balloon", "3d", "بالون", "assets/images/stickers/3d/balloon.png", "ملصق ثلاثي الأبعاد لبالون"),
      createDesign("3d-shoe", "3d", "حذاء رياضي", "assets/images/stickers/3d/running-shoe.png", "ملصق ثلاثي الأبعاد لحذاء رياضي"),
      createDesign("3d-speaker", "3d", "مكبر صوت", "assets/images/stickers/3d/loudspeaker.png", "ملصق ثلاثي الأبعاد لمكبر صوت"),
      createDesign("3d-cube", "3d", "مكعب سحري", "assets/images/stickers/3d/game-die.png", "ملصق ثلاثي الأبعاد لمكعب سحري"),
      createDesign("3d-alien", "3d", "كائن فضائي", "assets/images/stickers/3d/alien.png", "ملصق ثلاثي الأبعاد لكائن فضائي"),
    ]
  };

  const designById = new Map(
    Object.values(catalog).flat().map((design) => [design.id, design])
  );

  const state = {
    category: "brand",
    page: 1,
    query: "",
    quantity: 50,
    size: "10x10",
    shape: "custom",
    paper: "glossy",
    protection: "protected",
    image: null
  };

  const elements = {
    form: document.getElementById("productSearchForm"),
    search: document.getElementById("productSearch"),
    tabs: Array.from(document.querySelectorAll(".sticker-tab")),
    brandPanel: document.getElementById("brandPanel"),
    readyPanel: document.getElementById("readyPanel"),
    readyTitle: document.getElementById("readyTitle"),
    grid: document.getElementById("productGrid"),
    empty: document.getElementById("productsEmpty"),
    pagination: document.getElementById("stickersPagination"),
    count: document.getElementById("catalogCount"),
    brandExamples: Array.from(document.querySelectorAll(".brand-example")),
    brandEmpty: document.getElementById("brandEmpty"),
    fileInput: document.getElementById("stickerFile"),
    dropzone: document.getElementById("stickerDropzone"),
    uploadStatus: document.getElementById("uploadStatus"),
    preview: document.getElementById("stickerPreview"),
    previewFrame: document.getElementById("stickerPreviewFrame"),
    previewCaption: document.getElementById("previewCaption"),
    quantityInput: document.getElementById("stickerQuantity"),
    customizer: document.getElementById("stickerCustomizer"),
    cartButton: document.getElementById("cartButton"),
    cartCount: document.getElementById("cartCount"),
    fallbackToast: document.getElementById("stickerToast")
  };

  if (!elements.form || !elements.grid || !elements.brandPanel || !elements.readyPanel) return;

  let stickerCart = readCart();
  let favorites = readFavorites();
  let previewObjectUrl = null;
  let fallbackToastTimer = 0;
  let dragDepth = 0;
  let shapeAnimationTimer = 0;
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function normalize(value) {
    return String(value || "")
      .toLocaleLowerCase("ar")
      .replace(/[أإآ]/g, "ا")
      .replace(/ة/g, "ه")
      .replace(/ى/g, "ي")
      .replace(/[\u064B-\u065F\u0670\u06D6-\u06ED]/g, "")
      .replace(/\u0640/g, "")
      .trim();
  }

  function readCart() {
    try {
      const stored = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || "[]");
      return Array.isArray(stored) ? stored : [];
    } catch (_) {
      return [];
    }
  }

  function readFavorites() {
    try {
      const stored = JSON.parse(localStorage.getItem(FAVORITES_STORAGE_KEY) || "[]");
      return new Set(Array.isArray(stored) ? stored.filter((id) => designById.has(id)) : []);
    } catch (_) {
      return new Set();
    }
  }

  function saveFavorites() {
    try {
      localStorage.setItem(FAVORITES_STORAGE_KEY, JSON.stringify(Array.from(favorites)));
    } catch (_) {
      fallbackToast("تم تحديث المفضلة لهذه الجلسة، لكن تعذّر حفظها على الجهاز.", "exclamation-triangle");
    }
  }

  function saveCart() {
    try {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(stickerCart));
      return true;
    } catch (_) {
      fallbackToast("تمت الإضافة لهذه الجلسة، لكن تعذّر حفظ السلة على الجهاز.", "exclamation-triangle");
      return false;
    }
  }

  function updateCartCount() {
    const count = stickerCart.length;
    elements.cartCount.textContent = count > 99 ? "99+" : String(count);
    elements.cartCount.hidden = count === 0;
    elements.cartButton.setAttribute(
      "aria-label",
      count ? `سلة التسوق، ${count} طلب ملصقات` : "سلة التسوق، فارغة"
    );
  }

  function fallbackToast(message, icon = "check-circle") {
    if (!elements.fallbackToast) return;
    const iconElement = elements.fallbackToast.querySelector("i");
    const textElement = elements.fallbackToast.querySelector("span");
    if (iconElement) iconElement.className = `bi bi-${icon}`;
    if (textElement) textElement.textContent = message;
    window.clearTimeout(fallbackToastTimer);
    elements.fallbackToast.classList.add("is-visible");
    fallbackToastTimer = window.setTimeout(() => {
      elements.fallbackToast.classList.remove("is-visible");
    }, 2800);
  }

  function announceCart(message) {
    if (window.PalPrintNotifications && typeof window.PalPrintNotifications.add === "function") {
      window.PalPrintNotifications.add(message, "cart-check");
      return;
    }
    fallbackToast(message, "cart-check");
  }

  function makeCartId(prefix) {
    if (window.crypto && typeof window.crypto.randomUUID === "function") {
      return `${prefix}-${window.crypto.randomUUID()}`;
    }
    return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  }

  function reveal(element) {
    if (!element) return;
    element.classList.add("is-revealed");

    if (element.classList.contains("sticker-design-card")) {
      window.setTimeout(() => {
        if (element.isConnected) element.classList.add("is-floating");
      }, 700);
    }
  }

  function revealSequence(elementsToReveal, delay = 0, step = 140) {
    const nodes = Array.from(elementsToReveal).filter(Boolean);
    nodes.forEach((node) => node.classList.remove("is-revealed", "is-floating"));
    if (!nodes.length || reducedMotion) return;
    void nodes[0].offsetWidth;
    nodes.forEach((node, index) => window.setTimeout(() => reveal(node), delay + index * step));
  }

  function revealCardRows(delay = 0) {
    const rows = [];
    const startedAt = performance.now();

    Array.from(elements.grid.querySelectorAll(".sticker-design-card")).forEach((card) => {
      const top = card.getBoundingClientRect().top;
      let row = rows.find((group) => Math.abs(group.top - top) < 8);
      if (!row) {
        row = { top, cards: [] };
        rows.push(row);
      }
      row.cards.push(card);
    });

    if (!rows.length || reducedMotion) return;
    window.setTimeout(() => rows[0].cards.forEach(reveal), delay);

    rows.slice(1).forEach((row, index) => {
      const revealRow = () => {
        const earliestReveal = delay + (index + 1) * 600;
        const remainingDelay = Math.max(100, earliestReveal - (performance.now() - startedAt));
        window.setTimeout(() => row.cards.forEach(reveal), remainingDelay);
      };

      if (!("IntersectionObserver" in window)) {
        revealRow();
        return;
      }

      const observer = new IntersectionObserver((entries) => {
        if (!entries.some((entry) => entry.isIntersecting)) return;
        revealRow();
        observer.disconnect();
      }, { threshold: .12, rootMargin: "0px 0px -8% 0px" });
      observer.observe(row.cards[0]);
    });
  }

  function makeIconButton(label, icon, options = {}) {
    const button = document.createElement("button");
    button.type = "button";
    button.setAttribute("aria-label", label);
    if (options.disabled) button.disabled = true;
    if (options.current) button.setAttribute("aria-current", "page");
    if (options.page) button.dataset.page = String(options.page);
    if (options.action) button.dataset.pageAction = options.action;
    if (icon) {
      const iconElement = document.createElement("i");
      iconElement.className = `bi bi-${icon}`;
      iconElement.setAttribute("aria-hidden", "true");
      button.append(iconElement);
    } else {
      button.textContent = label;
    }
    return button;
  }

  function renderPagination(pageCount) {
    elements.pagination.replaceChildren();
    if (pageCount <= 1) {
      elements.pagination.hidden = true;
      return;
    }

    elements.pagination.hidden = false;
    elements.pagination.append(
      makeIconButton("الصفحة السابقة", "chevron-right", {
        action: "previous",
        disabled: state.page === 1
      })
    );

    for (let page = 1; page <= pageCount; page += 1) {
      elements.pagination.append(makeIconButton(String(page), "", {
        page,
        current: page === state.page
      }));
    }

    elements.pagination.append(
      makeIconButton("الصفحة التالية", "chevron-left", {
        action: "next",
        disabled: state.page === pageCount
      })
    );
  }

  function createCard(design, index) {
    const card = document.createElement("article");
    const media = document.createElement("div");
    const image = document.createElement("img");
    const favorite = document.createElement("button");
    const favoriteIcon = document.createElement("i");
    const body = document.createElement("div");
    const title = document.createElement("h3");
    const meta = document.createElement("div");
    const quantity = document.createElement("span");
    const quantityIcon = document.createElement("i");
    const price = document.createElement("span");
    const priceValue = document.createElement("strong");
    const button = document.createElement("button");
    const icon = document.createElement("i");

    card.className = "product-card sticker-design-card";
    card.dataset.designId = design.id;
    card.dataset.order = String(index + 1);
    media.className = "product-card__media sticker-design-media";
    image.src = design.image;
    image.alt = design.alt;
    image.loading = "lazy";
    image.decoding = "async";
    image.addEventListener("error", () => {
      if (image.dataset.fallbackApplied === "true") return;
      image.dataset.fallbackApplied = "true";
      image.src = "assets/images/icons8-sticker-48.png";
      image.alt = "تعذّر تحميل صورة التصميم";
    });

    favorite.className = "sticker-favorite pal-pressable";
    favorite.type = "button";
    favorite.dataset.favoriteDesign = design.id;
    favorite.setAttribute("aria-pressed", String(favorites.has(design.id)));
    favorite.setAttribute(
      "aria-label",
      `${favorites.has(design.id) ? "إزالة" : "إضافة"} تصميم ${design.title} ${favorites.has(design.id) ? "من" : "إلى"} المفضلة`
    );
    favoriteIcon.className = `bi bi-heart${favorites.has(design.id) ? "-fill" : ""}`;
    favoriteIcon.setAttribute("aria-hidden", "true");
    favorite.append(favoriteIcon);

    body.className = "product-card__body sticker-design-body";
    title.className = "sticker-design-title";
    title.textContent = design.title;
    meta.className = "sticker-design-meta";
    quantityIcon.className = "bi bi-layers";
    quantityIcon.setAttribute("aria-hidden", "true");
    quantity.append(quantityIcon, document.createTextNode("30 ستيكر"));
    price.append(document.createTextNode("السعر "));
    priceValue.textContent = "10 شيكل";
    price.append(priceValue);
    meta.append(quantity, price);
    button.className = "sticker-design-button pal-pressable";
    button.type = "button";
    button.dataset.addDesign = design.id;
    button.setAttribute("aria-label", `اختر تصميم ${design.title}`);
    icon.className = "bi bi-cart-plus";
    icon.setAttribute("aria-hidden", "true");
    button.append(icon, document.createTextNode("اختر التصميم"));
    media.append(image, favorite);
    body.append(title, meta, button);
    card.append(media, body);
    return card;
  }

  function filteredDesigns() {
    const query = normalize(state.query);
    const designs = catalog[state.category] || [];
    if (!query) return designs;
    return designs.filter((design) => normalize(`${design.title} ${design.alt}`).includes(query));
  }

  function renderCatalog() {
    const designs = filteredDesigns();
    const pageCount = Math.ceil(designs.length / PAGE_SIZE);
    if (pageCount && state.page > pageCount) state.page = pageCount;
    if (!pageCount) state.page = 1;
    const start = (state.page - 1) * PAGE_SIZE;
    const visible = designs.slice(start, start + PAGE_SIZE);
    const fragment = document.createDocumentFragment();

    visible.forEach((design, index) => fragment.append(createCard(design, index)));
    elements.grid.replaceChildren(fragment);
    elements.empty.hidden = designs.length > 0;
    elements.grid.hidden = designs.length === 0;
    renderPagination(pageCount);

    if (!designs.length) {
      elements.count.textContent = "لا توجد نتائج";
    } else {
      const end = Math.min(start + PAGE_SIZE, designs.length);
      elements.count.textContent = `عرض ${start + 1}–${end} من ${designs.length} تصميمًا`;
    }

    if (!reducedMotion) {
      window.requestAnimationFrame(() => {
        revealSequence([elements.readyPanel.querySelector(".ready-panel__header")], 0);
        revealCardRows(180);
      });
    }
  }

  function filterBrandExamples() {
    const query = normalize(state.query);
    let visibleCount = 0;
    elements.brandExamples.forEach((example) => {
      const searchable = normalize(`${example.dataset.search || ""} ${example.textContent}`);
      const isVisible = !query || searchable.includes(query);
      example.hidden = !isVisible;
      if (isVisible) visibleCount += 1;
    });
    elements.brandEmpty.hidden = visibleCount > 0;
  }

  function setCategory(tab, shouldFocus = false) {
    const category = tab.dataset.category;
    state.category = category;
    state.page = 1;

    elements.tabs.forEach((item) => {
      const selected = item === tab;
      item.classList.toggle("is-active", selected);
      item.setAttribute("aria-selected", String(selected));
      item.tabIndex = selected ? 0 : -1;
    });

    const isBrand = category === "brand";
    elements.brandPanel.hidden = !isBrand;
    elements.readyPanel.hidden = isBrand;

    if (isBrand) {
      filterBrandExamples();
      revealSequence(
        elements.brandPanel.querySelectorAll(".stickers-upload-card, .brand-examples-section, .stickers-customizer"),
        80,
        220
      );
    } else {
      elements.readyTitle.textContent = categoryMeta[category].title;
      elements.readyPanel.setAttribute("aria-labelledby", tab.id);
      renderCatalog();
    }

    if (shouldFocus) tab.focus();
  }

  function initTabs() {
    elements.tabs.forEach((tab, index) => {
      tab.addEventListener("click", () => setCategory(tab));
      tab.addEventListener("keydown", (event) => {
        let nextIndex = index;
        if (event.key === "ArrowRight") nextIndex = index - 1;
        else if (event.key === "ArrowLeft") nextIndex = index + 1;
        else if (event.key === "Home") nextIndex = 0;
        else if (event.key === "End") nextIndex = elements.tabs.length - 1;
        else return;

        event.preventDefault();
        nextIndex = (nextIndex + elements.tabs.length) % elements.tabs.length;
        const nextTab = elements.tabs[nextIndex];
        setCategory(nextTab, true);
        nextTab.scrollIntoView({ block: "nearest", inline: "nearest" });
      });
    });
  }

  function initSearch() {
    elements.form.addEventListener("submit", (event) => event.preventDefault());
    elements.search.addEventListener("input", () => {
      state.query = elements.search.value;
      state.page = 1;
      if (state.category === "brand") filterBrandExamples();
      else renderCatalog();
    });
  }

  function goToPage(page) {
    const pageCount = Math.ceil(filteredDesigns().length / PAGE_SIZE);
    const nextPage = Math.min(Math.max(1, page), Math.max(1, pageCount));
    if (nextPage === state.page) return;
    state.page = nextPage;
    renderCatalog();
    elements.readyPanel.scrollIntoView({
      behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth",
      block: "start"
    });
  }

  function initCatalogActions() {
    elements.pagination.addEventListener("click", (event) => {
      const button = event.target.closest("button");
      if (!button || button.disabled) return;
      if (button.dataset.page) goToPage(Number(button.dataset.page));
      if (button.dataset.pageAction === "previous") goToPage(state.page - 1);
      if (button.dataset.pageAction === "next") goToPage(state.page + 1);
    });

    elements.grid.addEventListener("click", (event) => {
      const favoriteButton = event.target.closest("[data-favorite-design]");
      if (favoriteButton) {
        const designId = favoriteButton.dataset.favoriteDesign;
        const design = designById.get(designId);
        if (!design) return;

        const wasFavorite = favorites.has(designId);
        if (wasFavorite) favorites.delete(designId);
        else favorites.add(designId);
        favoriteButton.setAttribute("aria-pressed", String(!wasFavorite));
        favoriteButton.setAttribute(
          "aria-label",
          `${wasFavorite ? "إضافة" : "إزالة"} تصميم ${design.title} ${wasFavorite ? "إلى" : "من"} المفضلة`
        );
        favoriteButton.querySelector("i").className = `bi bi-heart${wasFavorite ? "" : "-fill"}`;
        saveFavorites();

        if (!wasFavorite) {
          window.PalPrintNotifications?.add(`تمت إضافة «${design.title}» إلى المفضلة`, "heart-fill");
        }
        return;
      }

      const button = event.target.closest("[data-add-design]");
      if (!button) return;
      const design = (catalog[state.category] || []).find((item) => item.id === button.dataset.addDesign);
      if (!design) return;

      stickerCart.push({
        id: makeCartId("ready"),
        type: "ready",
        designId: design.id,
        design: design.title,
        category: categoryMeta[design.category].label,
        image: design.image,
        quantity: 30,
        price: 10,
        currency: "ILS",
        addedAt: new Date().toISOString()
      });
      saveCart();
      updateCartCount();
      announceCart(`تمت إضافة «${design.title}» إلى السلة`);
    });
  }

  function fileExtension(fileName) {
    const segments = String(fileName || "").toLowerCase().split(".");
    return segments.length > 1 ? segments.pop() : "";
  }

  function validateFile(file) {
    if (!file) return "لم يتم اختيار ملف.";
    if (!ALLOWED_TYPES.has(file.type) && !ALLOWED_EXTENSIONS.has(fileExtension(file.name))) {
      return "صيغة الملف غير مدعومة. اختر PNG أو JPG أو JPEG أو WEBP أو SVG.";
    }
    if (file.size > MAX_FILE_SIZE) return "حجم الملف أكبر من 10MB. اختر ملفًا أصغر.";
    return "";
  }

  function setUploadStatus(message, type) {
    elements.uploadStatus.textContent = message;
    elements.uploadStatus.classList.toggle("is-error", type === "error");
    elements.uploadStatus.classList.toggle("is-success", type === "success");
  }

  function applyPreviewShape(shape) {
    elements.previewFrame.dataset.shape = shape;
    elements.previewFrame.classList.remove("is-shape-changing");
    window.clearTimeout(shapeAnimationTimer);
    void elements.previewFrame.offsetWidth;
    elements.previewFrame.classList.add("is-shape-changing");
    shapeAnimationTimer = window.setTimeout(() => {
      elements.previewFrame.classList.remove("is-shape-changing");
    }, 450);
  }

  function useUploadedImage(file) {
    const validationError = validateFile(file);
    if (validationError) {
      setUploadStatus(validationError, "error");
      fallbackToast(validationError, "exclamation-triangle");
      return;
    }

    const candidateUrl = URL.createObjectURL(file);
    const imageProbe = new Image();
    imageProbe.onload = () => {
      if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
      previewObjectUrl = candidateUrl;
      state.image = file;
      elements.preview.src = candidateUrl;
      elements.preview.alt = `معاينة الملف المرفوع: ${file.name}`;
      elements.previewCaption.textContent = file.name;
      setUploadStatus(`تم تحميل ${file.name} بنجاح.`, "success");
      applyPreviewShape(state.shape);
      fallbackToast("تم تحديث معاينة الملصق", "image");
    };
    imageProbe.onerror = () => {
      URL.revokeObjectURL(candidateUrl);
      setUploadStatus("تعذّر قراءة الصورة. تأكد من أن الملف صالح ثم حاول مرة أخرى.", "error");
      fallbackToast("تعذّر قراءة ملف الصورة", "exclamation-triangle");
    };
    imageProbe.src = candidateUrl;
  }

  function preventDragDefaults(event) {
    event.preventDefault();
    event.stopPropagation();
  }

  function initUpload() {
    elements.fileInput.addEventListener("change", () => {
      useUploadedImage(elements.fileInput.files[0]);
      elements.fileInput.value = "";
    });

    ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
      elements.dropzone.addEventListener(eventName, preventDragDefaults);
    });

    elements.dropzone.addEventListener("dragenter", () => {
      dragDepth += 1;
      elements.dropzone.classList.add("is-drag-active");
    });
    elements.dropzone.addEventListener("dragover", (event) => {
      if (event.dataTransfer) event.dataTransfer.dropEffect = "copy";
    });
    elements.dropzone.addEventListener("dragleave", () => {
      dragDepth = Math.max(0, dragDepth - 1);
      if (!dragDepth) elements.dropzone.classList.remove("is-drag-active");
    });
    elements.dropzone.addEventListener("drop", (event) => {
      dragDepth = 0;
      elements.dropzone.classList.remove("is-drag-active");
      const files = event.dataTransfer ? event.dataTransfer.files : null;
      useUploadedImage(files && files[0]);
    });

    window.addEventListener("beforeunload", () => {
      if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
    });
  }

  function selectButton(groupSelector, selectedButton) {
    document.querySelectorAll(groupSelector).forEach((button) => {
      const selected = button === selectedButton;
      button.classList.toggle("is-selected", selected);
      button.setAttribute("aria-pressed", String(selected));
    });
  }

  function syncQuantity(quantity) {
    const safeQuantity = Math.max(1, Math.floor(Number(quantity) || 1));
    state.quantity = safeQuantity;
    elements.quantityInput.value = String(safeQuantity);
    document.querySelectorAll("[data-quantity]").forEach((button) => {
      const selected = Number(button.dataset.quantity) === safeQuantity;
      button.classList.toggle("is-selected", selected);
      button.setAttribute("aria-pressed", String(selected));
    });
  }

  function initCustomizer() {
    document.querySelectorAll("[data-quantity]").forEach((button) => {
      button.addEventListener("click", () => syncQuantity(button.dataset.quantity));
    });
    document.querySelectorAll("[data-quantity-action]").forEach((button) => {
      button.addEventListener("click", () => {
        syncQuantity(state.quantity + (button.dataset.quantityAction === "increase" ? 1 : -1));
      });
    });
    elements.quantityInput.addEventListener("input", () => {
      const value = Number(elements.quantityInput.value);
      if (Number.isFinite(value) && value >= 1) syncQuantity(value);
    });
    elements.quantityInput.addEventListener("change", () => syncQuantity(elements.quantityInput.value));

    document.querySelectorAll("[data-size]").forEach((button) => {
      button.addEventListener("click", () => {
        state.size = button.dataset.size;
        selectButton("[data-size]", button);
      });
    });
    document.querySelectorAll(".sticker-shape[data-shape]").forEach((button) => {
      button.addEventListener("click", () => {
        state.shape = button.dataset.shape;
        applyPreviewShape(state.shape);
        selectButton(".sticker-shape[data-shape]", button);
      });
    });
    document.querySelectorAll('input[name="paper"]').forEach((input) => {
      input.addEventListener("change", () => { if (input.checked) state.paper = input.value; });
    });
    document.querySelectorAll('input[name="protection"]').forEach((input) => {
      input.addEventListener("change", () => { if (input.checked) state.protection = input.value; });
    });

    elements.customizer.addEventListener("submit", (event) => {
      event.preventDefault();
      stickerCart.push({
        id: makeCartId("custom"),
        type: "custom",
        orderType: "brand",
        category: "البراند",
        imageName: state.image ? state.image.name : null,
        quantity: state.quantity,
        size: state.size,
        shape: state.shape,
        paper: state.paper,
        protection: state.protection,
        addedAt: new Date().toISOString()
      });
      saveCart();
      updateCartCount();
      announceCart("تمت إضافة طلب ملصقات البراند إلى السلة");
    });
  }

  function initCartButton() {
    elements.cartButton.addEventListener("click", () => {
      fallbackToast(
        stickerCart.length
          ? `لديك ${stickerCart.length} طلب ملصقات في السلة`
          : "سلة الملصقات فارغة حاليًا",
        "cart3"
      );
    });
  }

  function initProfileLogout() {
    const logout = document.querySelector(".profile-dropdown__logout");
    if (!logout) return;
    logout.addEventListener("click", () => {
      if (window.confirm("هل تريد تسجيل الخروج من حسابك؟")) window.location.href = "login.html";
    });
  }

  function initEntranceMotion() {
    const initial = [
      document.querySelector(".catalog-topline"),
      document.querySelector(".stickers-heading"),
      document.querySelector(".stickers-categories"),
      ...elements.brandPanel.querySelectorAll(".stickers-upload-card, .brand-examples-section, .stickers-customizer")
    ];
    if (reducedMotion) return;
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        initial.slice(0, 3).forEach(reveal);
        revealSequence(initial.slice(3), 650, 600);
      });
    });
  }

  function init() {
    initTabs();
    initSearch();
    initCatalogActions();
    initUpload();
    initCustomizer();
    initCartButton();
    initProfileLogout();
    updateCartCount();
    filterBrandExamples();
    initEntranceMotion();
  }

  init();
})();
