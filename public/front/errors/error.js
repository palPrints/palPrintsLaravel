document.querySelectorAll("[data-error-action]").forEach(function (el) {
  el.addEventListener("click", function () {
    if (el.dataset.errorAction === "back") {
      if (window.history.length > 1) {
        window.history.back();
      } else {
        window.location.href = "/";
      }
    } else if (el.dataset.errorAction === "reload") {
      window.location.reload();
    }
  });
});