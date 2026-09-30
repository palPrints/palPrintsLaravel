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
  const hero = document.querySelector(".home-hero");
  const revealGroups = [
    {
      element: document.querySelector(".what-to-print"),
      items: ".what-to-print__header, .products-carousel",
    },
    {
      element: document.querySelector(".featured-designs"),
      items: ".featured-designs__header, .occasion-card",
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

  if (hero) {
    hero.classList.add("hero-reveal");
    prepareItems(hero, ".home-hero__content > *");
  }

  revealGroups.forEach(({ element, items, revealSelf }) => {
    element.classList.add("scroll-reveal");
    if (revealSelf) element.classList.add("reveal-self");
    if (items) prepareItems(element, items);
  });

  document.documentElement.classList.add("reveal-ready");

  if (reduceMotion) {
    hero?.classList.add("is-visible");
    revealGroups.forEach(({ element }) => element.classList.add("is-visible"));
    return;
  }

  requestAnimationFrame(() => {
    requestAnimationFrame(() => hero?.classList.add("is-visible"));
  });

  if (hero) {
    const slides = hero.querySelector(".home-hero__slides");
    let ticking = false;

    const updateHeroParallax = () => {
      ticking = false;
      const rect = hero.getBoundingClientRect();
      if (rect.bottom <= 0 || rect.top >= window.innerHeight) return;
      const shift = Math.min(Math.max(-rect.top * 0.12, 0), 24);
      slides?.style.setProperty("--hero-parallax", `${shift}px`);
    };

    window.addEventListener(
      "scroll",
      () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(updateHeroParallax);
      },
      { passive: true },
    );

    updateHeroParallax();
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
