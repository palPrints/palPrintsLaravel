/* Every table of the site shows its newest 10 rows (the server sends them newest first) and, when there are more,
   a numbered pager to move between the older ones. Works with pages that filter their own rows with `hidden` or
   `style.display = "none"`, and with tables whose rows are drawn later by JavaScript.
   Opt a table out with data-no-paginate. Change the size with data-page-size="20". */
(function () {
  "use strict";

  var DEFAULT_SIZE = 10;

  function isFiltered(row) {
    return row.hidden || row.style.display === "none";
  }

  function paginate(table) {
    var body = table.tBodies[0];
    if (!body) return;

    var size = parseInt(table.dataset.pageSize, 10) || DEFAULT_SIZE;
    var page = 1;
    var pager = document.createElement("nav");
    pager.className = "pp-pager";
    pager.setAttribute("aria-label", "صفحات الجدول");
    pager.hidden = true;

    var anchor = table;
    var parent = table.parentElement;
    if (parent && getComputedStyle(parent).overflowX !== "visible") anchor = parent;
    anchor.insertAdjacentElement("afterend", pager);

    function rows() {
      return Array.prototype.filter.call(body.rows, function (row) {
        return !isFiltered(row) && !row.querySelector("td[colspan]");
      });
    }

    function button(label, target, options) {
      var item = document.createElement("button");
      var opts = options || {};
      item.type = "button";
      if (opts.icon) item.innerHTML = '<i class="bi ' + opts.icon + '" aria-hidden="true"></i>';
      else item.textContent = label;
      if (opts.label) item.setAttribute("aria-label", opts.label);
      if (opts.disabled) item.disabled = true;
      if (!opts.icon && target === page) {
        item.className = "active";
        item.setAttribute("aria-current", "page");
      }
      item.addEventListener("click", function () {
        page = target;
        render();
        var top = table.getBoundingClientRect().top;
        if (top < 0) table.scrollIntoView({ block: "start", behavior: "smooth" });
      });
      return item;
    }

    function render() {
      var visible = rows();
      var pages = Math.max(1, Math.ceil(visible.length / size));
      if (page > pages) page = pages;

      Array.prototype.forEach.call(body.rows, function (row) { row.classList.remove("pp-page-hidden"); });
      visible.forEach(function (row, index) {
        row.classList.toggle("pp-page-hidden", index < (page - 1) * size || index >= page * size);
      });

      pager.replaceChildren();
      pager.hidden = pages <= 1;
      if (pages <= 1) return;

      pager.appendChild(button("", page - 1, { icon: "bi-chevron-right", label: "الصفحة السابقة", disabled: page === 1 }));
      for (var number = 1; number <= pages; number += 1) pager.appendChild(button(String(number), number));
      pager.appendChild(button("", page + 1, { icon: "bi-chevron-left", label: "الصفحة التالية", disabled: page === pages }));
    }

    var scheduled = false;
    new MutationObserver(function () {
      if (scheduled) return;
      scheduled = true;
      requestAnimationFrame(function () { scheduled = false; render(); });
    }).observe(body, { childList: true, subtree: true, attributes: true, attributeFilter: ["hidden", "style"] });

    render();
  }

  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("table:not([data-no-paginate])").forEach(paginate);
  });
})();
