(function (document, window) {
  "use strict";

  function initialize() {
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

    function applyOrderFilters() {
      let visibleRows = 0;

      orderRows.forEach(function (row) {
        const matchesStatus = selectedStatus === "all" || row.dataset.status === selectedStatus;
        const orderDate = row.dataset.orderDate;
        const matchesStart = !selectedDateFrom || orderDate >= selectedDateFrom;
        const matchesEnd = !selectedDateTo || orderDate <= selectedDateTo;
        const isVisible = matchesStatus && matchesStart && matchesEnd;

        row.hidden = !isVisible;
        if (isVisible) visibleRows += 1;
      });

      if (emptyOrdersRow) emptyOrdersRow.hidden = visibleRows !== 0;
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
      if (!filterMenu || !filterButton) return;
      filterMenu.hidden = true;
      filterButton.setAttribute("aria-expanded", "false");
    }

    if (filterButton && filterMenu) {
      filterButton.addEventListener("click", function (event) {
        event.stopPropagation();
        const willOpen = filterMenu.hidden;
        filterMenu.hidden = !willOpen;
        filterButton.setAttribute("aria-expanded", String(willOpen));
      });

      filterMenu.addEventListener("click", function (event) {
        event.stopPropagation();
      });
    }

    if (applyDateFilterButton) {
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
    }

    if (clearDateFilterButton) {
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
    }

    document.addEventListener("click", closeFilterMenu);

    const paginationButtons = document.querySelectorAll(".table-pagination button");

    paginationButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        if (!/^\d+$/.test(button.textContent.trim())) return;

        paginationButtons.forEach(function (item) {
          item.classList.remove("active");
          item.removeAttribute("aria-current");
        });

        button.classList.add("active");
        button.setAttribute("aria-current", "page");
      });
    });

    const detailsDialog = document.getElementById("orderDetailsDialog");
    const dialogTitle = document.getElementById("orderDialogTitle");
    const dialogContent = document.getElementById("orderDialogContent");
    const detailLabels = ["رقم الطلب", "المنتج", "الكمية", "قيمة الطلب", "تاريخ الطلب", "موعد التسليم", "الحالة", "التنبيه"];
    const orderProducts = {
      "#10358": [
        { name: "تيشيرت قطن أبيض", quantity: 30, price: "$72.00" },
        { name: "تيشيرت قطن أسود", quantity: 20, price: "$48.00" }
      ],
      "#10357": [
        { name: "هودي أسود", quantity: 5, price: "$60.00" },
        { name: "هودي رمادي", quantity: 5, price: "$60.00" },
        { name: "هودي كحلي", quantity: 5, price: "$60.00" },
        { name: "هودي أبيض", quantity: 5, price: "$60.00" }
      ],
      "#10356": [{ name: "كوب سيراميك مطبوع", quantity: 100, price: "$350.00" }],
      "#10355": [{ name: "ستيكرات مخصصة", quantity: 200, price: "$80.00" }],
      "#10354": [
        { name: "ورق A4 ملون", quantity: 200, price: "$60.00" },
        { name: "ورق A4 أبيض وأسود", quantity: 200, price: "$50.00" },
        { name: "ورق مقوّى", quantity: 100, price: "$40.00" }
      ],
      "#10353": [
        { name: "تيشيرت قطن أبيض", quantity: 15, price: "$37.50" },
        { name: "تيشيرت قطن أسود", quantity: 15, price: "$37.50" }
      ],
      "#10352": [{ name: "كوب سيراميك مطبوع", quantity: 80, price: "$280.00" }],
      "#10351": [{ name: "هودي بطباعة أمامية", quantity: 25, price: "$300.00" }],
      "#10350": [{ name: "ستيكرات مخصصة", quantity: 150, price: "$60.00" }],
      "#10349": [{ name: "ورق A4 ملون", quantity: 300, price: "$210.00" }]
    };

    function productVisuals(productName) {
      if (productName.includes("تيشيرت")) return "/front/assets/images/customer/tshirts/adults-good-day.png";
      if (productName.includes("هودي")) return "/front/assets/images/customer/orderBasket/good-vibes-hoodie.png";
      if (productName.includes("كوب")) return "/front/assets/images/customer/mugs/palestine.png";
      if (productName.includes("ستيكر")) return "/front/assets/images/customer/stickers/colorful/watermelon.svg";
      return "/front/assets/images/posterposter.png";
    }

    function createProductVisual(labelText, imagePath, alternativeText) {
      const figure = document.createElement("figure");
      const label = document.createElement("figcaption");
      const image = document.createElement("img");

      figure.className = "product-visual";
      label.textContent = labelText;
      image.src = imagePath;
      image.alt = alternativeText;
      image.loading = "lazy";
      figure.append(label, image);
      return figure;
    }

    function productSpecifications(productName, index) {
      if (productName.includes("تيشيرت")) {
        return {
          size: index % 2 === 0 ? "L" : "XL",
          color: productName.includes("أسود") ? "أسود" : "أبيض"
        };
      }

      if (productName.includes("هودي")) {
        const hoodieColors = ["أسود", "رمادي", "كحلي", "أبيض"];
        const hoodieSizes = ["M", "L", "XL", "2XL"];
        return { size: hoodieSizes[index % hoodieSizes.length], color: hoodieColors[index % hoodieColors.length] };
      }

      if (productName.includes("كوب")) return { size: "330 مل", color: "أبيض" };
      if (productName.includes("ستيكر")) return { size: "7 × 7 سم", color: "متعدد الألوان" };

      return {
        size: "A4",
        color: productName.includes("أبيض وأسود") ? "أبيض وأسود" : productName.includes("مقوّى") ? "أبيض" : "ملون"
      };
    }

    function appendOrderProducts(orderNumber) {
      const productsSection = document.createElement("section");
      const heading = document.createElement("h3");
      const productsList = document.createElement("div");
      const products = orderProducts[orderNumber] || [];

      productsSection.className = "order-products";
      heading.innerHTML = '<i class="bi bi-box-seam" aria-hidden="true"></i> منتجات الطلب <span>(' + products.length + ")</span>";
      productsList.className = "order-products-list";

      products.forEach(function (product, index) {
        const productRow = document.createElement("div");
        const productInfo = document.createElement("div");
        const name = document.createElement("strong");
        const quantity = document.createElement("span");
        const price = document.createElement("b");
        const options = document.createElement("div");
        const size = document.createElement("span");
        const color = document.createElement("span");
        const specifications = productSpecifications(product.name, index);
        const productImage = createProductVisual("المنتج بالتصميم", productVisuals(product.name), "صورة " + product.name + " بالتصميم");

        productRow.className = "order-product-row";
        productInfo.className = "order-product-info";
        name.textContent = String(index + 1) + ". " + product.name;
        quantity.textContent = "الكمية: " + product.quantity;
        price.textContent = product.price;
        options.className = "product-options";
        size.textContent = "القياس: " + specifications.size;
        color.textContent = "اللون: " + specifications.color;
        options.append(size, color);
        productInfo.append(name, quantity, options, price);
        productRow.append(productInfo, productImage);
        productsList.appendChild(productRow);
      });

      productsSection.append(heading, productsList);
      dialogContent.appendChild(productsSection);
    }

    document.querySelectorAll(".request-details-button").forEach(function (button) {
      button.addEventListener("click", function () {
        const row = button.closest("tr");
        const cells = Array.from(row.cells).slice(0, 8);
        const orderNumber = cells[0].textContent.trim();

        dialogTitle.textContent = orderNumber;
        dialogContent.replaceChildren();

        cells.forEach(function (cell, index) {
          if (index === 1) return;

          const item = document.createElement("div");
          const label = document.createElement("small");
          const value = document.createElement("strong");

          item.className = "order-detail-item";
          label.textContent = detailLabels[index];
          value.textContent = cell.textContent.trim();
          item.append(label, value);
          dialogContent.appendChild(item);
        });

        appendOrderProducts(orderNumber);

        if (typeof detailsDialog.showModal === "function") detailsDialog.showModal();
        else detailsDialog.setAttribute("open", "");
      });
    });

    detailsDialog.querySelectorAll("[data-dialog-close]").forEach(function (button) {
      button.addEventListener("click", function () {
        if (typeof detailsDialog.close === "function") detailsDialog.close();
        else detailsDialog.removeAttribute("open");
      });
    });

    detailsDialog.addEventListener("click", function (event) {
      if (event.target === detailsDialog) detailsDialog.close();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(document, window);
