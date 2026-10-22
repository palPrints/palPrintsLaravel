"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const cancelModal = document.getElementById("cancelModal");
  const modalOrderNumber = document.getElementById("modalOrderNumber");
  let selectedCancelButton = null;

  function closeModal() {
    if (!cancelModal) return;
    cancelModal.hidden = true;
    body.style.overflow = "";
    selectedCancelButton?.focus();
  }

  if (cancelModal) {
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
      const form = document.getElementById(selectedCancelButton.dataset.form);
      if (!form) return;
      selectedCancelButton.disabled = true;
      selectedCancelButton.innerHTML = '<i class="bi bi-hourglass-split"></i>جارٍ الإلغاء...';
      form.submit();
    });
  }

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

  const esc = (text) => String(text).replace(/[&<>"']/g, (char) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#39;" })[char]);

  function itemRow(item) {
    const lines = (item.details || []).map((row) => `<div class="details-modal__spec"><span>${esc(row.label)}</span><strong>${esc(row.value)}</strong></div>`).join("");
    const cards = (item.files || []).map((card) => `<div class="details-modal__file"><strong>${esc(card.title)}</strong>${card.rows.map((row) => `<div class="details-modal__spec"><span>${esc(row.label)}</span><strong>${esc(row.value)}</strong></div>`).join("")}</div>`).join("");
    const specs = lines || cards ? `<div class="details-modal__specs"><h4>تفاصيل الطباعة</h4>${lines}${cards}</div>` : "";
    const shown = (item.stickers || []).length > 4 ? item.stickers.slice(0, 3) : (item.stickers || []);
    const stickerTiles = shown.map((src) => `<img src="${esc(src)}" alt="" loading="lazy">`).join("") + ((item.stickers || []).length > 4 ? `<span class="sticker-thumbs__more">+${item.stickers.length - 3}</span>` : "");
    const stickerThumb = shown.length ? `<div class="sticker-thumbs sticker-thumbs--${Math.min(item.stickers.length, 4)}" role="img" aria-label="${esc(item.title)}">${stickerTiles}</div>` : "";
    return `<div class="details-modal__item">${stickerThumb ? `<span class="details-modal__thumb">${stickerThumb}</span>` : item.thumb ?`<span class="details-modal__thumb">${item.thumb}</span>` : `<img src="${item.image}" alt="${item.title}">`}<div><strong>${item.title}</strong><small>${item.desc} · الكمية: ${item.qty}</small></div><b>${item.price}</b></div>${specs}`;
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
    if (cancelModal && !cancelModal.hidden) closeModal();
    if (detailsModal && !detailsModal.hidden) closeDetailsModal();
  });
});
