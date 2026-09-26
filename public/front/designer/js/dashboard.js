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
    en: {
      documentTitle: "Dashboard | PalPrints",
      documentDescription: "PalPrints designer dashboard",
      sidebarLabel: "Designer sidebar",
      designerNavLabel: "Designer account links",
      goHome: "Go to the home page",
      openSidebar: "Open sidebar",
      closeSidebar: "Close sidebar",
      pageTools: "Page tools",
      switchToDark: "Switch to dark mode",
      switchToLight: "Switch to light mode",
      changeLanguage: "Change language",
      shoppingCart: "Shopping cart",
      twoItems: "Two items in the cart",
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
      designerIntroLabel: "Start a new design",
      heroEyebrow: "Your creative space",
      heroTitleLine1: "Turn your creative ideas into stunning designs",
      heroTitleLine2: "and start earning today!",
      heroSubtitle: "Create your next design and share it with customers looking for something distinctive.",
      ctaAction: "Start designing",
      dashboardSummary: "Dashboard summary",
      totalDesigns: "Total designs",
      designsThisMonth: "+6 this month",
      totalSales: "Total sales",
      salesThisMonth: "+12% this month",
      totalEarnings: "Total earnings",
      earningsThisMonth: "+1,200 ₪ this month",
      averageRating: "Average rating",
      ratingValue: "4.8 / 5",
      ratingsCount: "126 ratings",
      gazaTrends: "Gaza trends",
      trendsIntro: "Discover what is trending and turn popular ideas into eye-catching designs",
      viewMore: "View more",
      graduationAlt: "Gaza Graduation 2026",
      mostTrending: "Most trending",
      graduationSeason: "Graduation season",
      class2026: "Class of 2026",
      gazaGraduation: "Gaza Graduation 2026",
      graduationDesc: "Graduation backgrounds • Class of 2026 • Success",
      exploreTrend: "Explore the trend",
      seaAlt: "Gaza and the sea",
      weeklyGrowth: "+32% this week",
      calm: "Calm",
      boats: "Boats",
      sunset: "Sunset",
      gazaSea: "Gaza and the Sea",
      seaDesc: "Scenes and places • Natural landscapes",
      getInspired: "Get inspired",
      phrasesAlt: "Palestinian phrases",
      greatIdea: "Great idea",
      arabicCalligraphy: "Arabic calligraphy",
      inspiringWords: "Inspiring words",
      palestinianPhrases: "Palestinian Phrases",
      phrasesDesc: "On this land • Words and phrases",
      explore: "Explore",
      recentActivity: "Recent activity",
      activityTableLabel: "Recent activity table, horizontally scrollable",
      activity: "Activity",
      details: "Details",
      time: "Time",
      status: "Status",
      newDesignUploaded: "New design uploaded",
      newDesignDetails: "The design \"Palestinian Phrases — A Piece of Palestine\" was uploaded",
      twelveMinutesAgo: "12 minutes ago",
      completed: "Completed",
      printOrderCompleted: "Print order completed",
      printOrderDetails: "The design \"Bread in My Heart\" was sold",
      twentyFiveMinutesAgo: "25 minutes ago",
      newEarnings: "New earnings added",
      newEarningsDetails: "Earnings from the sale of 4 designs were added",
      thirtySixMinutesAgo: "36 minutes ago",
      processing: "Processing",
      designUnderReview: "Design under review",
      reviewDetails: "Your design received a 4-star rating",
      oneDayAgo: "1 day ago",
      underReview: "Under review",
      designRejected: "Design rejected",
      rejectedDetails: "Your design received a 2-star rating",
      fiveDaysAgo: "5 days ago",
      rejected: "Rejected"
    }
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
