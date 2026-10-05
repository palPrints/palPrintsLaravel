/* Admin shell: sidebar, users submenu, account and notification menus, logout confirmation, toast. */
(function (window, document) {
  "use strict";

  function initialize() {
    const body = document.body;
    const sidebar = document.getElementById("adminSidebar");
    const backdrop = document.getElementById("sidebarBackdrop");
    const menuButton = document.getElementById("menuButton");
    const usersNavGroup = document.getElementById("usersNavGroup");
    const usersNavToggle = document.getElementById("usersNavToggle");
    const usersSubmenu = document.getElementById("usersSubmenu");
    const logoutForm = document.getElementById("adminLogoutForm");
    const accountButton = document.getElementById("accountButton");
    const accountDropdown = document.getElementById("accountDropdown");
    const notificationButton = document.getElementById("notificationButton");
    const notificationDropdown = document.getElementById("notificationDropdown");
    const toast = document.getElementById("adminToast");
    const mobileLayout = window.matchMedia("(max-width: 760px)");
    const sidebarStorageKey = "palprints-admin-sidebar-collapsed";
    let toastTimer = 0;

    function showToast(message) {
      if (!toast) return;
      window.clearTimeout(toastTimer);
      toast.textContent = message;
      toast.classList.add("is-visible");
      toastTimer = window.setTimeout(function () {
        toast.classList.remove("is-visible");
      }, 2800);
    }

    /* Page scripts reuse the same toast. */
    window.PalAdmin = { toast: showToast };

    function isSidebarOpen() {
      return mobileLayout.matches
        ? body.classList.contains("sidebar-open")
        : !body.classList.contains("sidebar-collapsed");
    }

    function syncSidebarState() {
      const open = isSidebarOpen();
      if (menuButton) {
        menuButton.setAttribute("aria-expanded", String(open));
        menuButton.setAttribute("aria-label", open ? "إغلاق القائمة" : "فتح القائمة");
        const icon = menuButton.querySelector("i");
        if (icon) icon.className = open ? "bi bi-x-lg" : "bi bi-list";
      }
      if (sidebar) {
        if (mobileLayout.matches && !open) sidebar.setAttribute("aria-hidden", "true");
        else sidebar.removeAttribute("aria-hidden");
      }
    }

    function restoreSidebarPreference() {
      if (mobileLayout.matches) {
        body.classList.remove("sidebar-collapsed");
        return;
      }
      try {
        const storedPreference = window.localStorage.getItem(sidebarStorageKey);
        body.classList.toggle(
          "sidebar-collapsed",
          storedPreference === null
            ? body.classList.contains("sidebar-collapsed")
            : storedPreference === "true"
        );
      } catch (error) {
        // Keep the markup default when storage is unavailable.
      }
    }

    function closeMobileSidebar() {
      body.classList.remove("sidebar-open");
      if (backdrop) backdrop.classList.remove("open");
      syncSidebarState();
    }

    function toggleSidebar() {
      if (mobileLayout.matches) {
        const open = !body.classList.contains("sidebar-open");
        body.classList.toggle("sidebar-open", open);
        if (backdrop) backdrop.classList.toggle("open", open);
      } else {
        const collapsed = !body.classList.contains("sidebar-collapsed");
        body.classList.toggle("sidebar-collapsed", collapsed);
        try {
          window.localStorage.setItem(sidebarStorageKey, String(collapsed));
        } catch (error) {
          // The sidebar remains usable when persistence is unavailable.
        }
      }
      syncSidebarState();
    }

    function toggleUsersSubmenu() {
      if (!usersNavGroup || !usersNavToggle || !usersSubmenu) return;
      if (!mobileLayout.matches && body.classList.contains("sidebar-collapsed")) {
        body.classList.remove("sidebar-collapsed");
        usersNavGroup.classList.add("is-open");
        usersNavToggle.setAttribute("aria-expanded", "true");
        usersSubmenu.hidden = false;
        try {
          window.localStorage.setItem(sidebarStorageKey, "false");
        } catch (error) {
          // Expanding still works when persistence is unavailable.
        }
        syncSidebarState();
        return;
      }
      const open = !usersNavGroup.classList.contains("is-open");
      usersNavGroup.classList.toggle("is-open", open);
      usersNavToggle.setAttribute("aria-expanded", String(open));
      usersSubmenu.hidden = !open;
      syncSidebarState();
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

    restoreSidebarPreference();
    syncSidebarState();

    if (menuButton) menuButton.addEventListener("click", toggleSidebar);
    if (backdrop) backdrop.addEventListener("click", closeMobileSidebar);
    if (usersNavToggle) usersNavToggle.addEventListener("click", toggleUsersSubmenu);

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
        const open = !notificationDropdown.classList.contains("open");
        closeAccountMenu();
        notificationDropdown.classList.toggle("open", open);
        notificationDropdown.hidden = !open;
        notificationButton.setAttribute("aria-expanded", String(open));
      });
    }

    if (logoutForm) {
      logoutForm.addEventListener("submit", function (event) {
        event.preventDefault();
        PalAlert.confirm({
          title: "تسجيل الخروج",
          text: "هل تريد تسجيل الخروج من لوحة الإدارة؟",
          confirmText: "نعم، سجّل الخروج",
          icon: "question",
          danger: false,
        }).then(function (ok) {
          if (ok) HTMLFormElement.prototype.submit.call(logoutForm);
        });
      });
    }

    /* Links to pages that do not exist yet. */
    document.querySelectorAll("[data-soon]").forEach(function (link) {
      link.addEventListener("click", function (event) {
        event.preventDefault();
        showToast("هذه الصفحة قيد التطوير.");
      });
    });

    document.querySelectorAll('.admin-sidebar a[href^="#"]').forEach(function (link) {
      link.addEventListener("click", function () {
        if (mobileLayout.matches) closeMobileSidebar();
      });
    });

    document.addEventListener("click", function (event) {
      if (!event.target.closest(".account-wrap")) closeAccountMenu();
      if (!event.target.closest(".notification-wrap")) closeNotificationMenu();
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") return;
      closeAccountMenu();
      closeNotificationMenu();
      if (mobileLayout.matches && isSidebarOpen()) closeMobileSidebar();
    });

    function handleLayoutChange() {
      body.classList.remove("sidebar-open");
      if (backdrop) backdrop.classList.remove("open");
      restoreSidebarPreference();
      syncSidebarState();
    }

    if (typeof mobileLayout.addEventListener === "function") {
      mobileLayout.addEventListener("change", handleLayoutChange);
    } else {
      mobileLayout.addListener(handleLayoutChange);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(window, document);
