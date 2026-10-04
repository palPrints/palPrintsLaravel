(function () {
  "use strict";

  const list = document.getElementById("basketItems");
  if (!list) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  const toast = document.getElementById("basketToast") || document.querySelector(".toast");
  const pageCount = document.getElementById("itemsCount");
  const headerCount = document.getElementById("cartCount");
  const cartButton = document.getElementById("cartButton");
  let toastTimer = null;

  function money(value) {
    return new Intl.NumberFormat("en-US", { maximumFractionDigits: 0 }).format(Number(value) || 0) + " ₪";
  }

  function message(text) {
    if (!toast) return;
    window.clearTimeout(toastTimer);
    toast.textContent = text;
    toast.classList.add("show");
    toastTimer = window.setTimeout(() => toast.classList.remove("show"), 1800);
  }

  function quantity(row) {
    return Math.max(1, Math.min(99, Number(row.querySelector("[data-quantity-output]")?.textContent) || 1));
  }

  function setHeaderCount(count) {
    if (!headerCount) return;
    headerCount.textContent = String(count);
    headerCount.hidden = count <= 0;
    if (cartButton) {
      cartButton.setAttribute("aria-label", count ? "سلة التسوق، " + count + " منتجات" : "سلة التسوق، فارغة");
    }
  }

  function syncRow(row, nextQuantity, totals) {
    const safeQuantity = Math.max(1, Math.min(99, Number(nextQuantity) || 1));
    const unitPrice = Number(totals?.unit_price ?? row.dataset.unitPrice) || 0;
    const totalPrice = Number(totals?.total_price ?? unitPrice * safeQuantity) || 0;

    const output = row.querySelector("[data-quantity-output]");
    const decrease = row.querySelector('[data-quantity-action="decrease"]');
    const increase = row.querySelector('[data-quantity-action="increase"]');
    const lineTotal = row.querySelector("[data-line-total]");
    const unitSummary = row.querySelector("[data-unit-summary]");
    const inputs = row.querySelectorAll("[data-quantity-input]");

    if (output) output.textContent = String(safeQuantity);
    if (decrease) decrease.disabled = safeQuantity <= 1;
    if (increase) increase.disabled = safeQuantity >= 99;
    if (lineTotal) lineTotal.textContent = money(totalPrice);
    if (unitSummary) unitSummary.textContent = money(unitPrice) + " × " + safeQuantity;

    inputs.forEach((input) => {
      const form = input.closest("form");
      const action = form?.querySelector("[data-quantity-action]")?.dataset.quantityAction;
      input.value = String(action === "decrease" ? Math.max(1, safeQuantity - 1) : Math.min(99, safeQuantity + 1));
    });
  }

  list.addEventListener("submit", function (event) {
    const form = event.target.closest("[data-quantity-form]");
    if (!form) return;

    event.preventDefault();

    const row = form.closest("[data-id]");
    const button = event.submitter || form.querySelector("[data-quantity-action]");
    if (!row || !button || button.disabled) return;

    const current = quantity(row);
    const next = button.dataset.quantityAction === "decrease" ? Math.max(1, current - 1) : Math.min(99, current + 1);
    if (next === current) return;

    syncRow(row, next);
    button.disabled = true;

    window.fetch(form.action, {
      method: "PATCH",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrf,
      },
      body: JSON.stringify({ quantity: next }),
    })
      .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
      .then((result) => {
        if (!result.ok) throw new Error(result.data.message || "تعذر تحديث الكمية.");
        syncRow(row, result.data.item.quantity, result.data.item);
        if (pageCount && result.data.cart?.items_count !== undefined) pageCount.textContent = result.data.cart.items_count;
        if (result.data.cart?.quantity_count !== undefined) setHeaderCount(result.data.cart.quantity_count);
      })
      .catch((error) => {
        syncRow(row, current);
        message(error.message || "تعذر تحديث الكمية.");
      })
      .finally(() => {
        syncRow(row, quantity(row));
      });
  });
})();
