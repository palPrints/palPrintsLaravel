(function () {
  "use strict";

  const form = document.getElementById("profileForm");
  if (!form) return;

  const fullName = document.getElementById("fullName");
  const displayName = document.getElementById("displayName");
  const avatarInput = document.getElementById("avatarInput");
  const avatar = document.getElementById("avatar");
  const avatarImage = document.getElementById("avatarImage");
  const toast = document.getElementById("toast");
  let toastTimer;

  function message(text) {
    if (!toast) return;
    window.clearTimeout(toastTimer);
    toast.querySelector("span").textContent = text;
    toast.hidden = false;
    toast.classList.add("show");
    toastTimer = window.setTimeout(() => toast.classList.remove("show"), 2600);
  }

  avatarInput?.addEventListener("change", () => {
    const file = avatarInput.files && avatarInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.addEventListener("load", () => {
      avatarImage.src = reader.result;
      avatar.classList.add("has-image");
    });
    reader.readAsDataURL(file);
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    displayName.textContent = fullName.value.trim() || "عميل PalPrints";
    message("تم حفظ التغييرات بنجاح");
  });

  form.addEventListener("reset", () => {
    window.setTimeout(() => { displayName.textContent = fullName.value; }, 0);
  });
})();
