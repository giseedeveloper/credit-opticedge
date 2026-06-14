from __future__ import annotations

import asyncio
import logging
import os
import sys
from contextlib import asynccontextmanager
from typing import Any, List, Optional, Tuple

import cv2
import numpy as np
from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse
from insightface.app import FaceAnalysis


def _match_log_line(message: str) -> None:
    """
    Uvicorn / Docker only show our custom lines reliably on stderr (unbuffered).
    The named logger 'face_match' often has no handler under uvicorn, so INFO was invisible.
    """
    print(message, file=sys.stderr, flush=True)


_face_app: Optional[FaceAnalysis] = None
_model_warmup_complete = False


def _env_float(name: str, default: float) -> float:
    raw = os.getenv(name)
    if raw is None:
        return default
    try:
        return float(raw)
    except ValueError:
        return default


def _env_int(name: str, default: int) -> int:
    raw = os.getenv(name)
    if raw is None:
        return default
    try:
        return int(raw)
    except ValueError:
        return default


# Headshot / live selfie — stricter quality for 75% pass target.
PASS_THRESHOLD = _env_float("FACE_MATCH_PASS_THRESHOLD", 0.75)
REVIEW_THRESHOLD = _env_float("FACE_MATCH_REVIEW_THRESHOLD", 0.65)
MIN_FACE_AREA_RATIO = _env_float("FACE_MATCH_MIN_FACE_AREA_RATIO", 0.045)
MIN_SHARPNESS = _env_float("FACE_MATCH_MIN_SHARPNESS", 35.0)
MIN_BRIGHTNESS = _env_float("FACE_MATCH_MIN_BRIGHTNESS", 35.0)
MAX_BRIGHTNESS = _env_float("FACE_MATCH_MAX_BRIGHTNESS", 225.0)

# ID document (full card scan) — relaxed + ROI pipeline (NIDA-style prints, hologram noise).
ID_MIN_FACE_AREA_RATIO = _env_float("FACE_MATCH_ID_MIN_FACE_AREA_RATIO", 0.012)
ID_MIN_SHARPNESS = _env_float("FACE_MATCH_ID_MIN_SHARPNESS", 18.0)
ID_MIN_BRIGHTNESS = _env_float("FACE_MATCH_ID_MIN_BRIGHTNESS", 22.0)
ID_MAX_BRIGHTNESS = _env_float("FACE_MATCH_ID_MAX_BRIGHTNESS", 238.0)
# If max(h, w) is below this, upscale before detection (helps tiny portrait on card).
ID_UPSCALE_MIN_EDGE = _env_int("FACE_MATCH_ID_UPSCALE_MIN_EDGE", 960)


def _get_face_app() -> FaceAnalysis:
    global _face_app
    if _face_app is None:
        # CPU-only. InsightFace will download model weights on first run into ~/.insightface
        _face_app = FaceAnalysis(name="buffalo_l", providers=["CPUExecutionProvider"])
        _face_app.prepare(ctx_id=0, det_size=(640, 640))
    return _face_app


def _warmup_model() -> None:
    global _model_warmup_complete
    _get_face_app()
    _model_warmup_complete = True
    _match_log_line("face_match model warmup complete")


@asynccontextmanager
async def lifespan(_: FastAPI):
    loop = asyncio.get_running_loop()
    await loop.run_in_executor(None, _warmup_model)
    yield


app = FastAPI(title="OpticEdge Face Match", version="1.0.0", lifespan=lifespan)


@app.get("/health")
async def health():
    """Lightweight liveness check for Docker / load balancers (no model load)."""
    return {"status": "ok"}


@app.get("/health/ready")
async def health_ready():
    """Readiness: model loaded and ready to score matches."""
    return {
        "status": "ok" if _model_warmup_complete else "warming",
        "model_loaded": _model_warmup_complete,
    }


def _read_image(upload: UploadFile) -> Optional[np.ndarray]:
    raw = upload.file.read()
    if not raw:
        return None
    arr = np.frombuffer(raw, dtype=np.uint8)
    img = cv2.imdecode(arr, cv2.IMREAD_COLOR)
    return img


def _face_area_ratio(face: Any, img: np.ndarray) -> float:
    x1, y1, x2, y2 = face.bbox
    face_area = max(0.0, (x2 - x1)) * max(0.0, (y2 - y1))
    frame_area = float(img.shape[0] * img.shape[1])
    if frame_area <= 0:
        return 0.0
    return float(face_area / frame_area)


def _crop_from_bbox(img: np.ndarray, face: Any) -> Optional[np.ndarray]:
    x1, y1, x2, y2 = face.bbox
    h, w = img.shape[:2]
    x1 = max(0, int(x1))
    y1 = max(0, int(y1))
    x2 = min(w, int(x2))
    y2 = min(h, int(y2))
    if x2 <= x1 or y2 <= y1:
        return None
    return img[y1:y2, x1:x2]


def _face_quality_params(role: str) -> tuple[float, float, float, float]:
    if role == "id_front":
        return (
            ID_MIN_FACE_AREA_RATIO,
            ID_MIN_SHARPNESS,
            ID_MIN_BRIGHTNESS,
            ID_MAX_BRIGHTNESS,
        )
    return (
        MIN_FACE_AREA_RATIO,
        MIN_SHARPNESS,
        MIN_BRIGHTNESS,
        MAX_BRIGHTNESS,
    )


def _quality_reason(img: np.ndarray, face: Any, role: str) -> Optional[str]:
    min_ratio, min_sharp, min_bright, max_bright = _face_quality_params(role)
    ratio = _face_area_ratio(face, img)
    if ratio < min_ratio:
        return "face_too_small"

    crop = _crop_from_bbox(img, face)
    if crop is None or crop.size == 0:
        return "invalid_face_crop"

    gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
    sharpness = float(cv2.Laplacian(gray, cv2.CV_64F).var())
    if sharpness < min_sharp:
        return "image_blurry"

    brightness = float(gray.mean())
    if brightness < min_bright:
        return "image_too_dark"
    if brightness > max_bright:
        return "image_too_bright"

    return None


def _face_rank(face: Any, img: np.ndarray) -> float:
    """Prefer large, high-confidence detections (stable across ID vs selfie)."""
    area = _face_area_ratio(face, img)
    det = float(getattr(face, "det_score", 0.0) or 0.0)
    if det <= 0.0:
        det = 0.75
    return area * (0.25 + det)


def _roi_xywh(img: np.ndarray, fx0: float, fy0: float, fx1: float, fy1: float) -> np.ndarray:
    """Crop by fractions of width/height (0..1). Clamped to valid bounds."""
    h, w = img.shape[:2]
    x0 = int(max(0, min(w - 1, round(fx0 * w))))
    x1 = int(max(x0 + 1, min(w, round(fx1 * w))))
    y0 = int(max(0, min(h - 1, round(fy0 * h))))
    y1 = int(max(y0 + 1, min(h, round(fy1 * h))))
    return img[y0:y1, x0:x1]


def _enhance_id_image(img: np.ndarray) -> np.ndarray:
    """CLAHE on luminance + mild unsharp mask — typical for document scans."""
    if img is None or img.size == 0:
        return img
    lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)
    l_ch, a_ch, b_ch = cv2.split(lab)
    clahe = cv2.createCLAHE(clipLimit=2.2, tileGridSize=(8, 8))
    l2 = clahe.apply(l_ch)
    merged = cv2.merge((l2, a_ch, b_ch))
    out = cv2.cvtColor(merged, cv2.COLOR_LAB2BGR)
    blurred = cv2.GaussianBlur(out, (0, 0), sigmaX=1.0)
    out = cv2.addWeighted(out, 1.28, blurred, -0.28, 0)
    return out


def _maybe_upscale_for_id(img: np.ndarray) -> np.ndarray:
    """Upscale small scans so the portrait occupies more pixels for buffalo_l."""
    if img is None or img.size == 0:
        return img
    h, w = img.shape[:2]
    m = max(h, w)
    if m < ID_UPSCALE_MIN_EDGE and m > 0:
        scale = min(2.4, (ID_UPSCALE_MIN_EDGE / float(m)) * 0.98)
        nw = max(1, int(round(w * scale)))
        nh = max(1, int(round(h * scale)))
        return cv2.resize(img, (nw, nh), interpolation=cv2.INTER_CUBIC)
    return img


def _id_front_candidate_images(id_img: np.ndarray) -> List[Tuple[str, np.ndarray]]:
    """
    Multiple crops — NIDA-style cards usually place portrait on left;
    some layouts use right or centered upper region.
    """
    out: List[Tuple[str, np.ndarray]] = []
    if id_img is None or id_img.size == 0:
        return out

    enhanced = _enhance_id_image(id_img)

    def add(label: str, im: np.ndarray) -> None:
        if im is None or im.size == 0 or im.shape[0] < 48 or im.shape[1] < 48:
            return
        out.append((label, im))

    add("full_enhanced", _maybe_upscale_for_id(enhanced))
    add("full_raw", _maybe_upscale_for_id(np.ascontiguousarray(id_img)))

    # Portrait ROIs on enhanced image (fractions of W x H).
    rois: List[Tuple[str, float, float, float, float]] = [
        ("roi_nida_left", 0.02, 0.10, 0.36, 0.90),
        ("roi_left", 0.02, 0.08, 0.44, 0.82),
        ("roi_right", 0.54, 0.08, 0.99, 0.82),
        ("roi_center", 0.20, 0.10, 0.82, 0.78),
        ("roi_upper", 0.08, 0.04, 0.92, 0.55),
    ]
    for label, fx0, fy0, fx1, fy1 in rois:
        crop = _roi_xywh(enhanced, fx0, fy0, fx1, fy1)
        add(label, _maybe_upscale_for_id(crop))

    return out


def _best_face_embedding_single(
    img: np.ndarray, role: str
) -> Tuple[Optional[np.ndarray], str, float]:
    """
    One forward pass: detect, quality gate, return embedding + rank of chosen face.
    On failure, rank is -1.0.
    """
    faces = _get_face_app().get(img)
    if not faces:
        return None, "no_face_detected", -1.0

    if role == "headshot" and len(faces) > 1:
        return None, "multiple_faces_detected", -1.0

    best = max(faces, key=lambda f: _face_rank(f, img))
    rank = _face_rank(best, img)

    quality_error = _quality_reason(img, best, role)
    if quality_error is not None:
        return None, quality_error, -1.0

    emb = getattr(best, "embedding", None)
    if emb is None:
        return None, "embedding_unavailable", -1.0
    return emb.astype(np.float32), "ok", rank


def _best_face_embedding_id_document(id_img: np.ndarray) -> Tuple[Optional[np.ndarray], str]:
    """
    Try several crops / enhancements and keep the strongest usable embedding.
    Mirrors how production KYC stacks survive noisy ID scans.
    """
    last_reason = "no_face_detected"
    best_emb: Optional[np.ndarray] = None
    best_rank = -1.0

    for _label, cand in _id_front_candidate_images(id_img):
        emb, reason, rank = _best_face_embedding_single(cand, role="id_front")
        if emb is None:
            last_reason = reason
            continue
        if rank > best_rank:
            best_rank = rank
            best_emb = emb
            last_reason = "ok"

    if best_emb is not None:
        return best_emb, "ok"
    return None, last_reason


def _best_face_embedding(img: np.ndarray, role: str) -> Tuple[Optional[np.ndarray], str]:
    if role == "id_front":
        return _best_face_embedding_id_document(img)
    emb, reason, _rank = _best_face_embedding_single(img, role)
    return emb, reason


def _cosine(a: np.ndarray, b: np.ndarray) -> float:
    denom = (np.linalg.norm(a) * np.linalg.norm(b))
    if denom == 0:
        return 0.0
    return float(np.dot(a, b) / denom)


@app.post("/match")
async def match(
    id_front: UploadFile = File(...),
    headshot: UploadFile = File(...),
):
    try:
        id_img = _read_image(id_front)
        hs_img = _read_image(headshot)
        if id_img is None or hs_img is None:
            _match_log_line("face_match match outcome=review score=0.0 reason=invalid_image")
            return JSONResponse(
                status_code=400,
                content={"status": "review", "score": 0.0, "reason": "invalid_image"},
            )

        id_emb, id_reason = _best_face_embedding(id_img, role="id_front")
        if id_emb is None:
            _match_log_line(
                f"face_match match outcome=review score=0.0 reason=id_front:{id_reason}",
            )
            return {"status": "review", "score": 0.0, "reason": f"id_front:{id_reason}"}

        hs_emb, hs_reason = _best_face_embedding(hs_img, role="headshot")
        if hs_emb is None:
            _match_log_line(
                f"face_match match outcome=review score=0.0 reason=headshot:{hs_reason}",
            )
            return {"status": "review", "score": 0.0, "reason": f"headshot:{hs_reason}"}

        score = _cosine(id_emb, hs_emb)

        # Normalize to 0..1 (cosine can be [-1,1] but for face embeddings it’s usually [0,1])
        score = max(0.0, min(1.0, score))

        if score >= PASS_THRESHOLD:
            status = "passed"
        elif score >= REVIEW_THRESHOLD:
            status = "review"
        else:
            status = "failed"

        rounded = round(score, 4)
        _match_log_line(f"face_match match outcome={status} score={rounded} reason=null")
        return {"status": status, "score": rounded, "reason": None}
    except Exception:
        logging.exception("face_match internal_error")
        _match_log_line("face_match match outcome=review score=0.0 reason=internal_error")
        return JSONResponse(
            status_code=500,
            content={"status": "review", "score": 0.0, "reason": "internal_error"},
        )
