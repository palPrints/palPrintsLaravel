(function () {
  "use strict";

  const assets = window.palPrintsCustomerAssets || {};
  const mugsBase = assets.mugsImagesBase || "assets/images/mugs";

  const products = Array.isArray(assets.publishedDesigns) ? assets.publishedDesigns : [
    {
      id: "morning-calm",
      title: "كوب صباح هادئ",
      description: "تصميم بسيط لبداية يوم مليئة بالهدوء",
      designer: "Lina A.",
      price: 12,
      image: `${mugsBase}/coffee-salam.png`
    },
    {
      id: "coffee-first",
      title: "كوب القهوة أولاً",
      description: "رفيق أنيق لعشاق القهوة في كل صباح",
      designer: "Omar K.",
      price: 12,
      image: `${mugsBase}/red-coffee.png`,
      badge: "الأكثر مبيعًا"
    },
    {
      id: "warm-moments",
      title: "كوب لحظات دافئة",
      description: "لمسة دافئة تناسب البيت ومساحة العمل",
      designer: "Sara N.",
      price: 12,
      image: `${mugsBase}/cat-good-day.png`
    },
    {
      id: "daily-inspiration",
      title: "كوب إلهام يومي",
      description: "تفصيل صغير يذكّرك بأنك تستطيع",
      designer: "Ahmad Z.",
      price: 12,
      image: `${mugsBase}/be-happy.png`
    },
    {
      id: "creative-story",
      title: "كوب اصنع قصتك",
      description: "تصميم عصري لكل فكرة تستحق أن تُروى",
      designer: "Haneen S.",
      price: 12,
      image: `${mugsBase}/palestine.png`
    },
    {
      id: "classic-black",
      title: "كوب أسود أنيق",
      description: "اختيار كلاسيكي بطابع جريء ومميز",
      designer: "Yousef M.",
      price: 12,
      image: `${mugsBase}/black-explore.png`
    },
    {
      id: "special-memory",
      title: "كوب ذكرى خاصة",
      description: "هدية شخصية تحفظ أجمل التفاصيل",
      designer: "Rana H.",
      price: 12,
      image: `${mugsBase}/you-are-special.png`
    },
    {
      id: "good-vibes",
      title: "كوب طاقة إيجابية",
      description: "ألوان مبهجة ورسالة تلائم كل يوم",
      designer: "Khaled N.",
      price: 12,
      image: `${mugsBase}/good-vibes.png`
    }
  ];

  const previewPageUrl = assets.productPreviewUrl || "product-preview.html";
  const fallbackImage = assets.cupFallbackImage || "assets/images/cup.webp";
  const favoritesStorageKey = "palprints-mug-favorites";
  const grid = document.getElementById("productGrid");
  const search = document.getElementById("productSearch");
  const emptyState = document.getElementById("productsEmpty");
  const catalogCount = document.getElementById("catalogCount");
  const pagination = document.querySelector(".catalog-pagination");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  let entranceComplete = reducedMotion;
  let favorites = new Set();

  try {
    const saved = JSON.parse(localStorage.getItem(favoritesStorageKey) || "[]");
    if (Array.isArray(saved)) {
      favorites = new Set(saved.filter((id) => products.some((product) => product.id === id)));
    }
  } catch (_) {
    /* Favorites remain available for this session. */
  }

  const normalize = (text) => text
    .toLocaleLowerCase("ar")
    .replace(/[\u064B-\u065F\u0670\u06D6-\u06ED]/g, "")
    .replace(/[أإآ]/g, "ا")
    .replace(/ة/g, "ه")
    .trim();

  function buildPreviewPayload(product) {
    return {
      version: 2,
      product: {
        id: product.id,
        name: product.title,
        sellingPrice: product.price,
        currency: "ILS",
        colors: [{ id: "default", name: "الأساسي", value: "#dfe8f3", image: product.image, toneClass: "" }],
        sizes: [{ id: "standard", name: "الحجم القياسي" }],
        printAreas: [{ id: "front", name: "الكوب", image: product.image, fee: 0, placement: { top: 20, left: 20, width: 60, height: 60 } }]
      },
      design: {
        id: product.id,
        name: product.title,
        designerName: product.designer,
        preview: { images: [], texts: [], icons: [] }
      },
      selection: {
        colorId: "default",
        sizeId: "standard",
        quantity: 1,
        printAreaIds: ["front"],
        defaultItem: { colorId: "default", sizeId: "standard", printAreaIds: ["front"] },
        items: [{ colorId: "default", sizeId: "standard", printAreaIds: ["front"] }],
        activeItemIndex: 0
      },
      customerWarnings: []
    };
  }

  function reveal(element) {
    if (!element) return;
    element.classList.add("is-revealed");

    if (element.classList.contains("product-card")) {
      window.setTimeout(() => {
        if (element.isConnected) element.classList.add("is-floating");
      }, 700);
    }
  }

  function revealCardRows(delay) {
    const rows = [];
    const startedAt = performance.now();

    [...grid.querySelectorAll(".product-card")].forEach((card) => {
      const top = card.getBoundingClientRect().top;
      let row = rows.find((group) => Math.abs(group.top - top) < 8);

      if (!row) {
        row = { top, cards: [] };
        rows.push(row);
      }

      row.cards.push(card);
    });

    if (!rows.length) return;
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
      }, { threshold: 0.12, rootMargin: "0px 0px -8% 0px" });

      observer.observe(row.cards[0]);
    });
  }

  function favoriteLabel(product, isFavorite) {
    return isFavorite
      ? `إزالة ${product.title} من المفضلة`
      : `إضافة ${product.title} إلى المفضلة`;
  }

  function productTemplate(product) {
    const isFavorite = favorites.has(product.id);
    const badge = product.badge
      ? `<span class="mug-badge-accent"><i class="bi bi-stars" aria-hidden="true"></i>${product.badge}</span>`
      : "";

    return `
      <article class="product-card" data-id="${product.id}">
        <div class="product-card__media">
          <img src="${product.image}" alt="${product.title} — ${product.description}" loading="lazy" referrerpolicy="no-referrer" data-product-image>
          ${badge}
          <button class="mug-favorite pal-pressable" type="button" aria-label="${favoriteLabel(product, isFavorite)}" aria-pressed="${isFavorite}">
            <i class="bi bi-heart${isFavorite ? "-fill" : ""}" aria-hidden="true"></i>
          </button>
        </div>
        <div class="product-card__body">
          <h3>${product.title}</h3>
          <p>${product.description}</p>
          <div class="mug-credit">
            <span>يبدأ من <strong dir="ltr">$${product.price}</strong></span>
            <span class="mug-designer" dir="ltr"><i class="bi bi-person" aria-hidden="true"></i>by ${product.designer}</span>
          </div>
          <button type="button" class="mug-preview pal-pressable" data-preview="${product.id}">
            <i class="bi bi-eye" aria-hidden="true"></i>معاينة المنتج
          </button>
        </div>
      </article>`;
  }

  function attachImageFallbacks() {
    grid.querySelectorAll("[data-product-image]").forEach((image) => {
      image.addEventListener("error", () => {
        if (image.dataset.fallbackApplied === "true") return;
        image.dataset.fallbackApplied = "true";
        image.classList.add("is-fallback");
        image.src = fallbackImage;
      }, { once: true });
    });
  }

  function render() {
    const query = normalize(search.value);
    const visible = products.filter((product) => normalize(
      `${product.title} ${product.description} ${product.designer}`
    ).includes(query));

    grid.innerHTML = visible.map(productTemplate).join("");
    attachImageFallbacks();
    emptyState.hidden = visible.length > 0;
    catalogCount.textContent = `عرض ${visible.length} من ${products.length} تصاميم`;
    pagination.hidden = visible.length === 0;

    if (entranceComplete && !reducedMotion) {
      requestAnimationFrame(() => revealCardRows(0));
    }
  }

  search.addEventListener("input", render);
  document.getElementById("productSearchForm").addEventListener("submit", (event) => {
    event.preventDefault();
    render();
  });

  grid.addEventListener("click", (event) => {
    const favoriteButton = event.target.closest(".mug-favorite");

    if (favoriteButton) {
      const productId = favoriteButton.closest("[data-id]").dataset.id;
      const product = products.find((item) => item.id === productId);
      const wasFavorite = favorites.has(productId);

      if (wasFavorite) favorites.delete(productId);
      else favorites.add(productId);

      favoriteButton.setAttribute("aria-pressed", String(!wasFavorite));
      favoriteButton.setAttribute("aria-label", favoriteLabel(product, !wasFavorite));
      favoriteButton.querySelector("i").className = `bi bi-heart${wasFavorite ? "" : "-fill"}`;

      try {
        localStorage.setItem(favoritesStorageKey, JSON.stringify([...favorites]));
      } catch (_) {
        /* Session-only fallback. */
      }

      if (!wasFavorite) {
        window.PalPrintNotifications?.add(`تمت إضافة «${product.title}» إلى المفضلة`, "heart-fill");
      }
    }

    const previewButton = event.target.closest("[data-preview]");
    if (previewButton) {
      const product = products.find((item) => item.id === previewButton.dataset.preview);
      try { sessionStorage.setItem("palprintsCustomerPreview", JSON.stringify(buildPreviewPayload(product))); } catch (_) { /* Preview falls back to its own demo data. */ }
      window.location.href = previewPageUrl;
    }
  });

  render();

  if (!reducedMotion) {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        reveal(document.querySelector(".catalog-topline"));
        reveal(document.querySelector(".catalog-heading"));
        revealCardRows(650);
        entranceComplete = true;
      });
    });
  }
})();
