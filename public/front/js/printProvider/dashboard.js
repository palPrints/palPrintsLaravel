(function (window, document) {
  "use strict";

  function initialize() {
    const body = document.body;
    const sidebar = document.getElementById("walletSidebar");
    const backdrop = document.getElementById("sidebarBackdrop");
    const menuButton = document.getElementById("menuButton");
    const searchInput = document.getElementById("dashboardSearch");
    const emptySearchRow = document.getElementById("emptySearchRow");
    const logoutButton = document.getElementById("logoutButton");
    const accountButton = document.getElementById("accountButton");
    const accountDropdown = document.getElementById("accountDropdown");
    const notificationButton = document.getElementById("notificationButton");
    const notificationDropdown = document.getElementById("notificationDropdown");
    const toast = document.getElementById("dashboardToast");
    const mobileLayout = window.matchMedia("(max-width: 760px)");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    let toastTimer = 0;

    function setupContentMotion() {
      // A toast inside the main area is not a section: "is-visible" is also what shows a toast, so revealing it would
      // leave it on screen for good.
      const sections = Array.from(
        document.querySelectorAll("#printshopDashboardMain > *:not(.printshop-toast)")
      );

      sections.forEach(function (section, index) {
        section.classList.add("section-reveal");
        section.style.setProperty("--reveal-delay", String(index * 120) + "ms");
      });

      if (reducedMotion.matches || !("IntersectionObserver" in window)) {
        sections.forEach(function (section) {
          section.classList.add("is-visible");
        });
      } else {
        const revealObserver = new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (!entry.isIntersecting) return;
              entry.target.classList.add("is-visible");
              revealObserver.unobserve(entry.target);
            });
          },
          { threshold: 0.05, rootMargin: "0px 0px -8% 0px" }
        );

        sections.forEach(function (section) {
          revealObserver.observe(section);
        });
      }

      const counters = Array.from(
        document.querySelectorAll("[data-dashboard-counter]")
      );

      function animateCounter(counter) {
        if (counter.dataset.counted === "true") return;

        counter.dataset.counted = "true";
        const target = Number(counter.dataset.dashboardCounter);
        const currency = counter.dataset.counterCurrency === "true";
        const duration = 1300;
        const start = window.performance.now();
        const formatter = new Intl.NumberFormat("en-US", {
          minimumFractionDigits: currency ? 2 : 0,
          maximumFractionDigits: currency ? 2 : 0
        });

        function update(now) {
          const progress = Math.min((now - start) / duration, 1);
          const eased = 1 - Math.pow(1 - progress, 3);
          counter.textContent = formatter.format(target * eased) + (currency ? " ₪" : "");

          if (progress < 1) window.requestAnimationFrame(update);
        }

        window.requestAnimationFrame(update);
      }

      if (reducedMotion.matches || !("IntersectionObserver" in window)) {
        counters.forEach(function (counter) {
          counter.dataset.counted = "true";
        });
      } else {
        const counterObserver = new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (!entry.isIntersecting) return;
              animateCounter(entry.target);
              counterObserver.unobserve(entry.target);
            });
          },
          { threshold: 0.45 }
        );

        counters.forEach(function (counter) {
          counterObserver.observe(counter);
        });
      }
    }

    function isMenuOpen() {
      return mobileLayout.matches
        ? body.classList.contains("sidebar-open")
        : !body.classList.contains("sidebar-collapsed");
    }

    function syncMenuState() {
      if (!menuButton) return;

      const open = isMenuOpen();
      menuButton.setAttribute("aria-expanded", String(open));
      menuButton.setAttribute("aria-label", open ? "إغلاق القائمة" : "فتح القائمة");

      const icon = menuButton.querySelector("i");
      if (icon) icon.className = open ? "bi bi-x-lg" : "bi bi-list";

      if (sidebar) {
        if (mobileLayout.matches && !open) sidebar.setAttribute("aria-hidden", "true");
        else sidebar.removeAttribute("aria-hidden");
      }
    }

    function closeMenu() {
      body.classList.remove("sidebar-open");
      if (backdrop) backdrop.classList.remove("open");
      syncMenuState();
    }

    function toggleMenu() {
      if (mobileLayout.matches) {
        const open = !body.classList.contains("sidebar-open");
        body.classList.toggle("sidebar-open", open);
        if (backdrop) backdrop.classList.toggle("open", open);
      } else {
        body.classList.toggle("sidebar-collapsed");
      }

      syncMenuState();
    }

    function closeAccountMenu() {
      if (!accountButton || !accountDropdown) return;
      accountDropdown.classList.remove("open");
      accountDropdown.hidden = true;
      accountButton.setAttribute("aria-expanded", "false");
    }

    function closeNotificationMenu() {
      if (!notificationButton || !notificationDropdown) return;
      notificationDropdown.classList.remove("open");
      notificationDropdown.hidden = true;
      notificationButton.setAttribute("aria-expanded", "false");
    }

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
        const text = normalizeSearchValue(row.getAttribute("data-search") || row.textContent);
        const matches = !query || text.includes(query);
        row.hidden = !matches;
        if (matches) visibleRows += 1;
      });

      // "No matching orders" only makes sense when there are orders to search in.
      if (emptySearchRow) emptySearchRow.hidden = rows.length === 0 || visibleRows !== 0;
    }

    function showToast(message) {
      if (!toast) return;
      window.clearTimeout(toastTimer);
      toast.textContent = message;
      toast.classList.add("is-visible");
      toastTimer = window.setTimeout(function () {
        toast.classList.remove("is-visible");
      }, 2600);
    }

    if (menuButton) menuButton.addEventListener("click", toggleMenu);
    if (backdrop) backdrop.addEventListener("click", closeMenu);
    if (searchInput) searchInput.addEventListener("input", filterOrders);

    if (accountButton && accountDropdown) {
      accountButton.addEventListener("click", function (event) {
        event.stopPropagation();
        const open = !accountDropdown.classList.contains("open");
        closeNotificationMenu();
        accountDropdown.classList.toggle("open", open);
        accountDropdown.hidden = !open;
        accountButton.setAttribute("aria-expanded", String(open));
      });
    }

    if (notificationButton && notificationDropdown) {
      notificationButton.addEventListener("click", function (event) {
        event.stopPropagation();
        notificationButton.classList.remove("is-shaking");
        void notificationButton.offsetWidth;
        notificationButton.classList.add("is-shaking");
        window.setTimeout(function () {
          notificationButton.classList.remove("is-shaking");
        }, 600);
        const open = !notificationDropdown.classList.contains("open");
        closeAccountMenu();
        notificationDropdown.classList.toggle("open", open);
        notificationDropdown.hidden = !open;
        notificationButton.setAttribute("aria-expanded", String(open));
      });
    }

    // The bell keeps itself current: every 30 seconds while the tab is visible (and when it comes back into view) it asks
    // the feed for the unread count and the menu, and a notification newer than the last one seen shakes the bell and
    // shows its title. The open menu is left alone until it is closed, so a click is never pulled from under the pointer.
    const bellWrap = document.querySelector(".notification-wrap[data-feed-url]");
    const counter = document.getElementById("notificationCounter");
    if (bellWrap && notificationButton && notificationDropdown) {
      let latestSeen = Number(bellWrap.dataset.latestId || 0);
      let busy = false;

      function refreshBell() {
        if (busy || document.hidden) return;
        busy = true;
        fetch(bellWrap.dataset.feedUrl, { headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" }, credentials: "same-origin" })
          .then(function (response) { return response.ok ? response.json() : null; })
          .then(function (feed) {
            if (!feed) return;
            if (counter) {
              counter.textContent = feed.unread > 99 ? "99+" : String(feed.unread);
              counter.hidden = feed.unread === 0;
            }
            if (!notificationDropdown.classList.contains("open")) notificationDropdown.innerHTML = feed.html;
            const latest = Number(feed.latest_id || 0);
            if (latest > latestSeen && feed.newest) {
              notificationButton.classList.remove("is-shaking");
              void notificationButton.offsetWidth;
              notificationButton.classList.add("is-shaking");
              window.setTimeout(function () { notificationButton.classList.remove("is-shaking"); }, 600);
              showToast("إشعار جديد: " + feed.newest.title);
            }
            latestSeen = Math.max(latestSeen, latest);
          })
          .catch(function () { /* offline for a moment: the next round tries again */ })
          .finally(function () { busy = false; });
      }

      window.setInterval(refreshBell, 30000);
      document.addEventListener("visibilitychange", function () { if (!document.hidden) refreshBell(); });
    }

    // Both logout buttons (sidebar and account menu) are real forms: ask first, then submit.
    document.querySelectorAll('form[action$="/logout"]').forEach(function (logoutForm) {
      logoutForm.addEventListener("submit", function (event) {
        event.preventDefault();
        PalAlert.confirm({
          title: "تسجيل الخروج",
          text: "هل تريد تسجيل الخروج من حساب المطبعة؟",
          confirmText: "نعم، سجّل الخروج",
          icon: "question",
          danger: false,
        }).then(function (ok) {
          if (ok) HTMLFormElement.prototype.submit.call(logoutForm);
        });
      });
    });

    document.addEventListener("click", function (event) {
      const unavailableLink = event.target.closest('a[href="#"]');
      if (unavailableLink) {
        event.preventDefault();
        showToast("ستتوفر هذه الميزة قريبًا.");
      }

      if (!event.target.closest(".account-wrap")) closeAccountMenu();
      if (!event.target.closest(".notification-wrap")) closeNotificationMenu();
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") return;
      closeAccountMenu();
      closeNotificationMenu();
      if (mobileLayout.matches && isMenuOpen()) closeMenu();
    });

    function handleLayoutChange() {
      body.classList.remove("sidebar-open");
      if (backdrop) backdrop.classList.remove("open");
      syncMenuState();
    }

    if (typeof mobileLayout.addEventListener === "function") {
      mobileLayout.addEventListener("change", handleLayoutChange);
    } else {
      mobileLayout.addListener(handleLayoutChange);
    }

    setupContentMotion();
    filterOrders();
    syncMenuState();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(window, document);
