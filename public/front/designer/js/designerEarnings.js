"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const Core = window.PalProfile;

  if (!Core) {
    return;
  }

  const withdrawFormElement = document.getElementById("withdrawForm");
  const availableBalance = Number(withdrawFormElement?.dataset.available || 0);
  const availableLabel = availableBalance.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const csrfToken = document.querySelector('meta[name="csrf-token"]');

  const dictionary = {
    ar: {
      documentTitle: "الأرباح | PalPrints",
      documentDescription: "تابع أرباحك وطلبات السحب في PalPrints",
      skipToContent: "تخطي إلى المحتوى",
      sidebarLabel: "القائمة الجانبية للمصمم",
      designerNavLabel: "روابط حساب المصمم",
      goHome: "الانتقال إلى الصفحة الرئيسية",
      openSidebar: "فتح القائمة الجانبية",
      closeSidebar: "إغلاق القائمة الجانبية",
      close: "إغلاق",
      changeTheme: "تغيير المظهر",
      switchToDark: "تفعيل الوضع الليلي",
      switchToLight: "تفعيل الوضع النهاري",
      changeLanguage: "تغيير اللغة",
      shoppingCart: "سلة المشتريات",
      notifications: "الإشعارات",
      dashboard: "لوحة التحكم",
      uploadDesign: "رفع تصميم جديد",
      myDesigns: "تصاميمي",
      profileTitle: "الملف الشخصي",
      earnings: "الأرباح",
      settings: "الإعدادات",
      support: "التواصل مع الدعم الفني",
      logout: "تسجيل الخروج",
      logoutConfirm: "هل تريد تسجيل الخروج من حسابك؟",
      earningsIntro: "تابع أرباحك واطلب استلامها بسهولة",
      requestEarnings: "طلب استلام الأرباح",
      earningsSummary: "ملخص الأرباح",
      availableBalance: "الرصيد المتاح للسحب",
      availableBalanceLabel: "الرصيد المتاح للسحب:",
      totalEarnings: "إجمالي الأرباح",
      pendingBalance: "قيد الاستحقاق",
      recentActivity: "حدث مؤخراً",
      filter: "الفلترة",
      earningsTableLabel: "جدول معاملات الأرباح، قابل للتمرير أفقياً",
      transactionCode: "كود العملية",
      product: "المنتج",
      designName: "اسم التصميم",
      date: "التاريخ",
      profit: "الربح",
      profitStatus: "حالة الربح",
      noMatchingTransactions: "لا توجد معاملات تطابق عوامل التصفية.",
      statusPending: "معلق",
      statusAvailable: "متاح للسحب",
      statusRequested: "في طلب السحب",
      statusTransferring: "قيد التحويل",
      statusComplete: "تم التحويل",
      statusRejected: "مرفوض",
      productTshirt: "تيشيرت",
      productMug: "كوب",
      productHoodie: "هودي",
      productShirt: "قميص",
      productBag: "حقيبة",
      designNature: "معاصرة الطبيعة",
      designCalligraphy: "خط عربي",
      designSimple: "تصميم بسيط",
      designLion: "الأسد الملك",
      designMinimal: "minimal vibes",
      designBlocked: "تصميم محظور",
      filterEarnings: "فلترة الأرباح",
      filterDescription: "حدّد الخيارات المناسبة للوصول إلى المعاملات التي تبحث عنها.",
      fromDate: "من تاريخ",
      toDate: "إلى تاريخ",
      minimumAmount: "الحد الأدنى",
      maximumAmount: "الحد الأقصى",
      allStatuses: "كل الحالات",
      allProducts: "كل المنتجات",
      operationType: "نوع العملية",
      allOperations: "كل العمليات",
      withdrawalFailed: "تعذر حفظ طلب السحب.",
      searchDesign: "ابحث باسم التصميم",
      reset: "إعادة تعيين",
      applyFilter: "تطبيق الفلترة",
      withdrawAmount: "المبلغ المراد سحبه",
      payoutMethod: "طريقة الاستلام",
      chooseMethod: "اختر الطريقة",
      bankTransfer: "تحويل بنكي",
      electronicWallet: "محفظة إلكترونية",
      amountError: "أدخل مبلغاً بين 1 ₪ و" + availableLabel + " ₪.",
      methodError: "اختر طريقة الاستلام.",
      notesOptional: "ملاحظات (اختياري)",
      notesPlaceholder: "أي تفاصيل تساعدنا في معالجة الطلب",
      cancel: "إلغاء",
      confirmRequest: "تأكيد الطلب",
      withdrawalSuccess: "تم إرسال طلب استلام الأرباح بنجاح."
    },
    en: {
      documentTitle: "Earnings | PalPrints",
      documentDescription: "Track your earnings and withdrawal requests on PalPrints",
      skipToContent: "Skip to content",
      sidebarLabel: "Designer sidebar",
      designerNavLabel: "Designer account links",
      goHome: "Go to the home page",
      openSidebar: "Open sidebar",
      closeSidebar: "Close sidebar",
      close: "Close",
      changeTheme: "Change theme",
      switchToDark: "Switch to dark mode",
      switchToLight: "Switch to light mode",
      changeLanguage: "Change language",
      shoppingCart: "Shopping cart",
      notifications: "Notifications",
      dashboard: "Dashboard",
      uploadDesign: "Upload new design",
      myDesigns: "My designs",
      profileTitle: "Profile",
      earnings: "Earnings",
      settings: "Settings",
      support: "Contact support",
      logout: "Log out",
      logoutConfirm: "Do you want to log out of your account?",
      earningsIntro: "Track your earnings and request a payout with ease",
      requestEarnings: "Request earnings payout",
      earningsSummary: "Earnings summary",
      availableBalance: "Available to withdraw",
      availableBalanceLabel: "Available to withdraw:",
      totalEarnings: "Total earnings",
      pendingBalance: "Pending clearance",
      recentActivity: "Recent activity",
      filter: "Filter",
      earningsTableLabel: "Earnings transactions table, horizontally scrollable",
      transactionCode: "Transaction ID",
      product: "Product",
      designName: "Design name",
      date: "Date",
      profit: "Earnings",
      profitStatus: "Earning status",
      noMatchingTransactions: "No transactions match the selected filters.",
      statusPending: "Pending",
      statusAvailable: "Available to withdraw",
      statusRequested: "Withdrawal requested",
      statusTransferring: "Transferring",
      statusComplete: "Transferred",
      statusRejected: "Rejected",
      productTshirt: "T-shirt",
      productMug: "Mug",
      productHoodie: "Hoodie",
      productShirt: "Shirt",
      productBag: "Bag",
      designNature: "Contemporary Nature",
      designCalligraphy: "Arabic Calligraphy",
      designSimple: "Minimal Design",
      designLion: "The Lion King",
      designMinimal: "minimal vibes",
      designBlocked: "Restricted Design",
      filterEarnings: "Filter earnings",
      filterDescription: "Choose the options that narrow the transactions you are looking for.",
      fromDate: "From date",
      toDate: "To date",
      minimumAmount: "Minimum amount",
      maximumAmount: "Maximum amount",
      allStatuses: "All statuses",
      allProducts: "All products",
      operationType: "Operation type",
      allOperations: "All operations",
      withdrawalFailed: "Could not save the withdrawal request.",
      searchDesign: "Search by design name",
      reset: "Reset",
      applyFilter: "Apply filters",
      withdrawAmount: "Withdrawal amount",
      payoutMethod: "Payout method",
      chooseMethod: "Choose a method",
      bankTransfer: "Bank transfer",
      electronicWallet: "Electronic wallet",
      amountError: "Enter an amount between 1 ₪ and " + availableLabel + " ₪.",
      methodError: "Choose a payout method.",
      notesOptional: "Notes (optional)",
      notesPlaceholder: "Any details that help us process your request",
      cancel: "Cancel",
      confirmRequest: "Confirm request",
      withdrawalSuccess: "Your earnings payout request was submitted successfully."
    }
  };

  Core.init({ dictionary: dictionary });

  const counters = document.querySelectorAll("[data-earnings-counter]");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const numberFormat = new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  function animateCounter(counter) {
    if (counter.dataset.counterAnimated === "true") return;
    counter.dataset.counterAnimated = "true";

    const target = Number(counter.dataset.earningsCounter);
    const render = function (value) { counter.textContent = numberFormat.format(value) + " ₪"; };

    if (reduceMotion || !Number.isFinite(target)) {
      render(target || 0);
      return;
    }

    const duration = 950;
    const startedAt = performance.now();
    render(0);

    function update(now) {
      const progress = Math.min((now - startedAt) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 4);
      render(target * eased);
      if (progress < 1) window.requestAnimationFrame(update);
    }

    window.requestAnimationFrame(update);
  }

  if (reduceMotion || !("IntersectionObserver" in window)) {
    counters.forEach(animateCounter);
  } else {
    const counterObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.45 });

    counters.forEach(function (counter) { counterObserver.observe(counter); });
  }

  const filterToggle = document.getElementById("filterToggle");
  const filterDialog = document.getElementById("filterDialog");
  const filters = document.getElementById("earningsFilters");
  const rows = Array.from(document.querySelectorAll("#earningsRows tr"));
  const empty = document.getElementById("earningsEmpty");

  if (filterToggle && filterDialog && filters) {
    filterToggle.addEventListener("click", function () {
      filterToggle.setAttribute("aria-expanded", "true");
      filterDialog.showModal();
      window.setTimeout(function () { filters.querySelector("input, select").focus(); }, 0);
    });

    filterDialog.querySelector("[data-filter-close]").addEventListener("click", function () { filterDialog.close(); });
    filterDialog.addEventListener("click", function (event) { if (event.target === filterDialog) filterDialog.close(); });
    filterDialog.addEventListener("close", function () {
      filterToggle.setAttribute("aria-expanded", "false");
      filterToggle.focus();
    });
  }

  function applyFilters() {
    const data = new FormData(filters);
    const from = data.get("dateFrom");
    const to = data.get("dateTo");
    const min = data.get("minAmount") === "" ? -Infinity : Number(data.get("minAmount"));
    const max = data.get("maxAmount") === "" ? Infinity : Number(data.get("maxAmount"));
    const status = String(data.get("status") || "");
    const product = String(data.get("product") || "");
    const locale = Core.getLanguage() === "en" ? "en" : "ar";
    const design = String(data.get("design") || "").trim().toLocaleLowerCase(locale);
    let visible = 0;
    rows.forEach(function (row) {
      const amount = Number(row.dataset.amount);
      const designCell = row.querySelector("td:nth-child(3)");
      const translatedDesign = designCell ? designCell.textContent.trim().toLocaleLowerCase(locale) : "";
      const match = (!from || row.dataset.date >= from) && (!to || row.dataset.date <= to) && amount >= min && amount <= max && (!status || row.dataset.status === status) && (!product || row.dataset.product === product) && (!design || translatedDesign.includes(design));
      row.hidden = !match;
      if (match) visible += 1;
    });
    empty.hidden = visible !== 0;
  }

  if (filters) {
    filters.addEventListener("submit", function (event) { event.preventDefault(); applyFilters(); filterDialog.close(); });
    filters.addEventListener("reset", function () { window.setTimeout(applyFilters, 0); });
  }

  Core.onLanguageChange(function () {
    Core.applyTheme(Core.currentTheme());
    if (filters) applyFilters();
  });

  const dialog = document.getElementById("withdrawDialog");
  const openDialog = document.getElementById("openWithdrawDialog");
  const withdrawForm = document.getElementById("withdrawForm");
  const amount = document.getElementById("withdrawAmount");
  const method = document.getElementById("payoutMethod");

  function closeDialog() { if (dialog && dialog.open) dialog.close(); }
  if (openDialog && dialog) openDialog.addEventListener("click", function () {
    openDialog.setAttribute("aria-expanded", "true");
    withdrawForm.querySelectorAll(".profile-field").forEach(function (field) { field.classList.remove("has-error"); });
    amount.removeAttribute("aria-invalid");
    method.removeAttribute("aria-invalid");
    dialog.showModal();
    window.setTimeout(function () { amount.focus(); }, 0);
  });
  document.querySelectorAll("[data-dialog-close]").forEach(function (button) { button.addEventListener("click", closeDialog); });
  if (dialog) dialog.addEventListener("click", function (event) { if (event.target === dialog) closeDialog(); });
  if (dialog) dialog.addEventListener("close", function () {
    if (openDialog) {
      openDialog.setAttribute("aria-expanded", "false");
      openDialog.focus();
    }
  });

  if (withdrawForm) {
    let submitting = false;

    withdrawForm.addEventListener("submit", async function (event) {
      event.preventDefault();
      if (submitting) return;

      const value = Number(amount.value);
      const amountValid = amount.value !== "" && value >= 1 && value <= availableBalance;
      const methodValid = Boolean(method.value);
      amount.closest(".profile-field").classList.toggle("has-error", !amountValid);
      method.closest(".profile-field").classList.toggle("has-error", !methodValid);
      amount.setAttribute("aria-invalid", String(!amountValid));
      method.setAttribute("aria-invalid", String(!methodValid));
      if (!amountValid) return amount.focus();
      if (!methodValid) return method.focus();

      const submitButton = withdrawForm.querySelector("[type=submit]");
      submitting = true;
      if (submitButton) submitButton.disabled = true;

      try {
        const response = await fetch(withdrawForm.dataset.withdrawUrl, {
          method: "POST",
          headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken ? csrfToken.content : ""
          },
          body: JSON.stringify({
            amount: amount.value,
            method: method.value,
            notes: document.getElementById("withdrawNotes")?.value || null
          })
        });

        if (!response.ok) {
          const error = await response.json().catch(function () { return {}; });
          const firstError = error.errors ? Object.values(error.errors)[0] : null;
          Core.toast((firstError && firstError[0]) || error.message || Core.translate("withdrawalFailed"), "error");
          return;
        }

        closeDialog();
        withdrawForm.reset();
        Core.toast(Core.translate("withdrawalSuccess"), "success");
        window.setTimeout(function () { window.location.reload(); }, 900);
      } catch (error) {
        Core.toast(Core.translate("withdrawalFailed"), "error");
      } finally {
        submitting = false;
        if (submitButton) submitButton.disabled = false;
      }
    });
  }
});
