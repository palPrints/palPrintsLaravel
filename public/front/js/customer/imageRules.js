(function () {
  "use strict";

  // One definition of "an image the customer may upload", used by the
  // upload-your-design dialog and by the sticker page's own upload box.
  const MAX_FILE_SIZE = 10 * 1024 * 1024;
  const ALLOWED_TYPES = new Set(["image/png", "image/jpeg", "image/webp", "image/svg+xml"]);
  const ALLOWED_EXTENSIONS = new Set(["png", "jpg", "jpeg", "webp", "svg"]);

  function extension(fileName) {
    const segments = String(fileName || "").toLowerCase().split(".");
    return segments.length > 1 ? segments.pop() : "";
  }

  // Returns an Arabic error message, or "" when the file is acceptable.
  function validate(file) {
    if (!file) return "لم يتم اختيار ملف.";
    if (!ALLOWED_TYPES.has(file.type) && !ALLOWED_EXTENSIONS.has(extension(file.name))) {
      return "صيغة الملف غير مدعومة. اختر PNG أو JPG أو JPEG أو WEBP أو SVG.";
    }
    if (file.size > MAX_FILE_SIZE) return "حجم الملف أكبر من 10MB. اختر ملفًا أصغر.";
    return "";
  }

  window.PalPrintImageRules = { validate: validate };
})();
