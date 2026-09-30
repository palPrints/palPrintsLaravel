/*
 * CanvasHistoryManager
 * Reusable undo/redo history for Fabric.js canvases.
 *
 * Browser usage:
 *   <script src="assets/js/pages/canvas-history-manager.js"></script>
 *   const history = new CanvasHistoryManager(fabricCanvas);
 *
 * CommonJS usage:
 *   const CanvasHistoryManager = require('./assets/js/pages/canvas-history-manager.js');
 */
(function attachCanvasHistoryManager(root, factory) {
    const CanvasHistoryManager = factory(root);

    if (typeof module === "object" && module.exports) {
        module.exports = CanvasHistoryManager;
    } else {
        root.CanvasHistoryManager = CanvasHistoryManager;
    }
})(typeof globalThis !== "undefined" ? globalThis : window, function createCanvasHistoryManager(root) {
    "use strict";

    class CanvasHistoryManager {
        /**
         * @param {object} canvas A Fabric.js Canvas instance.
         * @param {object} [options]
         * @param {number} [options.maxHistory=100] Maximum snapshots kept in memory.
         * @param {number} [options.textDebounceMs=140] Debounce for text:changed events.
         * @param {string[]|false} [options.events] Fabric events that create snapshots.
         * @param {string[]} [options.propertiesToInclude] Extra serialization properties.
         * @param {(canvas: object) => object} [options.serialize] Custom serializer.
         * @param {Document|HTMLElement|false} [options.keyboardTarget=document] Shortcut target.
         * @param {number|string} [options.fabricVersion] Set to 5 when Fabric is imported
         * without a global `fabric.version` and uses callback-based loadFromJSON.
         * @param {(state: object) => void} [options.onChange] History-state callback.
         * @param {(error: Error) => void} [options.onError] Error callback.
         */
        constructor(canvas, options = {}) {
            if (!canvas || typeof canvas.on !== "function" || typeof canvas.toJSON !== "function" || typeof canvas.loadFromJSON !== "function") {
                throw new TypeError("CanvasHistoryManager requires a valid Fabric.js canvas instance.");
            }

            this.canvas = canvas;
            this.options = {
                maxHistory: 100,
                textDebounceMs: 140,
                events: ["object:added", "object:removed", "object:modified", "text:changed"],
                propertiesToInclude: [],
                serialize: null,
                keyboardTarget: typeof document !== "undefined" ? document : false,
                fabricVersion: null,
                onChange: null,
                onError: null,
                ...options
            };

            this.options.maxHistory = Math.max(2, Number(this.options.maxHistory) || 100);
            this.options.textDebounceMs = Math.max(0, Number(this.options.textDebounceMs) || 0);

            this.history = [];
            this.historyIndex = -1;
            this.isRespondingToHistory = false;
            this.isDestroyed = false;

            this._captureTimer = null;
            this._fabricBindings = [];
            this._boundKeydown = this._handleKeydown.bind(this);
            this._boundActivate = this.activate.bind(this);

            CanvasHistoryManager._instances.add(this);
            if (!CanvasHistoryManager.activeInstance) CanvasHistoryManager.activeInstance = this;

            this.capture({ force: true, reason: "initial" });
            this._bindFabricEvents();
            this._bindKeyboard();
        }

        get canUndo() {
            return !this.isDestroyed && !this.isRespondingToHistory && this.historyIndex > 0;
        }

        get canRedo() {
            return !this.isDestroyed && !this.isRespondingToHistory && this.historyIndex >= 0 && this.historyIndex < this.history.length - 1;
        }

        get length() {
            return this.history.length;
        }

        activate() {
            if (!this.isDestroyed) CanvasHistoryManager.activeInstance = this;
        }

        /** Save the current canvas as a new history entry. */
        capture({ force = false, reason = "manual" } = {}) {
            if (this.isDestroyed || this.isRespondingToHistory) return false;

            let snapshot;
            try {
                snapshot = this._serialize();
            } catch (error) {
                this._reportError(error);
                return false;
            }

            if (!force && snapshot === this.history[this.historyIndex]) return false;

            // A new edit after undo creates a new branch. Everything ahead is redo
            // history and must be discarded.
            if (this.historyIndex < this.history.length - 1) {
                this.history.splice(this.historyIndex + 1);
            }

            this.history.push(snapshot);
            if (this.history.length > this.options.maxHistory) {
                this.history.splice(0, this.history.length - this.options.maxHistory);
            }

            this.historyIndex = this.history.length - 1;
            this._notify(reason);
            return true;
        }

        async undo() {
            this._flushPendingCapture();
            if (!this.canUndo) return false;
            return this._goTo(this.historyIndex - 1, "undo");
        }

        async redo() {
            this._flushPendingCapture();
            if (!this.canRedo) return false;
            return this._goTo(this.historyIndex + 1, "redo");
        }

        /** Replace the stack with the canvas's current state. */
        reset() {
            if (this.isDestroyed) return;
            this._clearCaptureTimer();
            this.history = [];
            this.historyIndex = -1;
            this.capture({ force: true, reason: "reset" });
        }

        getState(reason = "state") {
            return {
                reason,
                canUndo: this.canUndo,
                canRedo: this.canRedo,
                index: this.historyIndex,
                length: this.history.length,
                isRespondingToHistory: this.isRespondingToHistory,
                manager: this
            };
        }

        destroy() {
            if (this.isDestroyed) return;
            this._clearCaptureTimer();
            this._unbindFabricEvents();

            const keyboardTarget = this.options.keyboardTarget;
            if (keyboardTarget && typeof keyboardTarget.removeEventListener === "function") {
                keyboardTarget.removeEventListener("keydown", this._boundKeydown, true);
            }

            this.isDestroyed = true;
            CanvasHistoryManager._instances.delete(this);
            if (CanvasHistoryManager.activeInstance === this) {
                CanvasHistoryManager.activeInstance = CanvasHistoryManager._instances.values().next().value || null;
            }
        }

        _serialize() {
            const serializer = this.options.serialize;
            const value = typeof serializer === "function"
                ? serializer(this.canvas)
                : this.options.propertiesToInclude.length && typeof this.canvas.toObject === "function"
                    ? this.canvas.toObject(this.options.propertiesToInclude)
                    : this.canvas.toJSON();
            return JSON.stringify(value);
        }

        _bindFabricEvents() {
            const events = this.options.events === false ? [] : this.options.events;

            events.forEach(eventName => {
                const handler = () => {
                    if (this.isRespondingToHistory || this.isDestroyed) return;
                    if (eventName === "text:changed" && this.options.textDebounceMs > 0) {
                        this._scheduleCapture(eventName);
                    } else {
                        this._clearCaptureTimer();
                        this.capture({ reason: eventName });
                    }
                };

                const disposer = this.canvas.on(eventName, handler);
                this._fabricBindings.push({ eventName, handler, disposer: typeof disposer === "function" ? disposer : null });
            });

            const activationDisposer = this.canvas.on("mouse:down", this._boundActivate);
            this._fabricBindings.push({
                eventName: "mouse:down",
                handler: this._boundActivate,
                disposer: typeof activationDisposer === "function" ? activationDisposer : null
            });
        }

        _unbindFabricEvents() {
            this._fabricBindings.forEach(binding => {
                if (binding.disposer) binding.disposer();
                else if (typeof this.canvas.off === "function") this.canvas.off(binding.eventName, binding.handler);
            });
            this._fabricBindings = [];
        }

        _bindKeyboard() {
            const keyboardTarget = this.options.keyboardTarget;
            if (keyboardTarget && typeof keyboardTarget.addEventListener === "function") {
                keyboardTarget.addEventListener("keydown", this._boundKeydown, true);
            }
        }

        _handleKeydown(event) {
            if (this.isDestroyed || this.isRespondingToHistory || CanvasHistoryManager.activeInstance !== this) return;
            if (event.defaultPrevented || event.altKey || this._isEditableTarget(event.target)) return;

            const modifier = event.ctrlKey || event.metaKey;
            if (!modifier) return;

            const key = String(event.key || "").toLowerCase();
            const code = String(event.code || "").toLowerCase();
            const isZ = key === "z" || code === "keyz";
            const isY = key === "y" || code === "keyy";
            const wantsRedo = isY || (isZ && event.shiftKey);
            const wantsUndo = isZ && !event.shiftKey;

            if (wantsUndo && (this.canUndo || this._captureTimer !== null)) {
                event.preventDefault();
                void this.undo();
            } else if (wantsRedo && this.canRedo) {
                event.preventDefault();
                void this.redo();
            }
        }

        _isEditableTarget(target) {
            const activeObject = typeof this.canvas.getActiveObject === "function" ? this.canvas.getActiveObject() : null;
            if (activeObject?.hiddenTextarea && target === activeObject.hiddenTextarea) return false;
            if (!target || typeof target.closest !== "function") return false;
            return Boolean(target.closest("input, textarea, select, [contenteditable='true'], [contenteditable='']"));
        }

        _scheduleCapture(reason) {
            this._clearCaptureTimer();
            this._captureTimer = root.setTimeout(() => {
                this._captureTimer = null;
                this.capture({ reason });
            }, this.options.textDebounceMs);
        }

        _flushPendingCapture() {
            if (this._captureTimer === null) return;
            this._clearCaptureTimer();
            this.capture({ reason: "text:changed" });
        }

        _clearCaptureTimer() {
            if (this._captureTimer !== null) {
                root.clearTimeout(this._captureTimer);
                this._captureTimer = null;
            }
        }

        async _goTo(targetIndex, reason) {
            if (this.isRespondingToHistory || targetIndex < 0 || targetIndex >= this.history.length) return false;

            const previousIndex = this.historyIndex;
            this.isRespondingToHistory = true;
            this._notify(`${reason}:start`);

            try {
                if (typeof this.canvas.discardActiveObject === "function") this.canvas.discardActiveObject();
                await this._loadSnapshot(this.history[targetIndex]);
                this.historyIndex = targetIndex;

                if (typeof this.canvas.requestRenderAll === "function") this.canvas.requestRenderAll();
                else if (typeof this.canvas.renderAll === "function") this.canvas.renderAll();

                return true;
            } catch (error) {
                this.historyIndex = previousIndex;
                this._reportError(error);
                return false;
            } finally {
                this.isRespondingToHistory = false;
                this._notify(reason);
            }
        }

        async _loadSnapshot(snapshot) {
            const serializedState = JSON.parse(snapshot);
            const fabricMajor = this._getFabricMajorVersion();

            if (fabricMajor > 0 && fabricMajor < 6) {
                await new Promise((resolve, reject) => {
                    try {
                        this.canvas.loadFromJSON(serializedState, () => resolve(this.canvas));
                    } catch (error) {
                        reject(error);
                    }
                });
                return;
            }

            const result = this.canvas.loadFromJSON(serializedState);
            if (result && typeof result.then === "function") {
                await result;
                return;
            }

            // Safe fallback for non-global/custom Fabric 5 builds. Supplying
            // `fabricVersion: 5` is recommended when images load asynchronously.
            await new Promise(resolve => {
                const schedule = typeof root.requestAnimationFrame === "function"
                    ? root.requestAnimationFrame.bind(root)
                    : callback => root.setTimeout(callback, 0);
                schedule(() => schedule(resolve));
            });
        }

        _getFabricMajorVersion() {
            const suppliedVersion = this.options.fabricVersion;
            const detectedVersion = suppliedVersion
                || root.fabric?.version
                || this.canvas.version
                || this.canvas.constructor?.version
                || "";
            return Number.parseInt(String(detectedVersion).split(".")[0], 10) || 0;
        }

        _notify(reason) {
            const state = this.getState(reason);

            if (typeof this.options.onChange === "function") {
                try { this.options.onChange(state); }
                catch (error) { this._reportError(error); }
            }

            if (typeof this.canvas.fire === "function") {
                this.canvas.fire("history:changed", state);
            }
        }

        _reportError(error) {
            const normalizedError = error instanceof Error ? error : new Error(String(error));
            if (typeof this.options.onError === "function") this.options.onError(normalizedError);
            else if (root.console?.error) root.console.error("CanvasHistoryManager:", normalizedError);
        }
    }

    CanvasHistoryManager.activeInstance = null;
    CanvasHistoryManager._instances = new Set();

    return CanvasHistoryManager;
});
