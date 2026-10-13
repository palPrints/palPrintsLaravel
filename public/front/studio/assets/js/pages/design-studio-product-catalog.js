(function publishStudioProductCatalog(window) {
  "use strict";

  const sizes = (...ids) => ids.map(id => ({ id, name: id === "standard" ? "قياسي" : id }));
  const area = (id, name, role, icon, mockup, printZone, visibleBounds) => ({
    id, name, role, icon, image: mockup, mockup, printZone, ...(visibleBounds ? { visibleBounds } : {})
  });
  const product = config => {
    const printAreas = config.printAreas.map(item => ({ ...item, printZone: { ...item.printZone } }));
    return {
      ...config,
      printAreas,
      editor: {
        defaultAreaId: config.defaultAreaId,
        printAreas: printAreas.map(item => ({ ...item, printZone: { ...item.printZone } }))
      }
    };
  };

  const rightSleeve = "assets/images/design-studio/garments/standard-tshirt/white-right-sleeve.png";
  const leftSleeve = "assets/images/design-studio/garments/standard-tshirt/white-left-sleeve.png";
  const garmentMockup = (garment, color, view) => `assets/images/design-studio/garments/${garment}/${color}-${view}.png`;
  const bodyZone = { leftPct: 31, topPct: 28, widthPct: 38, heightPct: 42, widthCm: 21, heightCm: 29.7,
    physicalFormat: "A4", physicalDimensionsStatus: "provisional-maximum", physicalFitStatus: "unverified" };
  const rightSleeveZone = { leftPct: 42.5, topPct: 27.8, widthPct: 13.5, heightPct: 12.15, widthCm: 10, heightCm: 12 };
  // The left source garment has a smaller visible pixel envelope, so its normalized zone is
  // compensated around the same sleeve center to render at the same size as the right zone.
  const leftSleeveZone = { leftPct: 46.1, topPct: 29.2, widthPct: 12.4, heightPct: 11.16, widthCm: 10, heightCm: 12 };
  const standardTshirtFront = garmentMockup("standard-tshirt", "white", "front");
  const standardTshirtBack = garmentMockup("standard-tshirt", "white", "back");
  const hoodieFront = garmentMockup("hoodie", "white", "front-stringless");
  const hoodieBack = garmentMockup("hoodie", "white", "back");
  // Shared alpha envelope measured across all four 1086 × 1448 stringless fronts.
  // Keeping one envelope prevents color-specific fit changes from moving or resizing the A4 zone.
  const hoodieFrontVisibleBounds = { left: 48 / 1086, top: 150 / 1448, right: 1070 / 1086, bottom: 1330 / 1448 };

  const products = [
    product({
      id: "product-001", categoryId: "tshirts", name: "تي شيرت كلاسيكي", studioTitle: "تيشيرت Unisex قطن كلاسيكي",
      description: "تي شيرت كلاسيكي عالي الجودة 100% قطن", price: 29, defaultAreaId: "front", defaultColor: "white",
      colors: [
        { id: "white", name: "أبيض", value: "#ffffff", image: garmentMockup("standard-tshirt", "white", "front"), areaMockups: { front: garmentMockup("standard-tshirt", "white", "front"), back: garmentMockup("standard-tshirt", "white", "back") } },
        { id: "black", name: "أسود", value: "#171717", image: garmentMockup("standard-tshirt", "black", "front"), areaMockups: { front: garmentMockup("standard-tshirt", "black", "front"), back: garmentMockup("standard-tshirt", "black", "back") } },
        { id: "navy", name: "كحلي", value: "#172238", image: garmentMockup("standard-tshirt", "navy", "front"), areaMockups: { front: garmentMockup("standard-tshirt", "navy", "front"), back: garmentMockup("standard-tshirt", "navy", "back") } },
        { id: "red", name: "أحمر", value: "#A9272D", image: garmentMockup("standard-tshirt", "red", "front"), areaMockups: { front: garmentMockup("standard-tshirt", "red", "front"), back: garmentMockup("standard-tshirt", "red", "back") } }
      ],
      sizes: sizes("S", "M", "L", "XL", "XXL"),
      printAreas: [
        area("front", "الأمام", "front", "bi bi-person-standing", standardTshirtFront, bodyZone),
        area("back", "الخلف", "back", "bi bi-person-standing", standardTshirtBack, bodyZone),
        area("right-sleeve", "الكم الأيمن", "right-sleeve", "bi bi-arrow-right", rightSleeve, rightSleeveZone),
        area("left-sleeve", "الكم الأيسر", "left-sleeve", "bi bi-arrow-left", leftSleeve, leftSleeveZone)
      ],
      thumbnail: standardTshirtFront
    }),
    product({
      id: "product-002", categoryId: "hoodies", name: "هودي بسيط", studioTitle: "هودي Unisex قطني",
      description: "هودي مريح للاستخدام اليومي", price: 79, defaultAreaId: "front", defaultColor: "white",
      colors: [
        { id: "white", name: "أبيض", value: "#ffffff", image: garmentMockup("hoodie", "white", "front-stringless"), areaMockups: { front: garmentMockup("hoodie", "white", "front-stringless"), back: garmentMockup("hoodie", "white", "back") } },
        { id: "black", name: "أسود", value: "#171717", image: garmentMockup("hoodie", "black", "front-stringless"), areaMockups: { front: garmentMockup("hoodie", "black", "front-stringless"), back: garmentMockup("hoodie", "black", "back") } },
        { id: "gray", name: "رمادي", value: "#B9B9B9", image: garmentMockup("hoodie", "gray", "front-stringless"), areaMockups: { front: garmentMockup("hoodie", "gray", "front-stringless"), back: garmentMockup("hoodie", "gray", "back") } },
        { id: "navy", name: "كحلي", value: "#172238", image: garmentMockup("hoodie", "navy", "front-stringless"), areaMockups: { front: garmentMockup("hoodie", "navy", "front-stringless"), back: garmentMockup("hoodie", "navy", "back") } }
      ],
      sizes: sizes("S", "M", "L", "XL"),
      printAreas: [
        area("front", "الأمام", "front", "bi bi-person-standing", hoodieFront, { leftPct: 36.5, topPct: 26.2, widthPct: 31, heightPct: 32.88, widthCm: 21, heightCm: 29.7,
          physicalFormat: "A4", physicalDimensionsStatus: "provisional-maximum", physicalFitStatus: "configured-stringless-mockup" }, hoodieFrontVisibleBounds),
        area("back", "الخلف", "back", "bi bi-person-standing", hoodieBack, { leftPct: 32, topPct: 35, widthPct: 36, heightPct: 39, widthCm: 21, heightCm: 29.7,
          physicalFormat: "A4", physicalDimensionsStatus: "provisional-maximum", physicalFitStatus: "unverified" })
      ],
      thumbnail: hoodieFront
    }),
    product({
      id: "product-003", categoryId: "mugs", name: "كوب سيراميك", studioTitle: "كوب سيراميك",
      description: "كوب سيراميك عالي الجودة", price: 19, defaultAreaId: "front", defaultColor: "white",
      colors: [{ id: "white", name: "أبيض", value: "#ffffff", image: "assets/images/mug.png" }], sizes: sizes("standard"),
      printAreas: [area("front", "الواجهة", "primary", "bi bi-cup-hot", "assets/images/mug.png", { leftPct: 24, topPct: 34, widthPct: 40, heightPct: 34, widthCm: 20, heightCm: 9 })],
      thumbnail: "assets/images/mug.png"
    }),
    product({
      id: "product-004", categoryId: "bags", name: "حقيبة قماشية", studioTitle: "حقيبة قماشية قطنية",
      description: "حقيبة قماشية عملية ومتينة", price: 39, defaultAreaId: "front", defaultColor: "white",
      colors: [{ id: "white", name: "أبيض", value: "#ffffff", image: "assets/images/bag.png" }], sizes: sizes("standard"),
      printAreas: [
        area("front", "الأمام", "front", "bi bi-bag", "assets/images/bag.png", { leftPct: 24, topPct: 22, widthPct: 52, heightPct: 56, widthCm: 28, heightCm: 30 }),
        area("back", "الخلف", "back", "bi bi-bag", "assets/images/bag.png", { leftPct: 24, topPct: 22, widthPct: 52, heightPct: 56, widthCm: 28, heightCm: 30 })
      ],
      thumbnail: "assets/images/bag.png"
    }),
    product({
      id: "product-006", categoryId: "caps", name: "قبعة كلاسيكية", studioTitle: "قبعة Unisex كلاسيكية",
      description: "قبعة كلاسيكية قابلة للتعديل ومناسبة للطباعة الأمامية", price: 25, defaultAreaId: "front", defaultColor: "black",
      colors: [
        { id: "black", name: "أسود", value: "#171717", image: "assets/images/products1/cap/cap-black-removebg-preview.png" },
        { id: "navy", name: "كحلي", value: "#1D1E2B", image: "assets/images/products1/cap/cap-navy-removebg-preview.png" },
        { id: "storm", name: "رمادي فاتح", value: "#D3D3D3", image: "assets/images/products1/cap/cap-storm-removebg-preview.png" },
        { id: "walnut", name: "جوزي", value: "#786551", image: "assets/images/products1/cap/cap-wallnut-removebg-preview.png" }
      ],
      sizes: [{ id: "قياسي", name: "قياسي" }],
      printAreas: [area("front", "الواجهة", "primary", "bi bi-bullseye", "assets/images/products1/cap/cap-black-removebg-preview.png", { leftPct: 31, topPct: 29, widthPct: 38, heightPct: 20, widthCm: 18, heightCm: 8 })],
      thumbnail: "assets/images/products1/cap/cap-black-removebg-preview.png"
    })
  ];

  // The garment photos show a colour lit and shaded, so its swatch hex (#172238) is darker than the photo looks. To paint a
  // side that has no photo of that colour (the sleeves) so that it matches the sides that do, take the colour from the
  // photos themselves: the average of the coloured front photo divided by the average of the white front photo, per channel.
  const photoTints = new Map();
  const averageColor = url => new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => {
      try {
        const canvas = document.createElement("canvas"); canvas.width = canvas.height = 96;
        const context = canvas.getContext("2d", { willReadFrequently: true });
        context.drawImage(image, 0, 0, 96, 96);
        const data = context.getImageData(0, 0, 96, 96).data;
        let r = 0, g = 0, b = 0, count = 0;
        for (let i = 0; i < data.length; i += 4) { if (data[i + 3] > 200) { r += data[i]; g += data[i + 1]; b += data[i + 2]; count += 1; } }
        count ? resolve([r / count, g / count, b / count]) : reject(new Error("empty"));
      } catch (error) { reject(error); }
    };
    image.onerror = reject; image.src = url;
  });
  const photoTint = (colorUrl, whiteUrl) => {
    const key = `${colorUrl}|${whiteUrl}`;
    if (!photoTints.has(key)) photoTints.set(key, Promise.all([averageColor(colorUrl), averageColor(whiteUrl)]).then(([c, w]) =>
      "#" + c.map((value, i) => Math.max(0, Math.min(255, Math.round(value / w[i] * 255))).toString(16).padStart(2, "0")).join("")).catch(() => null));
    return photoTints.get(key);
  };

  window.PALPRINTS_PHOTO_TINT = photoTint;
  window.PALPRINTS_PRODUCT_CATALOG = Object.freeze({ products: Object.freeze(products) });
})(window);
