"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const avatarInput = document.getElementById("avatarInput");
  const avatar = document.getElementById("avatar");
  const avatarImage = document.getElementById("avatarImage");

  const profileView = document.getElementById("profileView");
  const profileForm = document.getElementById("profileForm");
  const editButton = document.getElementById("profileEditButton");
  const cancelButton = document.getElementById("profileCancelButton");
  const fullName = document.getElementById("fullName");

  const toast = document.getElementById("toast");

  avatarInput.addEventListener("change", () => {
    const file = avatarInput.files && avatarInput.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.addEventListener("load", () => {
      avatarImage.src = reader.result;
      avatar.classList.add("has-image");
    });
    reader.readAsDataURL(file);
  });

  function showForm() {
    profileView.hidden = true;
    profileForm.hidden = false;
    fullName.focus();
  }

  function showView() {
    profileForm.hidden = true;
    profileView.hidden = false;
  }

  editButton.addEventListener("click", showForm);

  cancelButton.addEventListener("click", () => {
    profileForm.reset();
    showView();
  });

  if (toast && toast.dataset.flash === "1") {
    showToast("تم حفظ التغييرات بنجاح");
  }

  function showToast(message) {
    if (!toast) return;
    const span = toast.querySelector("span");
    if (span) span.textContent = message;
    toast.hidden = false;
    requestAnimationFrame(() => toast.classList.add("show"));
    window.setTimeout(() => {
      toast.classList.remove("show");
      window.setTimeout(() => {
        toast.hidden = true;
      }, 300);
    }, 2600);
  }
});
