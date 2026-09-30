/* Shows the loading overlay between pages: on in-site link clicks, form submits and any other navigation
   (window.location), and on pages that opt in (data-wait) until they dispatch "palprints:ready". */
(function () {
  "use strict";
  var overlay = document.getElementById("pageLoader");
  if (!overlay) return;
  var safety = null;

  function show() {
    overlay.classList.add("is-visible");
    overlay.setAttribute("aria-hidden", "false");
    // A navigation that never unloads this page (file download, cancelled request) must not leave the overlay stuck.
    clearTimeout(safety);
    safety = setTimeout(hide, 15000);
  }
  function hide() {
    clearTimeout(safety);
    overlay.classList.remove("is-visible");
    overlay.setAttribute("aria-hidden", "true");
  }
  window.PalPrintLoader = { show: show, hide: hide };

  document.addEventListener("click", function (event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    var link = event.target.closest && event.target.closest("a[href]");
    if (!link || (link.target && link.target !== "_self") || link.hasAttribute("download")) return;
    var url;
    try { url = new URL(link.href, location.href); } catch (_) { return; }
    if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return;
    if (url.pathname === location.pathname && url.search === location.search) return; // same page or #anchor
    show();
  });

  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!form || form.target === "_blank" || form.method === "dialog") return;
    // Forms handled with fetch() call preventDefault() in their own listener, which runs before this one.
    setTimeout(function () { if (!event.defaultPrevented) show(); }, 0);
  });

  window.addEventListener("beforeunload", show);
  window.addEventListener("pageshow", hide); // back/forward cache restores the page with the overlay still on

  if (overlay.dataset.wait === "1") {
    show();
    window.addEventListener("palprints:ready", hide, { once: true });
    window.addEventListener("load", function () { setTimeout(hide, 8000); });
  }
})();
