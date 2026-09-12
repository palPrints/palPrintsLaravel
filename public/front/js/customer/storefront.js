(function () {
  "use strict";

  const form = document.getElementById("productSearchForm");
  const input = document.getElementById("productSearch");
  const grid = document.getElementById("productGrid");
  const emptyState = document.getElementById("productsEmpty");
  const countLabel = document.getElementById("productsCount");
  /* واجهة فلترة التصنيفات غير مركّبة في storefront.html حالياً،
     فهذه المحدِّدات تعود بقوائم فارغة والكود أدناه لا يفعل شيئاً.
     محفوظ لحين إعادة تركيب شريط الفلاتر. */
  const categoryButtons = [
    ...document.querySelectorAll(
      ".category-pill[data-category]:not(.category-submenu-toggle), .category-quick-pill[data-category]:not(.category-submenu-toggle)"
    )
  ];
  const categoryFilterToggle = document.querySelector(".category-filter-toggle");
  const categoryFilterMenu = document.getElementById("categoryFilterMenu");
  const submenuToggles = [...document.querySelectorAll(".category-submenu-toggle")];
  const submenus = [...document.querySelectorAll(".category-submenu")];
  const profileMenuToggle = document.getElementById("profileMenuToggle");
  const profileDropdown = document.getElementById("profileDropdown");
  const notificationsToggle = document.getElementById("notificationsToggle");
  const notificationsPanel = document.getElementById("notificationsPanel");
  const notificationsBadge = document.querySelector(".notifications-badge");
  const notificationsList = document.querySelector(".notifications-list");
  const notificationsEmpty = document.querySelector(".notifications-empty");
  const notificationsClear = document.querySelector(".notifications-clear");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const storeSidebar = document.getElementById("storeSidebar");
  const sidebarBackdrop = document.getElementById("sidebarBackdrop");
  const sidebarLogout = document.getElementById("storeSidebarLogout");

  // هيدر مصغّر أثناء التمرير — يبقى لاصقاً بأعلى الصفحة لكن بارتفاع أقل
  const storeHeader = document.querySelector(".store-header");
  if (storeHeader) {
    const HEADER_SCROLL_OFFSET = 8;
    const syncHeaderScrollState = () => {
      storeHeader.classList.toggle("is-scrolled", window.scrollY > HEADER_SCROLL_OFFSET);
    };
    window.addEventListener("scroll", syncHeaderScrollState, { passive: true });
    syncHeaderScrollState();
  }

  // القائمة المنسدلة "المتجر" بالهيدير — تفتح بالتمرير فوقها (ماوس)،
  // أو باللمس/الكيبورد بالضغطة الأولى، وتنتقل للرابط عادي بالضغطة الثانية
  const shopNavItem = document.getElementById("storeNavShop");
  const shopTrigger = document.getElementById("storeNavShopTrigger");
  const shopMenu = document.getElementById("storeNavShopMenu");

  if (shopNavItem && shopTrigger && shopMenu) {
    const hoverCapable = window.matchMedia("(hover: hover)");
    let hoverCloseTimer = null;

    const openShopMenu = () => {
      window.clearTimeout(hoverCloseTimer);
      shopMenu.hidden = false;
      shopNavItem.classList.add("is-open");
      shopTrigger.setAttribute("aria-expanded", "true");
    };

    const closeShopMenu = () => {
      shopMenu.hidden = true;
      shopNavItem.classList.remove("is-open");
      shopTrigger.setAttribute("aria-expanded", "false");
    };

    shopNavItem.addEventListener("pointerenter", (event) => {
      if (event.pointerType === "touch" || !hoverCapable.matches) return;
      openShopMenu();
    });

    shopNavItem.addEventListener("pointerleave", (event) => {
      if (event.pointerType === "touch" || !hoverCapable.matches) return;
      hoverCloseTimer = window.setTimeout(closeShopMenu, 150);
    });

    shopTrigger.addEventListener("click", (event) => {
      if (hoverCapable.matches) return;
      if (shopMenu.hidden) {
        event.preventDefault();
        openShopMenu();
      }
    });

    shopTrigger.addEventListener("keydown", (event) => {
      if (event.key === "ArrowDown") {
        event.preventDefault();
        openShopMenu();
        shopMenu.querySelector(".store-mega__item")?.focus();
      } else if (event.key === "Escape") {
        closeShopMenu();
      }
    });

    shopMenu.addEventListener("keydown", (event) => {
      if (event.key !== "Escape") return;
      closeShopMenu();
      shopTrigger.focus();
    });

    document.addEventListener("click", (event) => {
      if (event.target.closest("#storeNavShop")) return;
      closeShopMenu();
    });

    document.addEventListener("focusin", (event) => {
      if (!shopNavItem.contains(event.target)) closeShopMenu();
    });
  }

  const closeSubmenus = () => {
    submenus.forEach((submenu) => {
      submenu.hidden = true;
    });
    submenuToggles.forEach((toggle) => {
      toggle.setAttribute("aria-expanded", "false");
    });
  };

  if (!form || !input || !grid || !emptyState) return;

  if (notificationsToggle && notificationsPanel && notificationsBadge && notificationsList && notificationsEmpty) {
    const storageKey = "palprints-store-notifications";
    let notifications = [];
    let toastTimer = 0;

    try {
      const savedNotifications = JSON.parse(localStorage.getItem(storageKey) || "[]");
      if (Array.isArray(savedNotifications)) notifications = savedNotifications.slice(0, 20);
    } catch (_) { /* Notifications remain available for this session. */ }

    const saveNotifications = () => {
      try { localStorage.setItem(storageKey, JSON.stringify(notifications)); }
      catch (_) { /* Session-only fallback. */ }
    };

    const renderNotifications = () => {
      notificationsList.replaceChildren();

      notifications.forEach((notification) => {
        const item = document.createElement("div");
        const icon = document.createElement("i");
        const content = document.createElement("div");
        const message = document.createElement("p");
        const time = document.createElement("time");

        item.className = "notification-item";
        icon.className = `bi bi-${notification.icon || "bell"}`;
        icon.setAttribute("aria-hidden", "true");
        message.textContent = notification.message;
        time.dateTime = new Date(notification.createdAt).toISOString();
        time.textContent = new Intl.DateTimeFormat("ar", { hour: "numeric", minute: "2-digit" }).format(notification.createdAt);
        content.append(message, time);
        item.append(icon, content);
        notificationsList.append(item);
      });

      const unreadCount = notifications.filter((notification) => notification.unread).length;
      notificationsBadge.textContent = unreadCount > 99 ? "99+" : String(unreadCount);
      notificationsBadge.hidden = unreadCount === 0;
      notificationsEmpty.hidden = notifications.length > 0;
      notificationsToggle.setAttribute("aria-label", unreadCount ? `الإشعارات، ${unreadCount} جديدة` : "الإشعارات");
    };

    const showToast = (message) => {
      document.querySelector(".store-notification-toast")?.remove();
      window.clearTimeout(toastTimer);

      const toast = document.createElement("div");
      toast.className = "store-notification-toast";
      toast.setAttribute("role", "status");
      toast.setAttribute("aria-live", "polite");
      toast.innerHTML = '<i class="bi bi-heart-fill" aria-hidden="true"></i><span></span>';
      toast.querySelector("span").textContent = message;
      document.body.append(toast);
      requestAnimationFrame(() => toast.classList.add("is-visible"));

      toastTimer = window.setTimeout(() => {
        toast.classList.remove("is-visible");
        window.setTimeout(() => toast.remove(), 300);
      }, 2800);
    };

    const ringNotificationBell = () => {
      notificationsToggle.classList.remove("has-new-notification");
      void notificationsToggle.offsetWidth;
      notificationsToggle.classList.add("has-new-notification");
      window.setTimeout(() => notificationsToggle.classList.remove("has-new-notification"), 800);
    };

    window.PalPrintNotifications = {
      add(message, icon = "bell") {
        notifications.unshift({ message, icon, unread: true, createdAt: Date.now() });
        notifications = notifications.slice(0, 20);
        saveNotifications();
        renderNotifications();
        ringNotificationBell();
        showToast(message);
      }
    };

    notificationsToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      const willOpen = notificationsPanel.hidden;
      notificationsPanel.hidden = !willOpen;
      notificationsToggle.setAttribute("aria-expanded", String(willOpen));

      if (willOpen) {
        if (profileDropdown && profileMenuToggle) {
          profileDropdown.hidden = true;
          profileMenuToggle.setAttribute("aria-expanded", "false");
        }
        notifications.forEach((notification) => { notification.unread = false; });
        saveNotifications();
        renderNotifications();
      }
    });

    notificationsClear?.addEventListener("click", () => {
      notifications = [];
      saveNotifications();
      renderNotifications();
    });

    document.addEventListener("click", (event) => {
      if (event.target.closest(".notifications-menu")) return;
      notificationsPanel.hidden = true;
      notificationsToggle.setAttribute("aria-expanded", "false");
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || notificationsPanel.hidden) return;
      notificationsPanel.hidden = true;
      notificationsToggle.setAttribute("aria-expanded", "false");
      notificationsToggle.focus();
    });

    renderNotifications();
  }

  if (profileMenuToggle && profileDropdown) {
    profileMenuToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      const isOpen = profileMenuToggle.getAttribute("aria-expanded") === "true";
      profileMenuToggle.setAttribute("aria-expanded", String(!isOpen));
      profileDropdown.hidden = isOpen;
      if (!isOpen && notificationsPanel && notificationsToggle) {
        notificationsPanel.hidden = true;
        notificationsToggle.setAttribute("aria-expanded", "false");
      }
    });

    document.addEventListener("click", (event) => {
      if (event.target.closest(".profile-menu")) return;
      profileDropdown.hidden = true;
      profileMenuToggle.setAttribute("aria-expanded", "false");
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || profileDropdown.hidden) return;
      profileDropdown.hidden = true;
      profileMenuToggle.setAttribute("aria-expanded", "false");
      profileMenuToggle.focus();
    });
  }

  // قائمة الحساب الجانبية — بالديسكتوب/التابلت بتدفع المحتوى وتظهر جنبه
  // (بدون تعتيم فوق الصفحة)، وبالموبايل الشاشة صغيرة فبتظهر فوق المحتوى
  // بخلفية معتمة لأنه ما في مساحة تدفع فيها المحتوى بدون ما يتكسر.
  if (sidebarToggle && storeSidebar && sidebarBackdrop) {
    const wideScreen = window.matchMedia("(min-width: 992px)");
    const sidebarToggleIcon = sidebarToggle.querySelector("i");
    storeSidebar.hidden = false;
    storeSidebar.setAttribute("aria-hidden", "true");

    const openSidebar = () => {
      storeSidebar.classList.add("is-open");
      sidebarToggle.setAttribute("aria-expanded", "true");
      sidebarToggle.setAttribute("aria-label", "إغلاق القائمة الجانبية");
      storeSidebar.setAttribute("aria-hidden", "false");
      sidebarToggleIcon?.classList.replace("bi-list", "bi-x-lg");

      if (wideScreen.matches) {
        document.body.classList.add("sidebar-push-open");
      } else {
        sidebarBackdrop.hidden = false;
        document.body.style.overflow = "hidden";
      }
      storeSidebar.querySelector(".store-sidebar__item")?.focus();
    };

    const closeSidebar = () => {
      storeSidebar.classList.remove("is-open");
      sidebarToggle.setAttribute("aria-expanded", "false");
      sidebarToggle.setAttribute("aria-label", "فتح القائمة الجانبية");
      storeSidebar.setAttribute("aria-hidden", "true");
      sidebarToggleIcon?.classList.replace("bi-x-lg", "bi-list");
      document.body.classList.remove("sidebar-push-open");
      sidebarBackdrop.hidden = true;
      document.body.style.overflow = "";
    };

    wideScreen.addEventListener("change", () => {
      if (!storeSidebar.classList.contains("is-open")) return;
      closeSidebar();
    });

    sidebarToggle.addEventListener("click", () => {
      if (storeSidebar.classList.contains("is-open")) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    sidebarBackdrop.addEventListener("click", closeSidebar);

    storeSidebar.addEventListener("click", (event) => {
      if (event.target.closest("a")) closeSidebar();
    });

    if (sidebarLogout) {
      sidebarLogout.addEventListener("click", () => {
        const confirmed = window.confirm("هل تريد تسجيل الخروج من حسابك؟");
        if (confirmed) window.location.href = sidebarLogout.dataset.href || "login.html";
      });
    }

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || !storeSidebar.classList.contains("is-open")) return;
      closeSidebar();
      sidebarToggle.focus();
    });
  }

  // Category pages reuse the store shell and provide their own catalog behavior.
  if (document.body.classList.contains("hoodies-page")) return;

  const normalize = (value) => value
    .toLocaleLowerCase("ar")
    .replace(/[\u064B-\u065F\u0670\u06D6-\u06ED]/g, "")
    .replace(/ـ/g, "")
    .trim();

  const cards = [...grid.querySelectorAll(".product-card")];
  cards
    .slice()
    .sort((a, b) => Number(a.dataset.order) - Number(b.dataset.order))
    .forEach((card) => grid.append(card));

  const productPrices = {
    shirt: 15,
    hoodie: 35,
    cap: 20,
    bag: 50,
    scarf: 18,
    "phone-case": 25,
    cups: 12,
    paper: 10,
    notebooks: 15,
    posters: 20,
    stickers: 8,
    "wedding-cards": 30
  };

  const productImages = {
    shirt: "1.png",
    hoodie: "2.png",
    cap: "3.png",
    bag: "4.png",
    scarf: "5.png",
    "phone-case": "6.png",
    cups: "7.png",
    paper: "8.png",
    notebooks: "9.png",
    posters: "10.png",
    stickers: "11.png",
    "wedding-cards": "12.png"
  };

  const hoverImages = {
    shirt: "تحديث1.png",
    hoodie: "تحديث2.png",
    paper: "تحديث3.png",
    stickers: "تحديث4.png",
    cups: "تحديث5.png"
  };

  const heroCopy = document.querySelector(".hero-copy");
  const heroVisual = document.querySelector(".hero-visual");
  const productsSection = document.querySelector(".products-section");
  const productsHeading = document.querySelector(".section-heading");

  const reveal = (element) => {
    if (element) element.classList.add("is-revealed");
  };

  if (heroCopy && heroVisual && productsSection && productsHeading) {
    document.body.classList.add("storefront-motion-ready");

    requestAnimationFrame(() => {
      reveal(heroCopy);
      window.setTimeout(() => reveal(heroVisual), 420);
    });

    const rows = [];
    cards.forEach((card) => {
      const top = card.getBoundingClientRect().top;
      let row = rows.find((group) => Math.abs(group.top - top) < 8);
      if (!row) {
        row = { top, cards: [] };
        rows.push(row);
      }
      row.cards.push(card);
    });

    const revealProducts = () => {
      reveal(productsHeading);
      if (!rows.length) return;

      window.setTimeout(() => {
        rows[0].cards.forEach(reveal);
      }, 260);

      rows.slice(1).forEach((row) => {
        const rowObserver = new IntersectionObserver((entries, observer) => {
          if (!entries.some((entry) => entry.isIntersecting)) return;
          row.cards.forEach(reveal);
          observer.disconnect();
        }, { threshold: 0.15, rootMargin: "0px 0px -12% 0px" });
        rowObserver.observe(row.cards[0]);
      });
    };

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      revealProducts();
    } else {
      const productsObserver = new IntersectionObserver((entries, observer) => {
        if (!entries.some((entry) => entry.isIntersecting)) return;
        revealProducts();
        observer.disconnect();
      }, { threshold: 0.12 });
      productsObserver.observe(productsSection);
    }
  }

  cards.forEach((card) => {
    const body = card.querySelector(".product-card__body");
    if (!body) return;

    const price = document.createElement("span");
    price.className = "product-card__price";
    price.dir = "ltr";
    price.textContent = `$${productPrices[card.dataset.product].toFixed(2)}`;
    body.append(price);
  });

  cards.forEach((card) => {
    const media = card.querySelector(".product-card__media");
    const image = media?.querySelector("img");
    if (!image) return;

    const product = card.dataset.product;
    const productImagesBase = window.palPrintsCustomerAssets?.products || "/front/assets/images/customer/products";
    const originalSrc = `${productImagesBase}/${productImages[product]}`;
    const hoverSrc = hoverImages[product]
      ? `${productImagesBase}/${hoverImages[product]}`
      : null;
    image.src = originalSrc;

    if (hoverSrc) {
      const hoverImage = new Image();
      hoverImage.src = hoverSrc;
    }

    const setHovered = (isHovered) => {
      card.classList.toggle("is-image-hovered", isHovered);
      if (!hoverSrc) return;
      image.src = isHovered ? hoverSrc : originalSrc;
      image.classList.toggle("is-hover-image", isHovered);
    };

    card.addEventListener("pointerenter", () => setHovered(true));
    card.addEventListener("pointerleave", () => setHovered(false));
    card.addEventListener("focusin", () => setHovered(true));
    card.addEventListener("focusout", () => setHovered(false));
  });

  cards.forEach((card) => {
    const href = card.dataset.href;
    if (!href || card.dataset.available === "false") return;

    card.addEventListener("click", (event) => {
      if (event.target.closest("a, button")) return;
      window.location.href = href;
    });
  });

  let selectedCategory = "all";

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    applyFilters();
  });

  const applyFilters = () => {
    const query = normalize(input.value);
    let visibleCount = 0;

    cards.forEach((card) => {
      const searchableText = normalize(card.textContent);
      const categoryMatch = selectedCategory === "all" || card.dataset.category === selectedCategory;
      const isMatch = categoryMatch && (!query || searchableText.includes(query));

      card.classList.toggle("is-filter-hidden", !isMatch);
      if (isMatch) visibleCount += 1;
    });

    emptyState.hidden = visibleCount > 0;
    if (countLabel) countLabel.textContent = `${visibleCount} منتجات`;
  };

  input.addEventListener("input", applyFilters);

  categoryButtons.forEach((button) => {
    button.addEventListener("click", () => {
      selectedCategory = button.dataset.category;
      categoryButtons.forEach((categoryButton) => {
        const isSelected = categoryButton.dataset.category === selectedCategory;
        categoryButton.setAttribute("aria-pressed", String(isSelected));
        categoryButton.classList.toggle("is-active", isSelected);
      });
      applyFilters();

      if (categoryFilterMenu && categoryFilterToggle) {
        categoryFilterMenu.hidden = true;
        categoryFilterToggle.setAttribute("aria-expanded", "false");
      }
      closeSubmenus();
    });
  });

  if (categoryFilterToggle && categoryFilterMenu) {
    categoryFilterToggle.addEventListener("click", () => {
      const isOpen = categoryFilterToggle.getAttribute("aria-expanded") === "true";
      categoryFilterToggle.setAttribute("aria-expanded", String(!isOpen));
      categoryFilterMenu.hidden = isOpen;
    });

    document.addEventListener("click", (event) => {
      if (!event.target.closest(".category-filter-dropdown")) {
        categoryFilterMenu.hidden = true;
        categoryFilterToggle.setAttribute("aria-expanded", "false");
        closeSubmenus();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        categoryFilterMenu.hidden = true;
        categoryFilterToggle.setAttribute("aria-expanded", "false");
        closeSubmenus();
      }
    });
  }

  submenuToggles.forEach((submenuToggle) => {
    const submenu = document.getElementById(submenuToggle.getAttribute("aria-controls"));
    if (!submenu) return;

    submenuToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      const isOpen = submenuToggle.getAttribute("aria-expanded") === "true";
      closeSubmenus();
      submenuToggle.setAttribute("aria-expanded", String(!isOpen));
      submenu.hidden = isOpen;
    });
  });

  applyFilters();
})();
