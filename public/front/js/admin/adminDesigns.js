/* Admin designs page: filters, search, details dialog and approve / reject. */
(function (window, document) {
  "use strict";

  function initialize() {
    const rows = function () { return Array.from(document.querySelectorAll("[data-design-row]")); };
    const search = document.getElementById("dashboardSearch");
    const tableEmpty = document.getElementById("tableEmpty");
    const dialog = document.getElementById("designDialog");
    const dialogActions = document.getElementById("dialogActions");
    const csrf = document.querySelector('meta[name="csrf-token"]');
    let activeCategory = "all";
    let activeStatus = "all";
    let activeRow = null;

    const toast = function (message) {
      if (window.PalAdmin) window.PalAdmin.toast(message);
    };

    const normalize = function (value) {
      return String(value || "").trim().toLocaleLowerCase("ar")
        .replace(/[أإآ]/g, "ا").replace(/ة/g, "ه").replace(/ى/g, "ي");
    };

    const setText = function (id, value) {
      document.getElementById(id).textContent = value;
    };

    function updateSummary() {
      const all = rows();
      const count = function (status) {
        return all.filter(function (row) { return row.dataset.status === status; }).length;
      };
      const pending = count("pending");

      setText("totalDesigns", all.length);
      setText("pendingDesigns", pending);
      setText("approvedDesigns", count("approved"));
      setText("rejectedDesigns", count("rejected"));
      setText("totalTabCount", all.length);
      setText("pendingTabCount", pending);
      setText("approvedTabCount", count("approved"));
      setText("rejectedTabCount", count("rejected"));

      const badge = document.querySelector('.admin-sidebar a[href$="/admin/designs"] .admin-nav-badge');
      if (badge) {
        badge.textContent = pending;
        badge.hidden = pending === 0;
      }
    }

    function filterRows() {
      const query = normalize(search ? search.value : "");
      let visible = 0;

      rows().forEach(function (row) {
        const text = normalize([row.dataset.code, row.dataset.title, row.dataset.designer, row.dataset.categoryLabel].join(" "));
        const matches = (!query || text.includes(query))
          && (activeCategory === "all" || row.dataset.category === activeCategory)
          && (activeStatus === "all" || row.dataset.status === activeStatus);
        row.hidden = !matches;
        if (matches) visible += 1;
      });

      tableEmpty.hidden = visible !== 0;
    }

    function bindFilter(selector, attribute, apply) {
      document.querySelectorAll(selector).forEach(function (button) {
        button.addEventListener("click", function () {
          apply(button.dataset[attribute]);
          document.querySelectorAll(selector).forEach(function (item) {
            item.setAttribute("aria-pressed", String(item === button));
          });
          filterRows();
        });
      });
    }

    bindFilter("[data-category-filter]", "categoryFilter", function (value) { activeCategory = value; });
    bindFilter("[data-status-filter]", "statusFilter", function (value) { activeStatus = value; });

    if (search) search.addEventListener("input", filterRows);

    const stateLabels = { pending: "قيد المراجعة", approved: "معتمد", rejected: "مرفوض" };

    function openDetails(row) {
      const data = row.dataset;
      const image = document.getElementById("dialogDesignImage");
      const empty = document.getElementById("dialogDesignEmpty");
      const status = document.getElementById("dialogStatus");

      activeRow = row;
      image.hidden = !data.image;
      empty.hidden = Boolean(data.image);
      if (data.image) image.src = data.image;
      image.alt = "معاينة تصميم " + data.title;

      setText("dialogDesignTitle", data.title);
      setText("dialogDesignId", data.code);
      setText("dialogCategoryPreview", data.categoryLabel);
      setText("dialogCategory", data.categoryLabel);
      document.getElementById("dialogCategory").className = "category " + (data.categoryClass || "");
      setText("dialogDesigner", data.designer);
      setText("dialogProduct", data.product || "—");
      setText("dialogDate", data.date);
      [["audience", "dialogAudience"], ["previewColor", "dialogPreviewColor"], ["colors", "dialogColors"], ["sizes", "dialogSizes"]].forEach(function (pair) {
        setText(pair[1], data[pair[0]] || "");
        dialog.querySelector('[data-detail-row="' + pair[0] + '"]').hidden = !data[pair[0]];
      });
      setText("dialogBasePrice", data.basePrice);
      setText("dialogSellingPrice", data.sellingPrice);
      setText("dialogProfit", data.profit);
      status.className = "design-status " + data.status;
      status.textContent = stateLabels[data.status];

      document.getElementById("dialogReasonRow").hidden = !(data.status === "rejected" && data.reason);
      setText("dialogReason", data.reason || "");
      dialogActions.hidden = data.status !== "pending";
      dialog.showModal();
    }

    document.getElementById("designTableBody").addEventListener("click", function (event) {
      const button = event.target.closest("[data-details]");
      if (button) openDetails(button.closest("[data-design-row]"));
    });

    dialog.querySelectorAll("[data-dialog-close]").forEach(function (button) {
      button.addEventListener("click", function () { dialog.close(); });
    });
    dialog.addEventListener("click", function (event) {
      if (event.target === dialog) dialog.close();
    });

    function reviewDesign(action, reason) {
      if (!activeRow) return;
      reason = reason === undefined ? null : reason;

      const row = activeRow;
      const buttons = dialogActions.querySelectorAll("button");
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

          const state = action === "approve" ? "approved" : "rejected";
          const badge = row.querySelector(".design-status");
          row.dataset.status = state;
          row.dataset.reason = reason || "";
          badge.className = "design-status " + state;
          badge.textContent = stateLabels[state];
          dialog.close();
          updateSummary();
          filterRows();
          toast(result.body.message);
        })
        .catch(function (error) {
          toast(error.message || "تعذّر تنفيذ الإجراء.");
        })
        .finally(function () {
          buttons.forEach(function (button) { button.disabled = false; });
        });
    }

    document.getElementById("approveDesign").addEventListener("click", function () { reviewDesign("approve"); });
    const rejectDialog = document.getElementById("rejectDialog"), rejectReason = document.getElementById("rejectReason");
    rejectReason.addEventListener("input", function () { setText("rejectCount", String(rejectReason.value.length)); });
    document.getElementById("rejectDesign").addEventListener("click", function () {
      rejectReason.value = ""; setText("rejectCount", "0");
      rejectDialog.showModal(); rejectReason.focus();
    });
    document.getElementById("rejectCancel").addEventListener("click", function () { rejectDialog.close(); });
    rejectDialog.addEventListener("click", function (event) { if (event.target === rejectDialog) rejectDialog.close(); });
    document.getElementById("rejectForm").addEventListener("submit", function (event) {
      event.preventDefault();
      const reason = rejectReason.value.trim();
      rejectDialog.close();
      reviewDesign("reject", reason);
    });

    updateSummary();
    filterRows();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(window, document);
