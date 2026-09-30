(function () {
  "use strict";

  var STORAGE_KEY = "palprints-theme";
  var savedTheme = null;

  try {
    savedTheme = localStorage.getItem(STORAGE_KEY);
  } catch (error) {
    savedTheme = null;
  }

  /* Light is the safe default. Dark mode is enabled only by the settings
     switch (or another existing theme control) and then remembered. */
  var initialTheme = savedTheme === "dark" ? "dark" : "light";

  function syncBody(theme) {
    if (document.body) {
      document.body.classList.toggle("dark-mode", theme === "dark");
    }
  }

  function applyTheme(theme, persist) {
    var nextTheme = theme === "light" ? "light" : "dark";
    document.documentElement.setAttribute("data-bs-theme", nextTheme);

    syncBody(nextTheme);

    if (persist) {
      try {
        localStorage.setItem(STORAGE_KEY, nextTheme);
      } catch (error) {
        /* Storage may be unavailable in private browsing contexts. */
      }
    }

    window.dispatchEvent(new CustomEvent("palprints:themechange", {
      detail: { theme: nextTheme }
    }));
  }

  applyTheme(initialTheme, false);

  document.addEventListener("DOMContentLoaded", function () {
    applyTheme(initialTheme, false);

    /* Existing page modules update data-bs-theme directly. Mirroring it to
       the legacy body class keeps every page on the same visual state. */
    new MutationObserver(function () {
      syncBody(
        document.documentElement.getAttribute("data-bs-theme") === "dark"
          ? "dark"
          : "light"
      );
    }).observe(document.documentElement, {
      attributes: true,
      attributeFilter: ["data-bs-theme"]
    });
  });

  /* A few legacy pages set a light theme during DOMContentLoaded. Re-apply the
     saved/default preference once all page initializers have completed. */
  window.addEventListener("load", function () {
    applyTheme(initialTheme, false);
  });

  window.addEventListener("storage", function (event) {
    if (event.key === STORAGE_KEY) {
      applyTheme(event.newValue === "dark" ? "dark" : "light", false);
    }
  });

  window.PalPrintsTheme = {
    get: function () {
      return document.documentElement.getAttribute("data-bs-theme") === "light"
        ? "light"
        : "dark";
    },
    set: function (theme) {
      applyTheme(theme, true);
    },
    toggle: function () {
      applyTheme(this.get() === "dark" ? "light" : "dark", true);
    }
  };
}());
