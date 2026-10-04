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
    const approvePaymentButton = document.getElementById("approvePaymentButton");
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

    /* ---------- Details dialog ---------- */
    function fillDetails(row) {
      const cells = Array.from(row.cells);
      const status = row.dataset.status;
      const locked = status === "completed" || status === "cancelled";

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
      setText("dialogShipment", presentation[status].shipment);
      setText("dialogNotes", row.dataset.notes);

      const canReviewPayment = row.dataset.paymentStatus === "pending_review" && row.dataset.receiptUrl && row.dataset.approvePaymentUrl;
      if (manualPaymentReview) manualPaymentReview.hidden = !canReviewPayment;
      if (paymentReceiptLink) paymentReceiptLink.href = row.dataset.receiptUrl || "#";
      if (approvePaymentButton) approvePaymentButton.disabled = !canReviewPayment;

      statusSelect.value = status;
      printerSelect.value = row.dataset.printerId || "";
      printerSelect.disabled = true; // display only: printers are assigned per branch, not from here
      hint.textContent = locked
        ? "لا يمكن إعادة توجيه الطلبات المكتملة أو الملغاة إلى مطبعة أخرى."
        : "يمكنك تحديث الحالة أو إعادة توجيه الطلب إلى مطبعة أخرى.";
      feedback.textContent = "";
    }

    document.getElementById("ordersTableBody").addEventListener("click", function (event) {
      const button = event.target.closest(".order-details");
      if (!button) return;

      activeRow = button.closest("tr");
      fillDetails(activeRow);
      dialog.showModal();
    });

    saveButton.addEventListener("click", function () {
      if (!activeRow) return;

      const row = activeRow;
      const body = { status: statusSelect.value };

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
          badge.className = "order-status " + next.className;
          badge.textContent = next.label;

          if (!printerSelect.disabled && printerSelect.value) {
            row.dataset.printerId = printerSelect.value;
            row.cells[4].textContent = printerSelect.options[printerSelect.selectedIndex].textContent;
          }

          feedback.textContent = result.data.message;
          setText("dialogShipment", next.shipment);
          filterOrders();
        })
        .catch(function (error) {
          feedback.textContent = error.message;
        })
        .finally(function () {
          saveButton.disabled = false;
        });
    });


    if (approvePaymentButton) {
      approvePaymentButton.addEventListener("click", function () {
        if (!activeRow || !activeRow.dataset.approvePaymentUrl) return;

        approvePaymentButton.disabled = true;
        window.fetch(activeRow.dataset.approvePaymentUrl, {
          method: "POST",
          headers: {
            "Accept": "application/json",
            "X-CSRF-TOKEN": csrf ? csrf.content : "",
          },
        })
          .then(function (response) {
            return response.json().then(function (data) { return { ok: response.ok, data: data }; });
          })
          .then(function (result) {
            if (!result.ok) throw new Error(result.data.message || "تعذر اعتماد الدفع.");

            activeRow.dataset.paid = "1";
            activeRow.dataset.paymentStatus = "paid";
            activeRow.dataset.approvePaymentUrl = "";
            activeRow.dataset.status = "processing";

            const next = presentation.processing;
            const badge = activeRow.querySelector(".order-status");
            badge.className = "order-status " + next.className;
            badge.textContent = next.label;

            setText("dialogPaid", "مدفوع");
            setText("dialogShipment", next.shipment);
            if (manualPaymentReview) manualPaymentReview.hidden = true;
            feedback.textContent = result.data.message;
            filterOrders();
          })
          .catch(function (error) {
            feedback.textContent = error.message;
            approvePaymentButton.disabled = false;
          });
      });
    }
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
