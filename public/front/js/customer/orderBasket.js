(function(){
  "use strict";

  const list = document.getElementById("basketItems");
  if (!list) return;

  const CART_STORAGE_KEY = "palprints-basket-cart";
  const assets = window.palPrintsCustomerAssets || {};
  const count = document.getElementById("itemsCount");
  const toast = document.querySelector(".toast");
  const emptyBasketUrl = assets.emptyBasketUrl || "";
  let toastTimer;

  // Self-sufficient cart read/write so this page never depends on
  // storefront.js having finished running first — it uses window.PalPrintCart
  // when available (to keep the header dropdown in sync) but falls back to
  // talking to localStorage directly otherwise.
  function readCart() {
    if (window.PalPrintCart) return window.PalPrintCart.getItems();
    try {
      const saved = JSON.parse(localStorage.getItem(CART_STORAGE_KEY));
      if (Array.isArray(saved)) return saved;
    } catch (_) { /* Fall through to the seed below. */ }
    return Array.isArray(assets.basketSeed) ? assets.basketSeed : [];
  }

  function saveCart(items) {
    if (window.PalPrintCart) {
      window.PalPrintCart.save(items);
      return;
    }
    try { localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items)); }
    catch (_) { /* Session-only fallback. */ }
  }

  function message(text) {
    if (!toast) return;
    window.clearTimeout(toastTimer);
    toast.textContent = text;
    toast.classList.add("show");
    toastTimer = window.setTimeout(() => toast.classList.remove("show"), 1800);
  }
  toggle.addEventListener("click",()=>setSidebar(!sidebar.classList.contains("is-open")));
  closeButton.addEventListener("click",()=>setSidebar(false));
  backdrop.addEventListener("click",()=>setSidebar(false));
  sidebar.addEventListener("click",event=>{if(event.target.closest("a")&&mobileScreen.matches)setSidebar(false)});
  mobileScreen.addEventListener("change",()=>setSidebar(false));
  document.addEventListener("keydown",event=>{if(event.key==="Escape"&&sidebar.classList.contains("is-open")){setSidebar(false);toggle.focus()}});

  const profileToggle=document.getElementById("profileMenuToggle");
  const profileDropdown=document.getElementById("profileDropdown");
  const notificationsToggle=document.getElementById("notificationsToggle");
  const notificationsPanel=document.getElementById("notificationsPanel");
  function closeMenus(){profileDropdown.hidden=true;notificationsPanel.hidden=true;profileToggle.setAttribute("aria-expanded","false");notificationsToggle.setAttribute("aria-expanded","false")}
  profileToggle.addEventListener("click",event=>{event.stopPropagation();const open=profileDropdown.hidden;closeMenus();profileDropdown.hidden=!open;profileToggle.setAttribute("aria-expanded",String(open))});
  notificationsToggle.addEventListener("click",event=>{event.stopPropagation();const open=notificationsPanel.hidden;closeMenus();notificationsPanel.hidden=!open;notificationsToggle.setAttribute("aria-expanded",String(open))});
  document.addEventListener("click",event=>{if(!event.target.closest(".profile-menu,.notifications-menu"))closeMenus()});
  function logout(){if(window.confirm("هل تريد تسجيل الخروج من حسابك؟"))window.location.href="login.html"}
  document.getElementById("storeSidebarLogout")?.addEventListener("click",logout);
  document.querySelector(".profile-dropdown__logout")?.addEventListener("click",logout);

  function message(text){clearTimeout(toastTimer);toast.textContent=text;toast.classList.add("show");toastTimer=setTimeout(()=>toast.classList.remove("show"),1800)}
  function syncCount(){
    const total=items.querySelectorAll(".basket-item").length;
    count.textContent=total;
    cartBadge.textContent=total;
    cartBadge.hidden=total===0;
    return total;
  }

  function render() {
    const items = readCart();
    list.innerHTML = items.map(itemTemplate).join("");
    if (count) count.textContent = items.length;
    return items;
  }

  list.addEventListener("click", (event) => {
    const article = event.target.closest(".basket-item");
    if (!article) return;

    const items = readCart();
    const index = items.findIndex((item) => item.id === article.dataset.id);
    if (index === -1) return;

    if (event.target.closest(".remove-item")) {
      items.splice(index, 1);
      saveCart(items);
      render();
      message("تم حذف المنتج من السلة");
      if(!remaining)setTimeout(()=>{window.location.href="emptyBasket.html"},350);
      return;
    }

    const action = event.target.closest("[data-action]");
    if (!action) return;

    items[index].quantity = action.dataset.action === "increase"
      ? items[index].quantity + 1
      : Math.max(1, items[index].quantity - 1);
    saveCart(items);
    render();
  });
  document.querySelector(".notifications-clear")?.addEventListener("click",()=>{document.querySelector(".notifications-list").replaceChildren();document.querySelector(".notifications-empty").hidden=false;notificationsPanel.hidden=true;notificationsToggle.setAttribute("aria-expanded","false");message("تم مسح الإشعارات")});
})();
