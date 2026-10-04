(function () {
  "use strict";

  const assets = window.palPrintsCustomerAssets || {};
  const products = Array.isArray(assets.publishedDesigns) ? assets.publishedDesigns : [];
  const previewPageUrl = assets.productPreviewUrl || "product-preview.html";
  const hoodieImageUrl = assets.hoodieImage || "assets/images/hoodie.png";
  const HOODIE_TONE_CLASSES = { black: "hoodie-tone--black", navy: "hoodie-tone--blue", white: "hoodie-tone--cream" };
  const grid = document.getElementById("productGrid");
  const search = document.getElementById("productSearch");
  const filters = Array.from(document.querySelectorAll("[data-filter]"));
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  let entranceComplete = reducedMotion;
  let selected = "all";
  const favorites = new Set(products.filter((product) => product.is_favorite).map((product) => String(product.id)));

  const normalize = (text) => String(text || "").toLowerCase().replace(/[\u064B-\u065F\u0670]/g, "").replace(/[أإآ]/g, "ا").trim();

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

  function buildPreviewPayload(product) {
    const ownImage = product.image !== hoodieImageUrl;
    return window.PalPrintPreview.build({
      code: "HOODIE-PREMIUM",
      product,
      options: assets.productOptions,
      designName: product.description,
      defaultSizeId: "m",
      fallbackSizes: [{ id: "m", name: "M" }],
      toneClass: (colorId) => ownImage ? "" : (HOODIE_TONE_CLASSES[colorId] || ""),
      printAreas: [{ id: "front", name: "الأمام", image: product.image, fee: 0, placement: { top: 25, left: 29, width: 42, height: 42 } }]
    });
  }

  function favoriteLabel(product, isFavorite) {
    return isFavorite ? `إزالة ${product.description} من المفضلة` : `إضافة ${product.description} إلى المفضلة`;
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
      if (data.favorited) window.PalPrintNotifications?.add(`تمت إضافة «${product.description}» إلى المفضلة`, "heart-fill");
    } catch (_) {
      syncFavoriteButton(button, product, previous);
      window.PalPrintNotifications?.add("تعذر تحديث المفضلة، حاولي مرة أخرى", "exclamation-triangle");
    } finally {
      button.disabled = false;
    }
  }

  const media = (product) => `<div class="product-card__media"><img src="${product.image || hoodieImageUrl}" alt="${product.title} — ${product.description}" loading="lazy"></div>`;

  function render() {
    const visible = products.filter((product) => (selected === "all" || product.category === selected) && normalize(`${product.title} ${product.description} ${product.designer}`).includes(normalize(search.value)));
    grid.innerHTML = visible.map((product) => {
      const isFavorite = favorites.has(String(product.id));
      return `<article class="product-card" data-id="${product.id}">${media(product).replace('</div>', `<button class="hoodie-favorite" type="button" aria-label="${favoriteLabel(product, isFavorite)}" aria-pressed="${isFavorite}"><i class="bi bi-heart${isFavorite ? "-fill" : ""}" aria-hidden="true"></i></button></div>`)}<div class="product-card__body"><h3>${product.title}</h3><p>${product.description}</p><div class="hoodie-credit"><span>يبدأ من <strong dir="ltr">${product.price} ₪</strong></span><span class="hoodie-designer" dir="ltr"><i class="bi bi-person" aria-hidden="true"></i>by ${product.designer}</span></div><button type="button" class="hoodie-preview" data-preview="${product.id}"><i class="bi bi-eye" aria-hidden="true"></i>معاينة المنتج</button></div></article>`;
    }).join("");
    document.getElementById("productsEmpty").hidden = visible.length > 0;
    document.getElementById("catalogCount").textContent = `عرض ${visible.length} من ${products.length} تصاميم`;
    document.querySelector(".catalog-pagination").hidden = visible.length === 0;
    if (entranceComplete && !reducedMotion) requestAnimationFrame(() => revealCardRows(0));
  }

  filters.forEach((button) => button.addEventListener("click", () => {
    selected = selected === button.dataset.filter ? "all" : button.dataset.filter;
    filters.forEach((filter) => filter.setAttribute("aria-pressed", String(filter.dataset.filter === selected)));
    render();
  }));
  search.addEventListener("input", render);
  document.getElementById("productSearchForm").addEventListener("submit", (event) => { event.preventDefault(); render(); });

  function openPreview(id) {
    const product = products.find((item) => String(item.id) === String(id));
    if (!product) return;
    try { sessionStorage.setItem("palprintsCustomerPreview", JSON.stringify(buildPreviewPayload(product))); } catch (_) { /* Preview falls back to its own demo data. */ }
    window.location.href = previewPageUrl;
  }

  grid.addEventListener("click", async (event) => {
    const favorite = event.target.closest(".hoodie-favorite");
    if (favorite) {
      const product = products.find((item) => String(item.id) === favorite.closest("[data-id]").dataset.id);
      if (product) await toggleFavorite(product, favorite);
      return;
    }
    const preview = event.target.closest("[data-preview]");
    if (preview) openPreview(preview.dataset.preview);
    const card = event.target.closest(".product-card");
    if (card && !event.target.closest("button")) openPreview(card.dataset.id);
  });

  grid.addEventListener("keydown", (event) => {
    if (!event.target.matches(".product-card") || !["Enter", " "].includes(event.key)) return;
    event.preventDefault();
    openPreview(event.target.dataset.id);
  });

  render();
  if (!reducedMotion) requestAnimationFrame(() => requestAnimationFrame(() => {
    reveal(document.querySelector(".catalog-topline"));
    reveal(document.querySelector(".catalog-heading"));
    reveal(document.querySelector(".hoodie-filters"));
    revealCardRows(650);
    entranceComplete = true;
  }));
})();