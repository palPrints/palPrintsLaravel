(function () {
  "use strict";

  const openButton = document.getElementById("uploadDesignOpen");
  const dialog = document.getElementById("uploadDesignDialog");
  if (!openButton || !dialog) return;

  const input = document.getElementById("uploadDesignFile");
  const drop = document.getElementById("uploadDesignDrop");
  const preview = document.getElementById("uploadDesignPreview");
  const empty = document.getElementById("uploadDesignEmpty");
  const status = document.getElementById("uploadDesignStatus");
  const reset = document.getElementById("uploadDesignReset");
  const done = document.getElementById("uploadDesignDone");
  const assets = window.palPrintsCustomerAssets || {};
  let objectUrl = null;
  let currentFile = null;

  function setStatus(message, type) {
    status.textContent = message;
    status.classList.toggle("is-error", type === "error");
    status.classList.toggle("is-success", type === "success");
  }

  function clearPreview() {
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
    preview.hidden = true;
    preview.removeAttribute("src");
    empty.hidden = false;
    reset.hidden = true;
    input.value = "";
    currentFile = null;
    done.textContent = "إغلاق";
  }

  function useFile(file) {
    if (!file) return;
    const error = window.PalPrintImageRules.validate(file);
    if (error) {
      setStatus(error, "error");
      return;
    }

    const candidate = URL.createObjectURL(file);
    const probe = new Image();
    probe.onload = function () {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = candidate;
      preview.src = candidate;
      preview.alt = "معاينة " + file.name;
      preview.hidden = false;
      empty.hidden = true;
      reset.hidden = false;
      currentFile = file;
      done.textContent = assets.chooseProductUrl ? "اختيار المنتج" : "إغلاق";
      setStatus("تم اختيار " + file.name, "success");
    };
    probe.onerror = function () {
      URL.revokeObjectURL(candidate);
      setStatus("تعذّر قراءة الصورة. تأكد من أن الملف صالح ثم حاول مرة أخرى.", "error");
    };
    probe.src = candidate;
  }

  openButton.addEventListener("click", function () {
    setStatus("", "");
    if (typeof dialog.showModal === "function") dialog.showModal();
    else dialog.setAttribute("open", "");
  });

  document.getElementById("uploadDesignClose").addEventListener("click", function () { dialog.close(); });

  /* Keeps the chosen image for the next pages (IndexedDB handles large files that
     sessionStorage cannot), then continues to the product picker. */
  function keepImage(file) {
    return new Promise(function (resolve, reject) {
      const request = indexedDB.open("palprintsUploads", 1);
      request.onupgradeneeded = function () { request.result.createObjectStore("files"); };
      request.onerror = function () { reject(request.error); };
      request.onsuccess = function () {
        const put = request.result.transaction("files", "readwrite").objectStore("files").put(file, "pending");
        put.onsuccess = resolve;
        put.onerror = function () { reject(put.error); };
      };
    });
  }

  done.addEventListener("click", function () {
    if (!currentFile || !assets.chooseProductUrl) { dialog.close(); return; }
    done.disabled = true;
    setStatus("جارٍ تجهيز الصورة…", "");
    keepImage(currentFile).then(function () {
      window.location.href = assets.chooseProductUrl;
    }).catch(function () {
      done.disabled = false;
      setStatus("تعذّر حفظ الصورة على هذا المتصفح. حاول مرة أخرى.", "error");
    });
  });

  dialog.addEventListener("click", function (event) {
    if (event.target === dialog) dialog.close();
  });

  dialog.addEventListener("close", function () {
    clearPreview();
    setStatus("", "");
    openButton.focus();
  });

  input.addEventListener("change", function () { useFile(input.files[0]); });
  reset.addEventListener("click", function () { clearPreview(); setStatus("", ""); input.click(); });

  ["dragenter", "dragover", "dragleave", "drop"].forEach(function (name) {
    drop.addEventListener(name, function (event) { event.preventDefault(); });
  });
  ["dragenter", "dragover"].forEach(function (name) {
    drop.addEventListener(name, function () { drop.classList.add("is-drag-active"); });
  });
  ["dragleave", "drop"].forEach(function (name) {
    drop.addEventListener(name, function () { drop.classList.remove("is-drag-active"); });
  });
  drop.addEventListener("drop", function (event) {
    useFile(event.dataTransfer && event.dataTransfer.files[0]);
  });
})();
