"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const statNumbers = document.querySelectorAll(".stat-number");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function animateNumber(element) {
    if (element.dataset.counted === "true") return;

    const target = Number(element.textContent.trim());
    element.dataset.counted = "true";

    if (reducedMotion || !Number.isFinite(target)) {
      element.textContent = String(target);
      return;
    }

    const duration = 900;
    const startTime = performance.now();
    element.textContent = "0";

    function update(currentTime) {
      const progress = Math.min((currentTime - startTime) / duration, 1);
      const easedProgress = 1 - Math.pow(1 - progress, 3);
      element.textContent = String(Math.round(target * easedProgress));

      if (progress < 1) requestAnimationFrame(update);
    }

    requestAnimationFrame(update);
  }

  if ("IntersectionObserver" in window) {
    const statsObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;

        animateNumber(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.35 });

    statNumbers.forEach(function (number) {
      statsObserver.observe(number);
    });
  } else {
    statNumbers.forEach(animateNumber);
  }

  const statusTabs = document.querySelectorAll(".status-tabs button");
  const orderRows = document.querySelectorAll("#ordersTableBody tr[data-status]");
  const emptyOrdersRow = document.querySelector(".empty-orders-row");
  const filterButton = document.getElementById("ordersFilterButton");
  const filterMenu = document.getElementById("ordersFilterMenu");
  const dateFromInput = document.getElementById("ordersDateFrom");
  const dateToInput = document.getElementById("ordersDateTo");
  const applyDateFilterButton = document.getElementById("applyDateFilter");
  const clearDateFilterButton = document.getElementById("clearDateFilter");
  const dateFilterError = document.getElementById("dateFilterError");
  let selectedStatus = "all";
  let selectedDateFrom = "";
  let selectedDateTo = "";

  /* The table's pager (shared/table-pagination.js) pages whatever rows are left visible here. */
  function applyOrderFilters() {
    let matching = 0;

    orderRows.forEach(function (row) {
      const orderDate = row.dataset.orderDate;
      const isVisible = (selectedStatus === "all" || row.dataset.status === selectedStatus)
        && (!selectedDateFrom || orderDate >= selectedDateFrom)
        && (!selectedDateTo || orderDate <= selectedDateTo);

      row.hidden = !isVisible;
      if (isVisible) matching += 1;
    });

    if (orderRows.length > 0) {
      emptyOrdersRow.hidden = matching !== 0;
      emptyOrdersRow.querySelector("td span").textContent = "لا توجد طلبات ضمن الفلاتر المحددة.";
    }
  }

  statusTabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      selectedStatus = tab.dataset.status;

      statusTabs.forEach(function (item) {
        const isActive = item === tab;
        item.classList.toggle("active", isActive);
        item.setAttribute("aria-selected", String(isActive));
      });

      applyOrderFilters();
    });
  });

  function closeFilterMenu() {
    filterMenu.hidden = true;
    filterButton.setAttribute("aria-expanded", "false");
  }

  filterButton.addEventListener("click", function (event) {
    event.stopPropagation();
    const willOpen = filterMenu.hidden;
    filterMenu.hidden = !willOpen;
    filterButton.setAttribute("aria-expanded", String(willOpen));
  });

  filterMenu.addEventListener("click", function (event) {
    event.stopPropagation();
  });

  applyDateFilterButton.addEventListener("click", function () {
    const dateFrom = dateFromInput.value;
    const dateTo = dateToInput.value;

    if (dateFrom && dateTo && dateFrom > dateTo) {
      dateFilterError.hidden = false;
      return;
    }

    dateFilterError.hidden = true;
    selectedDateFrom = dateFrom;
    selectedDateTo = dateTo;
    filterButton.classList.toggle("has-filter", Boolean(dateFrom || dateTo));
    applyOrderFilters();
    closeFilterMenu();
  });

  clearDateFilterButton.addEventListener("click", function () {
    dateFromInput.value = "";
    dateToInput.value = "";
    selectedDateFrom = "";
    selectedDateTo = "";
    dateFilterError.hidden = true;
    filterButton.classList.remove("has-filter");
    applyOrderFilters();
    closeFilterMenu();
  });

  document.addEventListener("click", closeFilterMenu);
  applyOrderFilters();

  /* ---------- order details dialog (the data comes from the server) ---------- */

  const orders = window.printProviderOrders || {};
  const routes = window.printProviderRoutes || {};
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";
  const detailsDialog = document.getElementById("orderDetailsDialog");
  const dialogTitle = document.getElementById("orderDialogTitle");
  const dialogContent = document.getElementById("orderDialogContent");
  const dialogFooter = document.getElementById("orderDialogFooter");

  function element(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function formatMoney(value) {
    return Number(value).toFixed(2) + " ₪";
  }

  function formatSize(bytes) {
    if (!bytes) return "";
    if (bytes < 1024 * 1024) return Math.max(1, Math.round(bytes / 1024)) + " ك.ب";
    return (bytes / (1024 * 1024)).toFixed(1) + " م.ب";
  }

  function detailItem(label, value) {
    const item = element("div", "order-detail-item");
    item.append(element("small", "", label), element("strong", "", value));
    return item;
  }

  function appendOrderItems(order) {
    const section = element("section", "order-products");
    const heading = element("h3");
    heading.innerHTML = '<i class="bi bi-box-seam" aria-hidden="true"></i> منتجات الطلب ';
    heading.appendChild(element("span", "", "(" + order.items.length + ")"));
    const list = element("div", "order-products-list");

    order.items.forEach(function (item, index) {
      const row = element("div", "order-product-row");
      const info = element("div", "order-product-info");
      info.append(
        element("strong", "", String(index + 1) + ". " + item.name),
        element("span", "", "الكمية: " + item.quantity)
      );

      if (item.design_title) info.appendChild(element("span", "", "التصميم: " + item.design_title));

      if (item.details.length) {
        const options = element("div", "product-options");
        item.details.forEach(function (detail) { options.appendChild(element("span", "", detail.label + ": " + detail.value)); });
        info.appendChild(options);
      }

      info.appendChild(element("b", "", formatMoney(item.cost)));

      if (item.files.length) {
        const files = element("div", "order-files");
        files.appendChild(element("small", "", "ملفات الطباعة"));
        item.files.forEach(function (file) {
          const link = element("a", "order-file-link");
          link.href = file.url;
          const meta = [file.pages ? file.pages + " صفحة" : "", formatSize(file.size)].filter(Boolean).join(" · ");
          link.innerHTML = '<i class="bi bi-download" aria-hidden="true"></i>';
          link.append(element("span", "", file.name));
          if (meta) link.appendChild(element("em", "", meta));
          files.appendChild(link);
        });
        info.appendChild(files);
      } else if (item.type === "upload") {
        info.appendChild(element("span", "", "لا توجد ملفات مرفقة"));
      }

      row.appendChild(info);

      if (item.image) {
        const figure = element("figure", "product-visual");
        const image = document.createElement("img");
        image.src = item.image;
        image.alt = "تصميم " + item.name;
        image.loading = "lazy";
        figure.append(element("figcaption", "", "التصميم"), image);
        row.appendChild(figure);
      }

      list.appendChild(row);
    });

    section.append(heading, list);
    dialogContent.appendChild(section);
  }

  function post(action, order, body) {
    return fetch(routes[action].replace("__ID__", order.id), {
      method: "POST",
      headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json", "Content-Type": "application/json" },
      body: JSON.stringify(body || {})
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok) throw new Error((data.errors && Object.values(data.errors)[0][0]) || data.message || "تعذر تنفيذ الإجراء.");
        return data;
      });
    });
  }

  function showResult(text, icon) {
    if (window.PalAlert) return window.PalAlert.alert(text, { icon: icon || "success" });
    window.alert(text);
    return Promise.resolve();
  }

  function runAction(action, order, body, successIcon) {
    return post(action, order, body)
      .then(function (data) { return showResult(data.message, successIcon).then(function () { window.location.reload(); }); })
      .catch(function (error) { return showResult(error.message, "error"); });
  }

  function confirmAction(options) {
    return window.PalAlert ? window.PalAlert.confirm(options) : Promise.resolve(window.confirm(options.title));
  }

  function closeDialog() {
    if (typeof detailsDialog.close === "function") detailsDialog.close();
    else detailsDialog.removeAttribute("open");
  }

  function footerButton(label, className, onClick) {
    const button = element("button", "dialog-close-button " + className, label);
    button.type = "button";
    button.addEventListener("click", onClick);
    dialogFooter.appendChild(button);
  }

  function renderFooter(order) {
    dialogFooter.replaceChildren();

    if (order.actions.accept) {
      footerButton("قبول الطلب", "dialog-accept-button", function () {
        closeDialog();
        confirmAction({ title: "قبول الطلب #" + order.number, text: "بعد القبول يظهر الطلب كقيد التنفيذ ويُبلَّغ العميل.", confirmText: "نعم، اقبل", icon: "question", danger: false })
          .then(function (ok) { if (ok) runAction("accept", order); });
      });
    }

    if (order.actions.reject) {
      footerButton("رفض الطلب", "dialog-reject-button", function () {
        closeDialog();
        const ask = window.PalAlert
          ? window.PalAlert.prompt({ title: "رفض الطلب #" + order.number, text: "اكتب سبب الرفض ليصل للعميل.", placeholder: "سبب الرفض", confirmText: "رفض الطلب", multiline: true })
          : Promise.resolve(window.prompt("سبب الرفض"));
        ask.then(function (reason) {
          if (reason === null || reason === undefined) return;
          if (String(reason).trim().length < 3) { showResult("اكتب سببًا واضحًا للرفض.", "warning"); return; }
          runAction("reject", order, { reason: String(reason).trim() }, "info");
        });
      });
    }

    if (order.actions.ready) {
      footerButton("جاهز للتسليم", "dialog-accept-button", function () {
        closeDialog();
        confirmAction({ title: "الطلب #" + order.number + " جاهز؟", text: "سيُبلَّغ العميل أن الطلب جاهز للتسليم لشركة التوصيل.", confirmText: "نعم، جاهز", icon: "question", danger: false })
          .then(function (ok) { if (ok) runAction("ready", order); });
      });
    }

    footerButton("إغلاق", "", closeDialog);
  }

  document.querySelectorAll(".details-button").forEach(function (button) {
    button.addEventListener("click", function () {
      const order = orders[button.closest("tr").dataset.orderId];
      if (!order) return;

      dialogTitle.textContent = "#" + order.number;
      dialogContent.replaceChildren();
      dialogContent.append(
        detailItem("رقم الطلب", "#" + order.number),
        detailItem("الحالة", order.status_label),
        detailItem("تاريخ الطلب", order.date_label),
        detailItem("الكمية", String(order.quantity)),
        detailItem("مستحقاتك", formatMoney(order.value))
      );
      if (order.notes) dialogContent.appendChild(detailItem("ملاحظات العميل", order.notes));
      appendOrderItems(order);
      renderFooter(order);

      if (typeof detailsDialog.showModal === "function") detailsDialog.showModal();
      else detailsDialog.setAttribute("open", "");
    });
  });

  detailsDialog.querySelectorAll("[data-dialog-close]").forEach(function (button) {
    button.addEventListener("click", closeDialog);
  });

  detailsDialog.addEventListener("click", function (event) {
    if (event.target === detailsDialog) closeDialog();
  });
});
