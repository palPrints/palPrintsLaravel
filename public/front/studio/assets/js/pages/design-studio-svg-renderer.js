/* Runtime-only SVG buffers; never persisted in the normalized design document. */
(function publishStudioSvgRenderer(window) {
  "use strict";
  const sources = new Map(), reviews = new Map();
  const INITIAL_LONG_EDGE = 1024, MAX_EDGE = 4096, MAX_PIXELS = 8000000;
  function removeDevelopmentReload(text) {
    // Live Server appends executable reload code to SVG responses. Remove its
    // marked trailing block as text BEFORE XML validation; never execute it or
    // carry it into the rendered SVG Blob. Other scripts remain rejected.
    return text.replace(/<!--\s*Code injected by live-server\s*-->\s*<script\s*>([\s\S]*?)<\/script>\s*(?=<\/svg\s*>)/gi, (block, script) => {
      const knownReload = script.includes('function refreshCSS()')
        && script.includes('new WebSocket(address)')
        && script.includes('IsThisFirstTime_Log_From_LiveServer');
      return knownReload ? '' : block;
    });
  }
  async function sourceFor(record) {
    if (!sources.has(record.id)) {
      const pending = (async () => {
        const response = await fetch(record.assetPath);
        if (!response.ok && response.status !== 0) throw new Error("SVG download failed");
        const text = removeDevelopmentReload(await response.text());
        const doc = new DOMParser().parseFromString(text, "image/svg+xml"), root = doc.documentElement;
        if (doc.querySelector("parsererror") || root.localName !== "svg") throw new Error("Invalid SVG");
        if (doc.getElementsByTagNameNS("*", "image").length) {
          reviews.set(record.id, { scalable: false, type: "raster-wrapped", status: "review-required" });
          throw new Error("SVG_RASTER_CONTENT");
        }
        if (doc.querySelector("script,foreignObject")) throw new Error("Unsafe SVG");
        for (const el of doc.querySelectorAll("*")) for (const attr of el.attributes) {
          if (/^on/i.test(attr.name) || /(?:https?:|data:|javascript:|@import)/i.test(attr.value)
            && attr.name !== "xmlns" && !attr.name.startsWith("xmlns:")) throw new Error("Unsafe SVG resource");
        }
        const view = root.getAttribute("viewBox")?.trim().split(/[\s,]+/).map(Number);
        if (!view || view.length !== 4 || !view.every(Number.isFinite) || view[2] <= 0 || view[3] <= 0) throw new Error("Invalid SVG viewBox");
        reviews.set(record.id, { scalable: true, type: "vector", status: "validated" });
        return { text, width: view[2], height: view[3] };
      })();
      sources.set(record.id, pending); pending.catch(() => sources.delete(record.id));
    }
    return sources.get(record.id);
  }
  async function elementAt(source, width, height) {
    const doc = new DOMParser().parseFromString(source.text, "image/svg+xml");
    doc.documentElement.setAttribute("width", String(width)); doc.documentElement.setAttribute("height", String(height));
    const url = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(doc)], { type: "image/svg+xml" }));
    try {
      const el = new Image();
      await new Promise((resolve, reject) => { el.onload = resolve; el.onerror = reject; el.src = url; });
      return el;
    } finally { URL.revokeObjectURL(url); }
  }
  async function create(record, options = {}) {
    const source = await sourceFor(record), ImageClass = window.fabric.FabricImage || window.fabric.Image;
    // Build a sufficiently detailed first buffer before insertion. This avoids
    // briefly enlarging a tiny intrinsic viewBox while the adaptive refresh is
    // still pending. Later zooms continue to regenerate from the vector source.
    const scale = INITIAL_LONG_EDGE / Math.max(source.width, source.height);
    const el = await elementAt(source, Math.max(1, Math.ceil(source.width * scale)), Math.max(1, Math.ceil(source.height * scale)));
    const object = new ImageClass(el, { ...options, width: source.width, height: source.height });
    object.studioSvgSource = source;
    object.studioSvgPixels = { width: el.naturalWidth || el.width, height: el.naturalHeight || el.height };
    object.studioSourceQuality = "vector";
    // FabricImage crop/filter scaling assumes source pixels equal logical size.
    // Our SVG buffer has independent pixel dimensions; draw its full contents
    // inside the unchanged logical rectangle, including any recolor filter.
    object._renderFill = function renderSvgBuffer(ctx) {
      const drawable = this.getElement();
      if (drawable) ctx.drawImage(drawable, -this.width / 2, -this.height / 2, this.width, this.height);
    };
    // The SVG source/filter buffer is already cached. Avoid a second bitmap
    // cache for these SVG images only; all other Fabric caching is unchanged.
    object.set({ objectCaching: false, noScaleCache: false });
    return object;
  }
  function refresh(object, canvas) {
    if (!object.studioSvgSource || object.studioSvgPending) return;
    const scaling = object.getTotalObjectScaling();
    let width = Math.max(1, Math.ceil(object.width * Math.abs(scaling.x) * 1.25));
    let height = Math.max(1, Math.ceil(object.height * Math.abs(scaling.y) * 1.25));
    const limit = Math.min(1, MAX_EDGE / width, MAX_EDGE / height, Math.sqrt(MAX_PIXELS / (width * height)));
    object.studioSvgResolutionLimited = limit < 1;
    width = Math.max(1, Math.ceil(width * limit)); height = Math.max(1, Math.ceil(height * limit));
    if (object.studioSvgPixels.width >= width && object.studioSvgPixels.height >= height) return;
    object.studioSvgPending = elementAt(object.studioSvgSource, width, height).then(el => {
      const logicalWidth = object.width, logicalHeight = object.height;
      // setElement reapplies one-color filters to the new high-resolution SVG.
      object.setElement(el, { width: logicalWidth, height: logicalHeight });
      object.set({ width: logicalWidth, height: logicalHeight, dirty: true });
      object.studioSvgPixels = { width, height };
    }).catch(error => console.error("SVG resolution refresh failed", error)).finally(() => {
      object.studioSvgPending = null;
      if (object.canvas === canvas) canvas.requestRenderAll();
    });
    return object.studioSvgPending;
  }
  window.PALPRINTS_STUDIO_SVG = Object.freeze({ create, refresh, reviews });
})(window);
