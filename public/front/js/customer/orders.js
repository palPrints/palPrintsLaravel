"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const menuButton = document.getElementById("menuButton");
  const menuIcon = menuButton.querySelector("i");
  const sidebarOverlay = document.getElementById("sidebarOverlay");
  const notificationButton = document.getElementById("notificationButton");
  const notificationWrap = document.querySelector(".notification-wrap");
  const notificationDropdown = document.getElementById("notificationDropdown");
  const notificationCounter = document.getElementById("notificationCounter");
  const cancelModal = document.getElementById("cancelModal");
  const modalOrderNumber = document.getElementById("modalOrderNumber");
  const mobileLayout = window.matchMedia("(max-width: 820px)");
  let selectedCancelButton = null;

  function closeSidebar() {
    body.classList.remove("sidebar-open");
    menuButton.setAttribute("aria-expanded", "false");
    menuButton.setAttribute("aria-label", "فتح القائمة");
    menuIcon.className = "bi bi-list";
  }

  function syncSidebarMode() {
    body.classList.remove("sidebar-open");
    body.classList.toggle("sidebar-collapsed", !mobileLayout.matches);
    menuButton.setAttribute("aria-expanded", "false");
    menuButton.setAttribute("aria-label", "فتح القائمة");
    menuIcon.className = "bi bi-list";
  }

  menuButton.addEventListener("click", () => {
    if (mobileLayout.matches) {
      const open = !body.classList.contains("sidebar-open");
      body.classList.toggle("sidebar-open", open);
      menuButton.setAttribute("aria-expanded", String(open));
      menuIcon.className = open ? "bi bi-x-lg" : "bi bi-list";
    } else {
      const collapsed = !body.classList.contains("sidebar-collapsed");
      body.classList.toggle("sidebar-collapsed", collapsed);
      menuButton.setAttribute("aria-expanded", String(!collapsed));
      menuIcon.className = collapsed ? "bi bi-list" : "bi bi-x-lg";
    }
  });

  syncSidebarMode();
  mobileLayout.addEventListener("change", syncSidebarMode);
  sidebarOverlay.addEventListener("click", closeSidebar);

  function setNotificationsOpen(open) {
    notificationDropdown.hidden = !open;
    if (open) {
      const wrapRect = notificationWrap.getBoundingClientRect();
      const width = notificationDropdown.offsetWidth;
      const center = wrapRect.left + wrapRect.width / 2;
      const left = Math.max(8, Math.min(center - width / 2, window.innerWidth - width - 8));
      notificationDropdown.style.left = `${left - wrapRect.left}px`;
      notificationDropdown.style.setProperty("--arrow-offset", `${center - left}px`);
      notificationCounter.hidden = true;
    }
    requestAnimationFrame(() => notificationDropdown.classList.toggle("open", open));
    notificationButton.setAttribute("aria-expanded", String(open));
  }

  notificationButton.addEventListener("click", (event) => {
    event.stopPropagation();
    setNotificationsOpen(!notificationDropdown.classList.contains("open"));
  });

  document.addEventListener("click", (event) => {
    if (notificationDropdown.classList.contains("open") && !notificationDropdown.contains(event.target)) setNotificationsOpen(false);
  });

  function closeModal() {
    cancelModal.hidden = true;
    body.style.overflow = "";
    selectedCancelButton?.focus();
  }

  document.querySelectorAll(".cancel-order").forEach((button) => {
    button.addEventListener("click", () => {
      selectedCancelButton = button;
      modalOrderNumber.textContent = `#${button.dataset.order}`;
      cancelModal.hidden = false;
      body.style.overflow = "hidden";
      document.getElementById("keepOrder").focus();
    });
  });

  cancelModal.querySelector(".modal-close").addEventListener("click", closeModal);
  document.getElementById("keepOrder").addEventListener("click", closeModal);
  cancelModal.addEventListener("click", (event) => { if (event.target === cancelModal) closeModal(); });
  document.getElementById("confirmCancel").addEventListener("click", () => {
    if (!selectedCancelButton) return;
    const row = selectedCancelButton.closest("tr");
    row.querySelector(".status").className = "status status-cancelled";
    row.querySelector(".status").innerHTML = '<i class="bi bi-x-circle"></i>ملغي';
    selectedCancelButton.disabled = true;
    selectedCancelButton.innerHTML = '<i class="bi bi-check2"></i>تم الإلغاء';
    row.classList.add("is-cancelled-row");
    closeModal();
  });

  const detailsModal = document.getElementById("orderDetailsModal");
  const detailsItems = document.getElementById("detailsItems");
  const detailsOrderNumber = document.getElementById("detailsOrderNumber");
  const detailsDate = document.getElementById("detailsDate");
  const detailsAddress = document.getElementById("detailsAddress");
  const detailsPayment = document.getElementById("detailsPayment");
  const detailsTotal = document.getElementById("detailsTotal");
  const detailsStatus = document.getElementById("detailsStatus");
  let selectedDetailsButton = null;

  function closeDetailsModal() {
    detailsModal.hidden = true;
    document.body.style.overflow = "";
    selectedDetailsButton?.focus();
  }

  function itemRow(item) {
    return `<div class="details-modal__item"><img src="${item.image}" alt="${item.title}"><div><strong>${item.title}</strong><small>${item.desc} · الكمية: ${item.qty}</small></div><b>${item.price}</b></div>`;
  }

  if (detailsModal) {
    document.querySelectorAll(".details-button").forEach((button) => {
      button.addEventListener("click", () => {
        selectedDetailsButton = button;
        const data = button.dataset;
        let items = [];
        try { items = JSON.parse(data.items || "[]"); } catch (_) { items = []; }
        detailsItems.innerHTML = items.map(itemRow).join("");
        detailsOrderNumber.textContent = `#${data.order}`;
        detailsDate.textContent = data.date;
        detailsAddress.textContent = data.address;
        detailsPayment.textContent = data.payment;
        detailsTotal.textContent = data.total;
        detailsStatus.className = `status ${data.statusClass}`;
        detailsStatus.innerHTML = `<i class="bi ${data.statusIcon}"></i>${data.status}`;
        detailsModal.hidden = false;
        document.body.style.overflow = "hidden";
        detailsModal.querySelector(".modal-close").focus();
      });
    });

    detailsModal.querySelector(".modal-close").addEventListener("click", closeDetailsModal);
    detailsModal.addEventListener("click", (event) => { if (event.target === detailsModal) closeDetailsModal(); });
  }

  window.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;
    if (!cancelModal.hidden) closeModal();
    if (detailsModal && !detailsModal.hidden) closeDetailsModal();
  });
});
