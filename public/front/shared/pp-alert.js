/* One place for every confirm / alert / prompt pop-up in the site, drawn by SweetAlert2 (loaded before this file).
   Use window.PalAlert instead of window.confirm / alert / prompt:

     PalAlert.confirm({ title, text, confirmText, cancelText, icon, danger }) -> Promise<boolean>
     PalAlert.alert(text, { title, icon })                                    -> Promise<void>
     PalAlert.prompt({ title, text, placeholder, confirmText, multiline })    -> Promise<string|null>  (null = cancelled)

   A form can also ask before it submits, with no JavaScript:
     data-confirm-title="حذف المنتج"
     data-confirm-message="هل تريد حذف «:name» من السلة؟"   (data-confirm-name fills :name, shown in bold)
     data-confirm-action="نعم، احذف"                        (optional)

   If SweetAlert2 could not load (offline CDN), everything falls back to the browser's own pop-ups so no action is lost. */
(function () {
  "use strict";

  function classes(danger) {
    return {
      popup: "pp-swal",
      title: "pp-swal__title",
      htmlContainer: "pp-swal__text",
      actions: "pp-swal__actions",
      confirmButton: "pp-swal__btn pp-swal__btn--" + (danger ? "danger" : "primary"),
      cancelButton: "pp-swal__btn pp-swal__btn--ghost",
      input: "pp-swal__input",
    };
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (ch) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
    });
  }

  function confirmAction(options) {
    var o = options || {};
    if (!window.Swal) return Promise.resolve(window.confirm(o.text || o.title || ""));

    return window.Swal.fire({
      icon: o.icon || "warning",
      title: o.title,
      text: o.html ? undefined : o.text,
      html: o.html,
      showCancelButton: true,
      confirmButtonText: o.confirmText || "نعم",
      cancelButtonText: o.cancelText || "إلغاء",
      focusCancel: true,
      heightAuto: false,
      buttonsStyling: false,
      customClass: classes(o.danger !== false),
    }).then(function (result) { return result.isConfirmed; });
  }

  function alertMessage(text, options) {
    var o = options || {};
    if (!window.Swal) { window.alert(text); return Promise.resolve(); }

    return window.Swal.fire({
      icon: o.icon || "info",
      title: o.title,
      text: text,
      confirmButtonText: o.confirmText || "حسنًا",
      heightAuto: false,
      buttonsStyling: false,
      customClass: classes(false),
    }).then(function () {});
  }

  function promptValue(options) {
    var o = options || {};
    if (!window.Swal) return Promise.resolve(window.prompt(o.text || o.title || "", ""));

    return window.Swal.fire({
      icon: o.icon || "question",
      title: o.title,
      text: o.text,
      input: o.multiline ? "textarea" : "text",
      inputPlaceholder: o.placeholder || "",
      showCancelButton: true,
      confirmButtonText: o.confirmText || "تأكيد",
      cancelButtonText: o.cancelText || "إلغاء",
      heightAuto: false,
      buttonsStyling: false,
      customClass: classes(o.danger === true),
    }).then(function (result) { return result.isConfirmed ? String(result.value || "") : null; });
  }

  window.PalAlert = { confirm: confirmAction, alert: alertMessage, prompt: promptValue };

  /* Forms that ask before they submit. */
  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!form || !form.hasAttribute || !form.hasAttribute("data-confirm-message")) return;
    if (form.__ppConfirmed) return;

    event.preventDefault();

    var name = form.dataset.confirmName ? "<strong>" + escapeHtml(form.dataset.confirmName) + "</strong>" : "";
    var message = escapeHtml(form.dataset.confirmMessage).split(":name").join(name);

    confirmAction({
      title: form.dataset.confirmTitle || "تأكيد الحذف",
      html: message,
      confirmText: form.dataset.confirmAction || "نعم، احذف",
    }).then(function (ok) {
      if (!ok) return;
      form.__ppConfirmed = true;
      if (window.PalPrintLoader) window.PalPrintLoader.show();
      HTMLFormElement.prototype.submit.call(form);
    });
  });
})();
