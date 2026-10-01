/* Draws a design on its product picture and returns it as a PNG data URL: the product (tinted with the chosen
   color when it has no picture of its own), the print zone, and every image and text placed inside it.
   The same description (a "mockup") the cart uses, so the gallery picture matches what the designer previewed.

   mockup: { image, tint, zone: {top,left,width,height}, images: [{url, x,y,width,height, rotation, flipX, flipY, layer, tint}],
             texts: [{content, x,y,width, size_percent, font_family, color, font_weight, font_style, text_align, line_height,
                      rotation, flipX, flipY, layer}] }
   Positions are percentages: the zone of the picture's 1 : 1.1 box, items inside the zone. */
(function (window, document) {
  "use strict";

  function load(url) {
    return new Promise(function (resolve) {
      if (!url) return resolve(null);
      const image = new Image();
      image.onload = function () { resolve(image); };
      image.onerror = function () { resolve(null); };
      image.src = url;
    });
  }

  function layer(width, height) {
    const canvas = document.createElement("canvas");
    canvas.width = Math.max(1, Math.round(width));
    canvas.height = Math.max(1, Math.round(height));
    return canvas;
  }

  /** Draws image inside the box like CSS object-fit: contain. */
  function contain(context, image, x, y, width, height) {
    const scale = Math.min(width / image.width, height / image.height);
    const drawWidth = image.width * scale, drawHeight = image.height * scale;
    context.drawImage(image, x + (width - drawWidth) / 2, y + (height - drawHeight) / 2, drawWidth, drawHeight);
  }

  /** The image's shape filled with one color. */
  function silhouette(image, color, width, height) {
    const canvas = layer(width, height), context = canvas.getContext("2d");
    context.fillStyle = color;
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.globalCompositeOperation = "destination-in";
    contain(context, image, 0, 0, canvas.width, canvas.height);
    return canvas;
  }

  function wrapLines(context, text, maxWidth) {
    const lines = [];
    String(text).split("\n").forEach(function (paragraph) {
      let line = "";
      paragraph.split(/\s+/).forEach(function (word) {
        const attempt = line ? line + " " + word : word;
        if (line && context.measureText(attempt).width > maxWidth) { lines.push(line); line = word; } else { line = attempt; }
      });
      lines.push(line);
    });
    return lines;
  }

  async function render(mockup, options) {
    const width = (options && options.width) || 900, height = Math.round(width * 1.1);
    const canvas = layer(width, height), context = canvas.getContext("2d");
    const product = await load(mockup.image);
    if (!product) throw new Error("product picture unavailable");

    // Product, tinted when needed: the color layer first, then the picture multiplied over it so folds and shadows stay.
    if (mockup.tint) {
      context.drawImage(silhouette(product, mockup.tint, width, height), 0, 0);
      context.globalCompositeOperation = "multiply";
      contain(context, product, 0, 0, width, height);
      context.globalCompositeOperation = "source-over";
    } else {
      contain(context, product, 0, 0, width, height);
    }

    const zone = mockup.zone;
    const zoneX = zone.left / 100 * width, zoneY = zone.top / 100 * height, zoneWidth = zone.width / 100 * width, zoneHeight = zone.height / 100 * height;
    context.save();
    context.beginPath();
    context.rect(zoneX, zoneY, zoneWidth, zoneHeight);
    context.clip();

    const place = function (item, draw) {
      context.save();
      context.translate(zoneX + item.x / 100 * zoneWidth, zoneY + item.y / 100 * zoneHeight);
      context.rotate((item.rotation || 0) * Math.PI / 180);
      context.scale(item.flipX ? -1 : 1, item.flipY ? -1 : 1);
      draw();
      context.restore();
    };

    const items = []
      .concat((mockup.images || []).map(function (item) { return Object.assign({ kind: "image" }, item); }))
      .concat((mockup.texts || []).map(function (item) { return Object.assign({ kind: "text" }, item); }))
      .sort(function (a, b) { return (a.layer || 1) - (b.layer || 1); });

    for (const item of items) {
      if (item.kind === "image") {
        const image = await load(item.url);
        if (!image) continue;
        const boxWidth = item.width / 100 * zoneWidth, boxHeight = item.height / 100 * zoneHeight;
        place(item, function () {
          if (item.tint) context.drawImage(silhouette(image, item.tint, boxWidth, boxHeight), -boxWidth / 2, -boxHeight / 2, boxWidth, boxHeight);
          else contain(context, image, -boxWidth / 2, -boxHeight / 2, boxWidth, boxHeight);
        });
      } else {
        const size = item.size_percent / 100 * zoneHeight;
        const font = (item.font_style === "italic" ? "italic " : "") + (item.font_weight === "bold" ? "700 " : "400 ") + size + 'px "' + (item.font_family || "Cairo") + '", sans-serif';
        try { await document.fonts.load(font, item.content); } catch (error) { /* A missing font falls back to sans-serif. */ }
        place(item, function () {
          context.font = font;
          context.fillStyle = item.color || "#0b1f3a";
          context.textBaseline = "middle";
          context.textAlign = item.text_align === "left" ? "left" : item.text_align === "right" ? "right" : "center";
          const maxWidth = item.width / 100 * zoneWidth, lineHeight = size * (item.line_height || 1.2);
          const lines = wrapLines(context, item.content, maxWidth);
          const startX = item.text_align === "left" ? -maxWidth / 2 : item.text_align === "right" ? maxWidth / 2 : 0;
          lines.forEach(function (line, index) { context.fillText(line, startX, (index - (lines.length - 1) / 2) * lineHeight); });
        });
      }
    }

    context.restore();
    return canvas.toDataURL("image/png");
  }

  window.PalPrintComposite = { render: render };
})(window, document);
