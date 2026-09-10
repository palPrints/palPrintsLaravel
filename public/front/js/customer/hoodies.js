(function () {
  "use strict";
  const products = [
    { id: "explore", category: "oversized", title: "هودي أوفر سايز", description: "اكتشف أكثر", designer: "Lina A.", tone: "cream", icon: "tree-fill", print: "EXPLORE<br>MORE" },
    { id: "salam", category: "adults", title: "هودي رجال / نساء", description: "سلام دائم", designer: "Omar K.", tone: "black", icon: "flower2", print: "سلام", badge: "الأكثر مبيعاً" },
    { id: "kind", category: "kids", title: "هودي أطفال", description: "قلب صغير كبير", designer: "Sara N.", tone: "pink", icon: "emoji-smile", print: "BE KIND<br>LITTLE ONE" },
    { id: "dream", category: "oversized", title: "هودي أوفر سايز", description: "احلم أكبر", designer: "Ahmad Z.", tone: "purple", icon: "rocket-takeoff", print: "DREAM<br>BIGGER" },
    { id: "day", category: "kids", title: "هودي أطفال", description: "يوم أجمل", designer: "Haneen S.", tone: "blue", icon: "moon-stars-fill", print: "IT’S A<br>GOOD DAY" },
    { id: "good", category: "adults", title: "هودي رجال / نساء", description: "الأشياء الجيدة", designer: "Yousef M.", tone: "green", icon: "leaf", print: "GOOD THINGS<br>TAKE TIME" },
    { id: "smile", category: "kids", title: "هودي أطفال", description: "ابتسم دائماً", designer: "Rana H.", tone: "cream", icon: "emoji-laughing", print: "KEEP<br>SMILING" },
    { id: "create", category: "adults", title: "هودي رجال / نساء", description: "اصنع قصتك", designer: "Khaled N.", tone: "black", icon: "stars", print: "CREATE<br>YOUR STORY" }
  ];
  const previewPageUrl = "product-preview.html";
  const hoodieImageUrl = window.palPrintsCustomerAssets?.hoodieImage || "assets/images/hoodie.png";
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
  const normalize = text => text.toLowerCase().replace(/[\u064B-\u065F\u0670]/g, "").replace(/[أإآ]/g, "ا").trim();
  const media = product => `<div class="product-card__media"><img class="hoodie-tone--${product.tone}" src="${hoodieImageUrl}" alt="${product.title} — ${product.description}" loading="lazy"><span class="hoodie-print ${["black", "purple", "green"].includes(product.tone) ? "hoodie-print--light" : ""} ${product.id === "salam" ? "hoodie-print--arabic" : ""}" aria-hidden="true"><i class="bi bi-${product.icon}"></i>${product.print}</span>${product.badge ? `<span class="hoodie-badge-accent"><i class="bi bi-stars" aria-hidden="true"></i>${product.badge}</span>` : ""}</div>`;
  function render() {
    const visible = products.filter(product => (selected === "all" || product.category === selected) && normalize(`${product.title} ${product.description} ${product.designer}`).includes(normalize(search.value)));
    grid.innerHTML = visible.map(product => `<article class="product-card" data-id="${product.id}">${media(product).replace('</div>', `<button class="hoodie-favorite" type="button" aria-label="مفضلة: ${product.description}" aria-pressed="${favorites.has(product.id)}"><i class="bi bi-heart${favorites.has(product.id) ? "-fill" : ""}" aria-hidden="true"></i></button></div>`)}<div class="product-card__body"><h3>${product.title}</h3><p>${product.description}</p><div class="hoodie-credit"><span>يبدأ من <strong dir="ltr">$20</strong></span><span class="hoodie-designer" dir="ltr"><i class="bi bi-person" aria-hidden="true"></i>by ${product.designer}</span></div><button type="button" class="hoodie-preview" data-preview="${product.id}"><i class="bi bi-eye" aria-hidden="true"></i>معاينة المنتج</button></div></article>`).join("");
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
      const target = new URL(previewPageUrl, window.location.href);
      target.searchParams.set("id", preview.dataset.preview);
      window.location.href = target.href;
    }
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
