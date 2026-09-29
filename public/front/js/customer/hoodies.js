(function () {
  "use strict";
  const assets = window.palPrintsCustomerAssets || {};
  // The designs are the published hoodie designs from the database.
  const products = Array.isArray(assets.publishedDesigns) ? assets.publishedDesigns : [];
  const previewPageUrl = assets.productPreviewUrl || "product-preview.html";
  const hoodieImageUrl = assets.hoodieImage || "assets/images/hoodie.png";
  // Only the tint for a database color code; colors without one show untinted.
  const HOODIE_TONE_CLASSES = { black: "hoodie-tone--black", navy: "hoodie-tone--blue", white: "hoodie-tone--cream" };
  const grid = document.getElementById("productGrid");
  const search = document.getElementById("productSearch");
  const filters = [...document.querySelectorAll("[data-filter]")];
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  let entranceComplete = reducedMotion;
  let selected = "all";
  let favorites = new Set();

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
  try {
    const saved = JSON.parse(localStorage.getItem("palprints-hoodie-favorites") || "[]");
    if (Array.isArray(saved)) favorites = new Set(saved.filter(id => products.some(product => product.id === id)));
  } catch (_) { /* Favorites remain available for this session. */ }
  function buildPreviewPayload(product) {
    const ownImage = product.image !== hoodieImageUrl;
    return window.PalPrintPreview.build({
      code: "HOODIE-PREMIUM",
      product,
      options: assets.productOptions,
      designName: product.description,
      defaultSizeId: "m",
      fallbackSizes: [{ id: "m", name: "M" }],
      toneClass: colorId => (ownImage ? "" : (HOODIE_TONE_CLASSES[colorId] || "")),
      printAreas: [{ id: "front", name: "الأمام", image: product.image, fee: 0, placement: { top: 25, left: 29, width: 42, height: 42 } }]
    });
  }
  const normalize = text => text.toLowerCase().replace(/[\u064B-\u065F\u0670]/g, "").replace(/[أإآ]/g, "ا").trim();
  const media = product => `<div class="product-card__media"><img src="${product.image || hoodieImageUrl}" alt="${product.title} — ${product.description}" loading="lazy"></div>`;
  function render() {
    const visible = products.filter(product => (selected === "all" || product.category === selected) && normalize(`${product.title} ${product.description} ${product.designer}`).includes(normalize(search.value)));
    grid.innerHTML = visible.map(product => `<article class="product-card" data-id="${product.id}">${media(product).replace('</div>', `<button class="hoodie-favorite" type="button" aria-label="مفضلة: ${product.description}" aria-pressed="${favorites.has(product.id)}"><i class="bi bi-heart${favorites.has(product.id) ? "-fill" : ""}" aria-hidden="true"></i></button></div>`)}<div class="product-card__body"><h3>${product.title}</h3><p>${product.description}</p><div class="hoodie-credit"><span>يبدأ من <strong dir="ltr">${product.price} ₪</strong></span><span class="hoodie-designer" dir="ltr"><i class="bi bi-person" aria-hidden="true"></i>by ${product.designer}</span></div><button type="button" class="hoodie-preview" data-preview="${product.id}"><i class="bi bi-eye" aria-hidden="true"></i>معاينة المنتج</button></div></article>`).join("");
    document.getElementById("productsEmpty").hidden = visible.length > 0;
    document.getElementById("catalogCount").textContent = `عرض ${visible.length} من ${products.length} تصاميم`;
    document.querySelector(".catalog-pagination").hidden = visible.length === 0;
    if (entranceComplete && !reducedMotion) requestAnimationFrame(() => revealCardRows(0));
  }
  filters.forEach(button => button.addEventListener("click", () => {
    selected = selected === button.dataset.filter ? "all" : button.dataset.filter;
    filters.forEach(filter => filter.setAttribute("aria-pressed", String(filter.dataset.filter === selected)));
    render();
  }));
  search.addEventListener("input", render);
  document.getElementById("productSearchForm").addEventListener("submit", event => { event.preventDefault(); render(); });
  function openPreview(id) {
    const product = products.find(item => item.id === id);
    if (!product) return;

    try {
      sessionStorage.setItem("palprintsCustomerPreview", JSON.stringify(buildPreviewPayload(product)));
    } catch (_) { /* The preview page supplies a safe fallback if storage is unavailable. */ }
    window.location.href = previewPageUrl;
  }
  grid.addEventListener("click", event => {
    const favorite = event.target.closest(".hoodie-favorite");
    if (favorite) {
      const id = favorite.closest("[data-id]").dataset.id;
      const wasFavorite = favorites.has(id);
      wasFavorite ? favorites.delete(id) : favorites.add(id);
      favorite.setAttribute("aria-pressed", String(favorites.has(id)));
      favorite.querySelector("i").className = `bi bi-heart${favorites.has(id) ? "-fill" : ""}`;
      try { localStorage.setItem("palprints-hoodie-favorites", JSON.stringify([...favorites])); } catch (_) { /* Session-only fallback. */ }

      if (!wasFavorite) {
        const product = products.find(item => item.id === id);
        window.PalPrintNotifications?.add(`تمت إضافة «${product.description}» إلى المفضلة`, "heart-fill");
      }
    }
    const preview = event.target.closest("[data-preview]");
    if (preview) {
      const product = products.find(item => item.id === preview.dataset.preview);
      try { sessionStorage.setItem("palprintsCustomerPreview", JSON.stringify(buildPreviewPayload(product))); } catch (_) { /* Preview falls back to its own demo data. */ }
      window.location.href = previewPageUrl;
    }

    const card = event.target.closest(".product-card");
    if (card && !event.target.closest("button")) openPreview(card.dataset.id);
  });
  grid.addEventListener("keydown", event => {
    if (!event.target.matches(".product-card") || !["Enter", " "].includes(event.key)) return;
    event.preventDefault();
    openPreview(event.target.dataset.id);
  });
  render();

  if (!reducedMotion) {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        reveal(document.querySelector(".catalog-topline"));
        reveal(document.querySelector(".catalog-heading"));
        reveal(document.querySelector(".hoodie-filters"));
        revealCardRows(650);
        entranceComplete = true;
      });
    });
  }
})();
