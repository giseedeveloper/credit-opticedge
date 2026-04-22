const collectDetectionValues = (items = []) =>
    items
        .map((item) => item?.rawValue ?? item?.text ?? "")
        .filter((value) => typeof value === "string" && value.trim() !== "");

const createObjectUrl = (file) => {
    try {
        return URL.createObjectURL(file);
    } catch (_) {
        return null;
    }
};

const detectWithZxing = async (file) => {
    const url = createObjectUrl(file);

    if (!url) {
        return [];
    }

    try {
        const { BrowserMultiFormatReader } = await import("@zxing/browser");
        const reader = new BrowserMultiFormatReader();

        // decodeFromImageUrl throws if nothing found; we treat that as empty.
        const result = await reader.decodeFromImageUrl(url);
        const value = result?.getText?.() ?? "";
        return value && typeof value === "string" ? [value] : [];
    } catch (_) {
        return [];
    } finally {
        URL.revokeObjectURL(url);
    }
};

const detectWithTesseract = async (file) => {
    try {
        const Tesseract = await import("tesseract.js");
        const { data } = await Tesseract.recognize(file, "eng", {
            logger: () => {},
        });
        return typeof data?.text === "string" ? data.text : "";
    } catch (_) {
        return "";
    }
};

const extractEmailFromText = (rawText) => {
    if (typeof rawText !== "string" || rawText.trim() === "") {
        return null;
    }

    const match = rawText.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);
    return match?.[0] ? match[0].toLowerCase() : null;
};

const extractLikelyModelLine = (rawText) => {
    if (typeof rawText !== "string" || rawText.trim() === "") {
        return null;
    }

    // Very lightweight heuristic: pick a line mentioning common handset keywords.
    const lines = rawText
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .slice(0, 80);

    const keywords = [
        "GALAXY",
        "SAMSUNG",
        "INFINIX",
        "TECNO",
        "ITEL",
        "IPHONE",
        "XIAOMI",
        "REDMI",
        "OPPO",
        "VIVO",
        "REALME",
        "NOKIA",
        "HUAWEI",
        "PIXEL",
        "128GB",
        "64GB",
        "256GB",
    ];

    const scored = lines
        .map((line) => {
            const upper = line.toUpperCase();
            const score = keywords.reduce(
                (acc, key) => acc + (upper.includes(key) ? 1 : 0),
                0
            );
            return { line, score };
        })
        .filter((row) => row.score > 0)
        .sort((a, b) => b.score - a.score);

    return scored[0]?.line ?? null;
};

const extractModelCode = (rawText) => {
    if (typeof rawText !== "string" || rawText.trim() === "") {
        return null;
    }

    // Example: SM-A065F/DS
    const match = rawText.match(/\bSM-[A-Z0-9]{3,10}(?:\/[A-Z0-9]{1,6})?\b/i);
    return match?.[0] ? match[0].toUpperCase() : null;
};

const extractRamStorage = (rawText) => {
    if (typeof rawText !== "string" || rawText.trim() === "") {
        return { ram: null, storage: null };
    }

    // Example: 4GB|64GB or 4GB | 64GB
    const match = rawText.match(/(\d{1,2})\s*GB\s*\|\s*(\d{2,4})\s*GB/i);

    if (!match) {
        return { ram: null, storage: null };
    }

    return {
        ram: `${match[1]}GB`,
        storage: `${match[2]}GB`,
    };
};

const extractColor = (rawText) => {
    if (typeof rawText !== "string" || rawText.trim() === "") {
        return null;
    }

    const candidates = [
        "BLACK",
        "WHITE",
        "BLUE",
        "GREEN",
        "RED",
        "SILVER",
        "GOLD",
        "PURPLE",
        "GRAY",
        "GREY",
    ];

    const upper = rawText.toUpperCase();
    const found = candidates.find((c) => upper.includes(c));

    if (!found) {
        return null;
    }

    return found === "GREY" ? "GRAY" : found;
};

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

    // Fallbacks for broader browser support:
    // - ZXing for barcodes
    // - Tesseract.js for OCR
    if (barcodeValues.length === 0) {
        const zxingValues = await detectWithZxing(file);
        if (zxingValues.length > 0) {
            barcodeValues.push(...zxingValues);
            detectors.push("zxing");
        }
    }

    if (textValues.length === 0) {
        const ocrText = await detectWithTesseract(file);
        if (ocrText && typeof ocrText === "string") {
            textValues.push(ocrText);
            detectors.push("tesseract");
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

        this.message = "Scanning image for IMEI and serial number...";

        try {
            const payload = await detectDeviceIdentifiers(file);
            payload.source_field = sourceField;
            payload.detected_email = extractEmailFromText(payload.raw_text);
            payload.detected_model_text = extractLikelyModelLine(payload.raw_text);
            payload.detected_model_code = extractModelCode(payload.raw_text);
            const ramStorage = extractRamStorage(payload.raw_text);
            payload.detected_ram = ramStorage.ram;
            payload.detected_storage = ramStorage.storage;
            payload.detected_color = extractColor(payload.raw_text);
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
