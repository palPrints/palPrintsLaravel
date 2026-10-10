/* Shared script for the admin settings and support pages (tabs, forms, support inbox, tickets). */
(function (window, document) {
  "use strict";
  if (document.body.classList.contains("admin-support-inbox-page")) {
    if ("scrollRestoration" in window.history) window.history.scrollRestoration = "manual";
    window.addEventListener("pageshow", function () {
      window.setTimeout(function () { window.scrollTo(0, 0); }, 0);
    });
  }
  function init() {
    function toast(message) {
      if (window.PalAdmin) window.PalAdmin.toast(message);
    }

    var tabs = Array.from(document.querySelectorAll("[data-settings-tab]"));
    var panels = Array.from(document.querySelectorAll("[data-settings-panel]"));
    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        var target = tab.dataset.settingsTab;
        tabs.forEach(function (item) { var active = item === tab; item.classList.toggle("active", active); item.setAttribute("aria-selected", String(active)); });
        panels.forEach(function (panel) { panel.hidden = panel.dataset.settingsPanel !== target; });
      });
    });
    var pageTabs = Array.from(document.querySelectorAll("[data-page-tab]"));
    pageTabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        pageTabs.forEach(function (item) { item.classList.toggle("active", item === tab); });
        document.querySelectorAll("[data-page-pane]").forEach(function (pane) { pane.hidden = pane.dataset.pagePane !== tab.dataset.pageTab; });
      });
    });
    document.querySelectorAll("[data-subtab]").forEach(function (button) {
      button.addEventListener("click", function () {
        var scope = button.closest("[data-settings-panel]");
        scope.querySelectorAll("[data-subtab]").forEach(function (item) { var active = item === button; item.classList.toggle("active", active); item.setAttribute("aria-selected", String(active)); });
        scope.querySelectorAll("[data-subpanel]").forEach(function (panel) { panel.hidden = panel.dataset.subpanel !== button.dataset.subtab; });
      });
    });
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : "";

    function postForm(url, body) {
      return window.fetch(url, {
        method: "POST",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken
        },
        body: JSON.stringify(body)
      }).then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        });
      });
    }

    document.querySelectorAll("[data-admin-form]").forEach(function (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;
        var url = form.dataset.action;
        if (!url) return;

        var body = {};
        Array.from(form.elements).forEach(function (field) {
          if (!field.name) return;
          if (field.type === "checkbox") { body[field.name] = field.checked ? 1 : 0; return; }
          body[field.name] = field.value;
        });

        var submitButton = form.querySelector('[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        postForm(url, body)
          .then(function (result) {
            if (!result.ok) throw new Error(result.data.message || "تعذّر حفظ الإعدادات.");
            toast(result.data.message);
          })
          .catch(function (error) { toast(error.message || "تعذّر حفظ الإعدادات."); })
          .finally(function () { if (submitButton) submitButton.disabled = false; });
      });
    });

    document.querySelectorAll("[data-payment-method]").forEach(function (card) {
      var checkbox = card.querySelector('input[type="checkbox"]');
      var status = card.querySelector(".admin-status");
      if (!checkbox) return;
      checkbox.addEventListener("change", function () {
        var url = card.dataset.action;
        if (!url) return;
        checkbox.disabled = true;
        postForm(url, { enabled: checkbox.checked ? 1 : 0 })
          .then(function (result) {
            if (!result.ok) throw new Error(result.data.message || "تعذّر تحديث وسيلة الدفع.");
            if (status) {
              status.classList.toggle("is-success", checkbox.checked);
              status.classList.toggle("is-warning", !checkbox.checked);
              status.textContent = checkbox.checked ? "نشطة" : "متوقفة";
            }
            toast(result.data.message);
          })
          .catch(function (error) {
            checkbox.checked = !checkbox.checked;
            toast(error.message || "تعذّر تحديث وسيلة الدفع.");
          })
          .finally(function () { checkbox.disabled = false; });
      });
    });

    document.querySelectorAll("[data-toast]").forEach(function (button) {
      button.addEventListener("click", function () { toast(button.dataset.toast); });
    });

    var exportAuditLogButton = document.getElementById("exportAuditLogButton");
    if (exportAuditLogButton) {
      exportAuditLogButton.addEventListener("click", function () {
        var rows = [["الإجراء", "الوصف", "التاريخ"]];
        document.querySelectorAll("[data-audit-row]").forEach(function (row) {
          rows.push([row.dataset.title || "", row.dataset.description || "", row.dataset.date || ""]);
        });
        if (rows.length === 1) {
          toast("لا يوجد نشاط لتصديره.");
          return;
        }
        var csv = rows.map(function (row) {
          return row.map(function (cell) { return '"' + String(cell).replace(/"/g, '""') + '"'; }).join(",");
        }).join("\r\n");
        var blobUrl = URL.createObjectURL(new Blob(["﻿" + csv], { type: "text/csv;charset=utf-8" }));
        var link = document.createElement("a");
        link.href = blobUrl;
        link.download = "palprints-audit-log.csv";
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 1000);
        toast("تم تنزيل سجل النشاط بصيغة CSV.");
      });
    }

    var supportRequests = Array.from(document.querySelectorAll("[data-support-request]"));
    var requestFilters = Array.from(document.querySelectorAll("[data-request-filter]"));
    var roleFilters = Array.from(document.querySelectorAll("[data-role-filter]"));
    var listStatusFilter = document.getElementById("supportListStatus");
    var requestsEmpty = document.getElementById("supportRequestsEmpty");
    var requestSearch = document.getElementById("dashboardSearch");
    var activeRequestFilter = "all";
    var activeRoleFilter = "all";
    var selectedRequest = supportRequests[0] || null;

    function setText(id, value) { var element = document.getElementById(id); if (element) element.textContent = value; }
    function openSupportRequest(item) {
      if (!item) return;
      selectedRequest = item;
      supportRequests.forEach(function (request) { request.classList.toggle("active", request === item); });
      setText("supportDetailAvatar", item.dataset.initials);
      setText("supportDetailName", item.dataset.name);
      setText("supportDetailRole", item.dataset.roleLabel);
      setText("supportDetailId", item.dataset.id);
      setText("supportDetailCategory", item.dataset.category);
      setText("supportDetailTime", item.dataset.time);
      setText("supportDetailSubject", item.dataset.subject);
      setText("supportMessageSender", item.dataset.name);
      setText("supportDetailMessage", item.dataset.message);
      var email = document.getElementById("supportDetailEmail");
      if (email) { email.textContent = item.dataset.email; email.href = "mailto:" + item.dataset.email; }
      var status = document.getElementById("supportRequestStatus");
      if (status) status.value = item.dataset.status;
      var dialogStatus = document.getElementById("supportDialogStatus");
      if (dialogStatus) { dialogStatus.className = "is-" + item.dataset.status; dialogStatus.textContent = item.dataset.status === "resolved" ? "تم الحل" : item.dataset.status === "open" ? "قيد المعالجة" : "جديدة"; }
      var roleBadge = document.getElementById("supportDetailRole");
      if (roleBadge) roleBadge.className = "request-role is-" + item.dataset.role;
      var history = document.getElementById("supportReplyHistory");
      if (history) {
        if (item.dataset.reply) { setText("supportPreviousReply", item.dataset.reply); history.hidden = false; }
        else history.hidden = true;
      }
      var replyForm = document.getElementById("supportReplyForm");
      if (replyForm) replyForm.hidden = true;
      var problemDialog = document.getElementById("supportProblemDialog");
      if (problemDialog && !problemDialog.open && typeof problemDialog.showModal === "function") problemDialog.showModal();
    }
    function filterSupportRequests() {
      var query = requestSearch ? requestSearch.value.trim().toLocaleLowerCase("ar") : "";
      var visible = 0;
      supportRequests.forEach(function (item) {
        var statusValue = listStatusFilter ? listStatusFilter.value : activeRequestFilter;
        var filterMatch = statusValue === "all" || item.dataset.status === statusValue;
        var roleMatch = activeRoleFilter === "all" || item.dataset.role === activeRoleFilter;
        var haystack = [item.dataset.name, item.dataset.subject, item.dataset.category, item.dataset.message].join(" ").toLocaleLowerCase("ar");
        var match = filterMatch && roleMatch && (!query || haystack.includes(query));
        item.hidden = !match;
        if (match) visible += 1;
      });
      if (requestsEmpty) requestsEmpty.hidden = visible !== 0;
    }
    supportRequests.forEach(function (item) { item.addEventListener("click", function () { openSupportRequest(item); }); });
    var supportProblemDialog = document.getElementById("supportProblemDialog");
    var closeSupportDialog = document.getElementById("closeSupportDialog");
    if (closeSupportDialog && supportProblemDialog) closeSupportDialog.addEventListener("click", function () { supportProblemDialog.close(); });
    if (supportProblemDialog) supportProblemDialog.addEventListener("click", function (event) { if (event.target === supportProblemDialog) supportProblemDialog.close(); });
    requestFilters.forEach(function (button) { button.addEventListener("click", function () { activeRequestFilter = button.dataset.requestFilter; requestFilters.forEach(function (item) { item.classList.toggle("active", item === button); }); filterSupportRequests(); }); });
    roleFilters.forEach(function (button) { button.addEventListener("click", function () { activeRoleFilter = button.dataset.roleFilter; roleFilters.forEach(function (item) { item.classList.toggle("active", item === button); }); filterSupportRequests(); }); });
    if (listStatusFilter) listStatusFilter.addEventListener("change", filterSupportRequests);
    if (requestSearch && supportRequests.length) requestSearch.addEventListener("input", filterSupportRequests);
    function syncRequestState(item, statusValue) {
      if (!item) return;
      item.dataset.status = statusValue;
      setText("supportNewCount", supportRequests.filter(function (r) { return r.dataset.status === "new"; }).length);
      var unreadMark = item.querySelector(".request-unread");
      if (unreadMark) unreadMark.remove();
      var state = item.querySelector(".request-state");
      var cleanState = item.querySelector(".support-clean-status");
      if (cleanState) {
        cleanState.className = "support-clean-status " + (statusValue === "resolved" ? "is-resolved" : statusValue === "open" ? "is-open" : "is-new");
        cleanState.textContent = statusValue === "resolved" ? "تم الحل" : statusValue === "open" ? "قيد المعالجة" : "جديدة";
        var dialogBadge = document.getElementById("supportDialogStatus");
        if (dialogBadge) { dialogBadge.className = "is-" + statusValue; dialogBadge.textContent = cleanState.textContent; }
        return;
      }
      if (!state) { state = document.createElement("span"); state.className = "request-state"; item.appendChild(state); }
      state.className = "request-state" + (statusValue === "resolved" ? " is-resolved" : "");
      state.textContent = statusValue === "resolved" ? "تم الحل" : statusValue === "open" ? "قيد المعالجة" : "جديدة";
    }
    function updateTicketStatus(item, statusValue) {
      if (!item || !item.dataset.statusUrl) return;
      return postForm(item.dataset.statusUrl, { status: statusValue })
        .then(function (result) {
          if (!result.ok) throw new Error(result.data.message || "تعذّر تحديث حالة الطلب.");
          syncRequestState(item, statusValue);
          toast(result.data.message);
          filterSupportRequests();
        })
        .catch(function (error) { toast(error.message || "تعذّر تحديث حالة الطلب."); });
    }
    var supportStatus = document.getElementById("supportRequestStatus");
    if (supportStatus) supportStatus.addEventListener("change", function () { updateTicketStatus(selectedRequest, supportStatus.value); });
    var resolveRequest = document.getElementById("resolveSupportRequest");
    if (resolveRequest) resolveRequest.addEventListener("click", function () {
      if (!selectedRequest) return;
      updateTicketStatus(selectedRequest, "resolved").then(function () { if (supportStatus) supportStatus.value = "resolved"; });
    });
    var supportReplyForm = document.getElementById("supportReplyForm");
    if (supportReplyForm) supportReplyForm.addEventListener("submit", function (event) {
      event.preventDefault();
      var reply = document.getElementById("supportReplyText");
      if (!reply || !reply.value.trim() || !selectedRequest || !selectedRequest.dataset.reviewUrl) return;
      var message = reply.value.trim();
      var submitButton = supportReplyForm.querySelector('[type="submit"]');
      if (submitButton) submitButton.disabled = true;
      postForm(selectedRequest.dataset.reviewUrl, { message: message })
        .then(function (result) {
          if (!result.ok) throw new Error(result.data.message || "تعذّر إرسال الرد.");
          selectedRequest.dataset.reply = message;
          setText("supportPreviousReply", message);
          var history = document.getElementById("supportReplyHistory");
          if (history) history.hidden = false;
          syncRequestState(selectedRequest, "open");
          if (supportStatus) supportStatus.value = "open";
          reply.value = "";
          supportReplyForm.hidden = true;
          toast(result.data.message);
          filterSupportRequests();
        })
        .catch(function (error) { toast(error.message || "تعذّر إرسال الرد."); })
        .finally(function () { if (submitButton) submitButton.disabled = false; });
    });
    var showSupportReply = document.getElementById("showSupportReply");
    var cancelSupportReply = document.getElementById("cancelSupportReply");
    if (showSupportReply && supportReplyForm) showSupportReply.addEventListener("click", function () { supportReplyForm.hidden = false; var reply = document.getElementById("supportReplyText"); if (reply) reply.focus(); });
    if (cancelSupportReply && supportReplyForm) cancelSupportReply.addEventListener("click", function () { supportReplyForm.hidden = true; });

    var ticketButtons = Array.from(document.querySelectorAll("[data-ticket-filter]"));
    var priorityFilter = document.getElementById("priorityFilter");
    var search = document.getElementById("dashboardSearch");
    var rows = Array.from(document.querySelectorAll("[data-ticket-row]"));
    var empty = document.getElementById("ticketsEmpty");
    var activeStatus = "all";
    function filterTickets() {
      var query = search ? search.value.trim().toLocaleLowerCase("ar") : "";
      var priority = priorityFilter ? priorityFilter.value : "all";
      var visible = 0;
      rows.forEach(function (row) {
        var statusMatch = activeStatus === "all" || row.dataset.status === activeStatus;
        var priorityMatch = priority === "all" || row.dataset.priority === priority;
        var searchMatch = !query || (row.dataset.search || row.textContent).toLocaleLowerCase("ar").includes(query);
        row.hidden = !(statusMatch && priorityMatch && searchMatch);
        if (!row.hidden) visible += 1;
      });
      if (empty) empty.hidden = visible !== 0;
    }
    ticketButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        activeStatus = button.dataset.ticketFilter;
        ticketButtons.forEach(function (item) { item.classList.toggle("active", item === button); });
        filterTickets();
      });
    });
    if (priorityFilter) priorityFilter.addEventListener("change", filterTickets);
    if (search && rows.length) search.addEventListener("input", filterTickets);

    var dialog = document.getElementById("ticketDialog");
    var dialogTitle = document.getElementById("ticketDialogTitle");
    var dialogUser = document.getElementById("ticketDialogUser");
    var dialogSubject = document.getElementById("ticketDialogSubject");
    var activeTicketRow = null;
    document.querySelectorAll(".support-action").forEach(function (button) {
      button.addEventListener("click", function () {
        activeTicketRow = button.closest("[data-ticket-row]");
        if (dialogTitle) dialogTitle.textContent = "#" + button.dataset.ticket;
        if (dialogUser) dialogUser.textContent = button.dataset.user || "مستخدم المنصة";
        if (dialogSubject) dialogSubject.textContent = button.dataset.subject || "طلب دعم فني";
        if (dialog && typeof dialog.showModal === "function") dialog.showModal();
      });
    });
    var replyButton = document.getElementById("sendTicketReply");
    if (replyButton) replyButton.addEventListener("click", function (event) {
      var reply = document.getElementById("ticketReply");
      if (!reply || !reply.value.trim()) { event.preventDefault(); reply.focus(); toast("اكتب ردًا قبل الإرسال."); return; }
      var status = document.getElementById("ticketStatus");
      if (activeTicketRow && status) {
        activeTicketRow.dataset.status = status.value;
        var badge = activeTicketRow.querySelector(".admin-status.is-new, .admin-status.is-progress, .admin-status.is-success");
        if (badge) {
          badge.className = "admin-status " + (status.value === "resolved" ? "is-success" : "is-progress");
          badge.textContent = status.value === "resolved" ? "محلولة" : "قيد المتابعة";
        }
      }
      toast("تم إرسال الرد وتسجيل الإجراء في سجل النشاط.");
      reply.value = "";
      filterTickets();
    });
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init, { once:true });
  else init();
})(window, document);
