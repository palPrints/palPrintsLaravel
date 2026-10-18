"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const toast = document.getElementById("toast");
  function showToast(message) {
    if (!toast) return;
    const span = toast.querySelector("span");
    if (span) span.textContent = message;
    toast.hidden = false;
    requestAnimationFrame(() => toast.classList.add("show"));
    window.setTimeout(() => {
      toast.classList.remove("show");
      window.setTimeout(() => { toast.hidden = true; }, 300);
    }, 2600);
  }

  // Sends the ticket for real, then opens the new ticket as a chat page.
  const form = document.getElementById("supportTicketForm");
  form?.addEventListener("submit", (event) => {
    event.preventDefault();

    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    fetch(form.dataset.action, {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
      },
      body: JSON.stringify({
        category: form.elements.category.value,
        reference: form.elements.reference.value,
        subject: form.elements.subject.value,
        message: form.elements.message.value,
      }),
    })
      .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
      .then((result) => {
        if (!result.ok) {
          const errors = result.data.errors ? Object.values(result.data.errors).flat().join(" ") : "";
          throw new Error(errors || result.data.message || "تعذّر إرسال الطلب.");
        }
        showToast(result.data.message);
        window.setTimeout(() => { window.location.href = result.data.url; }, 900);
      })
      .catch((error) => { showToast(error.message || "تعذّر إرسال الطلب."); submit.disabled = false; });
  });
});
