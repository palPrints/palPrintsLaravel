(function (document) {
  "use strict";

  function initialize() {
    const toast = document.getElementById("dashboardToast");
    let toastTimer = 0;

    function showToast(message) {
      if (!toast) return;
      window.clearTimeout(toastTimer);
      toast.textContent = message;
      toast.classList.add("is-visible");
      toastTimer = window.setTimeout(function () {
        toast.classList.remove("is-visible");
      }, 2600);
    }

    document.querySelectorAll("#workDays button").forEach(function (day) {
      day.addEventListener("click", function () {
        day.classList.toggle("selected");
      });
    });

    const availability = document.getElementById("availabilityToggle");
    const availabilityText = document.getElementById("availabilityText");

    if (availability && availabilityText) {
      availability.addEventListener("change", function () {
        availabilityText.innerHTML = availability.checked ? "متاح <i></i>" : "غير متاح";
        availabilityText.classList.toggle("off", !availability.checked);
      });
    }

    const form = document.getElementById("printerProfileForm");
    const submitReview = document.getElementById("submitReview");

    if (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;
        showToast("تم حفظ التغييرات بنجاح");
      });
    }

    if (submitReview && form) {
      submitReview.addEventListener("click", function () {
        if (!form.reportValidity()) return;
        showToast("تم إرسال بيانات الملف للمراجعة");
      });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(document);
