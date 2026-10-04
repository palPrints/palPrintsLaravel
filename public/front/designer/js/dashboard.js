"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const Core = window.PalProfile;

  if (!Core) return;

  const dictionary = {
    ar: {
      documentTitle: "لوحة التحكم | PalPrints",
      documentDescription: "لوحة تحكم مصمم PalPrints",
      sidebarLabel: "القائمة الجانبية للمصمم",
      designerNavLabel: "روابط حساب المصمم",
      goHome: "الصفحة الرئيسية",
      openSidebar: "فتح القائمة الجانبية",
      closeSidebar: "إغلاق القائمة الجانبية",
      pageTools: "أدوات الصفحة",
      switchToDark: "تفعيل الوضع الليلي",
      switchToLight: "تفعيل الوضع النهاري",
      changeLanguage: "تغيير اللغة",
      shoppingCart: "سلة المشتريات",
      twoItems: "عنصران في السلة",
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
      designerIntroLabel: "ابدأ تصميمًا جديدًا",
      heroEyebrow: "مساحتك للإبداع",
      heroTitleLine1: "حوّل أفكارك المبتكرة إلى تصاميم مذهلة",
      heroTitleLine2: "وابدأ الربح الآن!",
      heroSubtitle: "أنشئ تصميمك القادم وشاركه مع عملاء يبحثون عن أفكار مميزة.",
      ctaAction: "ابدأ التصميم",
      dashboardSummary: "ملخص لوحة التحكم",
      totalDesigns: "إجمالي التصاميم",
      designsThisMonth: "+6 هذا الشهر",
      totalSales: "إجمالي المبيعات",
      salesThisMonth: "+12% هذا الشهر",
      totalEarnings: "إجمالي الأرباح",
      earningsThisMonth: "+1,200 ₪ هذا الشهر",
      averageRating: "متوسط التقييم",
      ratingValue: "4.8 / 5",
      ratingsCount: "126 تقييم",
      gazaTrends: "ترند غزة",
      trendsIntro: "اكتشف الأفكار الرائجة الآن وحوّلها إلى تصاميم تلفت الانتباه",
      viewMore: "عرض المزيد",
      graduationAlt: "تخرج غزة 2026",
      mostTrending: "الأكثر رواجاً",
      graduationSeason: "موسم التخرج",
      class2026: "دفعة 2026",
      gazaGraduation: "تخرج غزة 2026",
      graduationDesc: "خلفيات تخرج • دفعة 2026 • النجاح",
      exploreTrend: "استلهم من الترند",
      seaAlt: "غزة والبحر",
      weeklyGrowth: "+32% هذا الأسبوع",
      calm: "الهدوء",
      boats: "القوارب",
      sunset: "الغروب",
      gazaSea: "غزة والبحر",
      seaDesc: "مشاهد وأماكن • مناظر طبيعية",
      getInspired: "استلهم فكرة",
      phrasesAlt: "عبارات فلسطينية",
      greatIdea: "فكرة رائعة",
      arabicCalligraphy: "خط عربي",
      inspiringWords: "كلمات ملهمة",
      palestinianPhrases: "عبارات فلسطينية",
      phrasesDesc: "من على هذه الأرض • كلمات وعبارات",
      explore: "استكشف",
      recentActivity: "آخر النشاطات",
      activityTableLabel: "جدول آخر النشاطات، قابل للتمرير أفقياً",
      activity: "النشاط",
      details: "التفاصيل",
      time: "الوقت",
      status: "الحالة",
      newDesignUploaded: "تم رفع تصميم جديد",
      newDesignDetails: "تم تحميل تصميم \"عبارات فلسطينية - جزء من فلسطين\"",
      twelveMinutesAgo: "منذ 12 دقيقة",
      completed: "مكتمل",
      printOrderCompleted: "اكتمال طلب طباعة",
      printOrderDetails: "تم بيع تصميم \"خبز في قلبي\"",
      twentyFiveMinutesAgo: "منذ 25 دقيقة",
      newEarnings: "إضافة أرباح جديدة",
      newEarningsDetails: "تمت إضافة أرباح جديدة من بيع 4 تصاميم",
      thirtySixMinutesAgo: "منذ 36 دقيقة",
      processing: "قيد الإجراء",
      designUnderReview: "تصميم قيد المراجعة",
      reviewDetails: "حصل تصميمك على تقييم 4 نجوم",
      oneDayAgo: "منذ يوم",
      underReview: "قيد المراجعة",
      designRejected: "تصميم مرفوض",
      rejectedDetails: "حصل تصميمك على تقييم نجمتين",
      fiveDaysAgo: "منذ 5 أيام",
      rejected: "مرفوض"
    },
  };

  Core.init({ dictionary: dictionary });

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

  /* Elements whose text comes straight from the server in both languages. */
  function applyServerCopy() {
    const attribute = Core.getLanguage() === "ar" ? "data-copy-ar" : "data-copy-en";

    document.querySelectorAll("[data-copy-ar][data-copy-en]").forEach(function (element) {
      const value = element.getAttribute(attribute);
      if (value !== null) element.textContent = value;
    });
  }

  applyServerCopy();
  Core.onLanguageChange(applyServerCopy);

  /* Animated counters */
  const counters = document.querySelectorAll(".counter");

  function format(value) {
    return new Intl.NumberFormat(Core.getLanguage() === "ar" ? "ar-u-nu-latn" : "en-US").format(value);
  }

  function animateCounter(counter) {
    const target = Number(counter.getAttribute("data-target"));
    if (!Number.isFinite(target)) return;

    if (reducedMotion.matches) {
      counter.textContent = format(target);
      return;
    }

    const startedAt = performance.now();
    const duration = 1200;

    function update(now) {
      const progress = Math.min((now - startedAt) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      counter.textContent = format(Math.floor(eased * target));
      if (progress < 1) window.requestAnimationFrame(update);
    }

    window.requestAnimationFrame(update);
  }

  counters.forEach(animateCounter);
  Core.onLanguageChange(function () {
    counters.forEach(function (counter) {
      counter.textContent = format(Number(counter.getAttribute("data-target")) || 0);
    });
  });

  /* Sales/profit performance chart period switch */
  (function setupChartPeriods() {
    const buttons = Array.from(document.querySelectorAll("[data-chart-period]"));
    const salesLine = document.querySelector(".chart-line.is-sales");
    const profitLine = document.querySelector(".chart-line.is-profit");
    const salesArea = document.querySelector(".chart-area");
    const salesMarker = document.getElementById("salesChartMarker");
    const caption = document.querySelector(".performance-panel .dashboard-panel-head p");
    let markerFrame = 0;
    const dataNode = document.getElementById("designerChartData");
    let chartData = {};
    try {
      chartData = dataNode ? JSON.parse(dataNode.textContent) : {};
    } catch (error) {
      chartData = {};
    }
    const yAxis = document.getElementById("chartYAxis");
    const monthsRow = document.getElementById("chartMonths");
    const CHART = { left: 10, right: 710, top: 14, bottom: 218 };

    function niceMax(value) {
      if (value <= 0) return 100;
      const magnitude = Math.pow(10, Math.floor(Math.log10(value)));
      const step = [1, 2, 2.5, 5, 10].find(function (n) { return value <= n * magnitude; });
      return step * magnitude;
    }

    function formatTick(value) {
      if (value >= 1000) return (Math.round(value / 100) / 10).toString().replace(/\.0$/, "") + "K";
      return String(Math.round(value * 10) / 10);
    }

    /* Smooth line through the points (Catmull-Rom converted to cubic Beziers). */
    function smoothPath(points) {
      if (!points.length) return "";
      if (points.length === 1) return "M" + points[0][0] + " " + points[0][1];
      let d = "M" + points[0][0].toFixed(1) + " " + points[0][1].toFixed(1);
      for (let i = 0; i < points.length - 1; i += 1) {
        const p0 = points[i - 1] || points[i];
        const p1 = points[i];
        const p2 = points[i + 1];
        const p3 = points[i + 2] || p2;
        const c1y = Math.min(CHART.bottom, Math.max(CHART.top, p1[1] + (p2[1] - p0[1]) / 6));
        const c2y = Math.min(CHART.bottom, Math.max(CHART.top, p2[1] - (p3[1] - p1[1]) / 6));
        d += " C" + (p1[0] + (p2[0] - p0[0]) / 6).toFixed(1) + " " + c1y.toFixed(1)
          + "," + (p2[0] - (p3[0] - p1[0]) / 6).toFixed(1) + " " + c2y.toFixed(1)
          + "," + p2[0].toFixed(1) + " " + p2[1].toFixed(1);
      }
      return d;
    }

    function toPoints(values, max) {
      const count = values.length;
      return values.map(function (value, index) {
        const x = count === 1 ? (CHART.left + CHART.right) / 2 : CHART.left + (CHART.right - CHART.left) * index / (count - 1);
        const y = CHART.bottom - (CHART.bottom - CHART.top) * (max ? value / max : 0);
        return [x, y];
      });
    }

    function buildSeries(key) {
      const source = chartData[key] || chartData.month || { labels: [], sales: [], profit: [], label: "" };
      const max = niceMax(Math.max.apply(null, source.sales.concat(source.profit, [0])));
      const sales = smoothPath(toPoints(source.sales, max));
      const profit = smoothPath(toPoints(source.profit, max));
      const last = toPoints(source.sales, max).slice(-1)[0] || [CHART.left, CHART.bottom];
      return { sales: sales, profit: profit, max: max, labels: source.labels, label: source.label, last: last };
    }

    function renderAxes(selected) {
      if (yAxis) {
        yAxis.innerHTML = [1, 0.75, 0.5, 0.25, 0].map(function (ratio) {
          return "<span>" + formatTick(selected.max * ratio) + "</span>";
        }).join("");
      }
      if (monthsRow) {
        monthsRow.style.gridTemplateColumns = "repeat(" + Math.max(selected.labels.length, 1) + ", 1fr)";
        monthsRow.innerHTML = selected.labels.map(function (label) {
          return "<span>" + label + "</span>";
        }).join("");
      }
    }

    function renderChart(key) {
      const selected = buildSeries(key);
      if (salesLine) salesLine.setAttribute("d", selected.sales);
      if (profitLine) profitLine.setAttribute("d", selected.profit);
      if (salesArea) salesArea.setAttribute("d", selected.sales + " L" + CHART.right + " " + CHART.bottom + " L" + CHART.left + " " + CHART.bottom + " Z");
      if (caption) caption.textContent = selected.label;
      renderAxes(selected);
    }

    renderChart("month");

    function animateChart() {
      if (!salesLine || !profitLine) return;
      [salesLine, profitLine, salesArea].forEach(function (element) {
        if (element) element.classList.remove("is-drawing");
      });

      void salesLine.getBoundingClientRect();
      [salesLine, profitLine, salesArea].forEach(function (element) {
        if (element) element.classList.add("is-drawing");
      });

      if (!salesMarker) return;
      window.cancelAnimationFrame(markerFrame);
      salesMarker.classList.remove("is-resting");
      salesMarker.classList.add("is-active");
      const pathLength = salesLine.getTotalLength();
      const duration = reducedMotion.matches ? 0 : 1550;
      const startedAt = performance.now();

      function moveMarker(now) {
        const progress = duration ? Math.min((now - startedAt) / duration, 1) : 1;
        const eased = 1 - Math.pow(1 - progress, 3);
        const point = salesLine.getPointAtLength(pathLength * eased);
        salesMarker.setAttribute("cx", point.x.toFixed(2));
        salesMarker.setAttribute("cy", point.y.toFixed(2));
        if (progress < 1) markerFrame = window.requestAnimationFrame(moveMarker);
        else {
          salesMarker.classList.remove("is-active");
          salesMarker.classList.add("is-resting");
        }
      }
      markerFrame = window.requestAnimationFrame(moveMarker);
    }

    if (!buttons.length) return;

    buttons.forEach(function (button) {
      button.addEventListener("click", function () {
        buttons.forEach(function (item) {
          item.classList.toggle("active", item === button);
          item.setAttribute("aria-pressed", item === button ? "true" : "false");
        });
        renderChart(button.getAttribute("data-chart-period"));
        animateChart();
      });
      button.setAttribute("aria-pressed", button.classList.contains("active") ? "true" : "false");
    });

    const chart = document.querySelector(".designer-performance-chart");
    if (chart && !reducedMotion.matches && "IntersectionObserver" in window) {
      const chartObserver = new IntersectionObserver(function (entries) {
        if (!entries.some(function (entry) { return entry.isIntersecting; })) return;
        animateChart();
        chartObserver.disconnect();
      }, { threshold: 0.35 });
      chartObserver.observe(chart);
    } else {
      animateChart();
    }
  })();

  /* Trend cards fade in when they scroll into view */
  const cards = document.querySelectorAll(".trend-card");

  if (reducedMotion.matches || !("IntersectionObserver" in window)) {
    cards.forEach(function (card) { card.classList.add("visible"); });
    return;
  }

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry, index) {
      if (!entry.isIntersecting) return;
      window.setTimeout(function () { entry.target.classList.add("visible"); }, index * 130);
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.12 });

  cards.forEach(function (card) { observer.observe(card); });
});
