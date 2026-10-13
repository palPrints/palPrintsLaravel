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

/* Phone: red frame and a message as soon as the number cannot be accepted (059 / 056 followed by 7 digits). */
document.addEventListener("DOMContentLoaded", () => {
  const phone = document.getElementById("phone");
  if (!phone) return;

  const message = document.createElement("span");
  message.className = "pp-error";
  message.id = "phoneClientError";
  message.hidden = true;
  phone.insertAdjacentElement("afterend", message);
  phone.setAttribute("aria-describedby", "phoneClientError");

  const check = () => {
    const value = phone.value.replace(/[\s-]+/g, "");
    let problem = "";

    if (value !== "") {
      if (/\D/.test(value)) problem = "رقم الهاتف يجب أن يحتوي على أرقام فقط.";
      else if (!/^05[69]/.test(value)) problem = "يجب أن يبدأ رقم الهاتف بـ 059 أو 056.";
      else if (value.length < 10) problem = "رقم الهاتف يجب أن يتكوّن من 10 أرقام.";
    }

    phone.classList.toggle("is-invalid", problem !== "");
    phone.setAttribute("aria-invalid", problem !== "" ? "true" : "false");
    message.textContent = problem;
    message.hidden = problem === "";
    phone.setCustomValidity(problem);
  };

  phone.addEventListener("input", check);
  phone.addEventListener("blur", check);
  check();
});