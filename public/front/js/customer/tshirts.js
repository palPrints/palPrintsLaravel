(function () {
  "use strict";

  const assets = window.palPrintsCustomerAssets || {};
  const tshirtsBase = assets.tshirtsImagesBase || "assets/images/tshirts";

  const products = [
    { id: "flower-good-day", category: "kids", title: "تيشيرت أطفال", description: "يوم سعيد", designer: "Lina A.", price: 20, image: `${tshirtsBase}/kids-flower-good-day.png` },
    { id: "panda-music", category: "kids", title: "تيشيرت أطفال", description: "موسيقى دائماً", designer: "Omar K.", price: 20, image: `${tshirtsBase}/kids-panda-music.png` },
    { id: "little-explorer", category: "kids", title: "تيشيرت أطفال", description: "للمستكشف الصغير", designer: "Sara N.", price: 20, image: `${tshirtsBase}/kids-little-explorer.png` },
    { id: "moon-good-day", category: "adults", title: "تيشيرت رجال / نساء", description: "يوم جيد دائماً", designer: "Ahmad Z.", price: 20, image: `${tshirtsBase}/adults-good-day.png` },
    { id: "good-things", category: "adults", title: "تيشيرت رجال / نساء", description: "الأمور الجيدة تستغرق وقتاً", designer: "Haneen S.", price: 20, image: `${tshirtsBase}/adults-good-things.png` },
    { id: "salam", category: "oversized", title: "تيشيرت أوفر سايز", description: "سلام", designer: "Yousef M.", price: 25, image: `${tshirtsBase}/oversized-salam.png` }
  ];

  const previewPageUrl = "product-preview.html";
  const fallbackImage = assets.tshirtFallbackImage || "assets/images/tshirt.webp";
  const favoritesStorageKey = "palprints-tshirt-favorites";
  const grid = document.getElementById("productGrid");
  const search = document.getElementById("productSearch");
  const filters = [...document.querySelectorAll("[data-filter]")];
  const emptyState = document.getElementById("productsEmpty");
  const catalogCount = document.getElementById("catalogCount");
  const pagination = document.querySelector(".catalog-pagination");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  let entranceComplete = reducedMotion;
  let selected = "all";
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
    .trim();

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
      ? `إزالة ${product.description} من المفضلة`
      : `إضافة ${product.description} إلى المفضلة`;
  }

  function productTemplate(product) {
    const isFavorite = favorites.has(product.id);

    return `
      <article class="product-card" data-id="${product.id}">
        <div class="product-card__media">
          <img src="${product.image}" alt="${product.title} — ${product.description}" loading="lazy" data-product-image>
          <button class="tshirt-favorite pal-pressable" type="button" aria-label="${favoriteLabel(product, isFavorite)}" aria-pressed="${isFavorite}">
            <i class="bi bi-heart${isFavorite ? "-fill" : ""}" aria-hidden="true"></i>
          </button>
        </div>
        <div class="product-card__body">
          <h3>${product.title}</h3>
          <p>${product.description}</p>
          <div class="tshirt-credit">
            <span>يبدأ من <strong dir="ltr">$${product.price}</strong></span>
            <span class="tshirt-designer" dir="ltr"><i class="bi bi-person" aria-hidden="true"></i>by ${product.designer}</span>
          </div>
          <button type="button" class="tshirt-preview pal-pressable" data-preview="${product.id}">
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
        image.src = fallbackImage;
      }, { once: true });
    });
  }

  function render() {
    const query = normalize(search.value);
    const visible = products.filter((product) =>
      (selected === "all" || product.category === selected)
      && normalize(`${product.title} ${product.description} ${product.designer}`).includes(query)
    );

    grid.innerHTML = visible.map(productTemplate).join("");
    attachImageFallbacks();
    emptyState.hidden = visible.length > 0;
    catalogCount.textContent = `عرض ${visible.length} من ${products.length} تصاميم`;
    pagination.hidden = visible.length === 0;

    if (entranceComplete && !reducedMotion) {
      requestAnimationFrame(() => revealCardRows(0));
    }
  }

  filters.forEach((button) => button.addEventListener("click", () => {
    selected = selected === button.dataset.filter ? "all" : button.dataset.filter;
    filters.forEach((filter) => {
      filter.setAttribute("aria-pressed", String(filter.dataset.filter === selected));
    });
    render();
  }));

  search.addEventListener("input", render);
  document.getElementById("productSearchForm").addEventListener("submit", (event) => {
    event.preventDefault();
    render();
  });

  grid.addEventListener("click", (event) => {
    const favoriteButton = event.target.closest(".tshirt-favorite");

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
        window.PalPrintNotifications?.add(`تمت إضافة «${product.description}» إلى المفضلة`, "heart-fill");
      }
    }

    const previewButton = event.target.closest("[data-preview]");
    if (previewButton) {
      const target = new URL(previewPageUrl, window.location.href);
      target.searchParams.set("id", previewButton.dataset.preview);
      window.location.href = target.href;
    }
  });

  render();

  if (!reducedMotion) {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        reveal(document.querySelector(".catalog-topline"));
        reveal(document.querySelector(".catalog-heading"));
        reveal(document.querySelector(".tshirt-filters"));
        revealCardRows(650);
        entranceComplete = true;
      });
    });
  }
})();
