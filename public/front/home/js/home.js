(() => {
  const menu = document.querySelector(".home-admin-header__menu");
  const navigation = document.getElementById("homeNavigation");
  if (!menu || !navigation) return;

  const setOpen = (open) => {
    navigation.classList.toggle("is-open", open);
    menu.setAttribute("aria-expanded", String(open));
    menu.setAttribute("aria-label", open ? "إغلاق قائمة التنقل" : "فتح قائمة التنقل");
  };

  menu.addEventListener("click", () => setOpen(menu.getAttribute("aria-expanded") !== "true"));
  navigation.addEventListener("click", (event) => {
    if (event.target.closest("a")) setOpen(false);
  });
  document.addEventListener("click", (event) => {
    if (!event.target.closest(".home-admin-header")) setOpen(false);
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && menu.getAttribute("aria-expanded") === "true") {
      setOpen(false);
      menu.focus();
    }
  });
  window.matchMedia("(max-width: 1000px)").addEventListener("change", () => setOpen(false));
})();

(() => {
  const track = document.getElementById("productsCarouselTrack");
  if (!track) return;

  const prevBtn = document.querySelector(".products-carousel__arrow--prev");
  const nextBtn = document.querySelector(".products-carousel__arrow--next");

  const scrollByCards = (direction) => {
    const card = track.querySelector(".product-card");
    const step = card ? card.getBoundingClientRect().width + 20 : 220;
    track.scrollBy({ left: direction * step * 2, behavior: "smooth" });
  };

  prevBtn?.addEventListener("click", () => scrollByCards(-1));
  nextBtn?.addEventListener("click", () => scrollByCards(1));
})();

(() => {
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const revealGroups = [
    {
      element: document.querySelector(".site-stats"),
      items: ".site-stats__header, .site-stat",
    },
    {
      element: document.querySelector(".about-us"),
      items: ".about-us__eyebrow, .about-us__content > h2, .about-us__text, .about-card, .about-us__button",
    },
    {
      element: document.querySelector(".what-to-print"),
      items: ".what-to-print__header, .products-carousel",
    },
    {
      element: document.querySelector(".partner-cta__video-title"),
      revealSelf: true,
    },
    {
      element: document.querySelector(".partner-cta__video"),
      revealSelf: true,
    },
    {
      element: document.querySelector(".partner-card--designer"),
      items: ".partner-card__content, .partner-card__photo",
      revealSelf: true,
    },
    {
      element: document.querySelector(".partner-card--printer"),
      items: ":scope > h2, :scope > p, .partner-card__stats, :scope > .partner-card__button",
      revealSelf: true,
    },
    {
      element: document.querySelector(".home-footer"),
      items: ".home-footer__brand, .home-footer__column, .home-footer__bottom",
    },
  ].filter(({ element }) => element);

  const prepareItems = (container, selector) => {
    container.querySelectorAll(selector).forEach((item, index) => {
      item.classList.add("reveal-item");
      item.style.setProperty("--reveal-delay", `${Math.min(index * 90, 450)}ms`);
    });
  };

  revealGroups.forEach(({ element, items, revealSelf }) => {
    element.classList.add("scroll-reveal");
    if (revealSelf) element.classList.add("reveal-self");
    if (items) prepareItems(element, items);
  });

  document.documentElement.classList.add("reveal-ready");

  if (reduceMotion) {
    revealGroups.forEach(({ element }) => element.classList.add("is-visible"));
    return;
  }

  if (!("IntersectionObserver" in window)) {
    revealGroups.forEach(({ element }) => element.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    {
      threshold: 0.14,
      rootMargin: "0px 0px -8% 0px",
    },
  );

  revealGroups.forEach(({ element }) => observer.observe(element));
})();

(() => {
  const counters = document.querySelectorAll("[data-count]");
  if (!counters.length) return;

  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduceMotion || !("IntersectionObserver" in window)) return;

  const duration = 1600;
  const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);

  const render = (el, value) => {
    el.textContent = `${Math.round(value)}${el.dataset.suffix || ""}`;
  };

  const run = (el) => {
    const target = Number(el.dataset.count);
    const start = performance.now();

    const tick = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      render(el, target * easeOutCubic(progress));
      if (progress < 1) requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  };

  counters.forEach((el) => render(el, 0));

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        run(entry.target);
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.6 },
  );

  counters.forEach((el) => observer.observe(el));
})();
