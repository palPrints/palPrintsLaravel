/* Admin payments page: animated counters, tabs, details dialog and withdrawal approve / reject. */
(function (window, document) {
  "use strict";

  function initialize() {
    const summaryCounters = Array.from(document.querySelectorAll("[data-counter]"));
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const csrf = document.querySelector('meta[name="csrf-token"]');

    function toast(message) {
      if (window.PalAdmin) window.PalAdmin.toast(message);
    }

    /* ---------- Counters ---------- */
    function formatCounter(value, decimals) {
      return new Intl.NumberFormat("en-US", {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
      }).format(value);
    }

    function animateCounter(counter) {
      if (counter.dataset.animated === "true") return;
      counter.dataset.animated = "true";

      const target = Number(counter.dataset.counter);
      const decimals = Number(counter.dataset.counterDecimals || 0);
      const startTime = performance.now();

      function draw(now) {
        const progress = Math.min((now - startTime) / 1200, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        counter.textContent = formatCounter(target * eased, decimals);
        if (progress < 1) requestAnimationFrame(draw);
      }

      requestAnimationFrame(draw);
    }

    if (reducedMotion || !("IntersectionObserver" in window)) {
      summaryCounters.forEach(function (counter) {
        counter.textContent = formatCounter(Number(counter.dataset.counter), Number(counter.dataset.counterDecimals || 0));
      });
    } else {
      const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        });
      }, { threshold: 0.55 });

      summaryCounters.forEach(function (counter) {
        counter.textContent = formatCounter(0, Number(counter.dataset.counterDecimals || 0));
        observer.observe(counter);
      });
    }

    /* ---------- Tabs ---------- */
    const financeTabs = Array.from(document.querySelectorAll("[data-finance-filter]"));
    const financePanels = Array.from(document.querySelectorAll("[data-finance-panel]"));

    function activateFinanceFilter(selectedTab) {
      financeTabs.forEach(function (tab) {
        const isActive = tab === selectedTab;
        tab.classList.toggle("active", isActive);
        tab.setAttribute("aria-selected", String(isActive));
        tab.tabIndex = isActive ? 0 : -1;
      });

      financePanels.forEach(function (panel) {
        panel.hidden = panel.dataset.financePanel !== selectedTab.dataset.financeFilter;
      });
    }

    financeTabs.forEach(function (tab, index) {
      tab.addEventListener("click", function () { activateFinanceFilter(tab); });

      tab.addEventListener("keydown", function (event) {
        if (!["ArrowRight", "ArrowLeft", "Home", "End"].includes(event.key)) return;
        event.preventDefault();

        let nextIndex = index;
        if (event.key === "ArrowRight") nextIndex = (index - 1 + financeTabs.length) % financeTabs.length;
        if (event.key === "ArrowLeft") nextIndex = (index + 1) % financeTabs.length;
        if (event.key === "Home") nextIndex = 0;
        if (event.key === "End") nextIndex = financeTabs.length - 1;

        financeTabs[nextIndex].focus();
        activateFinanceFilter(financeTabs[nextIndex]);
      });
    });

    /* ---------- Details dialog ---------- */
    const detailsDialog = document.getElementById("detailsDialog");
    const detailsDialogTitle = document.getElementById("detailsDialogTitle");
    const detailsDialogSubtitle = document.getElementById("detailsDialogSubtitle");
    const detailsList = document.getElementById("detailsList");
    const detailsDialogFooter = document.getElementById("detailsDialogFooter");
    const dialogReviewActions = document.getElementById("dialogReviewActions");
    const dialogApprove = document.getElementById("dialogApprove");
    const dialogReject = document.getElementById("dialogReject");
    let activeRow = null;

    /* visibleIndexes are the table cells shown in the dialog (the details button cell is skipped). */
    const detailSchemas = {
      withdrawal: {
        title: "تفاصيل طلب السحب",
        labels: ["التفاصيل", "الحالة", "طريقة السحب", "تاريخ الطلب", "المبلغ", "نوع الحساب", "صاحب الطلب"],
        visibleIndexes: [6, 5, 4, 3, 2, 1],
      },
      /* "transaction" schema removed along with the transactions table's details column/button. */
    };

    document.addEventListener("click", function (event) {
      const button = event.target.closest(".details-button[data-detail-kind]");
      if (!button) return;

      const row = button.closest("tr");
      const cells = Array.from(row.cells);
      const kind = button.dataset.detailKind;
      const schema = detailSchemas[kind];
      activeRow = row;

      detailsDialogTitle.textContent = schema.title;
      detailsDialogSubtitle.textContent = kind === "withdrawal"
        ? "راجع بيانات الطلب قبل اتخاذ القرار"
        : "بيانات العملية المسجلة في النظام";

      detailsList.replaceChildren(...schema.visibleIndexes.map(function (cellIndex) {
        const item = document.createElement("div");
        const term = document.createElement("dt");
        const value = document.createElement("dd");
        term.textContent = schema.labels[cellIndex];
        value.textContent = cells[cellIndex].innerText.trim();
        item.append(term, value);
        return item;
      }));

      const reviewable = kind === "withdrawal"
        && Boolean(row.querySelector(".payment-status.pending"))
        && Boolean(row.dataset.reviewUrl);
      dialogReviewActions.hidden = !reviewable;
      detailsDialogFooter.hidden = !reviewable;

      detailsDialog.showModal();
    });

    function reviewWithdrawal(action) {
      if (!activeRow) return;

      let reason = null;
      if (action === "reject") {
        reason = window.prompt("سبب رفض الطلب (اختياري):", "");
        if (reason === null) return;
      }

      const row = activeRow;
      const buttons = [dialogApprove, dialogReject];
      buttons.forEach(function (button) { button.disabled = true; });

      window.fetch(row.dataset.reviewUrl, {
        method: "POST",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf ? csrf.content : "",
        },
        body: JSON.stringify({ action: action, reason: reason }),
      })
        .then(function (response) {
          return response.json().then(function (body) { return { ok: response.ok, body: body }; });
        })
        .then(function (result) {
          if (!result.ok) throw new Error(result.body.message || "تعذّر تنفيذ الإجراء.");

          const approved = action === "approve";
          const status = row.querySelector(".payment-status");
          status.className = "payment-status " + (approved ? "completed" : "failed");
          status.textContent = approved ? "تم الاعتماد" : "مرفوض";
          dialogReviewActions.hidden = true;
          detailsDialogFooter.hidden = true;
          detailsDialog.close();
          toast(result.body.message);
          window.setTimeout(function () { window.location.reload(); }, 900);
        })
        .catch(function (error) {
          toast(error.message || "تعذّر تنفيذ الإجراء.");
        })
        .finally(function () {
          buttons.forEach(function (button) { button.disabled = false; });
        });
    }

    dialogApprove.addEventListener("click", function () { reviewWithdrawal("approve"); });
    dialogReject.addEventListener("click", function () { reviewWithdrawal("reject"); });

    detailsDialog.querySelectorAll("[data-dialog-close]").forEach(function (button) {
      button.addEventListener("click", function () { detailsDialog.close(); });
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
})(window, document);
