"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const menuButton = document.getElementById("menuButton");
  const menuIcon = menuButton.querySelector("i");
  const sidebarOverlay = document.getElementById("sidebarOverlay");
  const form = document.getElementById("profileForm");
  const fullName = document.getElementById("fullName");
  const displayName = document.getElementById("displayName");
  const avatarInput = document.getElementById("avatarInput");
  const avatar = document.getElementById("avatar");
  const avatarImage = document.getElementById("avatarImage");
  const notificationButton = document.getElementById("notificationButton");
  const notificationWrap = document.querySelector(".notification-wrap");
  const notificationDropdown = document.getElementById("notificationDropdown");
  const notificationList = document.getElementById("notificationList");
  const notificationCounter = document.getElementById("notificationCounter");
  const mobileLayout = window.matchMedia("(max-width: 820px)");
  const notifications = [];
  let unreadCount = 0;

  const closeSidebar = () => {
    body.classList.remove("sidebar-open");
    menuButton.setAttribute("aria-expanded", "false");
    menuButton.setAttribute("aria-label", "فتح القائمة");
    menuIcon.className = "bi bi-list";
  };

  menuButton.addEventListener("click", () => {
    if (mobileLayout.matches) {
      const willOpen = !body.classList.contains("sidebar-open");
      body.classList.toggle("sidebar-open", willOpen);
      menuButton.setAttribute("aria-expanded", String(willOpen));
      menuButton.setAttribute("aria-label", willOpen ? "إغلاق القائمة" : "فتح القائمة");
      menuIcon.className = willOpen ? "bi bi-x-lg" : "bi bi-list";
      return;
    }

    const willCollapse = !body.classList.contains("sidebar-collapsed");
    body.classList.toggle("sidebar-collapsed", willCollapse);
    menuButton.setAttribute("aria-expanded", String(!willCollapse));
    menuButton.setAttribute("aria-label", willCollapse ? "فتح القائمة" : "إغلاق القائمة");
    menuIcon.className = willCollapse ? "bi bi-list" : "bi bi-x-lg";
  });

  const syncSidebarMode = () => {
    body.classList.remove("sidebar-open");
    body.classList.toggle("sidebar-collapsed", !mobileLayout.matches);
    menuButton.setAttribute("aria-expanded", "false");
    menuButton.setAttribute("aria-label", "فتح القائمة");
    menuIcon.className = "bi bi-list";
  };

  syncSidebarMode();
  mobileLayout.addEventListener("change", syncSidebarMode);

  sidebarOverlay.addEventListener("click", closeSidebar);
  window.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeSidebar();
  });

  avatarInput.addEventListener("change", () => {
    const file = avatarInput.files && avatarInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.addEventListener("load", () => {
      avatarImage.src = reader.result;
      avatar.classList.add("has-image");
    });
    reader.readAsDataURL(file);
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    displayName.textContent = fullName.value.trim() || "عميل PalPrints";
    notify("تم حفظ التغييرات بنجاح");
  });

  form.addEventListener("reset", () => {
    window.setTimeout(() => {
      displayName.textContent = fullName.value;
    }, 0);
  });

  notificationButton.addEventListener("click", (event) => {
    event.stopPropagation();
    setDropdownOpen(!notificationDropdown.classList.contains("open"));
  });

  document.addEventListener("click", (event) => {
    if (!notificationDropdown.classList.contains("open")) return;
    if (notificationDropdown.contains(event.target) || notificationButton.contains(event.target)) return;
    setDropdownOpen(false);
  });

  window.addEventListener("keydown", (event) => {
    if (event.key === "Escape") setDropdownOpen(false);
  });

  function setDropdownOpen(open) {
    notificationDropdown.hidden = !open;
    if (open) positionDropdown();
    requestAnimationFrame(() => notificationDropdown.classList.toggle("open", open));
    notificationButton.setAttribute("aria-expanded", String(open));
    if (open) {
      unreadCount = 0;
      renderNotificationCounter();
    }
  }

  function positionDropdown() {
    const margin = 8;
    const wrapRect = notificationWrap.getBoundingClientRect();
    const ddWidth = notificationDropdown.offsetWidth;
    const wrapCenter = wrapRect.left + wrapRect.width / 2;

    let left = wrapCenter - ddWidth / 2;
    left = Math.max(margin, Math.min(left, window.innerWidth - ddWidth - margin));

    notificationDropdown.style.left = `${left - wrapRect.left}px`;
    notificationDropdown.style.setProperty("--arrow-offset", `${wrapCenter - left}px`);
  }

  function notify(message) {
    notifications.unshift({ message, time: new Date() });
    renderNotificationList();
    unreadCount += 1;
    renderNotificationCounter();
    showToast(message);
    shakeBell();
  }

  function renderNotificationList() {
    notificationList.innerHTML = "";
    if (!notifications.length) {
      const empty = document.createElement("li");
      empty.className = "notification-empty";
      empty.textContent = "لا توجد إشعارات بعد";
      notificationList.appendChild(empty);
      return;
    }
    notifications.forEach((item) => {
      const li = document.createElement("li");
      li.className = "notification-item";
      li.innerHTML = '<i class="bi bi-check-circle-fill"></i><div class="notification-item-text"><p></p><span></span></div>';
      li.querySelector("p").textContent = item.message;
      li.querySelector("span").textContent = item.time.toLocaleTimeString("ar", { hour: "2-digit", minute: "2-digit" });
      notificationList.appendChild(li);
    });
  }

  function renderNotificationCounter() {
    if (unreadCount > 0) {
      notificationCounter.hidden = false;
      notificationCounter.textContent = unreadCount > 9 ? "9+" : String(unreadCount);
    } else {
      notificationCounter.hidden = true;
    }
  }

  function shakeBell() {
    notificationButton.classList.remove("is-shaking");
    void notificationButton.offsetWidth;
    notificationButton.classList.add("is-shaking");
    window.setTimeout(() => notificationButton.classList.remove("is-shaking"), 500);
  }

  function showToast(message) {
    const oldToast = document.querySelector(".toast");
    if (oldToast) oldToast.remove();

    const toast = document.createElement("div");
    toast.className = "toast";
    toast.setAttribute("role", "status");
    toast.innerHTML = '<i class="bi bi-check-circle-fill"></i><span></span>';
    toast.querySelector("span").textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add("show"));
    window.setTimeout(() => {
      toast.classList.remove("show");
      window.setTimeout(() => toast.remove(), 300);
    }, 2600);
  }
});
