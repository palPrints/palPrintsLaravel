(function () {
  "use strict";

  /* Header dropdowns (account + notifications), shared by every designer page. */
  const dropdowns = [
    ["designerProfileMenuButton", "designerProfileMenu"],
    ["designerNotificationMenuButton", "designerNotificationMenu"]
  ].map(function (ids) {
    return {
      button: document.getElementById(ids[0]),
      menu: document.getElementById(ids[1])
    };
  }).filter(function (dropdown) {
    return dropdown.button && dropdown.menu;
  });

  function close(dropdown) {
    dropdown.button.setAttribute("aria-expanded", "false");
    dropdown.menu.classList.remove("is-open");
    dropdown.menu.hidden = true;
  }

  function open(dropdown) {
    dropdowns.forEach(function (item) {
      if (item !== dropdown) close(item);
    });

    dropdown.button.setAttribute("aria-expanded", "true");
    dropdown.menu.hidden = false;
    window.requestAnimationFrame(function () {
      dropdown.menu.classList.add("is-open");
    });
  }

  dropdowns.forEach(function (dropdown) {
    dropdown.button.addEventListener("click", function (event) {
      event.stopPropagation();

      if (dropdown.button.getAttribute("aria-expanded") === "true") {
        close(dropdown);
      } else {
        open(dropdown);
      }
    });

    dropdown.menu.addEventListener("click", function (event) {
      event.stopPropagation();
    });
  });

  document.addEventListener("click", function () {
    dropdowns.forEach(close);
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") dropdowns.forEach(close);
  });

  /* Sidebar, language, theme and toasts come from profile-core.js.
     Pages with their own script call PalProfile.init() themselves; every
     other page gets a default init once all page scripts have run. */
  const Core = window.PalProfile;

  if (Core && typeof Core.init === "function") {
    const originalInit = Core.init;
    let initialised = false;

    Core.init = function (options) {
      initialised = true;
      return originalInit.call(Core, options);
    };

    document.addEventListener("DOMContentLoaded", function () {
      window.setTimeout(function () {
        if (!initialised) Core.init();
      }, 0);
    });
  }
})();
