(function () {
  "use strict";

  /**
   * Builds the payload the product-preview page reads from sessionStorage
   * ("palprintsCustomerPreview"). Shared by the t-shirt, hoodie and mug pages,
   * which only differ in the product code, the print areas, the tint applied to
   * a color and the size that is pre-selected.
   *
   * config: { code, product, options, designName, printAreas, toneClass(colorId),
   *           defaultSizeId, fallbackSizes }
   *  - product: the published design card ({ id, title, price, designer, image })
   *  - options: { colors, sizes } from the database (window.palPrintsCustomerAssets.productOptions)
   */
  function build(config) {
    // A design picked from the store replaces whatever the design studio left behind;
    // otherwise the preview would keep showing the studio design (and fail to add to cart).
    try { sessionStorage.removeItem("palprintsStudioWorkflowContext"); } catch (_) { /* optional */ }
    const product = config.product;
    const options = config.options || {};
    const toneClass = config.toneClass || function () { return ""; };

    const colors = (options.colors || []).map(function (color) {
      return { id: color.id, name: color.name, value: color.value, image: product.image, toneClass: toneClass(color.id) };
    });
    const sizes = (options.sizes && options.sizes.length) ? options.sizes : config.fallbackSizes;
    const colorId = colors.length ? colors[0].id : "default";
    const sizeId = (sizes.find(function (size) { return size.id === config.defaultSizeId; }) || sizes[0]).id;
    // Print areas come from the database; the config only supplies where the design sits on the picture.
    const fallbackPlacement = config.printAreas[0] ? config.printAreas[0].placement : { top: 20, left: 20, width: 60, height: 60 };
    const dbAreas = Array.isArray(options.printAreas) ? options.printAreas : [];
    const printAreas = dbAreas.length ? dbAreas.map(function (area) {
      const known = config.printAreas.find(function (item) { return item.id === area.id; });
      return { id: area.id, name: area.name, image: product.image, fee: 0, placement: known ? known.placement : fallbackPlacement };
    }) : config.printAreas;
    const areaIds = printAreas.slice(0, 1).map(function (area) { return area.id; });

    return {
      version: 2,
      product: {
        id: product.id,
        code: config.code,
        name: product.title,
        sellingPrice: product.price,
        currency: "ILS",
        colors: colors.length ? colors : [{ id: "default", name: "الأساسي", value: "#dfe8f3", image: product.image, toneClass: "" }],
        sizes: sizes,
        printAreas: printAreas
      },
      design: {
        id: product.id,
        name: config.designName,
        designerName: product.designer,
        preview: { images: [], texts: [], icons: [] }
      },
      selection: {
        colorId: colorId,
        sizeId: sizeId,
        quantity: 1,
        printAreaIds: areaIds,
        defaultItem: { colorId: colorId, sizeId: sizeId, printAreaIds: areaIds },
        items: [{ colorId: colorId, sizeId: sizeId, printAreaIds: areaIds }],
        activeItemIndex: 0
      },
      customerWarnings: []
    };
  }

  window.PalPrintPreview = { build: build };
})();
