/* Admin reports & statistics page: report tabs, period switcher, animated charts and export. */
(function (window, document) {
  "use strict";

  function initialize() {
    const exportReportButton = document.getElementById("exportReportButton");
    const exportFormatMenu = document.getElementById("exportFormatMenu");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    const periodLabels = {
      week: "هذا الأسبوع",
      month: "هذا الشهر",
      quarter: "آخر ٣ أشهر",
      year: "هذه السنة"
    };
    const userStatsScript = document.getElementById("reportsUserStats");
    const userStatsByPeriod = userStatsScript ? JSON.parse(userStatsScript.textContent || "{}") : {};
    const unavailableTitles = {
      sales: ["إجمالي الإيرادات", "إجمالي الطلبات", "متوسط قيمة الطلب", "أعلى مبيعًا"],
      orders: ["الطلبات المكتملة", "قيد التنفيذ", "الطلبات الملغاة", "معدل الإكمال"]
    };
    unavailableTitles.profits = ["صافي الأرباح", "إجمالي العمولات", "دفعات المصممين", "هامش الربح"];

    function userCardItems(period) {
      const s = userStatsByPeriod[period] || {};
      return [
        { title: "إجمالي المستخدمين", value: s.total || 0, change: s.totalChange },
        { title: "مستخدمون جدد", value: s.newUsers || 0, suffix: " مستخدم", change: s.newUsersChange },
        { title: "المستخدمون النشطون", value: s.activeUsers || 0, change: s.activeUsersChange },
        { title: "معدل النمو", value: s.growthRate || 0, suffix: "%", change: null }
      ];
    }

    let activePeriod = "month";
    let activeReport = "users";
    let chartAnimationRun = 0;
    let breakdownAnimationRun = 0;

    function toast(message) {
      if (window.PalAdmin) window.PalAdmin.toast(message);
    }

    function formatStat(value, item) {
      return (item.prefix || "") + new Intl.NumberFormat("en-US").format(value) + (item.suffix || "");
    }

    function animateCounter(element, target, item) {
      if (reducedMotion.matches) {
        element.textContent = formatStat(target, item);
        return;
      }
      const start = window.performance.now();
      const duration = 700;
      function update(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        element.textContent = formatStat(Math.round(target * eased), item);
        if (progress < 1) window.requestAnimationFrame(update);
      }
      window.requestAnimationFrame(update);
    }

    function animateMiniCharts() {
      if (reducedMotion.matches) return;
      const cards = document.querySelectorAll("[data-stat-card]");
      cards.forEach(function (card) {
        card.classList.remove("is-chart-animating");
      });
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(function () {
          cards.forEach(function (card) {
            card.classList.add("is-chart-animating");
          });
        });
      });
    }

    function animateChartCounters() {
      const chartGrid = document.querySelector(".reports-charts-grid");
      if (!chartGrid) return;
      const productCounters = Array.from(chartGrid.querySelectorAll(".product-sales-row strong"));
      const monthlyCounters = Array.from(chartGrid.querySelectorAll(".monthly-bar b")).reverse();
      const counters = productCounters.map(function (counter, index) {
        return { counter: counter, delayIndex: index };
      }).concat(monthlyCounters.map(function (counter, index) {
        return { counter: counter, delayIndex: index };
      }));
      const run = ++chartAnimationRun;
      chartGrid.classList.remove("is-data-animating");

      counters.forEach(function (entry) {
        const counter = entry.counter;
        const delayIndex = entry.delayIndex;
        if (!counter.dataset.chartTarget) counter.dataset.chartTarget = counter.textContent.replace(/[^0-9.-]/g, "");
        const target = Number(counter.dataset.chartTarget);
        const chartItem = counter.closest(".monthly-bar, .product-sales-row");
        if (chartItem) chartItem.style.setProperty("--chart-delay", String(delayIndex * 55) + "ms");
        if (reducedMotion.matches) {
          counter.textContent = new Intl.NumberFormat("en-US").format(target);
          return;
        }
        counter.textContent = "0";
        const delay = delayIndex * 55;
        window.setTimeout(function () {
          if (run !== chartAnimationRun) return;
          const start = window.performance.now();
          function update(now) {
            if (run !== chartAnimationRun) return;
            const progress = Math.min((now - start) / 780, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            counter.textContent = new Intl.NumberFormat("en-US").format(Math.round(target * eased));
            if (progress < 1) window.requestAnimationFrame(update);
          }
          window.requestAnimationFrame(update);
        }, delay);
      });

      if (!reducedMotion.matches) {
        window.requestAnimationFrame(function () {
          window.requestAnimationFrame(function () {
            if (run === chartAnimationRun) chartGrid.classList.add("is-data-animating");
          });
        });
      }
    }

    function animateBreakdownCharts() {
      const breakdownGrid = document.querySelector(".reports-breakdown-grid");
      if (!breakdownGrid) return;
      const counters = Array.from(breakdownGrid.querySelectorAll(".city-sales-row strong bdi, .revenue-legend strong bdi, .revenue-donut strong"));
      const run = ++breakdownAnimationRun;
      breakdownGrid.classList.remove("is-breakdown-animating");

      breakdownGrid.querySelectorAll(".city-sales-row").forEach(function (row, index) {
        row.style.setProperty("--breakdown-delay", String(index * 80) + "ms");
      });
      breakdownGrid.querySelectorAll(".revenue-legend li").forEach(function (item, index) {
        item.style.setProperty("--breakdown-delay", String(index * 70 + 180) + "ms");
      });

      counters.forEach(function (counter, index) {
        if (!counter.dataset.breakdownTarget) counter.dataset.breakdownTarget = counter.textContent.replace(/[^0-9.-]/g, "");
        const target = Number(counter.dataset.breakdownTarget);
        const hasCurrency = counter.closest(".city-sales-row, .revenue-legend");
        if (reducedMotion.matches) {
          counter.textContent = (hasCurrency ? "₪ " : "") + new Intl.NumberFormat("en-US").format(target);
          return;
        }
        counter.textContent = hasCurrency ? "₪ 0" : "0";
        window.setTimeout(function () {
          if (run !== breakdownAnimationRun) return;
          const start = window.performance.now();
          function update(now) {
            if (run !== breakdownAnimationRun) return;
            const progress = Math.min((now - start) / 800, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            counter.textContent = (hasCurrency ? "₪ " : "") + new Intl.NumberFormat("en-US").format(Math.round(target * eased));
            if (progress < 1) window.requestAnimationFrame(update);
          }
          window.requestAnimationFrame(update);
        }, index * 45);
      });

      if (!reducedMotion.matches) {
        window.requestAnimationFrame(function () {
          window.requestAnimationFrame(function () {
            if (run === breakdownAnimationRun) breakdownGrid.classList.add("is-breakdown-animating");
          });
        });
      }
    }

    function updateReportCards() {
      const isUsers = activeReport === "users";
      const items = isUsers ? userCardItems(activePeriod) : null;
      const titles = isUsers ? null : (unavailableTitles[activeReport] || []);

      document.querySelectorAll("[data-stat-card]").forEach(function (card, index) {
        const title = card.querySelector("[data-stat-title]");
        const value = card.querySelector("[data-stat-value]");
        const change = card.querySelector("[data-stat-change]");
        const periodLabel = card.querySelector("[data-period-label]");
        card.classList.remove("is-text-stat");
        card.classList.toggle("is-unavailable", !isUsers);

        if (isUsers) {
          const item = items[index];
          title.textContent = item.title;
          animateCounter(value, item.value, item);
          if (change) {
            change.hidden = typeof item.change !== "number";
            if (typeof item.change === "number") {
              const positive = item.change >= 0;
              change.classList.toggle("is-positive", positive);
              change.classList.toggle("is-negative", !positive);
              change.innerHTML = '<i class="bi bi-caret-' + (positive ? "up" : "down") + '-fill" aria-hidden="true"></i> ' + Math.abs(item.change) + "%";
            }
          }
          if (periodLabel) periodLabel.textContent = periodLabels[activePeriod];
        } else {
          title.textContent = titles[index] || "";
          value.textContent = "—";
          if (change) change.hidden = true;
          if (periodLabel) periodLabel.textContent = "غير متاح حاليًا";
        }
      });

      animateMiniCharts();
      animateChartCounters();
      animateBreakdownCharts();
    }

    function closeExportMenu() {
      exportFormatMenu.hidden = true;
      exportReportButton.setAttribute("aria-expanded", "false");
    }

    function collectReportRows() {
      const rows = [["القسم", "القيمة", "الفترة"]];
      document.querySelectorAll("[data-stat-card]").forEach(function (card) {
        rows.push([
          card.querySelector("[data-stat-title]").textContent.trim(),
          card.querySelector("[data-stat-value]").textContent.trim(),
          card.querySelector("[data-period-label]").textContent.trim()
        ]);
      });
      return rows;
    }

    function downloadBlob(content, type, extension) {
      const blobUrl = URL.createObjectURL(new Blob([content], { type: type }));
      const link = document.createElement("a");
      link.href = blobUrl;
      link.download = "palprints-report-" + activeReport + "-" + activePeriod + "." + extension;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 1000);
    }

    function exportReport(format) {
      const rows = collectReportRows();
      if (format === "csv") {
        const csv = rows.map(function (row) {
          return row.map(function (cell) { return '"' + String(cell).replace(/"/g, '""') + '"'; }).join(",");
        }).join("\r\n");
        downloadBlob("﻿" + csv, "text/csv;charset=utf-8", "csv");
        toast("تم تنزيل التقرير بصيغة CSV.");
      } else if (format === "xlsx") {
        const tableRows = rows.map(function (row) {
          return "<tr>" + row.map(function (cell) { return "<td>" + cell + "</td>"; }).join("") + "</tr>";
        }).join("");
        const excel = '<html dir="rtl"><head><meta charset="UTF-8"></head><body><table border="1">' + tableRows + "</table></body></html>";
        downloadBlob(excel, "application/vnd.ms-excel;charset=utf-8", "xls");
        toast("تم تنزيل التقرير بصيغة Excel.");
      } else if (format === "pdf") {
        toast("اختر حفظ كملف PDF من نافذة الطباعة.");
        window.setTimeout(function () { window.print(); }, 180);
      }
      closeExportMenu();
    }

    updateReportCards();

    document.querySelectorAll("[data-period]").forEach(function (button) {
      button.addEventListener("click", function () {
        activePeriod = button.dataset.period;
        document.querySelectorAll("[data-period]").forEach(function (item) {
          item.classList.toggle("active", item === button);
        });
        updateReportCards();
      });
    });
    document.querySelectorAll(".report-tabs [role='tab']").forEach(function (tab) {
      tab.addEventListener("click", function () {
        activeReport = tab.dataset.report;
        document.querySelectorAll(".report-tabs [role='tab']").forEach(function (item) {
          const selected = item === tab;
          item.classList.toggle("active", selected);
          item.setAttribute("aria-selected", String(selected));
        });
        updateReportCards();
      });
    });
    exportReportButton.addEventListener("click", function (event) {
      event.stopPropagation();
      const open = exportFormatMenu.hidden;
      exportFormatMenu.hidden = !open;
      exportReportButton.setAttribute("aria-expanded", String(open));
      if (open) exportFormatMenu.querySelector("button").focus();
    });
    exportFormatMenu.addEventListener("click", function (event) {
      const option = event.target.closest("[data-export-format]");
      if (option) exportReport(option.dataset.exportFormat);
    });

    document.addEventListener("click", function (event) {
      if (!event.target.closest(".export-menu-wrap")) closeExportMenu();
    });
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") closeExportMenu();
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initialize, { once: true });
  else initialize();
})(window, document);
