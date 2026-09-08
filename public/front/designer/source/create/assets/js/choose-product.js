/* =============================================================
   PALPRINTS
   CHOOSE PRODUCT PAGE
   Vanilla JavaScript
============================================================= */


/* =============================================================
   DOM ELEMENTS
============================================================= */

const elements = {

    categories:
        document.getElementById("categoriesContainer"),

    productsGrid:
        document.getElementById("productsGrid"),

    selectedProductTitle:
        document.getElementById("selected-product-title"),

    selectedProductDescription:
        document.getElementById("selected-product-description"),

    previewImage:
        document.getElementById("previewImage"),

    previewPlaceholder:
        document.getElementById("previewPlaceholder"),

    productOptions:
        document.getElementById("productOptions"),

    colors:
        document.getElementById("colorsContainer"),

    sizes:
        document.getElementById("sizesContainer"),

    printAreas:
        document.getElementById("printAreasContainer"),

    startDesignButton:
        document.getElementById("startDesignButton"),

    selectionMessage:
        document.getElementById("selectionMessage"),

    liveRegion:
        document.getElementById("liveRegion"),

    backButton:
        document.getElementById("backButton")

};


/* =============================================================
   APPLICATION STATE
============================================================= */

const state = {

    categories: [],

    products: [],

    activeCategory: "all",

    selectedProduct: null,

    selectedColor: null,

    selectedSize: null,

    selectedPrintAreas: []

};


/* =============================================================
   BACKEND DATA

   Provided by the server via window.palPrintsCatalogData
   (see Designer\ProductCatalogController and
   resources/views/designer/designs/create.blade.php).
============================================================= */

const backendResponse = window.palPrintsCatalogData || { categories: [], products: [] };


/* =============================================================
   INITIALIZATION
============================================================= */

function initializePage() {

    state.categories =
        backendResponse.categories;

    state.products =
        backendResponse.products;


    renderCategories();

    renderProducts();

}


/* =============================================================
   RENDER CATEGORIES
============================================================= */

function renderCategories() {

    elements.categories.innerHTML = "";


    state.categories.forEach(category => {

        const button =
            document.createElement("button");


        button.type = "button";

        button.className =
            "category-button";


        button.dataset.categoryId =
            category.id;


        button.textContent =
            category.name;


        button.setAttribute(
            "aria-current",
            category.id === state.activeCategory
                ? "true"
                : "false"
        );


        if (
            category.id ===
            state.activeCategory
        ) {

            button.classList.add("active");

        }


        button.addEventListener(
            "click",
            () => {

                selectCategory(
                    category.id
                );

            }
        );


        elements.categories.appendChild(
            button
        );

    });

}


/* =============================================================
   SELECT CATEGORY
============================================================= */

function selectCategory(categoryId) {

    state.activeCategory =
        categoryId;


    renderCategories();

    renderProducts();

}


/* =============================================================
   GET FILTERED PRODUCTS
============================================================= */

function getFilteredProducts() {

    if (
        state.activeCategory ===
        "all"
    ) {

        return state.products;

    }


    return state.products.filter(
        product =>
            product.categoryId ===
            state.activeCategory
    );

}


/* =============================================================
   RENDER PRODUCTS
============================================================= */

function renderProducts() {

    elements.productsGrid.innerHTML = "";


    const products =
        getFilteredProducts();


    if (!products.length) {

        renderEmptyProducts();

        return;

    }


    products.forEach(product => {

        const card =
            createProductCard(product);


        elements.productsGrid.appendChild(
            card
        );

    });

}


/* =============================================================
   CREATE PRODUCT CARD
============================================================= */

function createProductCard(product) {

    const article =
        document.createElement("article");


    article.className =
        "product-card";


    if (
        state.selectedProduct &&
        state.selectedProduct.id === product.id
    ) {

        article.classList.add(
            "selected"
        );

    }


    article.dataset.productId =
        product.id;


    const button =
        document.createElement("button");


    button.type = "button";

    button.className =
        "product-card-button";


    button.setAttribute(
        "aria-label",
        `بدء تصميم ${product.name}`
    );


    button.addEventListener(
        "click",
        () => {

            selectProduct(
                product.id
            );

        }
    );


    /* -----------------------------------------
       Selected indicator
    ------------------------------------------ */

    const indicator =
        document.createElement("span");


    indicator.className =
        "product-selected-indicator";


    indicator.setAttribute(
        "aria-hidden",
        "true"
    );


    indicator.textContent =
        "✓";


    /* -----------------------------------------
       Image wrapper
    ------------------------------------------ */

    const imageWrapper =
        document.createElement("div");


    imageWrapper.className =
        "product-image-wrapper";


    const image =
        document.createElement("img");


    image.className =
        "product-image";


    image.src =
        product.thumbnail;


    image.alt =
        product.name;


    image.loading =
        "lazy";


    imageWrapper.appendChild(
        image
    );


    /* -----------------------------------------
       Product information
    ------------------------------------------ */

    const information =
        document.createElement("div");


    information.className =
        "product-info";


    const name =
        document.createElement("h3");


    name.className =
        "product-name";


    name.textContent =
        product.name;


    const price =
        document.createElement("p");


    price.className =
        "product-price";


    price.textContent =
        `${product.price.toFixed(2)} رس`;


    information.appendChild(name);

    information.appendChild(price);


    button.appendChild(
        imageWrapper
    );

    button.appendChild(
        information
    );


    article.appendChild(
        indicator
    );

    article.appendChild(
        button
    );


    return article;

}


/* =============================================================
   EMPTY PRODUCTS
============================================================= */

function renderEmptyProducts() {

    const empty =
        document.createElement("div");


    empty.className =
        "empty-products";


    empty.innerHTML = `
        <strong>
            لا توجد منتجات
        </strong>

        <span>
            لا توجد منتجات متاحة في هذه الفئة حاليًا.
        </span>
    `;


    elements.productsGrid.appendChild(
        empty
    );

}


/* =============================================================
   SELECT PRODUCT
============================================================= */

function selectProduct(productId) {

    const product =
        state.products.find(
            item =>
                item.id === productId
        );


    if (!product) {
        return;
    }


    state.selectedProduct =
        product;


    /*
        Reset product-specific state.
    */

    state.selectedColor =
        product.colors.find(
            color =>
                color.id ===
                product.defaultColor
        ) ||
        product.colors[0] ||
        null;


    state.selectedSize =
        product.sizes[0] ||
        null;


    state.selectedPrintAreas =
        product.printAreas
            .filter(
                area => area.id === "front"
            )
            .map(
                area => area.id
            );


    announce(
        `تم اختيار ${product.name}`
    );

    startDesign();

}


/* =============================================================
   RENDER PRODUCT DETAILS
============================================================= */

function renderProductDetails() {

    const product =
        state.selectedProduct;


    if (!product) {

        elements.productOptions.hidden =
            true;

        return;

    }


    elements.productOptions.hidden =
        false;


    elements.selectedProductTitle.textContent =
        product.name;


    elements.selectedProductDescription.textContent =
        product.description;


    renderPreview();

    renderColors();

    renderSizes();

    renderPrintAreas();

    updateStartButton();

}


/* =============================================================
   RENDER PREVIEW
============================================================= */

function renderPreview() {

    const color =
        state.selectedColor;


    if (!color) {

        elements.previewImage.hidden =
            true;

        if (elements.previewPlaceholder) {

            elements.previewPlaceholder.hidden =
                false;

        }

        return;

    }


    elements.previewImage.src =
        color.image;


    elements.previewImage.alt =
        `${state.selectedProduct.name} - ${color.name}`;


    elements.previewImage.hidden =
        false;


    if (elements.previewPlaceholder) {

        elements.previewPlaceholder.hidden =
            true;

    }

}


/* =============================================================
   RENDER COLORS
============================================================= */

function renderColors() {

    elements.colors.innerHTML = "";


    const colors =
        state.selectedProduct.colors;


    colors.forEach(color => {

        const label =
            document.createElement("label");


        label.className =
            "color-option";


        label.title =
            color.name;


        const input =
            document.createElement("input");


        input.type =
            "radio";


        input.name =
            "product-color";


        input.value =
            color.id;


        input.checked =
            state.selectedColor &&
            state.selectedColor.id ===
            color.id;


        input.setAttribute(
            "aria-label",
            color.name
        );


        input.addEventListener(
            "change",
            () => {

                selectColor(
                    color.id
                );

            }
        );


        const circle =
            document.createElement("span");


        circle.className =
            "color-circle";


        circle.style.backgroundColor =
            color.value;


        /*
            Add border for white colors
            so they remain visible.
        */

        if (
            color.value.toLowerCase() ===
            "#ffffff"
        ) {

            circle.style.border =
                "1px solid #d8dbe5";

        }


        label.appendChild(input);

        label.appendChild(circle);


        elements.colors.appendChild(
            label
        );

    });

}


/* =============================================================
   SELECT COLOR
============================================================= */

function selectColor(colorId) {

    const color =
        state.selectedProduct.colors.find(
            item =>
                item.id === colorId
        );


    if (!color) {
        return;
    }


    state.selectedColor =
        color;


    renderPreview();

    renderColors();

    announce(
        `تم اختيار اللون ${color.name}`
    );

}


/* =============================================================
   RENDER SIZES
============================================================= */

function renderSizes() {

    elements.sizes.innerHTML = "";


    state.selectedProduct.sizes.forEach(
        size => {

            const label =
                document.createElement("label");


            label.className =
                "size-option";


            const input =
                document.createElement("input");


            input.type =
                "radio";


            input.name =
                "product-size";


            input.value =
                size.id;


            input.checked =
                state.selectedSize &&
                state.selectedSize.id ===
                size.id;


            input.setAttribute(
                "aria-label",
                `المقاس ${size.name}`
            );


            input.addEventListener(
                "change",
                () => {

                    selectSize(
                        size.id
                    );

                }
            );


            const visualLabel =
                document.createElement("span");


            visualLabel.className =
                "size-label";


            visualLabel.textContent =
                size.name;


            label.appendChild(input);

            label.appendChild(
                visualLabel
            );


            elements.sizes.appendChild(
                label
            );

        }
    );

}


/* =============================================================
   SELECT SIZE
============================================================= */

function selectSize(sizeId) {

    const size =
        state.selectedProduct.sizes.find(
            item =>
                item.id === sizeId
        );


    if (!size) {
        return;
    }


    state.selectedSize =
        size;


    renderSizes();

    updateStartButton();

    announce(
        `تم اختيار المقاس ${size.name}`
    );

}


/* =============================================================
   RENDER PRINTING AREAS
============================================================= */

function renderPrintAreas() {

    elements.printAreas.innerHTML = "";


    /*
        Only areas returned by the Backend
        are rendered.

        Unsupported areas are never displayed.
    */

    state.selectedProduct.printAreas
        .forEach(area => {

            const label =
                document.createElement("label");


            label.className =
                "print-area-option";


            const input =
                document.createElement("input");


            input.type =
                "checkbox";


            input.name =
                "print-area";


            input.value =
                area.id;


            input.checked =
                state.selectedPrintAreas.includes(
                    area.id
                );


            input.setAttribute(
                "aria-label",
                area.name
            );


            input.addEventListener(
                "change",
                () => {

                    togglePrintArea(
                        area.id,
                        input.checked
                    );

                }
            );


            const visualLabel =
                document.createElement("span");


            visualLabel.className =
                "print-area-label";


            const icon =
                area.image
                    ? document.createElement("img")
                    : document.createElement("i");


            icon.className =
                "print-area-icon";


            icon.setAttribute(
                "aria-hidden",
                "true"
            );


            if (area.image) {

                icon.src =
                    area.image;

                icon.alt =
                    "";

                icon.draggable =
                    false;

            } else {

                icon.className =
                    `print-area-icon ${area.icon}`;

            }


            const name =
                document.createElement("span");


            name.textContent =
                area.name;


            visualLabel.appendChild(
                icon
            );


            visualLabel.appendChild(
                name
            );


            label.appendChild(input);

            label.appendChild(
                visualLabel
            );


            elements.printAreas.appendChild(
                label
            );

        });

}


/* =============================================================
   TOGGLE PRINT AREA
============================================================= */

function togglePrintArea(
    areaId,
    checked
) {

    if (checked) {

        if (
            !state.selectedPrintAreas
                .includes(areaId)
        ) {

            state.selectedPrintAreas.push(
                areaId
            );

        }

    } else {

        state.selectedPrintAreas =
            state.selectedPrintAreas.filter(
                id =>
                    id !== areaId
            );

    }


    updateStartButton();

}


/* =============================================================
   VALIDATE SELECTION
============================================================= */

function validateSelection() {

    if (!state.selectedProduct) {

        return {
            valid: false,
            message:
                "يرجى اختيار منتج أولًا."
        };

    }


    if (!state.selectedColor) {

        return {
            valid: false,
            message:
                "يرجى اختيار اللون."
        };

    }


    if (!state.selectedSize) {

        return {
            valid: false,
            message:
                "يرجى اختيار المقاس."
        };

    }


    if (
        state.selectedPrintAreas.length === 0
    ) {

        return {
            valid: false,
            message:
                "يرجى اختيار منطقة طباعة واحدة على الأقل."
        };

    }


    return {
        valid: true,
        message: ""
    };

}


/* =============================================================
   UPDATE START BUTTON
============================================================= */

function updateStartButton() {

    const validation =
        validateSelection();


    elements.startDesignButton.disabled =
        !validation.valid;


    elements.selectionMessage.textContent =
        validation.valid
            ? ""
            : validation.message;

}


/* =============================================================
   CREATE DESIGNER PAYLOAD
============================================================= */

function createDesignerPayload() {

    return {

        productId:
            state.selectedProduct.id,

        colorId:
            state.selectedColor.id,

        sizeId:
            state.selectedSize.id,

        printAreaIds:
            [...state.selectedPrintAreas]

    };

}


/* =============================================================
   START DESIGN
============================================================= */

function startDesign() {

    const validation =
        validateSelection();


    if (!validation.valid) {

        elements.selectionMessage.textContent =
            validation.message;

        announce(
            validation.message
        );

        return;

    }


    const payload =
        createDesignerPayload();


    /*
        This is the object that will be sent
        to the Designer.

        Example:

        {
            productId: "product-001",
            colorId: "black",
            sizeId: "M",
            printAreaIds: [
                "front",
                "back"
            ]
        }
    */


    console.log(
        "Designer payload:",
        payload
    );


    /*
        Later, when Designer is implemented:

        sessionStorage.setItem(
            "palprintsDesignerSelection",
            JSON.stringify(payload)
        );

        Navigate to the Laravel designer editor route.
    */


    sessionStorage.setItem(
        "palprintsDesignerSelection",
        JSON.stringify(payload)
    );


    /*
        Temporary navigation.

        Replace this with the actual
        Designer route.
    */

    window.location.href =
        window.palPrintsCreateRoutes.editor;

}


/* =============================================================
   BACK BUTTON
============================================================= */

function goBack() {

    if (
        window.history.length > 1
    ) {

        window.history.back();

        return;

    }


    /* Future designer dashboard fallback when no previous page exists. */

    window.location.href =
        window.palPrintsCreateRoutes.dashboard;

}


/* =============================================================
   SCREEN READER ANNOUNCEMENT
============================================================= */

function announce(message) {

    elements.liveRegion.textContent =
        "";


    window.setTimeout(
        () => {

            elements.liveRegion.textContent =
                message;

        },
        50
    );

}


/* =============================================================
   EVENTS
============================================================= */

elements.startDesignButton
    .addEventListener(
        "click",
        startDesign
    );


elements.backButton
    .addEventListener(
        "click",
        goBack
    );


/* =============================================================
   START APPLICATION
============================================================= */

initializePage();
