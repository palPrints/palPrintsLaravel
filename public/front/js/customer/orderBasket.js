(function () {
  "use strict";

  const list = document.getElementById("basketItems");
  const count = document.getElementById("itemsCount");
  const toast = document.querySelector(".toast");
  const emptyBasketUrl = window.palPrintsCustomerAssets?.emptyBasketUrl || "";
  let toastTimer;

  if (!list || !window.PalPrintCart) return;

  function message(text) {
    if (!toast) return;
    window.clearTimeout(toastTimer);
    toast.textContent = text;
    toast.classList.add("show");
    toastTimer = window.setTimeout(() => toast.classList.remove("show"), 1800);
  }

  function itemTemplate(item) {
    const tags = (item.meta || []).map((tag) => `<span>${tag}</span>`).join("");

    return `
      <article class="basket-item" data-id="${item.id}" data-price="${item.price}">
        <div class="product-info">
          <div class="product-media"><img src="${item.image}" alt="${item.title} — ${item.description}"></div>
          <div>
            <h3>${item.title}</h3>
            <p>${item.description}</p>
            <div class="tags">${tags}</div>
          </div>
        </div>
        <div class="item-price"><strong>$${(item.price * item.quantity).toFixed(2)}</strong><span><b>$${item.price.toFixed(2)}</b> × <b class="price-qty">${item.quantity}</b></span></div>
        <div class="quantity" aria-label="تحديد كمية ${item.title}"><button data-action="increase" aria-label="زيادة الكمية">+</button><output>${item.quantity}</output><button data-action="decrease" aria-label="تقليل الكمية">−</button></div>
        <button class="remove-item" type="button" aria-label="حذف ${item.title}"><i class="bi bi-trash3"></i></button>
      </article>`;
  }

  function render() {
    const items = window.PalPrintCart.getItems();
    list.innerHTML = items.map(itemTemplate).join("");
    if (count) count.textContent = items.length;
    return items;
  }

  list.addEventListener("click", (event) => {
    const article = event.target.closest(".basket-item");
    if (!article) return;

    const items = window.PalPrintCart.getItems();
    const index = items.findIndex((item) => item.id === article.dataset.id);
    if (index === -1) return;

    if (event.target.closest(".remove-item")) {
      const [removed] = items.splice(index, 1);
      window.PalPrintCart.save(items);
      render();
      message("تم حذف المنتج من السلة");
      if (!items.length && emptyBasketUrl) {
        window.setTimeout(() => { window.location.href = emptyBasketUrl; }, 350);
      }
      return;
    }

    const action = event.target.closest("[data-action]");
    if (!action) return;

    items[index].quantity = action.dataset.action === "increase"
      ? items[index].quantity + 1
      : Math.max(1, items[index].quantity - 1);
    window.PalPrintCart.save(items);
    render();
  });

  render();
})();
