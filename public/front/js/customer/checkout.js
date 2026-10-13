(function () {
  "use strict";

  const form = document.getElementById("checkoutForm");
  if (!form) return;

  const panels = Array.from(document.querySelectorAll(".step-panel"));
  const steps = Array.from(document.querySelectorAll(".steps li"));
  const title = document.getElementById("pageTitle");
  const subtitle = document.getElementById("pageSubtitle");
  const toast = document.getElementById("toast");
  let current = 1;

  const copy = {
    1: ["إتمام الطلب", "راجع بيانات المستلم الخاصة بالطلب."],
    2: ["عنوان التوصيل", "اختر عنواناً محفوظاً أو أدخل عنواناً جديداً."],
    3: ["طريقة الدفع", "اختر طريقة الدفع المناسبة لك."],
    4: ["مراجعة الطلب", "تأكد من البيانات قبل تحويل السلة إلى طلب."],
  };

  function message(text) {
    if (!toast) return;
    toast.textContent = text;
    toast.classList.add("show");
    setTimeout(() => toast.classList.remove("show"), 2200);
  }

  function required(input, text) {
    if (!input) return false;
    const field = input.closest(".field");
    const ok = input.value.trim().length > 0;
    field?.classList.toggle("invalid", !ok);
    const error = field?.querySelector(".error");
    if (error) error.textContent = ok ? "" : text;
    return ok;
  }


  function normalizePhone(input) {
    if (!input) return "";
    const digits = input.value.replace(/\D/g, "").slice(0, 10);
    if (input.value !== digits) input.value = digits;
    return digits;
  }

  function validPalestinianMobile(input) {
    if (!input) return false;
    const field = input.closest(".field");
    const value = normalizePhone(input);
    const ok = /^05[69][0-9]{7}$/.test(value);
    field?.classList.toggle("invalid", !ok);
    const error = field?.querySelector(".error");
    if (error) error.textContent = ok ? "" : "أدخلي رقم هاتف صحيح يبدأ بـ 059 أو 056 ويتكون من 10 أرقام";
    return ok;
  }
  function validateRecipient() {
    const name = document.getElementById("recipientName");
    const phone = document.getElementById("phone");
    const nameOk = required(name, "أدخلي اسم المستلم");
    const phoneOk = required(phone, "أدخلي رقم الهاتف") && validPalestinianMobile(phone);
    return nameOk && phoneOk;
  }
  function usingNewAddress() {
    const newAddress = document.querySelector(".new-address");
    const selectedAddress = document.querySelector('input[name="address_id"]:checked');
    return !selectedAddress || (newAddress && !newAddress.hidden);
  }

  function showNewAddressForm() {
    const area = document.querySelector(".new-address");
    if (!area) return;
    area.hidden = false;
    document.querySelectorAll('input[name="address_id"]').forEach((input) => { input.checked = false; });
    document.querySelectorAll(".address-list .choice-card").forEach((card) => card.classList.remove("selected"));
    area.querySelector("input")?.focus();
  }

  function validateAddress() {
    const selectedAddress = document.querySelector('input[name="address_id"]:checked');
    const newAddress = document.querySelector(".new-address");

    if (selectedAddress && (!newAddress || newAddress.hidden)) return true;

    if (!selectedAddress && newAddress?.hidden) {
      message("اضغطي على استخدام عنوان جديد أو اختاري عنواناً محفوظاً");
      return false;
    }

    const city = form.querySelector('[name="city"]');
    const street = form.querySelector('[name="street"]');
    return required(city, "أدخلي المدينة") && required(street, "أدخلي الشارع");
  }
  function requiresReceipt() {
    const payment = document.querySelector('[name="payment_method"]:checked')?.value || "palpay";
    return payment === "palpay" || payment === "jawwal" || payment === "bank";
  }

  function syncReceiptField() {
    const field = document.querySelector('[data-payment-receipt]');
    const input = document.getElementById('paymentReceipt');
    if (!field || !input) return;
    const needed = requiresReceipt();
    field.hidden = !needed;
    input.required = needed;
    if (!needed) { input.value = ''; showReceipt(input); }
  }

  /* Shows the chosen receipt's name and a small preview inside the upload box. */
  function showReceipt(input) {
    const box = input.closest('.receipt-drop');
    if (!box) return;
    const name = box.querySelector('[data-receipt-name]');
    const thumb = box.querySelector('.receipt-thumb');
    const file = input.files && input.files[0];
    if (thumb.src.startsWith('blob:')) URL.revokeObjectURL(thumb.src);
    box.classList.toggle('has-file', Boolean(file));
    name.textContent = file ? file.name : 'اضغطي لاختيار صورة الإشعار';
    thumb.hidden = !file;
    if (file) thumb.src = URL.createObjectURL(file); else thumb.removeAttribute('src');
  }

  document.getElementById('paymentReceipt')?.addEventListener('change', function (event) {
    showReceipt(event.target);
    if (event.target.files.length) validateReceipt();
  });

  function validateReceipt() {
    if (!requiresReceipt()) return true;
    const input = document.getElementById('paymentReceipt');
    const field = input?.closest('.field');
    const ok = Boolean(input?.files?.length);
    field?.classList.toggle('invalid', !ok);
    const error = field?.querySelector('.error');
    if (error) error.textContent = ok ? '' : 'ارفعي صورة إشعار الدفع';
    return ok;
  }

  function updateReview() {
    const name = document.getElementById("recipientName")?.value.trim() || "-";
    const phone = document.getElementById("phone")?.value.trim() || "-";
    const payment = document.querySelector('[name="payment_method"]:checked')?.value || "palpay";
    const paymentNames = { palpay: "PalPay", jawwal: "جوال Pay", bank: "بنك فلسطين" };
    const selectedAddress = document.querySelector('input[name="address_id"]:checked')?.closest("label")?.innerText.trim();
    const city = form.querySelector('[name="city"]')?.value.trim();
    const street = form.querySelector('[name="street"]')?.value.trim();

    document.getElementById("reviewCustomer").innerHTML = `${name}<br>${phone}`;
    document.getElementById("reviewAddress").innerHTML = selectedAddress && !usingNewAddress()
      ? selectedAddress.replace(/\n/g, "<br>")
      : `${city || "-"}<br>${street || "-"}`;
    document.getElementById("reviewPayment").innerHTML = `${paymentNames[payment]}<br>سيتم تأكيد الدفع عبر الطريقة المختارة`;
  }

  function showStep(step) {
    if (step >= 2 && !validateRecipient()) return;
    if (step >= 3 && !validateAddress()) return;
    if (step === 4) updateReview();

    current = step;
    panels.forEach((panel) => panel.classList.toggle("active", Number(panel.dataset.panel) === step));
    steps.forEach((item) => {
      const value = Number(item.dataset.step);
      item.classList.toggle("active", value === step);
      item.classList.toggle("done", value < step);
    });
    if (title) title.innerHTML = `${copy[step][0]} <i class="bi bi-bag-lock"></i>`;
    if (subtitle) subtitle.textContent = copy[step][1];
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  document.addEventListener("click", (event) => {
    const next = event.target.closest("[data-next]");
    const back = event.target.closest("[data-back]");
    if (next) showStep(Number(next.dataset.next));
    if (back) showStep(Number(back.dataset.back));
  });

  steps.forEach((step) => {
    step.querySelector("button")?.addEventListener("click", () => {
      const value = Number(step.dataset.step);
      showStep(value);
    });
  });

  document.querySelectorAll('.choice-card input, .payment-card input').forEach((input) => {
    input.addEventListener("change", () => {
      input.closest(".address-list, .payment-options")?.querySelectorAll("label").forEach((label) => label.classList.remove("selected"));
      input.closest("label")?.classList.add("selected");

      if (input.name === "address_id") {
        const area = document.querySelector(".new-address");
        if (area) area.hidden = true;
      }

      if (input.name === "payment_method") {
        document.querySelectorAll("[data-payment-fields]").forEach((panel) => {
          panel.hidden = panel.dataset.paymentFields !== input.value;
        });
        syncReceiptField();
      }
    });
  });

  document.querySelectorAll("[data-toggle-new-address]").forEach((button) => {
    button.addEventListener("click", showNewAddressForm);
  });

  const phoneInput = document.getElementById("phone");
  phoneInput?.addEventListener("input", () => normalizePhone(phoneInput));

  syncReceiptField();

  form.addEventListener("submit", (event) => {
    if (!validateRecipient() || !validateAddress() || !validateReceipt()) {
      event.preventDefault();
      message("راجعي الحقول المطلوبة قبل تأكيد الطلب");
      return;
    }
    form.querySelector('.confirm-btn')?.setAttribute('disabled', 'disabled');
  });
})();