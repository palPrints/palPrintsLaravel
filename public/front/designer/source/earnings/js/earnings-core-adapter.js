"use strict";

/* Compatibility bridge for the original earnings page and shared shell. */
(function (window, document) {
  const dictionaries = { ar: {}, en: {} };
  const languageListeners = [];
  let observerStarted = false;

  function getLanguage() {
    return document.documentElement.lang === "en" ? "en" : "ar";
  }

  function translate(key) {
    const language = getLanguage();
    return dictionaries[language][key] || dictionaries.ar[key] || "";
  }

  function applyTranslations() {
    document.querySelectorAll("[data-i18n]").forEach(function (element) {
      const value = translate(element.dataset.i18n);
      if (value) element.textContent = value;
    });

    document.querySelectorAll("[data-i18n-aria]").forEach(function (element) {
      const value = translate(element.dataset.i18nAria);
      if (value) element.setAttribute("aria-label", value);
    });

    document.querySelectorAll("[data-i18n-placeholder]").forEach(function (element) {
      const value = translate(element.dataset.i18nPlaceholder);
      if (value) element.setAttribute("placeholder", value);
    });

    const title = translate("documentTitle");
    const description = document.querySelector('meta[name="description"]');
    if (title) document.title = title;
    if (description && translate("documentDescription")) {
      description.setAttribute("content", translate("documentDescription"));
    }
  }

  function notifyLanguageChange() {
    applyTranslations();
    languageListeners.forEach(function (listener) {
      listener(getLanguage());
    });
  }

  function startObserver() {
    if (observerStarted) return;
    observerStarted = true;
    new MutationObserver(function (mutations) {
      if (mutations.some(function (mutation) { return mutation.attributeName === "lang"; })) {
        notifyLanguageChange();
      }
    }).observe(document.documentElement, { attributes: true, attributeFilter: ["lang"] });
  }

  function init(options) {
    const pageDictionary = options && options.dictionary ? options.dictionary : {};
    Object.assign(dictionaries.ar, pageDictionary.ar || {});
    Object.assign(dictionaries.en, pageDictionary.en || {});
    startObserver();
    applyTranslations();
  }

  function onLanguageChange(listener) {
    if (typeof listener === "function") languageListeners.push(listener);
  }

  function currentTheme() {
    return document.documentElement.getAttribute("data-bs-theme") === "dark" ? "dark" : "light";
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-bs-theme", theme === "dark" ? "dark" : "light");
  }

  function loadSection(section, loader) {
    if (!section || typeof loader !== "function") return Promise.resolve();
    section.dataset.state = "loading";
    return Promise.resolve().then(loader).then(function (result) {
      section.dataset.state = "success";
      return result;
    }).catch(function (error) {
      section.dataset.state = "error";
      throw error;
    });
  }

  function toast(message, type) {
    const element = document.getElementById("profileToast");
    const messageElement = document.getElementById("profileToastMessage");
    if (!element || !messageElement) return;
    messageElement.textContent = message;
    element.classList.toggle("is-error", type === "error");
    element.classList.add("is-visible");
    window.setTimeout(function () {
      element.classList.remove("is-visible");
    }, 3200);
  }

  window.PalProfile = {
    init: init,
    applyTranslations: applyTranslations,
    getLanguage: getLanguage,
    translate: translate,
    onLanguageChange: onLanguageChange,
    currentTheme: currentTheme,
    applyTheme: applyTheme,
    loadSection: loadSection,
    toast: toast
  };
})(window, document);
