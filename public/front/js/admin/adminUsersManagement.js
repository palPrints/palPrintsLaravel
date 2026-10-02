/* Admin users management page: customer/designer/print-shop lists, filters and detail panels. */
(function (window, document) {
  "use strict";

  function initialize() {
    const pageTitle = document.getElementById("usersPageTitle");
    const usersManagement = document.querySelector(".users-management");
    const usersFilters = document.getElementById("usersFilters");
    const usersPanel = document.getElementById("usersPanel");
    const usersListTitle = document.getElementById("usersListTitle");
    const usersCount = document.getElementById("usersCount");
    const usersList = document.getElementById("usersList");
    const usersEmpty = document.getElementById("usersEmpty");
    const usersSubmenu = document.getElementById("usersSubmenu");
    const customerDialog = document.getElementById("customerDetailsDialog");
    const closeCustomerDialog = document.getElementById("closeCustomerDialog");
    const customerDisableButton = document.getElementById("customerDisableButton");
    const rejectReasonDialog = document.getElementById("rejectReasonDialog");
    const closeRejectReasonDialog = document.getElementById("closeRejectReasonDialog");
    const rejectReasonInput = document.getElementById("rejectReasonInput");
    const rejectReasonError = document.getElementById("rejectReasonError");
    const confirmRejectReason = document.getElementById("confirmRejectReason");
    let pendingRejectEntry = null;
    const dataScript = document.getElementById("usersData");
    const serverData = dataScript ? JSON.parse(dataScript.textContent || "{}") : {};
    const csrf = document.querySelector('meta[name="csrf-token"]');
    const userTypes = {
      customers: {
        title: "العملاء", icon: "bi-person", metricIcon: "bi-cart3", metricLabel: "طلب",
        users: serverData.customers || []
      },
      designers: {
        title: "المصممون", icon: "bi-palette", metricIcon: "bi-palette", metricLabel: "تصميم",
        users: serverData.designers || []
      },
      printShops: {
        title: "المطابع", icon: "bi-shop-window", metricIcon: "bi-box-seam", metricLabel: "منتج",
        users: serverData.printShops || []
      }
    };
    const statusLabels = { active: "نشط", suspended: "معطّل", pending: "معلّق الاعتماد" };
    let activeUserType = "customers";
    let activeStatus = "all";
    let expandedDesignerIndex = -1;
    let activeDesignerTab = "info";
    let expandedPrintShopIndex = -1;
    let activePrintShopTab = "info";

    function toast(message) {
      if (window.PalAdmin) window.PalAdmin.toast(message);
    }

    function getInitials(name) {
      return name.trim().charAt(0);
    }

    function renderFilters() {
      const users = userTypes[activeUserType].users;
      const filters = [
        { key: "all", label: "الكل", count: users.length },
        { key: "active", label: "نشط", count: users.filter(function (user) { return user.status === "active"; }).length },
        { key: "pending", label: "معلّق", count: users.filter(function (user) { return user.status === "pending"; }).length },
        { key: "suspended", label: "معطّل", count: users.filter(function (user) { return user.status === "suspended"; }).length }
      ];
      usersFilters.innerHTML = filters.map(function (filter) {
        return '<button class="users-filter' + (filter.key === activeStatus ? ' active' : '') + '" type="button" data-status-filter="' + filter.key + '"><b>' + filter.count + '</b><span>' + filter.label + '</span></button>';
      }).join("");
    }

    function userRank(user) {
      if (user.status === "pending" && user.reviewUrl) return 0;
      if (user.status === "pending") return 1;
      if (user.status === "active") return 2;
      return 3;
    }

    function renderUsers() {
      const type = userTypes[activeUserType];
      const visibleUsers = type.users.filter(function (user) {
        const statusMatches = activeStatus === "all" || user.status === activeStatus;
        return statusMatches;
      }).sort(function (a, b) { return userRank(a) - userRank(b); });
      usersList.innerHTML = visibleUsers.map(function (user) {
        const customerIndex = activeUserType === "customers" ? userTypes.customers.users.indexOf(user) : -1;
        const designerIndex = activeUserType === "designers" ? userTypes.designers.users.indexOf(user) : -1;
        const printShopIndex = activeUserType === "printShops" ? userTypes.printShops.users.indexOf(user) : -1;
        const isDesignerExpanded = designerIndex >= 0 && designerIndex === expandedDesignerIndex;
        const isPrintShopExpanded = printShopIndex >= 0 && printShopIndex === expandedPrintShopIndex;
        const isExpanded = isDesignerExpanded || isPrintShopExpanded;
        const customerAttributes = customerIndex >= 0 ? ' is-clickable" data-customer-index="' + customerIndex + '" role="button" tabindex="0" aria-label="عرض تفاصيل ' + user.name : '';
        const arrowAttributes = designerIndex >= 0 ? ' data-designer-index="' + designerIndex + '"' : printShopIndex >= 0 ? ' data-print-shop-index="' + printShopIndex + '"' : '';
        const arrow = arrowAttributes ? '<button class="user-arrow-button" type="button"' + arrowAttributes + ' aria-expanded="' + String(isExpanded) + '" aria-label="' + (isExpanded ? 'إخفاء تفاصيل ' : 'عرض تفاصيل ') + user.name + '"><i class="bi bi-chevron-' + (isExpanded ? 'up' : 'left') + '" aria-hidden="true"></i></button>' : '<i class="bi bi-chevron-left user-arrow" aria-hidden="true"></i>';
        const row = '<article class="user-row' + customerAttributes + '">' +
          '<span class="user-avatar" aria-hidden="true">' + getInitials(user.name) + '</span>' +
          '<div class="user-main"><strong>' + user.name + '</strong><small dir="ltr">' + user.email + '</small></div>' +
          '<div class="user-meta"><i class="bi ' + type.metricIcon + '" aria-hidden="true"></i><span>' + user.metric + ' ' + type.metricLabel + '</span><span class="user-status is-' + user.status + '">' + statusLabels[user.status] + '</span></div>' +
          arrow +
        '</article>';
        const expansion = isDesignerExpanded ? renderDesignerExpansion(user, designerIndex) : isPrintShopExpanded ? renderPrintShopExpansion(user, printShopIndex) : '';
        return '<div class="user-entry' + (isExpanded ? ' is-expanded' : '') + '">' + row + expansion + '</div>';
      }).join("");
      usersCount.textContent = String(visibleUsers.length);
      usersEmpty.hidden = visibleUsers.length !== 0;
    }

    function renderDesignerExpansion(designer, index) {
      const infoVisible = activeDesignerTab === "info";
      const canReview = designer.status === "pending" && designer.reviewUrl;
      return '<section class="designer-expansion" aria-label="تفاصيل ' + designer.name + '">' +
        '<div class="designer-tabs" role="tablist"><button class="' + (infoVisible ? 'active' : '') + '" type="button" data-designer-tab="info" data-designer-tab-index="' + index + '">البيانات</button><button class="' + (!infoVisible ? 'active' : '') + '" type="button" data-designer-tab="designs" data-designer-tab-index="' + index + '">التصاميم</button></div>' +
        '<div class="designer-info-panel"' + (infoVisible ? '' : ' hidden') + '>' +
          '<div class="designer-stats"><div><strong>' + designer.metric + '</strong><span>التصاميم</span></div><div><strong>' + designer.sales + '</strong><span>المبيعات</span></div><div><strong dir="ltr">' + designer.revenue + '</strong><span>الأرباح</span></div><div><strong>' + designer.city + '</strong><span>المدينة</span></div></div>' +
          '<a class="designer-portfolio" href="https://' + designer.portfolio + '" target="_blank" rel="noopener"><span><i class="bi bi-bezier2" aria-hidden="true"></i>رابط الأعمال</span><strong dir="ltr">' + designer.portfolio + '</strong></a>' +
          (designer.avatar ? '<a class="designer-portfolio" href="' + designer.avatar + '" target="_blank" rel="noopener"><span><i class="bi bi-person-badge" aria-hidden="true"></i>الصورة الشخصية</span><strong>عرض الصورة</strong></a>' : '') +
          '<dl class="designer-contact">' +
            '<div><dt>الاسم المعروض</dt><dd>' + (designer.displayName || '—') + '</dd></div>' +
            '<div><dt>البريد الإلكتروني</dt><dd dir="ltr">' + designer.email + '</dd></div>' +
            '<div><dt>رقم الهاتف</dt><dd dir="ltr">' + designer.phone + '</dd></div>' +
            '<div><dt>تاريخ الانضمام</dt><dd>' + designer.joined + '</dd></div>' +
            '<div><dt>نبذة تعريفية</dt><dd>' + (designer.bio || '—') + '</dd></div>' +
            '<div><dt>المهارات</dt><dd>' + (designer.skills || '—') + '</dd></div>' +
            '<div><dt>تاريخ تقديم الطلب</dt><dd>' + (designer.submittedAt || '—') + '</dd></div>' +
            '<div><dt>تاريخ الاعتماد</dt><dd>' + (designer.status === 'active' ? designer.approvedAt : '—') + '</dd></div>' +
          '</dl>' +
          (designer.adminNotes ? '<div class="designer-portfolio"><span><i class="bi bi-chat-left-text" aria-hidden="true"></i>ملاحظات الإدارة</span><strong>' + designer.adminNotes + '</strong></div>' : '') +
          (canReview
            ? '<div class="designer-approval-actions">' +
                '<button class="designer-approve-button" type="button" data-approve-designer="' + index + '">قبول الطلب</button>' +
                '<button class="designer-reject-button" type="button" data-reject-designer="' + index + '">رفض الطلب</button>' +
              '</div>'
            : '<button class="designer-disable" type="button" data-disable-designer="' + index + '">' + (designer.status === 'suspended' ? 'تفعيل الحساب' : 'تعطيل الحساب') + '</button>') +
        '</div>' +
        '<div class="designer-designs-panel' + (designer.designs && designer.designs.length ? ' has-list' : '') + '"' + (!infoVisible ? '' : ' hidden') + '>' + renderDesignsList(designer.designs) + '</div>' +
      '</section>';
    }

    function renderDesignsList(designs) {
      if (!designs || !designs.length) {
        return '<i class="bi bi-palette" aria-hidden="true"></i><strong>لا توجد تصاميم</strong><span>لم يقم هذا المصمم برفع أي تصميم بعد</span>';
      }
      return '<ul class="design-mini-list">' + designs.map(function (design) {
        const thumb = design.image ? '<img src="' + design.image + '" alt="" loading="lazy">' : '<i class="bi bi-image" aria-hidden="true"></i>';
        return '<li class="design-mini-item">' +
          '<span class="design-mini-thumb">' + thumb + '</span>' +
          '<div class="design-mini-body">' +
            '<strong>' + design.title + '</strong>' +
            '<div class="design-mini-meta"><span class="user-status ' + design.statusClass + '">' + design.statusLabel + '</span></div>' +
          '</div>' +
        '</li>';
      }).join('') + '</ul>';
    }

    function renderDocumentRow(icon, label, url) {
      const uploaded = Boolean(url);
      return '<li class="print-shop-document-row">' +
        '<span><i class="bi ' + icon + '" aria-hidden="true"></i>' + label + '</span>' +
        (uploaded
          ? '<a href="' + url + '" target="_blank" rel="noopener">عرض الملف</a>'
          : '<strong class="is-missing">غير مرفوعة</strong>') +
      '</li>';
    }

    function renderPrintShopExpansion(shop, index) {
      const infoVisible = activePrintShopTab === "info";
      const canReview = shop.status === "pending" && shop.reviewUrl;
      const documents =
        '<ul class="print-shop-documents">' +
          renderDocumentRow('bi-patch-check', 'وثيقة التحقق', shop.verificationDocument) +
          (shop.idDocumentAvailable
            ? renderDocumentRow('bi-person-vcard', 'صورة الهوية', shop.idDocument)
            : '<li class="print-shop-document-row"><span><i class="bi bi-person-vcard" aria-hidden="true"></i>صورة الهوية</span><strong class="is-missing">غير متاحة بعد</strong></li>') +
        '</ul>';
      return '<section class="designer-expansion print-shop-expansion" aria-label="تفاصيل ' + shop.name + '">' +
        '<div class="designer-tabs" role="tablist"><button class="' + (infoVisible ? 'active' : '') + '" type="button" data-print-shop-tab="info" data-print-shop-tab-index="' + index + '">البيانات</button><button class="' + (!infoVisible ? 'active' : '') + '" type="button" data-print-shop-tab="jobs" data-print-shop-tab-index="' + index + '">وظائف الطباعة</button></div>' +
        '<div class="designer-info-panel"' + (infoVisible ? '' : ' hidden') + '>' +
          '<div class="designer-stats print-shop-stats"><div><strong>' + shop.metric + '</strong><span>الوظائف المنجزة</span></div><div><strong>' + shop.activeJobs + '</strong><span>الوظائف النشطة</span></div><div><strong>' + shop.delivery + '</strong><span>متوسط التسليم</span></div><div><strong>' + shop.city + '</strong><span>المدينة</span></div></div>' +
          '<div class="print-shop-revenue"><span><i class="bi bi-wallet2" aria-hidden="true"></i>إجمالي الإيرادات</span><strong dir="ltr">' + shop.revenue + '</strong></div>' +
          documents +
          '<dl class="designer-contact">' +
            '<div><dt>اسم المسؤول</dt><dd>' + (shop.contactName || '—') + '</dd></div>' +
            '<div><dt>البريد الإلكتروني</dt><dd dir="ltr">' + shop.email + '</dd></div>' +
            '<div><dt>رقم الهاتف</dt><dd dir="ltr">' + shop.phone + '</dd></div>' +
            '<div><dt>واتساب</dt><dd dir="ltr">' + (shop.whatsapp || '—') + '</dd></div>' +
            '<div><dt>العنوان</dt><dd>' + (shop.address || '—') + '</dd></div>' +
            '<div><dt>المنتجات التي تطبعها</dt><dd>' + (shop.products || '—') + '</dd></div>' +
            '<div><dt>ساعات العمل</dt><dd>' + (shop.workingHours || '—') + '</dd></div>' +
            '<div><dt>تاريخ الانضمام</dt><dd>' + shop.joined + '</dd></div>' +
            '<div><dt>تاريخ تقديم الطلب</dt><dd>' + (shop.submittedAt || '—') + '</dd></div>' +
            '<div><dt>تاريخ الاعتماد</dt><dd>' + (shop.status === 'active' ? shop.approvedAt : '—') + '</dd></div>' +
            '<div><dt>الحالة التشغيلية</dt><dd>' + (shop.operating ? 'يستقبل طلبات حاليًا' : 'متوقف مؤقتًا') + '</dd></div>' +
          '</dl>' +
          (shop.adminNotes ? '<div class="designer-portfolio"><span><i class="bi bi-chat-left-text" aria-hidden="true"></i>ملاحظات الإدارة</span><strong>' + shop.adminNotes + '</strong></div>' : '') +
          (canReview
            ? '<div class="designer-approval-actions">' +
                '<button class="designer-approve-button" type="button" data-approve-print-shop="' + index + '">قبول الطلب</button>' +
                '<button class="designer-reject-button" type="button" data-reject-print-shop="' + index + '">رفض الطلب</button>' +
              '</div>'
            : '<button class="designer-disable" type="button" data-disable-print-shop="' + index + '">' + (shop.status === 'suspended' ? 'تفعيل الحساب' : 'تعطيل الحساب') + '</button>') +
        '</div>' +
        '<div class="designer-designs-panel print-shop-jobs-panel"' + (!infoVisible ? '' : ' hidden') + '>' +
          (shop.jobsAvailable
            ? '<i class="bi bi-printer" aria-hidden="true"></i><strong>' + shop.activeJobs + ' وظائف نشطة</strong><span>وظائف الطباعة التي تتم معالجتها حاليًا</span>'
            : '<i class="bi bi-printer" aria-hidden="true"></i><strong>غير متاح حاليًا</strong><span>بيانات وظائف الطباعة تُضاف عند تفعيل نظام الطلبات</span>') +
        '</div>' +
      '</section>';
    }

    function showCustomerTab(tabName) {
      document.querySelectorAll("[data-customer-tab]").forEach(function (tab) {
        const selected = tab.dataset.customerTab === tabName;
        tab.classList.toggle("active", selected);
        tab.setAttribute("aria-selected", String(selected));
      });
      document.getElementById("customerInfoPanel").hidden = tabName !== "info";
      document.getElementById("customerOrdersPanel").hidden = tabName !== "orders";
    }

    function openCustomerDetails(index) {
      const customer = userTypes.customers.users[index];
      if (!customer) return;
      document.getElementById("customerDialogAvatar").textContent = getInitials(customer.name);
      document.getElementById("customerDialogName").textContent = customer.name;
      document.getElementById("customerDialogEmail").textContent = customer.email;
      document.getElementById("customerDialogPhone").textContent = customer.phone;
      document.getElementById("customerDialogCity").textContent = customer.city;
      document.getElementById("customerDialogRegistered").textContent = customer.registered;
      document.getElementById("customerDialogOrders").textContent = String(customer.metric);
      document.getElementById("customerDialogSpend").textContent = customer.spend;
      document.getElementById("customerDialogLastOrder").textContent = customer.lastOrder;
      document.getElementById("customerOrdersSummary").textContent = String(customer.metric);
      const status = document.getElementById("customerDialogStatus");
      status.textContent = statusLabels[customer.status];
      status.className = "user-status is-" + customer.status;
      customerDisableButton.textContent = customer.status === "suspended" ? "تفعيل الحساب" : "تعطيل الحساب";
      customerDisableButton.dataset.customerIndex = String(index);
      showCustomerTab("info");
      customerDialog.showModal();
    }

    function openRejectDialog(entry) {
      if (!entry.reviewUrl) return;
      pendingRejectEntry = entry;
      rejectReasonInput.value = "";
      rejectReasonInput.classList.remove("is-invalid");
      rejectReasonError.hidden = true;
      rejectReasonDialog.showModal();
      rejectReasonInput.focus();
    }

    function reviewAccountRequest(entry, action, reason) {
      if (!entry.reviewUrl) return;

      window.fetch(entry.reviewUrl, {
        method: "POST",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf ? csrf.content : ""
        },
        body: JSON.stringify({ action: action, admin_notes: reason })
      })
        .then(function (response) {
          return response.json().then(function (body) { return { ok: response.ok, body: body }; });
        })
        .then(function (result) {
          if (!result.ok) throw new Error(result.body.message || "تعذّر تنفيذ الإجراء.");
          entry.status = action === "approved" ? "active" : "suspended";
          entry.reviewUrl = null;
          renderFilters();
          renderUsers();
          toast(result.body.message);
        })
        .catch(function (error) {
          toast(error.message || "تعذّر تنفيذ الإجراء.");
        });
    }

    function selectUserType(typeKey, updateHash) {
      if (!userTypes[typeKey]) return;
      activeUserType = typeKey;
      activeStatus = "all";
      expandedDesignerIndex = -1;
      activeDesignerTab = "info";
      expandedPrintShopIndex = -1;
      activePrintShopTab = "info";
      const type = userTypes[typeKey];
      pageTitle.textContent = type.title;
      usersListTitle.innerHTML = '<i class="bi ' + type.icon + '" aria-hidden="true"></i><span>' + type.title + '</span>';
      usersPanel.className = "users-panel theme-" + typeKey;
      usersManagement.className = "users-management theme-" + typeKey;
      renderFilters();
      renderUsers();
      if (updateHash) window.history.replaceState(null, "", "#" + (typeKey === "printShops" ? "print-shops" : typeKey));
    }

    const initialHash = window.location.hash.replace("#", "");
    selectUserType(initialHash === "designers" ? "designers" : initialHash === "print-shops" ? "printShops" : "customers", false);

    if (usersSubmenu) {
      usersSubmenu.addEventListener("click", function (event) {
        const link = event.target.closest("[data-user-type]");
        if (!link) return;
        event.preventDefault();
        selectUserType(link.dataset.userType, true);
      });
    }
    usersFilters.addEventListener("click", function (event) {
      const button = event.target.closest("[data-status-filter]");
      if (!button) return;
      activeStatus = button.dataset.statusFilter;
      renderFilters();
      renderUsers();
    });
    usersList.addEventListener("click", function (event) {
      const printShopTab = event.target.closest("[data-print-shop-tab]");
      if (printShopTab) {
        activePrintShopTab = printShopTab.dataset.printShopTab;
        expandedPrintShopIndex = Number(printShopTab.dataset.printShopTabIndex);
        renderUsers();
        return;
      }
      const approvePrintShop = event.target.closest("[data-approve-print-shop]");
      if (approvePrintShop) {
        reviewAccountRequest(userTypes.printShops.users[Number(approvePrintShop.dataset.approvePrintShop)], "approved", null);
        return;
      }
      const rejectPrintShop = event.target.closest("[data-reject-print-shop]");
      if (rejectPrintShop) {
        openRejectDialog(userTypes.printShops.users[Number(rejectPrintShop.dataset.rejectPrintShop)]);
        return;
      }
      const disablePrintShop = event.target.closest("[data-disable-print-shop]");
      if (disablePrintShop) {
        const shop = userTypes.printShops.users[Number(disablePrintShop.dataset.disablePrintShop)];
        shop.status = shop.status === "suspended" ? "active" : "suspended";
        renderFilters();
        renderUsers();
        toast(shop.status === "suspended" ? "تم تعطيل حساب المطبعة." : "تم تفعيل حساب المطبعة.");
        return;
      }
      const designerTab = event.target.closest("[data-designer-tab]");
      if (designerTab) {
        activeDesignerTab = designerTab.dataset.designerTab;
        expandedDesignerIndex = Number(designerTab.dataset.designerTabIndex);
        renderUsers();
        return;
      }
      const approveDesigner = event.target.closest("[data-approve-designer]");
      if (approveDesigner) {
        reviewAccountRequest(userTypes.designers.users[Number(approveDesigner.dataset.approveDesigner)], "approved", null);
        return;
      }
      const rejectDesigner = event.target.closest("[data-reject-designer]");
      if (rejectDesigner) {
        openRejectDialog(userTypes.designers.users[Number(rejectDesigner.dataset.rejectDesigner)]);
        return;
      }
      const disableDesigner = event.target.closest("[data-disable-designer]");
      if (disableDesigner) {
        const designer = userTypes.designers.users[Number(disableDesigner.dataset.disableDesigner)];
        designer.status = designer.status === "suspended" ? "active" : "suspended";
        renderFilters();
        renderUsers();
        toast(designer.status === "suspended" ? "تم تعطيل حساب المصمم." : "تم تفعيل حساب المصمم.");
        return;
      }
      const row = event.target.closest("[data-customer-index]");
      if (row) openCustomerDetails(Number(row.dataset.customerIndex));
      const designerRow = event.target.closest("[data-designer-index]");
      if (designerRow) {
        const index = Number(designerRow.dataset.designerIndex);
        expandedDesignerIndex = expandedDesignerIndex === index ? -1 : index;
        activeDesignerTab = "info";
        renderUsers();
      }
      const printShopRow = event.target.closest("[data-print-shop-index]");
      if (printShopRow) {
        const index = Number(printShopRow.dataset.printShopIndex);
        expandedPrintShopIndex = expandedPrintShopIndex === index ? -1 : index;
        activePrintShopTab = "info";
        renderUsers();
      }
    });
    usersList.addEventListener("keydown", function (event) {
      if (event.key !== "Enter" && event.key !== " ") return;
      const row = event.target.closest("[data-customer-index]");
      if (row) {
        event.preventDefault();
        openCustomerDetails(Number(row.dataset.customerIndex));
        return;
      }
    });
    closeCustomerDialog.addEventListener("click", function () { customerDialog.close(); });
    customerDialog.addEventListener("click", function (event) {
      if (event.target === customerDialog) customerDialog.close();
    });
    document.querySelectorAll("[data-customer-tab]").forEach(function (tab) {
      tab.addEventListener("click", function () { showCustomerTab(tab.dataset.customerTab); });
    });
    closeRejectReasonDialog.addEventListener("click", function () { rejectReasonDialog.close(); });
    rejectReasonDialog.addEventListener("click", function (event) {
      if (event.target === rejectReasonDialog) rejectReasonDialog.close();
    });
    confirmRejectReason.addEventListener("click", function () {
      const reason = rejectReasonInput.value.trim();
      if (!reason) {
        rejectReasonInput.classList.add("is-invalid");
        rejectReasonError.hidden = false;
        return;
      }
      const entry = pendingRejectEntry;
      rejectReasonDialog.close();
      if (entry) reviewAccountRequest(entry, "rejected", reason);
    });
    customerDisableButton.addEventListener("click", function () {
      const customer = userTypes.customers.users[Number(customerDisableButton.dataset.customerIndex)];
      customer.status = customer.status === "suspended" ? "active" : "suspended";
      customerDialog.close();
      renderFilters();
      renderUsers();
      toast(customer.status === "suspended" ? "تم تعطيل حساب العميل." : "تم تفعيل حساب العميل.");
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initialize, { once: true });
  else initialize();
})(window, document);
