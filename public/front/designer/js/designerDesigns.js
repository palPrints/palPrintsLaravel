"use strict";

/* =========================================================
   PalPrints — My Designs
   السلوك الخاص بصفحة تصاميمي فقط.
   الهيكل، القائمة، الهيدر، اللغة والثيم يوفرها masterDisgner.js.
   ========================================================= */

(function (window, document) {
  const dictionary = {
    ar: {
      documentTitle: "تصاميمي | PalPrints",
      documentDescription: "إدارة تصاميم المصمم ومتابعة حالتها في PalPrints",
      skipToContent: "تخطي إلى المحتوى",
      designsSubtitle: "إدارة تصاميمك ومتابعة حالتها من مكان واحد.",
      newDesign: "تصميم جديد",
      designTools: "البحث وتصفية التصاميم",
      statusTabs: "حالة التصميم",
      all: "الكل",
      published: "المنشورة",
      underReview: "قيد المراجعة",
      drafts: "المسودات",
      rejected: "المرفوضة",
      filter: "فلترة",
      sortBy: "ترتيب التصاميم",
      newest: "الأحدث أولًا",
      oldest: "الأقدم أولًا",
      byName: "حسب الاسم",
      searchLabel: "البحث في التصاميم",
      searchDesigns: "ابحث في تصاميمك...",
      noMatchesTitle: "لا توجد تصاميم مطابقة لبحثك",
      noMatches: "جرّب كلمة بحث أخرى أو امسح البحث الحالي.",
      clearSearch: "مسح البحث",
      showAll: "عرض الكل",
      publishedStatus: "منشور",
      reviewStatus: "قيد المراجعة",
      draftStatus: "مسودة",
      rejectedStatus: "مرفوض",
      lastEdited: "آخر تعديل",
      designDate: "تاريخ الرفع",
      unnamedDesign: "تصميم بدون اسم",
      editDesign: "تعديل التصميم",
      previewDesign: "معاينة التصميم",
      moreActions: "المزيد من الإجراءات",
      copyPreviewLink: "نسخ رابط المعاينة",
      linkCopied: "تم نسخ الرابط"
    },
    en: {
      documentTitle: "My Designs | PalPrints",
      documentDescription: "Manage designer work and track its status on PalPrints",
      skipToContent: "Skip to content",
      designsSubtitle: "Manage your designs and track their status in one place.",
      newDesign: "New design",
      designTools: "Search and filter designs",
      statusTabs: "Design status",
      all: "All",
      published: "Published",
      underReview: "Under review",
      drafts: "Drafts",
      rejected: "Rejected",
      filter: "Filter",
      sortBy: "Sort designs",
      newest: "Newest first",
      oldest: "Oldest first",
      byName: "By name",
      searchLabel: "Search designs",
      searchDesigns: "Search your designs...",
      noMatchesTitle: "No designs match your search",
      noMatches: "Try another search term or clear the current search.",
      clearSearch: "Clear search",
      showAll: "View all",
      publishedStatus: "Published",
      reviewStatus: "Under review",
      draftStatus: "Draft",
      rejectedStatus: "Rejected",
      lastEdited: "Last edited",
      designDate: "Uploaded",
      unnamedDesign: "Untitled design",
      editDesign: "Edit design",
      previewDesign: "Preview design",
      moreActions: "More actions",
      copyPreviewLink: "Copy preview link",
      linkCopied: "Link copied"
    }
  };

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

  let designs = [];
  let activeStatus = "all";
  let query = "";
  let sortMode = "newest";

  const elements = {};

  function each(list, callback) {
    Array.prototype.forEach.call(list || [], callback);
  }

  function getLanguage() {
    return document.documentElement.getAttribute("lang") === "en"
      ? "en"
      : "ar";
  }

  function translate(key) {
    const language = getLanguage();
    const table = dictionary[language] || dictionary.ar;

    if (Object.prototype.hasOwnProperty.call(table, key)) {
      return table[key];
    }

    return Object.prototype.hasOwnProperty.call(dictionary.ar, key)
      ? dictionary.ar[key]
      : "";
  }

  function applyPageTranslations() {
    each(document.querySelectorAll("[data-i18n]"), function (element) {
      const value = translate(element.getAttribute("data-i18n"));

      if (value) {
        element.textContent = value;
      }
    });

    each(
      document.querySelectorAll("[data-i18n-placeholder]"),
      function (element) {
        const value = translate(
          element.getAttribute("data-i18n-placeholder")
        );

        if (value) {
          element.setAttribute("placeholder", value);
        }
      }
    );

    each(document.querySelectorAll("[data-i18n-aria]"), function (element) {
      const value = translate(element.getAttribute("data-i18n-aria"));

      if (value) {
        element.setAttribute("aria-label", value);
      }
    });

    document.title = translate("documentTitle");

    const description = document.querySelector(
      'meta[name="description"]'
    );

    if (description) {
      description.setAttribute(
        "content",
        translate("documentDescription")
      );
    }
  }

  function escapeHtml(value) {
    const node = document.createElement("div");
    node.textContent = String(value || "");
    return node.innerHTML;
  }

  function normalizeStatus(value) {
    const status = String(value || "draft").toLowerCase();

    if (status === "published" || status === "approved") {
      return "published";
    }

    if (
      status === "review" ||
      status === "pending" ||
      status === "under_review"
    ) {
      return "review";
    }

    if (status === "rejected" || status === "declined") {
      return "rejected";
    }

    return "draft";
  }

  function normalizeDesign(item, index) {
    const product = item.product || {};

    return {
      id: item.id || item.designId || "design-" + index,
      title: item.title || item.name || "",
      productType:
        item.productType ||
        item.productName ||
        product.name ||
        item.category ||
        "",
      status: normalizeStatus(item.status || item.designStatus),
      image:
        item.image ||
        item.previewImage ||
        item.thumbnail ||
        window.palPrintsDesignerFallbackImage || "",
      date: item.updatedAt || item.date || item.createdAt || "",
      previewUrl: item.previewUrl || "",
      editorUrl: item.editorUrl || ""
    };
  }

  function statusMeta(status) {
    if (status === "published") {
      return {
        label: translate("publishedStatus"),
        className: "completed",
        icon: "bi-check-circle-fill"
      };
    }

    if (status === "review") {
      return {
        label: translate("reviewStatus"),
        className: "review",
        icon: "bi-clock"
      };
    }

    if (status === "rejected") {
      return {
        label: translate("rejectedStatus"),
        className: "rejected",
        icon: "bi-x-circle-fill"
      };
    }

    return {
      label: translate("draftStatus"),
      className: "processing",
      icon: "bi-pencil"
    };
  }

  function statusTitle(status) {
    if (status === "published") {
      return translate("published");
    }

    if (status === "review") {
      return translate("underReview");
    }

    if (status === "rejected") {
      return translate("rejected");
    }

    return translate("drafts");
  }

  function formatDate(value) {
    const parsed = new Date(value);

    if (!value || Number.isNaN(parsed.getTime())) {
      return "—";
    }

    return new Intl.DateTimeFormat(
      getLanguage() === "en" ? "en-GB" : "ar-PS",
      {
        day: "numeric",
        month: "long",
        year: "numeric"
      }
    ).format(parsed);
  }

  function previewUrl(item) {
    return (
      item.previewUrl ||
      window.palPrintsDesignerRoutes.review +
        "?id=" +
        encodeURIComponent(item.id)
    );
  }

  function editorUrl(item) {
    return (
      item.editorUrl ||
      window.palPrintsDesignerRoutes.editor +
        "?designId=" +
        encodeURIComponent(item.id)
    );
  }

  function primaryAction(item) {
    const editable =
      item.status === "draft" || item.status === "rejected";

    return {
      action: editable ? "edit" : "preview",
      label: translate(
        editable ? "editDesign" : "previewDesign"
      ),
      icon: editable ? "bi-pencil" : "bi-eye",
      variant: editable ? "tertiary" : "trend"
    };
  }

  function designCard(item) {
    const status = statusMeta(item.status);
    const action = primaryAction(item);
    const title = item.title || translate("unnamedDesign");
    const product = item.productType
      ? '<span class="tag">' +
        escapeHtml(item.productType) +
        "</span>"
      : "";
    const dateLabel =
      item.status === "draft"
        ? translate("lastEdited")
        : translate("designDate");
    const menuId =
      "designMenu-" +
      String(item.id).replace(/[^a-zA-Z0-9_-]/g, "-");

    return [
      '<article class="trend-card design-card" tabindex="0"',
      ' data-design-id="' + escapeHtml(item.id) + '"',
      ' aria-label="' + escapeHtml(title) + '">',
      '<div class="card-image">',
      '<img src="' + escapeHtml(item.image) + '"',
      ' alt="' + escapeHtml(title) + '" loading="lazy">',
      '<span class="status-badge design-status ' +
        status.className + '">',
      '<i class="bi ' + status.icon + '" aria-hidden="true"></i>',
      escapeHtml(status.label),
      "</span>",
      "</div>",
      '<div class="card-body">',
      '<h3 class="card-title">' + escapeHtml(title) + "</h3>",
      '<div class="design-card-meta">',
      product,
      '<p class="card-desc"><i class="bi bi-calendar3"',
      ' aria-hidden="true"></i>' + escapeHtml(dateLabel),
      ": " + escapeHtml(formatDate(item.date)) + "</p>",
      "</div>",
      '<div class="design-card-actions">',
      '<button type="button" class="card-btn ' +
        action.variant + '" data-design-action="' +
        action.action + '">',
      '<i class="bi ' + action.icon + '" aria-hidden="true"></i>',
      "<span>" + escapeHtml(action.label) + "</span>",
      "</button>",
      "</div>",
      "</div>",
      "</article>"
    ].join("");
  }

  function sortDesigns(list) {
    return list.slice().sort(function (first, second) {
      if (sortMode === "name") {
        return (first.title || "").localeCompare(
          second.title || "",
          getLanguage()
        );
      }

      const firstDate = Date.parse(first.date);
      const secondDate = Date.parse(second.date);
      const firstValue = Number.isNaN(firstDate)
        ? Number(first.id) || 0
        : firstDate;
      const secondValue = Number.isNaN(secondDate)
        ? Number(second.id) || 0
        : secondDate;

      return sortMode === "oldest"
        ? firstValue - secondValue
        : secondValue - firstValue;
    });
  }

  function filteredDesigns() {
    return sortDesigns(
      designs.filter(function (item) {
        const matchesStatus =
          activeStatus === "all" ||
          item.status === activeStatus;
        const searchable = [
          item.title,
          item.productType
        ].join(" ").toLocaleLowerCase(getLanguage());

        return (
          matchesStatus &&
          searchable.includes(
            query.toLocaleLowerCase(getLanguage())
          )
        );
      })
    );
  }

  function revealCards() {
    const cards = document.querySelectorAll(
      "#designGroups .trend-card"
    );

    if (reducedMotion.matches) {
      each(cards, function (card) {
        card.classList.add("visible");
      });
      return;
    }

    each(cards, function (card, index) {
      window.setTimeout(function () {
        card.classList.add("visible");
      }, index * 90);
    });
  }

  function render() {
    const list = filteredDesigns();
    const hasAnyDesigns = designs.length > 0;

    if (elements.trueEmpty) elements.trueEmpty.hidden = hasAnyDesigns;
    elements.empty.hidden = !hasAnyDesigns || list.length > 0;
    elements.groups.hidden = list.length === 0;

    if (!list.length) {
      elements.groups.innerHTML = "";
      return;
    }

    const statuses =
      activeStatus === "all"
        ? ["draft", "review", "published", "rejected"]
        : [activeStatus];
    const arrow =
      document.documentElement.dir === "ltr"
        ? "bi-arrow-right"
        : "bi-arrow-left";

    elements.groups.innerHTML = statuses
      .map(function (status) {
        const items = list.filter(function (item) {
          return item.status === status;
        });

        if (!items.length) {
          return "";
        }

        const visibleItems =
          activeStatus === "all" ? items.slice(0, 3) : items;

        return [
          '<section class="design-group" data-group="' +
            status + '">',
          '<header class="design-group-header">',
          '<h2 class="design-group-title">',
          '<span class="tab-dot ' + status +
            '" aria-hidden="true"></span>',
          escapeHtml(statusTitle(status)) +
            " (" + items.length + ")",
          "</h2>",
          activeStatus === "all"
            ? '<button type="button" class="view-more-btn design-group-link"' +
              ' data-show-status="' + status + '">' +
              escapeHtml(translate("showAll")) +
              ' <i class="bi ' + arrow +
              '" aria-hidden="true"></i></button>'
            : "",
          "</header>",
          '<div class="trending-grid design-grid">',
          visibleItems.map(designCard).join(""),
          "</div>",
          "</section>"
        ].join("");
      })
      .join("");

    revealCards();
  }

  function updateCounts() {
    ["all", "draft", "review", "published", "rejected"]
      .forEach(function (status) {
        const target = document.querySelector(
          '[data-count="' + status + '"]'
        );

        if (!target) {
          return;
        }

        target.textContent =
          status === "all"
            ? designs.length
            : designs.filter(function (item) {
                return item.status === status;
              }).length;
      });
  }

  function setTab(status) {
    activeStatus = status;

    each(document.querySelectorAll(".design-tab"), function (button) {
      const selected = button.getAttribute("data-status") === status;
      button.classList.toggle("is-active", selected);
      button.setAttribute("aria-selected", selected ? "true" : "false");
      button.setAttribute("tabindex", selected ? "0" : "-1");
    });

    closeMenus();
    render();
  }

  function toggleFilter(force) {
    const open =
      typeof force === "boolean"
        ? force
        : elements.filterMenu.hidden;

    elements.filterMenu.hidden = !open;
    elements.filterButton.setAttribute(
      "aria-expanded",
      open ? "true" : "false"
    );
  }

  function closeCardMenus(except) {
    each(document.querySelectorAll(".design-more-menu"), function (menu) {
      if (menu === except) {
        return;
      }

      menu.hidden = true;

      const button = menu.parentElement.querySelector(
        "[data-more-toggle]"
      );

      if (button) {
        button.setAttribute("aria-expanded", "false");
      }
    });
  }

  function closeMenus() {
    toggleFilter(false);
    closeCardMenus();
  }

  function toggleCardMenu(button) {
    const menu = document.getElementById(
      button.getAttribute("aria-controls")
    );

    if (!menu) {
      return;
    }

    const open = menu.hidden;
    closeCardMenus(menu);
    toggleFilter(false);
    menu.hidden = !open;
    button.setAttribute("aria-expanded", open ? "true" : "false");
  }

  function setSort(mode) {
    sortMode = mode;

    each(document.querySelectorAll(".filter-option"), function (button) {
      button.classList.toggle(
        "is-active",
        button.getAttribute("data-sort") === mode
      );
    });

    toggleFilter(false);
    render();
  }

  function findDesign(card) {
    if (!card) {
      return null;
    }

    const id = card.getAttribute("data-design-id");

    return designs.find(function (item) {
      return String(item.id) === id;
    }) || null;
  }

  function navigateTo(item, action) {
    if (!item) {
      return;
    }

    if (action === "edit") {
      window.location.href = editorUrl(item);
      return;
    }

    window.location.href = previewUrl(item);
  }

  function copyPreviewLink(item, button) {
    const url = new URL(previewUrl(item), window.location.href).href;

    if (!navigator.clipboard || !navigator.clipboard.writeText) {
      return;
    }

    navigator.clipboard.writeText(url).then(function () {
      const label = button.querySelector("span");

      if (!label) {
        return;
      }

      const original = label.textContent;
      label.textContent = translate("linkCopied");

      window.setTimeout(function () {
        label.textContent = original;
      }, 1400);
    });
  }

  function handleDesignAction(button) {
    const card = button.closest(".design-card");
    const item = findDesign(card);
    const action = button.getAttribute("data-design-action");

    if (!item) {
      return;
    }

    if (action === "copy") {
      copyPreviewLink(item, button);
      closeCardMenus();
      return;
    }

    navigateTo(item, action);
  }

  function setupTabs() {
    const tabs = Array.from(
      document.querySelectorAll(".design-tab")
    );

    each(tabs, function (button) {
      button.addEventListener("click", function () {
        setTab(button.getAttribute("data-status"));
      });

      button.addEventListener("keydown", function (event) {
        if (
          event.key !== "ArrowLeft" &&
          event.key !== "ArrowRight"
        ) {
          return;
        }

        event.preventDefault();

        const direction =
          event.key === "ArrowLeft" ? -1 : 1;
        const index = tabs.indexOf(button);
        const next =
          tabs[(index + direction + tabs.length) % tabs.length];

        next.focus();
        setTab(next.getAttribute("data-status"));
      });
    });
  }

  function setupEvents() {
    setupTabs();

    if (elements.search) {
      elements.search.addEventListener("input", function () {
        query = elements.search.value.trim();
        render();
      });
    }

    elements.clearSearch.addEventListener("click", function () {
      query = "";
      if (elements.search) {
        elements.search.value = "";
        elements.search.focus();
      }
      render();
    });

    elements.filterButton.addEventListener("click", function (event) {
      event.stopPropagation();
      toggleFilter();
    });

    elements.filterMenu.addEventListener("click", function (event) {
      const option = event.target.closest("[data-sort]");

      if (option) {
        setSort(option.getAttribute("data-sort"));
      }
    });

    elements.groups.addEventListener("click", function (event) {
      const showAll = event.target.closest("[data-show-status]");

      if (showAll) {
        const status = showAll.getAttribute("data-show-status");
        setTab(status);

        const tab = document.querySelector(
          '.design-tab[data-status="' + status + '"]'
        );

        if (tab) {
          tab.focus();
        }

        document.querySelector(".designs-toolbar").scrollIntoView({
          behavior: reducedMotion.matches ? "auto" : "smooth",
          block: "start"
        });
        return;
      }

      const moreButton = event.target.closest("[data-more-toggle]");

      if (moreButton) {
        event.stopPropagation();
        toggleCardMenu(moreButton);
        return;
      }

      const action = event.target.closest("[data-design-action]");

      if (action) {
        event.stopPropagation();
        handleDesignAction(action);
        return;
      }

      const card = event.target.closest(".design-card");

      if (card) {
        const item = findDesign(card);
        const actionName =
          item &&
          (item.status === "draft" || item.status === "rejected")
            ? "edit"
            : "preview";

        navigateTo(item, actionName);
      }
    });

    elements.groups.addEventListener("keydown", function (event) {
      if (
        (event.key === "Enter" || event.key === " ") &&
        event.target.matches(".design-card")
      ) {
        event.preventDefault();

        const item = findDesign(event.target);
        const action =
          item &&
          (item.status === "draft" || item.status === "rejected")
            ? "edit"
            : "preview";

        navigateTo(item, action);
      }
    });

    document.addEventListener("click", function (event) {
      if (!event.target.closest(".filter-control")) {
        toggleFilter(false);
      }

      if (!event.target.closest(".design-more-control")) {
        closeCardMenus();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") {
        return;
      }

      const filterWasOpen =
        elements.filterButton.getAttribute("aria-expanded") === "true";

      closeMenus();

      if (filterWasOpen) {
        elements.filterButton.focus();
      }
    });

    const languageButton = document.getElementById(
      "languageToggleButton"
    );

    if (languageButton) {
      languageButton.addEventListener("click", function () {
        window.requestAnimationFrame(function () {
          applyPageTranslations();
          render();
        });
      });
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    elements.groups = document.getElementById("designGroups");
    elements.empty = document.getElementById("designsEmpty");
    elements.trueEmpty = document.getElementById("designsTrueEmpty");
    elements.clearSearch = document.getElementById(
      "clearSearchButton"
    );
    elements.search = document.getElementById("designSearch");
    elements.filterButton = document.getElementById("filterButton");
    elements.filterMenu = document.getElementById("filterMenu");

    applyPageTranslations();
    setupEvents();
    designs = (window.palPrintsDesignerDesigns || []).map(normalizeDesign);

    /* The top-bar search sends ?q= to this page. */
    const initialQuery = new URLSearchParams(window.location.search).get("q");

    if (initialQuery) {
      query = initialQuery.trim();
      if (elements.search) elements.search.value = query;
    }

    updateCounts();
    render();
  });
})(window, document);
