"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const Core = window.PalProfile;
  const form = document.getElementById("designerProfileForm");

  if (!form) {
    return;
  }

  const view = document.getElementById("designerProfileView");
  const edit = document.getElementById("designerProfileEdit");
  const actions = document.getElementById("designerFormActions");
  const editButton = document.getElementById("editDesignerProfile");
  const cancelButton = document.getElementById("cancelDesignerEdit");
  const description = document.getElementById("designerPageDescription");
  const portfolioField = document.getElementById("profilePortfolio");
  const portfolioClearButton = document.getElementById("portfolioClearButton");

  const submitReview = document.querySelector(".designer-submit-review");

  let submitting = false;

  /* The approval request only uses saved data, so it stays hidden while there are unsaved edits. */
  function setEditing(isEditing) {
    if (submitReview) submitReview.hidden = isEditing;
    view.hidden = isEditing;
    edit.hidden = !isEditing;
    actions.hidden = !isEditing;
    editButton.hidden = isEditing;
    editButton.setAttribute("aria-expanded", String(isEditing));

    if (description) {
      description.textContent = isEditing
        ? description.dataset.editText
        : description.dataset.viewText;
    }

    if (isEditing) {
      const first = document.getElementById("profileFullName");
      if (first) first.focus();
    }
  }

  if (submitReview) submitReview.hidden = !edit.hidden;

  editButton.addEventListener("click", function () {
    setEditing(true);
  });

  cancelButton.addEventListener("click", function () {
    /* Restores the values rendered by the server. */
    form.reset();
    form.querySelectorAll("[aria-invalid]").forEach(function (field) {
      field.removeAttribute("aria-invalid");
    });
    form.querySelectorAll(".designer-field-error[data-client]").forEach(function (message) {
      message.remove();
    });
    if (skillsBox) {
      skillsBox.classList.remove("is-invalid");
      skills = parseSkills(savedSkills);
      renderSkills();
    }
    setEditing(false);
  });

  if (portfolioClearButton && portfolioField) {
    portfolioClearButton.addEventListener("click", function () {
      portfolioField.value = "";
      portfolioField.focus();
    });
  }

  /* Skills: the designer types one skill and presses Enter; the chips are sent as one comma-separated value. */
  const skillsHidden = document.getElementById("profileSkills");
  const skillInput = document.getElementById("profileSkillsInput");
  const skillsBox = document.getElementById("skillsBox");
  const skillsChips = document.getElementById("skillsChips");
  const skillAddButton = document.getElementById("skillAddButton");
  const MAX_SKILLS = 12;
  let skills = [];
  const savedSkills = skillsHidden ? skillsHidden.value : "";

  function parseSkills(text) {
    const seen = new Set();
    return String(text || "")
      .split(/[,،\n]/)
      .map(function (item) { return item.trim(); })
      .filter(function (item) {
        const key = item.toLowerCase();
        if (!item || seen.has(key)) return false;
        seen.add(key);
        return true;
      })
      .slice(0, MAX_SKILLS);
  }

  function renderSkills() {
    skillsChips.textContent = "";

    skills.forEach(function (skill, index) {
      const chip = document.createElement("span");
      chip.className = "designer-skill-chip";
      chip.append(document.createTextNode(skill));

      const remove = document.createElement("button");
      remove.type = "button";
      remove.setAttribute("aria-label", "حذف المهارة " + skill);
      remove.textContent = "×";
      remove.addEventListener("click", function () {
        skills.splice(index, 1);
        renderSkills();
        skillInput.focus();
      });

      chip.append(remove);
      skillsChips.append(chip);
    });

    skillsHidden.value = skills.join("، ");
    skillInput.required = skills.length === 0;
    skillInput.placeholder = skills.length >= MAX_SKILLS
      ? "وصلت للحد الأقصى (" + MAX_SKILLS + " مهارة)"
      : "اكتب مهارة ثم اضغط Enter";
    skillInput.disabled = skills.length >= MAX_SKILLS;
    skillAddButton.disabled = skills.length >= MAX_SKILLS;
  }

  function addSkillsFromInput() {
    const added = parseSkills(skillInput.value);
    skillInput.value = "";
    if (!added.length) return;

    added.forEach(function (skill) {
      const exists = skills.some(function (item) { return item.toLowerCase() === skill.toLowerCase(); });
      if (!exists && skills.length < MAX_SKILLS) skills.push(skill);
    });

    clearFieldError(skillInput);
    renderSkills();
    if (!skillInput.disabled) skillInput.focus();
  }

  if (skillsHidden && skillInput && skillsBox && skillsChips && skillAddButton) {
    skills = parseSkills(savedSkills);
    renderSkills();

    skillInput.addEventListener("keydown", function (event) {
      if (event.key === "Enter" || event.key === "," || event.key === "،") {
        event.preventDefault();
        addSkillsFromInput();
      } else if (event.key === "Backspace" && skillInput.value === "" && skills.length) {
        skills.pop();
        renderSkills();
      }
    });

    /* Pasting "a, b, c" adds all three. */
    skillInput.addEventListener("paste", function () {
      window.setTimeout(addSkillsFromInput, 0);
    });

    skillAddButton.addEventListener("click", addSkillsFromInput);
  }

  function fieldErrorMessage(field) {
    if (field === skillInput && field.validity.valueMissing) return "أضف مهارة واحدة على الأقل.";
    if (field.validity.valueMissing) return "هذا الحقل مطلوب.";
    if (field.validity.patternMismatch && field.title) return field.title;
    if (field.validity.typeMismatch || field.validity.patternMismatch) return "أدخل قيمة صحيحة.";
    if (field.validity.tooShort) return "القيمة قصيرة جدًا.";
    if (field.validity.tooLong) return "القيمة طويلة جدًا.";
    return field.validationMessage || "القيمة غير صالحة.";
  }

  function showFieldError(field) {
    field.setAttribute("aria-invalid", "true");
    if (field === skillInput && skillsBox) skillsBox.classList.add("is-invalid");

    const box = field.closest("label, .designer-skills-field");
    if (!box) return;

    let message = box.querySelector(".designer-field-error[data-client]");
    if (!message) {
      message = document.createElement("small");
      message.className = "designer-field-error";
      message.dataset.client = "1";
      box.appendChild(message);
    }
    message.textContent = fieldErrorMessage(field);
  }

  function clearFieldError(field) {
    field.removeAttribute("aria-invalid");
    if (field === skillInput && skillsBox) skillsBox.classList.remove("is-invalid");

    const box = field.closest("label, .designer-skills-field");
    const message = box && box.querySelector(".designer-field-error[data-client]");
    if (message) message.remove();
  }

  form.addEventListener("input", function (event) {
    if (event.target.getAttribute && event.target.getAttribute("aria-invalid") === "true") {
      clearFieldError(event.target);
    }
  });

  form.addEventListener("submit", function (event) {
    if (submitting) {
      event.preventDefault();
      return;
    }

    /* A skill typed but not confirmed with Enter is still counted. */
    if (skillInput && skillInput.value.trim()) addSkillsFromInput();

    /* No browser bubble: every missing field gets its own message, plus a notice at the top. */
    if (!form.checkValidity()) {
      event.preventDefault();

      const invalidFields = Array.from(form.querySelectorAll(":invalid"));
      invalidFields.forEach(showFieldError);

      const first = invalidFields[0];
      if (first) first.focus();
      if (Core) {
        Core.toast(
          invalidFields.length > 1
            ? "راجع الحقول المطلوبة قبل الحفظ."
            : fieldErrorMessage(first),
          "error"
        );
      }
      return;
    }

    submitting = true;

    const submitButton = form.querySelector("[type=submit]");
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.setAttribute("aria-busy", "true");
    }
  });

  /* Back/forward cache restores the page with a disabled button. */
  window.addEventListener("pageshow", function () {
    submitting = false;

    const submitButton = form.querySelector("[type=submit]");
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.removeAttribute("aria-busy");
    }
  });

  if (window.designerProfileFlash === "updated" && Core) {
    Core.toast("تم حفظ بيانات الملف الشخصي بنجاح.", "success");
  }
});
