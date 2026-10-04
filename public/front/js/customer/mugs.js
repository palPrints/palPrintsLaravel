(function () {
  "use strict";

  const assets = window.palPrintsCustomerAssets || {};
  const mugsBase = assets.mugsImagesBase || "assets/images/mugs";
  const products = Array.isArray(assets.publishedDesigns) ? assets.publishedDesigns : [
    { id: "morning-calm", title: "كوب صباح هادئ", description: "تصميم بسيط لبداية يوم مليئة بالهدوء", designer: "Lina A.", price: 12, image: `${mugsBase}/coffee-salam.png` }
  ];
  const previewPageUrl = assets.productPreviewUrl || "product-preview.html";
  const fallbackImage = assets.cupFallbackImage || "assets/images/cup.webp";
  const grid = document.getElementById("productGrid");
  const search = document.getElementById("productSearch");
  const emptyState = document.getElementById("productsEmpty");
  const catalogCount = document.getElementById("catalogCount");
  const pagination = document.querySelector(".catalog-pagination");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  let entranceComplete = reducedMotion;
  const favorites = new Set(products.filter((product) => product.is_favorite).map((product) => String(product.id)));

  const normalize = (text) => String(text || "").toLocaleLowerCase("ar").replace(/[\u064B-\u065F\u0670\u06D6-\u06ED]/g, "").replace(/[أإآ]/g, "ا").replace(/ة/g, "ه").trim();

  function buildPreviewPayload(product) {
    return window.PalPrintPreview.build({
      code: "MUG-CERAMIC",
      product,
      options: assets.productOptions,
      designName: product.title,
      fallbackSizes: [{ id: "standard", name: "الحجم القياسي" }],
      printAreas: [{ id: "front", name: "الكوب", image: product.image, fee: 0, placement: { top: 20, left: 20, width: 60, height: 60 } }]
    });
  }

  function reveal(element) {
    if (!element) return;
    element.classList.add("is-revealed");
    if (element.classList.contains("product-card")) window.setTimeout(() => element.isConnected && element.classList.add("is-floating"), 700);
  }

  function revealCardRows(delay) {
    const rows = [];
    const startedAt = performance.now();
    Array.from(grid.querySelectorAll(".product-card")).forEach((card) => {
      const top = card.getBoundingClientRect().top;
      let row = rows.find((group) => Math.abs(group.top - top) < 8);
      if (!row) rows.push(row = { top, cards: [] });
      row.cards.push(card);
    });
    if (!rows.length) return;
    window.setTimeout(() => rows[0].cards.forEach(reveal), delay);
    rows.slice(1).forEach((row, index) => {
      const revealRow = () => window.setTimeout(() => row.cards.forEach(reveal), Math.max(100, delay + (index + 1) * 600 - (performance.now() - startedAt)));
      if (!("IntersectionObserver" in window)) { revealRow(); return; }
      const observer = new IntersectionObserver((entries) => {
        if (!entries.some((entry) => entry.isIntersecting)) return;
        revealRow();
        observer.disconnect();
      }, { threshold: 0.12, rootMargin: "0px 0px -8% 0px" });
      observer.observe(row.cards[0]);
    });
  }

  function favoriteLabel(product, isFavorite) {
    return isFavorite ? `إزالة ${product.title} من المفضلة` : `إضافة ${product.title} إلى المفضلة`;
  }

  function syncFavoriteButton(button, product, isFavorite) {
    button.setAttribute("aria-pressed", String(isFavorite));
    button.setAttribute("aria-label", favoriteLabel(product, isFavorite));
    button.querySelector("i").className = `bi bi-heart${isFavorite ? "-fill" : ""}`;
  }

  async function toggleFavorite(product, button) {
    const id = String(product.id);
    const previous = favorites.has(id);
    if (!product.favorite_url) {
      previous ? favorites.delete(id) : favorites.add(id);
      syncFavoriteButton(button, product, !previous);
      return;
    }

    button.disabled = true;
    try {
      const response = await fetch(product.favorite_url, {
        method: "POST",
        headers: { "Accept": "application/json", "X-CSRF-TOKEN": csrf, "X-Requested-With": "XMLHttpRequest" }
      });
      if (!response.ok) throw new Error("favorite failed");
      const data = await response.json();
      data.favorited ? favorites.add(id) : favorites.delete(id);
      syncFavoriteButton(button, product, data.favorited);
      if (data.favorited) window.PalPrintNotifications?.add(`تمت إضافة «${product.title}» إلى المفضلة`, "heart-fill");
    } catch (_) {
      syncFavoriteButton(button, product, previous);
      window.PalPrintNotifications?.add("تعذر تحديث المفضلة، حاولي مرة أخرى", "exclamation-triangle");
    } finally {
      button.disabled = false;
    }
  }

  function productTemplate(product) {
    const isFavorite = favorites.has(String(product.id));
    const badge = product.badge ? `<span class="mug-badge-accent"><i class="bi bi-stars" aria-hidden="true"></i>${product.badge}</span>` : "";
    return `<article class="product-card" data-id="${product.id}"><div class="product-card__media"><img src="${product.image}" alt="${product.title} — ${product.description}" loading="lazy" referrerpolicy="no-referrer" data-product-image>${badge}<button class="mug-favorite pal-pressable" type="button" aria-label="${favoriteLabel(product, isFavorite)}" aria-pressed="${isFavorite}"><i class="bi bi-heart${isFavorite ? "-fill" : ""}" aria-hidden="true"></i></button></div><div class="product-card__body"><h3>${product.title}</h3><p>${product.description}</p><div class="mug-credit"><span>يبدأ من <strong dir="ltr">${product.price} ₪</strong></span><span class="mug-designer" dir="ltr"><i class="bi bi-person" aria-hidden="true"></i>by ${product.designer}</span></div><button type="button" class="mug-preview pal-pressable" data-preview="${product.id}"><i class="bi bi-eye" aria-hidden="true"></i>معاينة المنتج</button></div></article>`;
  }

  function attachImageFallbacks() {
    grid.querySelectorAll("[data-product-image]").forEach((image) => image.addEventListener("error", () => {
      if (image.dataset.fallbackApplied === "true") return;
      image.dataset.fallbackApplied = "true";
      image.classList.add("is-fallback");
      image.src = fallbackImage;
    }, { once: true }));
  }

  function render() {
    const query = normalize(search.value);
    const visible = products.filter((product) => normalize(`${product.title} ${product.description} ${product.designer}`).includes(query));
    grid.innerHTML = visible.map(productTemplate).join("");
    attachImageFallbacks();
    emptyState.hidden = visible.length > 0;
    catalogCount.textContent = `عرض ${visible.length} من ${products.length} تصاميم`;
    pagination.hidden = visible.length === 0;
    if (entranceComplete && !reducedMotion) requestAnimationFrame(() => revealCardRows(0));
  }

  search.addEventListener("input", render);
  document.getElementById("productSearchForm").addEventListener("submit", (event) => { event.preventDefault(); render(); });

  grid.addEventListener("click", async (event) => {
    const favoriteButton = event.target.closest(".mug-favorite");
    if (favoriteButton) {
      const product = products.find((item) => String(item.id) === favoriteButton.closest("[data-id]").dataset.id);
      if (product) await toggleFavorite(product, favoriteButton);
      return;
    }
    const previewButton = event.target.closest("[data-preview]");
    if (previewButton) {
      const product = products.find((item) => String(item.id) === previewButton.dataset.preview);
      try { sessionStorage.setItem("palprintsCustomerPreview", JSON.stringify(buildPreviewPayload(product))); } catch (_) { /* Preview falls back to its own demo data. */ }
      window.location.href = previewPageUrl;
    }
  });

  render();
  if (!reducedMotion) requestAnimationFrame(() => requestAnimationFrame(() => {
    reveal(document.querySelector(".catalog-topline"));
    reveal(document.querySelector(".catalog-heading"));
    revealCardRows(650);
    entranceComplete = true;
  }));
})();