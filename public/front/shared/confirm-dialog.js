/* Asks before a destructive form is submitted. Mark the form with:
     data-confirm-title="حذف المنتج"
     data-confirm-message="هل تريد حذف «:name» من السلة؟"   (optional data-confirm-name fills :name, shown in bold)
     data-confirm-action="نعم، احذف"                        (optional) */
(function () {
  "use strict";
  if (typeof HTMLDialogElement === "undefined") return; // very old browser: the form just submits

  var dialog = null, pendingForm = null;

  function build() {
    dialog = document.createElement("dialog");
    dialog.className = "pp-confirm";
    dialog.setAttribute("aria-labelledby", "ppConfirmTitle");
    dialog.innerHTML =
      '<div class="pp-confirm__body">' +
        '<span class="pp-confirm__icon" aria-hidden="true"><i class="bi bi-trash3"></i></span>' +
        '<h2 class="pp-confirm__title" id="ppConfirmTitle"></h2>' +
        '<p class="pp-confirm__text"></p>' +
      '</div>' +
      '<div class="pp-confirm__actions">' +
        '<button type="button" class="pp-confirm__cancel">إلغاء</button>' +
        '<button type="button" class="pp-confirm__ok"></button>' +
      '</div>';
    document.body.appendChild(dialog);

    dialog.querySelector(".pp-confirm__cancel").addEventListener("click", function () { dialog.close(); });
    dialog.addEventListener("click", function (event) { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener("close", function () { pendingForm = null; });
    dialog.querySelector(".pp-confirm__ok").addEventListener("click", function () {
      var form = pendingForm;
      dialog.close();
      if (!form) return;
      if (window.PalPrintLoader) window.PalPrintLoader.show();
      HTMLFormElement.prototype.submit.call(form); // submit() skips this listener, so it does not ask twice
    });
  }

  function fillMessage(paragraph, template, name) {
    paragraph.textContent = "";
    var parts = String(template).split(":name");
    parts.forEach(function (part, index) {
      paragraph.appendChild(document.createTextNode(part));
      if (index < parts.length - 1) {
        var strong = document.createElement("strong");
        strong.textContent = name || "";
        paragraph.appendChild(strong);
      }
    });
  }

  document.addEventListener("submit", function (event) {
    var form = event.target;
    if (!form || !form.hasAttribute || !form.hasAttribute("data-confirm-message")) return;
    event.preventDefault();
    if (!dialog) build();
    pendingForm = form;
    dialog.querySelector(".pp-confirm__title").textContent = form.dataset.confirmTitle || "تأكيد الحذف";
    fillMessage(dialog.querySelector(".pp-confirm__text"), form.dataset.confirmMessage, form.dataset.confirmName);
    dialog.querySelector(".pp-confirm__ok").textContent = form.dataset.confirmAction || "نعم، احذف";
    dialog.showModal();
    dialog.querySelector(".pp-confirm__cancel").focus();
  });
})();
