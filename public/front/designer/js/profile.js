(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const dialog = document.getElementById('designerEditDialog');
    const form = document.getElementById('designerEditForm');
    const localeInput = document.getElementById('designerProfileLocale');
    const avatarInput = document.getElementById('profileAvatarInput');
    const avatarPicker = document.querySelector('[data-avatar-picker]');
    const avatarImages = Array.from(document.querySelectorAll('[data-profile-avatar-image]'));
    const avatarInitials = Array.from(document.querySelectorAll('[data-profile-avatar-initials]'));
    const toast = document.getElementById('profileToast');
    const initialAvatarUrl = avatarImages[0]?.getAttribute('src') || '';
    let initialHasAvatar = initialAvatarUrl !== '';
    let previousFocus = null;
    let previewUrl = null;
    let submitting = false;
    let toastTimer = null;

    function language() {
      return document.documentElement.lang === 'en' ? 'en' : 'ar';
    }

    function syncLocale() {
      const currentLanguage = language();

      if (localeInput) {
        localeInput.value = currentLanguage;
      }

      document.title = currentLanguage === 'en'
        ? 'Profile | PalPrints'
        : 'الملف الشخصي | PalPrints';

      const description = document.querySelector('meta[name=description]');

      if (description) {
        description.setAttribute(
          'content',
          currentLanguage === 'en'
            ? 'Manage your PalPrints designer profile.'
            : 'إدارة الملف الشخصي للمصمم في PalPrints.'
        );
      }

      document.querySelectorAll('[data-profile-aria-ar][data-profile-aria-en]').forEach(function (element) {
        element.setAttribute(
          'aria-label',
          element.getAttribute(currentLanguage === 'en' ? 'data-profile-aria-en' : 'data-profile-aria-ar')
        );
      });

      document.querySelectorAll('[data-profile-alt-ar][data-profile-alt-en]').forEach(function (element) {
        element.setAttribute(
          'alt',
          element.getAttribute(currentLanguage === 'en' ? 'data-profile-alt-en' : 'data-profile-alt-ar')
        );
      });
    }

    function updateAvatar(url, hasAvatar) {
      avatarImages.forEach(function (image) {
        if (hasAvatar) {
          image.src = url;
        } else {
          image.removeAttribute('src');
        }

        image.hidden = !hasAvatar;
      });

      avatarInitials.forEach(function (initials) {
        initials.hidden = hasAvatar;
      });
    }

    function resetAvatarPreview() {
      if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
        previewUrl = null;
      }

      if (avatarInput) {
        avatarInput.value = '';
      }

      updateAvatar(initialAvatarUrl, initialHasAvatar);
    }

    avatarImages.forEach(function (image) {
      image.addEventListener('error', function () {
        if (!previewUrl) {
          initialHasAvatar = false;
        }

        updateAvatar('', false);
      });
    });

    function openDialog(trigger) {
      if (!dialog || dialog.open) {
        return;
      }

      previousFocus = trigger || document.activeElement;

      if (typeof dialog.showModal === 'function') {
        dialog.showModal();
      } else {
        dialog.setAttribute('open', '');
      }

      document.body.classList.add('profile-dialog-open');

      const requestedField = trigger?.dataset.dialogFocus
        ? document.getElementById(trigger.dataset.dialogFocus)
        : null;
      const firstInvalid = dialog.querySelector('[aria-invalid=true]');
      const firstField = dialog.querySelector('input:not([type=hidden]), textarea');

      window.setTimeout(function () {
        (requestedField || firstInvalid || firstField)?.focus();
      }, 0);
    }

    function closeDialog() {
      if (!dialog?.open) {
        return;
      }

      if (typeof dialog.close === 'function') {
        dialog.close();
      } else {
        dialog.removeAttribute('open');
        resetDialog();
      }
    }

    function resetDialog() {
      document.body.classList.remove('profile-dialog-open');

      if (!submitting) {
        form?.reset();
        resetAvatarPreview();
      }

      if (previousFocus instanceof HTMLElement) {
        previousFocus.focus();
      }
    }

    function showToast(arabic, english, isError) {
      if (!toast) {
        return;
      }

      window.clearTimeout(toastTimer);
      toast.dataset.copyAr = arabic;
      toast.dataset.copyEn = english;
      toast.textContent = language() === 'en' ? english : arabic;
      toast.classList.toggle('is-error', Boolean(isError));
      toast.classList.add('is-visible');

      toastTimer = window.setTimeout(function () {
        toast.classList.remove('is-visible');
      }, 3400);
    }

    document.querySelectorAll('[data-dialog-open=designerEditDialog]').forEach(function (trigger) {
      trigger.addEventListener('click', function () {
        openDialog(trigger);
      });
    });

    dialog?.querySelectorAll('[data-dialog-close]').forEach(function (button) {
      button.addEventListener('click', closeDialog);
    });

    dialog?.addEventListener('click', function (event) {
      const bounds = dialog.getBoundingClientRect();
      const isOutside = event.clientX < bounds.left
        || event.clientX > bounds.right
        || event.clientY < bounds.top
        || event.clientY > bounds.bottom;

      if (isOutside) {
        closeDialog();
      }
    });

    dialog?.addEventListener('close', resetDialog);

    avatarPicker?.addEventListener('click', function () {
      avatarInput?.click();
    });

    avatarInput?.addEventListener('change', function () {
      const file = avatarInput.files?.[0];

      if (!file) {
        resetAvatarPreview();
        return;
      }

      if (!/^image\/(png|jpeg|webp)$/.test(file.type)) {
        resetAvatarPreview();
        showToast(
          'يُسمح بصور PNG أو JPG أو WEBP فقط.',
          'Only PNG, JPG, and WEBP images are allowed.',
          true
        );
        return;
      }

      if (file.size > 2 * 1024 * 1024) {
        resetAvatarPreview();
        showToast(
          'يجب ألا يتجاوز حجم الصورة 2 ميجابايت.',
          'The image must not exceed 2 MB.',
          true
        );
        return;
      }

      if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
      }

      previewUrl = URL.createObjectURL(file);
      updateAvatar(previewUrl, true);
      showToast(
        'تم اختيار الصورة. احفظ التغييرات لتثبيتها.',
        'Photo selected. Save changes to apply it.',
        false
      );
    });

    form?.addEventListener('submit', function () {
      syncLocale();
      submitting = true;

      const submitButton = form.querySelector('[type=submit]');

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
      }
    });

    syncLocale();

    new MutationObserver(syncLocale).observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['lang'],
    });

    if (window.designerProfileHasErrors) {
      openDialog();
    }

    if (toast?.classList.contains('is-visible')) {
      toastTimer = window.setTimeout(function () {
        toast.classList.remove('is-visible');
      }, 3400);
    }
  });
})();
