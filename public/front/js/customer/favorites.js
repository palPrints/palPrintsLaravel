(function () {
  "use strict";

  const grid = document.getElementById("favoritesGrid");
  if (!grid) return;

  const data = window.palPrintsFavoritesData || {};
  const imageBase = data.imageBase || "";
  const pages = data.pages || {};

  const catalogs = [
    { key: "palprints-tshirt-favorites", pageKey: "tshirts", items: [
      ["flower-good-day", "تيشيرت أطفال", "يوم سعيد", "Lina A.", 20, "tshirts/kids-flower-good-day.png"], ["panda-music", "تيشيرت أطفال", "موسيقى دائماً", "Omar K.", 20, "tshirts/kids-panda-music.png"], ["little-explorer", "تيشيرت أطفال", "للمستكشف الصغير", "Sara N.", 20, "tshirts/kids-little-explorer.png"], ["moon-good-day", "تيشيرت رجال / نساء", "يوم جيد دائماً", "Ahmad Z.", 20, "tshirts/adults-good-day.png"], ["good-things", "تيشيرت رجال / نساء", "الأمور الجيدة تستغرق وقتاً", "Haneen S.", 20, "tshirts/adults-good-things.png"], ["salam", "تيشيرت أوفر سايز", "سلام", "Yousef M.", 25, "tshirts/oversized-salam.png"]
    ]},
    { key: "palprints-mug-favorites", pageKey: "mugs", items: [
      ["morning-calm", "كوب صباح هادئ", "تصميم بسيط لبداية يوم مليئة بالهدوء", "Lina A.", 12, "mugs/coffee-salam.png"], ["coffee-first", "كوب القهوة أولاً", "رفيق أنيق لعشاق القهوة في كل صباح", "Omar K.", 12, "mugs/red-coffee.png"], ["warm-moments", "كوب لحظات دافئة", "لمسة دافئة تناسب البيت ومساحة العمل", "Sara N.", 12, "mugs/cat-good-day.png"], ["daily-inspiration", "كوب إلهام يومي", "تفصيل صغير يذكّرك بأنك تستطيع", "Ahmad Z.", 12, "mugs/be-happy.png"], ["creative-story", "كوب اصنع قصتك", "تصميم عصري لكل فكرة تستحق أن تُروى", "Haneen S.", 12, "mugs/palestine.png"], ["classic-black", "كوب أسود أنيق", "اختيار كلاسيكي بطابع جريء ومميز", "Yousef M.", 12, "mugs/black-explore.png"], ["special-memory", "كوب ذكرى خاصة", "هدية شخصية تحفظ أجمل التفاصيل", "Rana H.", 12, "mugs/you-are-special.png"], ["good-vibes", "كوب طاقة إيجابية", "ألوان مبهجة ورسالة تلائم كل يوم", "Khaled N.", 12, "mugs/good-vibes.png"]
    ]},
    { key: "palprints-hoodie-favorites", pageKey: "hoodies", items: [
      ["explore", "هودي أوفر سايز", "اكتشف أكثر", "Lina A.", 20, "hoodie.png"], ["salam", "هودي رجال / نساء", "سلام دائم", "Omar K.", 20, "hoodie-black.png"], ["kind", "هودي أطفال", "قلب صغير كبير", "Sara N.", 20, "hoodie.png"], ["dream", "هودي أوفر سايز", "احلم أكبر", "Ahmad Z.", 20, "hoodie.png"], ["day", "هودي أطفال", "يوم أجمل", "Haneen S.", 20, "hoodie.png"], ["good", "هودي رجال / نساء", "الأشياء الجيدة", "Yousef M.", 20, "hoodie.png"], ["smile", "هودي أطفال", "ابتسم دائماً", "Rana H.", 20, "hoodie.png"], ["create", "هودي رجال / نساء", "اصنع قصتك", "Khaled N.", 20, "hoodie-black.png"]
    ]}
  ];

  const search = document.getElementById("favoritesSearch");
  const emptyState = document.getElementById("favoritesEmpty");
  const suggestions = document.getElementById("favoritesSuggestions");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  let favorites = [];

  if (!reducedMotion) document.body.classList.add("favorites-motion-ready");

  function readFavorites() {
    favorites = catalogs.flatMap((catalog) => {
      let ids = [];
      try { const value = JSON.parse(localStorage.getItem(catalog.key) || "[]"); ids = Array.isArray(value) ? value : []; } catch (_) { ids = []; }
      return catalog.items.filter((item) => ids.includes(item[0])).map((item) => ({ id: item[0], title: item[1], description: item[2], designer: item[3], price: item[4], image: `${imageBase}/${item[5]}`, storageKey: catalog.key, page: pages[catalog.pageKey] || "#" }));
    });
  }

  function render() {
    const query = search.value.trim().toLocaleLowerCase("ar");
    const visible = favorites.filter((item) => `${item.title} ${item.description} ${item.designer}`.toLocaleLowerCase("ar").includes(query));
    const isEmpty = favorites.length === 0;
    grid.hidden = isEmpty;
    emptyState.hidden = !isEmpty;
    suggestions.hidden = !isEmpty;
    grid.innerHTML = visible.map((item) => `<article class="favorite-card" data-id="${item.id}" data-storage-key="${item.storageKey}"><div class="favorite-card__media"><img src="${item.image}" alt="${item.title} — ${item.description}" loading="lazy"><button class="favorite-remove" type="button" aria-label="إزالة ${item.title} من المفضلة"><i class="bi bi-heart-fill" aria-hidden="true"></i></button></div><div class="favorite-card__body"><h2>${item.title}</h2><p>${item.description}</p><div class="favorite-meta"><span dir="ltr"><i class="bi bi-person"></i> by ${item.designer}</span><strong dir="ltr">$${item.price}</strong></div><a class="btn-brand favorite-preview" href="${item.page}?id=${encodeURIComponent(item.id)}"><i class="bi bi-eye"></i>معاينة المنتج</a></div></article>`).join("");
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

  grid.addEventListener("click", (event) => {
    const button = event.target.closest(".favorite-remove");
    if (!button) return;
    const card = button.closest(".favorite-card");
    const ids = favorites.filter((item) => item.storageKey === card.dataset.storageKey && item.id !== card.dataset.id).map((item) => item.id);
    try { localStorage.setItem(card.dataset.storageKey, JSON.stringify(ids)); } catch (_) { /* The visual state still updates for this session. */ }
    favorites = favorites.filter((item) => !(item.storageKey === card.dataset.storageKey && item.id === card.dataset.id));
    render();
  });

  search.addEventListener("input", render);
  readFavorites();
  render();
})();
