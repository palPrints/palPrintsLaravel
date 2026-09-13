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

  document.querySelectorAll(".details-button").forEach((button) => {
    button.addEventListener("click", () => {
      const order = button.dataset.order;
      button.innerHTML = `<i class="bi bi-receipt"></i> الطلب #${order}`;
      window.setTimeout(() => { button.innerHTML = 'عرض التفاصيل<i class="bi bi-arrow-left"></i>'; }, 1800);
    });
  });

  window.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !cancelModal.hidden) closeModal();
  });
})();
