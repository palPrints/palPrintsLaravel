/* Admin dashboard page: orders search, animated counters, section reveal. */
(function (window, document) {
  "use strict";

  function initialize() {
    const searchInput = document.getElementById("dashboardSearch");
    const emptyOrdersRow = document.getElementById("emptyOrdersRow");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    function normalizeSearchValue(value) {
      return String(value || "")
        .trim()
        .toLocaleLowerCase("ar")
        .replace(/[أإآ]/g, "ا")
        .replace(/ة/g, "ه")
        .replace(/ى/g, "ي");
    }

    function filterOrders() {
      if (!searchInput) return;
      const query = normalizeSearchValue(searchInput.value);
      const rows = document.querySelectorAll("[data-order-row]");
      let visibleRows = 0;
      rows.forEach(function (row) {
        const searchableText = row.getAttribute("data-search") || row.textContent;
        const matches = !query || normalizeSearchValue(searchableText).includes(query);
        row.hidden = !matches;
        if (matches) visibleRows += 1;
      });
      if (emptyOrdersRow && rows.length > 0) emptyOrdersRow.hidden = visibleRows !== 0;
    }

    function animateCounters() {
      const counters = Array.from(document.querySelectorAll("[data-admin-counter]"));
      if (reducedMotion.matches) return;
      counters.forEach(function (counter) {
        const target = Number(counter.dataset.adminCounter);
        const start = window.performance.now();
        const formatter = new Intl.NumberFormat("en-US", { maximumFractionDigits: 0 });
        function update(now) {
          const progress = Math.min((now - start) / 950, 1);
          const eased = 1 - Math.pow(1 - progress, 3);
          counter.textContent = formatter.format(Math.round(target * eased));
          if (progress < 1) window.requestAnimationFrame(update);
        }
        window.requestAnimationFrame(update);
      });
    }

    function setupSectionReveal() {
      const sections = Array.from(document.querySelectorAll("#adminDashboardMain > *"));
      sections.forEach(function (section, index) {
        section.classList.add("admin-section-reveal");
        section.style.setProperty("--reveal-delay", String(index * 65) + "ms");
      });
      if (reducedMotion.matches || !("IntersectionObserver" in window)) {
        sections.forEach(function (section) {
          section.classList.add("is-visible");
        });
        return;
      }
      const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        });
      }, { threshold: 0.04, rootMargin: "0px 0px -6% 0px" });
      sections.forEach(function (section) {
        observer.observe(section);
      });
    }

    setupSectionReveal();
    animateCounters();
    filterOrders();

    if (searchInput) searchInput.addEventListener("input", filterOrders);

  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(window, document);
