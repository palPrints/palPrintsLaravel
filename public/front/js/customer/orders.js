(function () {
  "use strict";

  const cancelModal = document.getElementById("cancelModal");
  if (!cancelModal) return;

  const modalOrderNumber = document.getElementById("modalOrderNumber");
  let selectedCancelButton = null;

  function closeModal() {
    cancelModal.hidden = true;
    document.body.style.overflow = "";
    selectedCancelButton?.focus();
  }

  document.querySelectorAll(".cancel-order").forEach((button) => {
    button.addEventListener("click", () => {
      selectedCancelButton = button;
      modalOrderNumber.textContent = `#${button.dataset.order}`;
      cancelModal.hidden = false;
      document.body.style.overflow = "hidden";
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
})();
