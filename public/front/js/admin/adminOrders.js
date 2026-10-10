/* Admin orders page: counters, status filters, search, details dialog and status update. */
(function (window, document) {
  "use strict";

  function initialize() {
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const csrf = document.querySelector('meta[name="csrf-token"]');
    const orderRows = Array.from(document.querySelectorAll("[data-order-row]"));
    const orderFilters = Array.from(document.querySelectorAll("[data-order-filter]"));
    const ordersSearch = document.getElementById("ordersSearch");
    const ordersEmptyRow = document.getElementById("ordersEmptyRow");
    const ordersResultCount = document.getElementById("ordersResultCount");
    const dialog = document.getElementById("orderDetailsDialog");
    const detailsList = document.getElementById("orderDetailsList");
    const statusSelect = document.getElementById("dialogStatusSelect");
    const printerSelect = document.getElementById("dialogPrinterSelect");
    const hint = document.getElementById("orderManagementHint");
    const feedback = document.getElementById("orderDialogFeedback");
    const saveButton = document.getElementById("saveOrderChanges");
    const manualPaymentReview = document.getElementById("manualPaymentReview");
    const paymentReceiptLink = document.getElementById("paymentReceiptLink");
    let activeFilter = "all";
    let activeRow = null;

    const presentation = {
      processing: { label: "قيد التنفيذ", className: "is-processing", shipment: "قيد التجهيز" },
      shipped: { label: "تم الشحن", className: "is-shipped", shipment: "في الطريق إلى العميل" },
      completed: { label: "مكتمل", className: "is-completed", shipment: "تم التسليم" },
      pending: { label: "معلق", className: "is-pending", shipment: "بانتظار التجهيز" },
      cancelled: { label: "ملغي", className: "is-cancelled", shipment: "أُلغيت الشحنة" },
    };

    const setText = function (id, value) {
      document.getElementById(id).textContent = value || "—";
    };

    /* ---------- Counters ---------- */
    document.querySelectorAll("[data-stat-counter]").forEach(function (counter) {
      const target = Number(counter.dataset.statCounter);
      if (reducedMotion || target === 0) return;

      const start = performance.now();
      function draw(now) {
        const progress = Math.min((now - start) / 1100, 1);
        counter.textContent = Math.round(target * (1 - Math.pow(1 - progress, 3))).toLocaleString("en-US");
        if (progress < 1) requestAnimationFrame(draw);
      }
      requestAnimationFrame(draw);
    });

    /* ---------- Filters and search ---------- */
    const normalize = function (text) { return String(text || "").trim().toLocaleLowerCase("ar"); };

    function filterOrders() {
      const query = ordersSearch ? normalize(ordersSearch.value) : "";
      let visible = 0;

      orderRows.forEach(function (row) {
        const matches = (activeFilter === "all" || row.dataset.status === activeFilter)
          && normalize(row.dataset.search).includes(query);
        row.hidden = !matches;
        if (matches) visible += 1;
      });

      ordersEmptyRow.hidden = visible !== 0;
      ordersResultCount.textContent = "عرض " + visible + " من أصل " + orderRows.length + " طلبًا";
    }

    orderFilters.forEach(function (button) {
      button.addEventListener("click", function () {
        activeFilter = button.dataset.orderFilter;
        orderFilters.forEach(function (filter) { filter.classList.toggle("active", filter === button); });
        filterOrders();
      });
    });

    if (ordersSearch) ordersSearch.addEventListener("input", filterOrders);

    /* ---------- A shop turned the order down: send it to another shop ---------- */
    const rerouteSection = document.getElementById("rerouteSection");
    const rerouteTop = document.getElementById("rerouteTop");
    const rerouteOthers = document.getElementById("rerouteOthers");
    const rerouteButton = document.getElementById("rerouteButton");

    function readReroute(row) {
      try { return row.dataset.reroute ? JSON.parse(row.dataset.reroute) : null; } catch (error) { return null; }
    }

    function fillReroute(row) {
      const data = readReroute(row);
      if (!rerouteSection) return;
      rerouteSection.hidden = !data;
      if (!data) return;

      const hasShops = data.top.length > 0 || data.others.length > 0;
      document.getElementById("rerouteHint").textContent = hasShops
        ? "المطابع المقترحة مرتبة حسب مدينة العميل ثم الأقل تكلفة ثم الأسرع. المطبعة التي رفضت الطلب غير ظاهرة."
        : "لا توجد مطبعة أخرى تقدّم كل منتجات هذا الطلب. يمكنك إلغاء الطلب وإرجاع المبلغ للعميل.";

      rerouteTop.replaceChildren(...data.top.map(function (shop, index) {
        const label = document.createElement("label");
        label.className = "reroute-option";
        const input = document.createElement("input");
        input.type = "radio"; input.name = "rerouteChoice"; input.value = shop.id; input.checked = index === 0;
        input.addEventListener("change", function () { rerouteOthers.value = ""; });
        const body = document.createElement("span");
        const name = document.createElement("strong");
        name.textContent = (index + 1) + ". " + shop.name + (shop.city ? " (" + shop.city + ")" : "");
        const meta = document.createElement("small");
        meta.textContent = Number(shop.estimate).toFixed(2) + " ₪ · حتى " + shop.days + " يوم" + (shop.reasons.length ? " · " + shop.reasons.join("، ") : "");
        body.append(name, meta);
        label.append(input, body);
        return label;
      }));

      rerouteOthers.replaceChildren(...[["", "—"]].concat(data.others.map(function (shop) { return [shop.id, shop.label]; })).map(function (pair) {
        const option = document.createElement("option");
        option.value = pair[0]; option.textContent = pair[1];
        return option;
      }));
      rerouteOthers.parentElement.hidden = data.others.length === 0;
      rerouteButton.disabled = !hasShops;
    }

    if (rerouteOthers) {
      // A shop picked from the list wins over the ticked card, like on the payment notices page.
      rerouteOthers.addEventListener("change", function () {
        if (rerouteOthers.value) rerouteTop.querySelectorAll("input").forEach(function (input) { input.checked = false; });
      });
    }

    if (rerouteButton) {
      rerouteButton.addEventListener("click", function () {
        const data = activeRow && readReroute(activeRow);
        const chosen = rerouteOthers.value || (rerouteTop.querySelector("input:checked") || {}).value;
        if (!data || !chosen) { feedback.textContent = "اختر المطبعة التي سيُوجَّه إليها الطلب."; return; }

        rerouteButton.disabled = true;
        window.fetch(data.url, {
          method: "POST",
          headers: { "Accept": "application/json", "Content-Type": "application/json", "X-CSRF-TOKEN": csrf ? csrf.content : "" },
          body: JSON.stringify({ branch_id: Number(chosen) }),
        })
          .then(function (response) { return response.json().then(function (body) { return { ok: response.ok, body: body }; }); })
          .then(function (result) {
            if (!result.ok) throw new Error(result.body.message || "تعذّر توجيه الطلب.");
            feedback.textContent = result.body.message;
            // The printer column and the status change: reload so the table shows the new shop.
            window.setTimeout(function () { window.location.reload(); }, 900);
          })
          .catch(function (error) {
            feedback.textContent = error.message;
            rerouteButton.disabled = false;
          });
      });
    }

    /* ---------- Details dialog ---------- */
    function renderItems(row) {
      const list = document.getElementById("dialogItems");
      let items = [];
      try { items = JSON.parse(row.dataset.items || "[]"); } catch (error) { items = []; }

      if (items.length === 0) {
        const empty = document.createElement("li");
        empty.textContent = "—";
        list.replaceChildren(empty);
        return;
      }

      list.replaceChildren(...items.map(function (item) {
        const li = document.createElement("li");
        const head = document.createElement("div");
        const name = document.createElement("strong");
        const price = document.createElement("bdi");
        name.textContent = item.name + (item.design ? " — " + item.design : "") + " × " + item.quantity;
        price.textContent = item.total;
        head.append(name, price);
        li.append(head);
        if (item.details.length) {
          const small = document.createElement("small");
          small.textContent = item.details.join(" · ");
          li.append(small);
        }
        return li;
      }));
    }

    function fillDetails(row) {
      const cells = Array.from(row.cells);
      const status = row.dataset.status;
      const awaitingPayment = row.dataset.awaitingPayment === "1";
      const next = (row.dataset.next || "").split(",").filter(Boolean);

      document.getElementById("orderDialogSubtitle").textContent = row.dataset.number;
      detailsList.replaceChildren(...[
        ["رقم الطلب", row.dataset.number],
        ["تاريخ الطلب", cells[2].innerText.trim()],
        ["العميل", row.dataset.customer],
        ["المطبعة", cells[4].innerText.trim()],
        ["المبلغ", cells[3].innerText.trim()],
        ["الحالة", cells[1].innerText.trim()],
      ].map(function (pair) {
        const item = document.createElement("div");
        const term = document.createElement("dt");
        const value = document.createElement("dd");
        term.textContent = pair[0];
        value.textContent = pair[1];
        item.append(term, value);
        return item;
      }));

      setText("dialogPhone", row.dataset.phone);
      setText("dialogPayment", row.dataset.payment);
      setText("dialogPaid", row.dataset.paid === "1" ? "مدفوع" : (row.dataset.paymentStatus === "pending_review" ? "بانتظار مراجعة الدفع" : "غير مدفوع"));
      setText("dialogShipment", (presentation[status] || {}).shipment);
      setText("dialogNotes", row.dataset.notes);
      setText("dialogAddress", row.dataset.address);
      const noticesLink = document.getElementById("paymentNoticesLink");
      if (noticesLink) noticesLink.href = noticesLink.dataset.base + "?order=" + encodeURIComponent(row.dataset.number);
      renderItems(row);

      // The payment decision (and the choice of shop) is made on the payment notices page, not here.
      if (manualPaymentReview) manualPaymentReview.hidden = !awaitingPayment;
      if (paymentReceiptLink) {
        paymentReceiptLink.hidden = !row.dataset.receiptUrl;
        paymentReceiptLink.href = row.dataset.receiptUrl || "#";
      }

      fillReroute(row);

      // The select lists where this order is now, then only where the admin may take it.
      statusSelect.replaceChildren(...[status].concat(next).map(function (value, index) {
        const option = document.createElement("option");
        option.value = value;
        option.textContent = index === 0 ? cells[1].innerText.trim() : (presentation[value] || {}).label || value;
        return option;
      }));
      statusSelect.value = status;
      statusSelect.disabled = next.length === 0;
      saveButton.disabled = next.length === 0;

      printerSelect.value = row.dataset.printerId || "";
      printerSelect.disabled = true; // display only: printers are assigned per branch, not from here
      hint.textContent = awaitingPayment
        ? "هذا الطلب بانتظار مراجعة الدفع: وافق عليه أو ارفضه من صفحة إشعارات الدفع."
        : next.length === 0
          ? "هذا الطلب نهائي، ولا يمكن تغيير حالته."
          : "المطبعة تقبل الطلب وتُعلّمه «جاهز للتسليم». من هنا يمكنك شحنه وإكماله، أو إلغاؤه.";
      feedback.textContent = "";
    }

    document.getElementById("ordersTableBody").addEventListener("click", function (event) {
      const button = event.target.closest(".order-details");
      if (!button) return;

      activeRow = button.closest("tr");
      fillDetails(activeRow);
      dialog.showModal();
    });

    // Coming back with the browser's back button can restore the page with the dialog still open: close it.
    window.addEventListener("pageshow", function (event) {
      if (event.persisted && dialog.open) dialog.close();
    });

    // Arriving from the dashboard with ?open=<order number> opens that order's details straight away.
    const openNumber = new URLSearchParams(window.location.search).get("open");
    if (openNumber) {
      const target = orderRows.find(function (row) { return row.dataset.number === openNumber; });
      if (target) {
        activeRow = target;
        fillDetails(target);
        dialog.showModal();
      }
      window.history.replaceState(null, "", window.location.pathname);
    }

    saveButton.addEventListener("click", function () {
      if (!activeRow) return;

      const row = activeRow;
      const body = { status: statusSelect.value };

      if (body.status === row.dataset.status) {
        feedback.textContent = "اختر الحالة الجديدة أولًا.";
        return;
      }

      saveButton.disabled = true;
      window.fetch(row.dataset.updateUrl, {
        method: "PATCH",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf ? csrf.content : "",
        },
        body: JSON.stringify(body),
      })
        .then(function (response) {
          return response.json().then(function (data) { return { ok: response.ok, data: data }; });
        })
        .then(function (result) {
          if (!result.ok) throw new Error(result.data.message || "تعذّر حفظ التعديلات.");

          const next = presentation[body.status];
          const badge = row.querySelector(".order-status");
          row.dataset.status = body.status;
          row.dataset.next = body.status === "shipped" ? "completed" : "";
          badge.className = "order-status " + next.className;
          badge.textContent = next.label;

          feedback.textContent = result.data.message;
          setText("dialogShipment", next.shipment);
          filterOrders();
          fillDetails(row); // the select now lists only what is still possible
          feedback.textContent = result.data.message;
        })
        .catch(function (error) {
          feedback.textContent = error.message;
          saveButton.disabled = false;
        });
    });

    dialog.querySelectorAll("[data-order-dialog-close]").forEach(function (button) {
      button.addEventListener("click", function () { dialog.close(); });
    });

    dialog.addEventListener("click", function (event) {
      if (event.target === dialog) dialog.close();
    });

    filterOrders();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(window, document);
