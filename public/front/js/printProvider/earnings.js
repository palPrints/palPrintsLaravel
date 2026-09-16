(function (document, window) {
  "use strict";

  function initialize() {
    document.querySelectorAll("[data-period]").forEach(function (button) {
      button.addEventListener("click", function () {
        document.querySelectorAll("[data-period]").forEach(function (item) {
          item.classList.remove("active");
        });
        button.classList.add("active");

        const value = button.dataset.period;
        document.querySelectorAll("#earningsRows tr").forEach(function (row) {
          row.hidden = value === "today" || (!["custom", "90"].includes(value) && Number(row.dataset.days) > Number(value));
        });
      });
    });

    const dialog = document.getElementById("withdrawDialog");
    const amount = document.getElementById("withdrawAmount");
    const payoutAccount = document.getElementById("payoutAccount");
    const payoutAccountLabel = document.getElementById("payoutAccountLabel");
    const payoutAccountHint = document.getElementById("payoutAccountHint");
    const openWithdraw = document.getElementById("openWithdraw");
    const withdrawForm = document.getElementById("withdrawForm");

    const payoutMethods = {
      "bank-of-palestine": { label: "رقم حساب بنك فلسطين", placeholder: "أدخل رقم الحساب البنكي", hint: "أدخل رقم الحساب المرتبط بفرع بنك فلسطين.", pattern: "[0-9]{6,20}" },
      "palpay": { label: "رقم حساب PalPay", placeholder: "أدخل رقم حساب PalPay", hint: "أدخل رقم الحساب المرتبط بمحفظة PalPay.", pattern: "[0-9]{9,10}" },
      "jawwal-pay": { label: "رقم حساب جوال بي", placeholder: "أدخل رقم حساب جوال بي", hint: "أدخل رقم الحساب المرتبط بمحفظة جوال بي.", pattern: "[0-9]{9,10}" }
    };

    function updatePayoutAccount() {
      const selected = document.querySelector('input[name="method"]:checked');
      if (!selected) return;
      const config = payoutMethods[selected.value];
      payoutAccountLabel.textContent = config.label;
      payoutAccount.placeholder = config.placeholder;
      payoutAccountHint.textContent = config.hint;
      payoutAccount.pattern = config.pattern;
      payoutAccount.value = "";
    }

    document.querySelectorAll('input[name="method"]').forEach(function (method) {
      method.addEventListener("change", updatePayoutAccount);
    });
    updatePayoutAccount();

    if (openWithdraw && dialog) {
      openWithdraw.addEventListener("click", function () {
        dialog.showModal();
        window.setTimeout(function () {
          amount.focus();
        }, 0);
      });
    }

    document.querySelectorAll("[data-close]").forEach(function (button) {
      button.addEventListener("click", function () {
        dialog.close();
      });
    });

    if (dialog) {
      dialog.addEventListener("click", function (event) {
        if (event.target === dialog) dialog.close();
      });
    }

    if (withdrawForm) {
      withdrawForm.addEventListener("submit", function (event) {
        event.preventDefault();
        if (!amount.checkValidity()) {
          amount.reportValidity();
          return;
        }
        if (!payoutAccount.checkValidity()) {
          payoutAccount.reportValidity();
          return;
        }

        dialog.close();
        withdrawForm.reset();
        updatePayoutAccount();

        const toast = document.getElementById("dashboardToast");
        if (toast) {
          toast.textContent = "تم إرسال طلب السحب بنجاح";
          toast.classList.add("is-visible");
          window.setTimeout(function () {
            toast.classList.remove("is-visible");
          }, 3000);
        }
      });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize, { once: true });
  } else {
    initialize();
  }
})(document, window);
