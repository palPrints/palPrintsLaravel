/* Admin shipping page: shipment counters and status filters. */
(function (window, document) {
  "use strict";

  function initialize() {
    const shippingEmptyRow = document.getElementById("shippingEmptyRow");
    const shippingResultsCount = document.getElementById("shippingResultsCount");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    let activeShippingFilter = "all";

    function animateShippingCounters() {
      document.querySelectorAll("[data-shipping-counter]").forEach(function (counter, index) {
        const target = Number(counter.dataset.shippingCounter);
        if (reducedMotion.matches) {
          counter.textContent = String(target);
          return;
        }
        counter.textContent = "0";
        window.setTimeout(function () {
          const start = window.performance.now();
          function update(now) {
            const progress = Math.min((now - start) / 700, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            counter.textContent = String(Math.round(target * eased));
            if (progress < 1) window.requestAnimationFrame(update);
          }
          window.requestAnimationFrame(update);
        }, index * 90);
      });
    }

    function filterShipments() {
      const rows = document.querySelectorAll("[data-shipping-row]");
      let visible = 0;
      rows.forEach(function (row) {
        const statusMatches = activeShippingFilter === "all" || row.dataset.status === activeShippingFilter;
        row.hidden = !statusMatches;
        if (!row.hidden) visible += 1;
      });
      shippingEmptyRow.hidden = visible !== 0;
      shippingResultsCount.textContent = "عرض " + visible + " من أصل " + rows.length + " شحنات";
    }

    animateShippingCounters();
    filterShipments();

    document.querySelectorAll("[data-shipping-filter]").forEach(function (button) {
      button.addEventListener("click", function () {
        activeShippingFilter = button.dataset.shippingFilter;
        document.querySelectorAll("[data-shipping-filter]").forEach(function (item) { item.classList.toggle("active", item === button); });
        filterShipments();
      });
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initialize, { once: true });
  else initialize();
})(window, document);
