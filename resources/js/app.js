const collectDetectionValues = (items = []) =>
    items
        .map((item) => item?.rawValue ?? item?.text ?? "")
        .filter((value) => typeof value === "string" && value.trim() !== "");

const detectDeviceIdentifiers = async (file) => {
    if (typeof createImageBitmap !== "function") {
        return { raw_text: "", barcode_values: [], detectors: [] };
    }

    const bitmap = await createImageBitmap(file);
    const detectors = [];
    const barcodeValues = [];
    const textValues = [];

    try {
        if ("BarcodeDetector" in window) {
            try {
                const barcodeDetector = new window.BarcodeDetector();
                const barcodeResults = await barcodeDetector.detect(bitmap);
                barcodeValues.push(...collectDetectionValues(barcodeResults));
                detectors.push("barcode");
            } catch (error) {
                console.warn("BarcodeDetector scan failed", error);
            }
        }

        if ("TextDetector" in window) {
            try {
                const textDetector = new window.TextDetector();
                const textResults = await textDetector.detect(bitmap);
                textValues.push(...collectDetectionValues(textResults));
                detectors.push("text");
            } catch (error) {
                console.warn("TextDetector scan failed", error);
            }
        }
    } finally {
        if (typeof bitmap.close === "function") {
            bitmap.close();
        }
    }

    return {
        raw_text: textValues.join("\n"),
        barcode_values: [...new Set(barcodeValues)],
        detectors: [...new Set(detectors)],
    };
};

window.deviceIdentifierScanner = ($wire, sourceField) => ({
    message: "",
    async scan(event) {
        const file = event.target.files?.[0];

        if (!file) {
            this.message = "";
            return;
        }

        if (!("BarcodeDetector" in window) && !("TextDetector" in window)) {
            this.message = "Auto-scan is not supported in this browser. You can still enter IMEI and serial manually.";
            return;
        }

        this.message = "Scanning image for IMEI and serial number...";

        try {
            const payload = await detectDeviceIdentifiers(file);
            payload.source_field = sourceField;
            await $wire.applyDetectedIdentifiers(payload);
            this.message = "Scan finished. Any detected identifiers were pushed into the form.";
        } catch (error) {
            console.error("Device identifier scan failed", error);
            this.message = "Scan failed on this image. You can still continue with manual entry.";
        }
    },
});

window.signaturePadCapture = ($wire, field) => ({
    drawing: false,
    hasStroke: false,
    context: null,
    pointerId: null,
    resizeObserver: null,
    init() {
        this.setupCanvas();
        this.registerEvents();
        this.syncFromWire();

        this.resizeObserver = new ResizeObserver(() => {
            const snapshot = this.hasStroke ? this.$refs.canvas.toDataURL("image/png") : null;
            this.setupCanvas();

            if (snapshot) {
                this.drawSnapshot(snapshot);
            } else {
                this.syncFromWire();
            }
        });

        this.resizeObserver.observe(this.$refs.canvas);
    },
    destroy() {
        this.resizeObserver?.disconnect();
    },
    setupCanvas() {
        const canvas = this.$refs.canvas;
        const ratio = window.devicePixelRatio || 1;
        const width = Math.max(canvas.clientWidth || 1, 1);
        const height = Math.max(canvas.clientHeight || 1, 1);

        canvas.width = Math.floor(width * ratio);
        canvas.height = Math.floor(height * ratio);

        this.context = canvas.getContext("2d");
        this.context.setTransform(1, 0, 0, 1, 0, 0);
        this.context.scale(ratio, ratio);
        this.context.lineCap = "round";
        this.context.lineJoin = "round";
        this.context.lineWidth = 2;
        this.context.strokeStyle = "#1f2937";
        this.context.fillStyle = "#ffffff";
        this.context.fillRect(0, 0, width, height);
    },
    registerEvents() {
        const canvas = this.$refs.canvas;

        canvas.style.touchAction = "none";
        canvas.addEventListener("pointerdown", (event) => this.start(event));
        canvas.addEventListener("pointermove", (event) => this.move(event));
        canvas.addEventListener("pointerup", (event) => this.end(event));
        canvas.addEventListener("pointerleave", (event) => this.end(event));
        canvas.addEventListener("pointercancel", (event) => this.end(event));
    },
    coordinates(event) {
        const bounds = this.$refs.canvas.getBoundingClientRect();

        return {
            x: event.clientX - bounds.left,
            y: event.clientY - bounds.top,
        };
    },
    start(event) {
        event.preventDefault();
        this.pointerId = event.pointerId;
        this.drawing = true;
        this.hasStroke = true;

        const point = this.coordinates(event);
        this.context.beginPath();
        this.context.moveTo(point.x, point.y);
        this.context.lineTo(point.x + 0.01, point.y + 0.01);
        this.context.stroke();
        this.publish();
    },
    move(event) {
        if (!this.drawing || this.pointerId !== event.pointerId) {
            return;
        }

        event.preventDefault();
        const point = this.coordinates(event);
        this.context.lineTo(point.x, point.y);
        this.context.stroke();
    },
    end(event) {
        if (!this.drawing || this.pointerId !== event.pointerId) {
            return;
        }

        event.preventDefault();
        this.drawing = false;
        this.pointerId = null;
        this.publish();
    },
    publish() {
        if (!this.hasStroke) {
            return;
        }

        $wire.set(field, this.$refs.canvas.toDataURL("image/png"));
    },
    clear() {
        this.hasStroke = false;
        this.setupCanvas();
        $wire.clearSignature(field);
    },
    syncFromWire() {
        if (typeof $wire.get !== "function") {
            return;
        }

        const value = $wire.get(field);

        if (typeof value === "string" && value.startsWith("data:image/")) {
            this.drawSnapshot(value);
        }
    },
    drawSnapshot(dataUrl) {
        const image = new Image();

        image.onload = () => {
            const canvas = this.$refs.canvas;
            const width = canvas.clientWidth || canvas.width;
            const height = canvas.clientHeight || canvas.height;

            this.setupCanvas();
            this.context.drawImage(image, 0, 0, width, height);
            this.hasStroke = true;
        };

        image.src = dataUrl;
    },
});
