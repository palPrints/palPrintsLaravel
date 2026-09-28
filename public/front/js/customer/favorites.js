(function () {
  "use strict";

  const catalogs = [
    { key: "palprints-tshirt-favorites", page: "tshirts.html", items: [
      ["flower-good-day", "تيشيرت أطفال", "يوم سعيد", "Lina A.", 20, "assets/images/tshirts/kids-flower-good-day.png"], ["panda-music", "تيشيرت أطفال", "موسيقى دائماً", "Omar K.", 20, "assets/images/tshirts/kids-panda-music.png"], ["little-explorer", "تيشيرت أطفال", "للمستكشف الصغير", "Sara N.", 20, "assets/images/tshirts/kids-little-explorer.png"], ["moon-good-day", "تيشيرت رجال / نساء", "يوم جيد دائماً", "Ahmad Z.", 20, "assets/images/tshirts/adults-good-day.png"], ["good-things", "تيشيرت رجال / نساء", "الأمور الجيدة تستغرق وقتاً", "Haneen S.", 20, "assets/images/tshirts/adults-good-things.png"], ["salam", "تيشيرت أوفر سايز", "سلام", "Yousef M.", 25, "assets/images/tshirts/oversized-salam.png"]
    ]},
    { key: "palprints-mug-favorites", page: "mugs.html", items: [
      ["morning-calm", "كوب صباح هادئ", "تصميم بسيط لبداية يوم مليئة بالهدوء", "Lina A.", 12, "assets/images/mugs/coffee-salam.png"], ["coffee-first", "كوب القهوة أولاً", "رفيق أنيق لعشاق القهوة في كل صباح", "Omar K.", 12, "assets/images/mugs/red-coffee.png"], ["warm-moments", "كوب لحظات دافئة", "لمسة دافئة تناسب البيت ومساحة العمل", "Sara N.", 12, "assets/images/mugs/cat-good-day.png"], ["daily-inspiration", "كوب إلهام يومي", "تفصيل صغير يذكّرك بأنك تستطيع", "Ahmad Z.", 12, "assets/images/mugs/be-happy.png"], ["creative-story", "كوب اصنع قصتك", "تصميم عصري لكل فكرة تستحق أن تُروى", "Haneen S.", 12, "assets/images/mugs/palestine.png"], ["classic-black", "كوب أسود أنيق", "اختيار كلاسيكي بطابع جريء ومميز", "Yousef M.", 12, "assets/images/mugs/black-explore.png"], ["special-memory", "كوب ذكرى خاصة", "هدية شخصية تحفظ أجمل التفاصيل", "Rana H.", 12, "assets/images/mugs/you-are-special.png"], ["good-vibes", "كوب طاقة إيجابية", "ألوان مبهجة ورسالة تلائم كل يوم", "Khaled N.", 12, "assets/images/mugs/good-vibes.png"]
    ]},
    { key: "palprints-hoodie-favorites", page: "hoodies.html", items: [
      ["explore", "هودي أوفر سايز", "اكتشف أكثر", "Lina A.", 20, "assets/images/hoodie.png"], ["salam", "هودي رجال / نساء", "سلام دائم", "Omar K.", 20, "assets/images/hoodie-black.png"], ["kind", "هودي أطفال", "قلب صغير كبير", "Sara N.", 20, "assets/images/hoodie.png"], ["dream", "هودي أوفر سايز", "احلم أكبر", "Ahmad Z.", 20, "assets/images/hoodie.png"], ["day", "هودي أطفال", "يوم أجمل", "Haneen S.", 20, "assets/images/hoodie.png"], ["good", "هودي رجال / نساء", "الأشياء الجيدة", "Yousef M.", 20, "assets/images/hoodie.png"], ["smile", "هودي أطفال", "ابتسم دائماً", "Rana H.", 20, "assets/images/hoodie.png"], ["create", "هودي رجال / نساء", "اصنع قصتك", "Khaled N.", 20, "assets/images/hoodie-black.png"]
    ]}
  ];

  const grid = document.getElementById("favoritesGrid");
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
      return catalog.items.filter((item) => ids.includes(item[0])).map((item) => ({ id: item[0], title: item[1], description: item[2], designer: item[3], price: item[4], image: item[5], storageKey: catalog.key, page: catalog.page }));
    });
  }

  function render() {
    const visible = favorites;
    const isEmpty = favorites.length === 0;
    grid.hidden = isEmpty;
    emptyState.hidden = !isEmpty;
    suggestions.hidden = !isEmpty;
    grid.innerHTML = visible.map((item) => `<article class="favorite-card" data-id="${item.id}" data-storage-key="${item.storageKey}"><div class="favorite-card__media"><img src="${item.image}" alt="${item.title} — ${item.description}" loading="lazy"><button class="favorite-remove" type="button" aria-label="إزالة ${item.title} من المفضلة"><i class="bi bi-heart-fill" aria-hidden="true"></i></button></div><div class="favorite-card__body"><h2>${item.title}</h2><p>${item.description}</p><div class="favorite-meta"><span dir="ltr"><i class="bi bi-person"></i> by ${item.designer}</span><strong dir="ltr">${item.price} ₪</strong></div><a class="btn-brand favorite-preview" href="${item.page}?id=${encodeURIComponent(item.id)}"><i class="bi bi-eye"></i>معاينة المنتج</a></div></article>`).join("");
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

  readFavorites();
  render();

  const body = document.body, menuButton = document.getElementById("menuButton"), menuIcon = menuButton.querySelector("i"), overlay = document.getElementById("sidebarOverlay"), mobile = window.matchMedia("(max-width: 820px)");
  const closeSidebar = () => { body.classList.remove("sidebar-open"); menuButton.setAttribute("aria-expanded", "false"); menuIcon.className = "bi bi-list"; };
  const syncSidebar = () => { closeSidebar(); body.classList.toggle("sidebar-collapsed", !mobile.matches); };
  menuButton.addEventListener("click", () => { if (mobile.matches) { const open = !body.classList.contains("sidebar-open"); body.classList.toggle("sidebar-open", open); menuButton.setAttribute("aria-expanded", String(open)); menuIcon.className = open ? "bi bi-x-lg" : "bi bi-list"; } else { const collapsed = !body.classList.contains("sidebar-collapsed"); body.classList.toggle("sidebar-collapsed", collapsed); menuIcon.className = collapsed ? "bi bi-list" : "bi bi-x-lg"; } });
  overlay.addEventListener("click", closeSidebar); mobile.addEventListener("change", syncSidebar); syncSidebar();

  const notificationButton = document.getElementById("notificationButton"), notificationDropdown = document.getElementById("notificationDropdown");
  notificationButton.addEventListener("click", (event) => { event.stopPropagation(); const open = !notificationDropdown.classList.contains("open"); notificationDropdown.hidden = !open; requestAnimationFrame(() => notificationDropdown.classList.toggle("open", open)); notificationButton.setAttribute("aria-expanded", String(open)); });
  document.addEventListener("click", () => { notificationDropdown.classList.remove("open"); notificationDropdown.hidden = true; notificationButton.setAttribute("aria-expanded", "false"); });
})();
