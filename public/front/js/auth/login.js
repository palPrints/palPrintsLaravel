(() => {
  "use strict";

  const emailInput = document.getElementById("loginEmail");
  if (!emailInput) return;

  const emailFromQuery = new URLSearchParams(window.location.search).get("email");
  if (emailFromQuery && !emailInput.value) {
    emailInput.value = emailFromQuery;
  }
})();
