(function () {
  "use strict";

  const data = window.palPrintsFavoritesData || {};
  const grid = document.getElementById("favoritesGrid");
  const emptyState = document.getElementById("favoritesEmpty");
  const suggestions = document.getElementById("favoritesSuggestions");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  function readLegacyStickerFavorites() {
    try {
      const items = JSON.parse(localStorage.getItem("palprints-sticker-favorite-items") || "[]");
      return Array.isArray(items) ? items : [];
    } catch (_) {
      return [];
    }
  }

  let favorites = [
    ...(Array.isArray(data.items) ? data.items : []),
    ...readLegacyStickerFavorites(),
  ];

  if (!reducedMotion) document.body.classList.add("favorites-motion-ready");

  function render() {
    const isEmpty = favorites.length === 0;
    grid.hidden = isEmpty;
    emptyState.hidden = !isEmpty;
    suggestions.hidden = !isEmpty;
    grid.innerHTML = favorites.map((item) => `<article class="favorite-card" data-id="${item.id}"><div class="favorite-card__media"><img src="${item.image}" alt="${item.title} — ${item.description}" loading="lazy"><button class="favorite-remove" type="button" aria-label="إزالة ${item.title} من المفضلة"><i class="bi bi-heart-fill" aria-hidden="true"></i></button></div><div class="favorite-card__body"><h2>${item.title}</h2><p>${item.description || item.product || ""}</p><div class="favorite-meta"><span dir="ltr"><i class="bi bi-person"></i> by ${item.designer}</span><strong dir="ltr">${item.price} ₪</strong></div><a class="btn-brand favorite-preview" href="${item.page}?id=${encodeURIComponent(item.id)}"><i class="bi bi-eye"></i>معاينة المنتج</a></div></article>`).join("");

    if (!reducedMotion) {
      requestAnimationFrame(() => {
        grid.querySelectorAll(".favorite-card").forEach((card, index) => {
          window.setTimeout(() => {
            card.classList.add("is-revealed");
            window.setTimeout(() => card.isConnected && card.classList.add("is-floating"), 700);
          }, index * 90);
        });
      });
    }
  }

  async function removeFavorite(card) {
    const item = favorites.find((favorite) => String(favorite.id) === card.dataset.id);
    if (!item?.favorite_url) {
      favorites = favorites.filter((favorite) => String(favorite.id) !== card.dataset.id);
      try {
        const stickerItems = favorites.filter((favorite) => favorite.source === "stickers-static");
        localStorage.setItem("palprints-sticker-favorite-items", JSON.stringify(stickerItems));
        localStorage.setItem("palprints-sticker-favorites", JSON.stringify(stickerItems.map((favorite) => String(favorite.id))));
      } catch (_) { /* Visual update still applies. */ }
      render();
      return;
    }

    const button = card.querySelector(".favorite-remove");
    button.disabled = true;
    try {
      const response = await fetch(item.favorite_url, {
        method: "POST",
        headers: { "Accept": "application/json", "X-CSRF-TOKEN": csrf, "X-Requested-With": "XMLHttpRequest" }
      });
      if (!response.ok) throw new Error("favorite failed");
      const result = await response.json();
      if (result.favorited === false) {
        favorites = favorites.filter((favorite) => String(favorite.id) !== card.dataset.id);
        render();
      }
    } catch (_) {
      button.disabled = false;
      window.PalPrintNotifications?.add("تعذر تحديث المفضلة، حاولي مرة أخرى", "exclamation-triangle");
    }
  }

  grid.addEventListener("click", (event) => {
    const button = event.target.closest(".favorite-remove");
    if (!button) return;
    const card = button.closest(".favorite-card");
    if (card) removeFavorite(card);
  });

  render();
})();