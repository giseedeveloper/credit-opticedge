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
# TZ NIDA portrait zone — photo is on the RIGHT side of the card.
NIDA_RIGHT_ROIS: List[Tuple[str, float, float, float, float]] = [
    ("roi_nida_right_tight", 0.54, 0.16, 0.96, 0.72),
    ("roi_nida_right", 0.50, 0.12, 0.98, 0.78),
    ("roi_right", 0.52, 0.08, 0.99, 0.82),
]

# Fallback ROIs for non-NIDA or unusual layouts (tried only if right zone fails).
NIDA_FALLBACK_ROIS: List[Tuple[str, float, float, float, float]] = [
    ("roi_center", 0.20, 0.10, 0.82, 0.78),
    ("roi_upper", 0.08, 0.04, 0.92, 0.55),
    ("roi_nida_left", 0.02, 0.10, 0.38, 0.90),
    ("roi_left", 0.02, 0.08, 0.44, 0.82),
]

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
        scale = min(3.0, (ID_UPSCALE_MIN_EDGE / float(m)) * 1.05)
        nw = max(1, int(round(w * scale)))
        nh = max(1, int(round(h * scale)))
        return cv2.resize(img, (nw, nh), interpolation=cv2.INTER_CUBIC)
    return img


def _enhance_headshot_image(img: np.ndarray) -> np.ndarray:
    """Mild CLAHE + unsharp for live selfies (glare, uneven indoor light)."""
    if img is None or img.size == 0:
        return img
    lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)
    l_ch, a_ch, b_ch = cv2.split(lab)
    clahe = cv2.createCLAHE(clipLimit=1.8, tileGridSize=(8, 8))
    l2 = clahe.apply(l_ch)
    merged = cv2.merge((l2, a_ch, b_ch))
    out = cv2.cvtColor(merged, cv2.COLOR_LAB2BGR)
    blurred = cv2.GaussianBlur(out, (0, 0), sigmaX=0.9)
    return cv2.addWeighted(out, 1.18, blurred, -0.18, 0)


def _center_crop(img: np.ndarray, frac: float) -> np.ndarray:
    """Keep central region — reduces background clutter in selfies."""
    if img is None or img.size == 0:
        return img
    frac = max(0.5, min(0.95, frac))
    h, w = img.shape[:2]
    nh = max(1, int(round(h * frac)))
    nw = max(1, int(round(w * frac)))
    y0 = max(0, (h - nh) // 2)
    x0 = max(0, (w - nw) // 2)
    return img[y0 : y0 + nh, x0 : x0 + nw]


def _id_front_candidate_images(id_img: np.ndarray) -> List[Tuple[str, np.ndarray]]:
    """
    TZ NIDA portrait is on the RIGHT — try right-side ROIs first, then full card,
    then fallback layouts for other ID types.
    """
    out: List[Tuple[str, np.ndarray]] = []
    if id_img is None or id_img.size == 0:
        return out

    enhanced = _enhance_id_image(id_img)

    def add(label: str, im: np.ndarray) -> None:
        if im is None or im.size == 0 or im.shape[0] < 48 or im.shape[1] < 48:
            return
        out.append((label, im))

    for label, fx0, fy0, fx1, fy1 in NIDA_RIGHT_ROIS:
        crop = _roi_xywh(enhanced, fx0, fy0, fx1, fy1)
        add(label, _maybe_upscale_for_id(crop))

    add("full_enhanced", _maybe_upscale_for_id(enhanced))
    add("full_raw", _maybe_upscale_for_id(np.ascontiguousarray(id_img)))

    for label, fx0, fy0, fx1, fy1 in NIDA_FALLBACK_ROIS:
        crop = _roi_xywh(enhanced, fx0, fy0, fx1, fy1)
        add(label, _maybe_upscale_for_id(crop))

    return out


def _inspect_image_role(img: np.ndarray, role: str) -> Tuple[Optional[str], Optional[np.ndarray]]:
    """
    Detect the best face in [img] for [role].
    Returns (reason_code, embedding) — reason_code is None when OK.
    """
    faces = _get_face_app().get(img)
    if not faces:
        return "no_face_detected", None

    if role == "headshot" and len(faces) > 1:
        return "multiple_faces_detected", None

    best = max(faces, key=lambda f: _face_rank(f, img))
    quality_error = _quality_reason(img, best, role)
    if quality_error is not None:
        return quality_error, None

    emb = getattr(best, "embedding", None)
    if emb is None:
        return "embedding_unavailable", None
    return None, emb.astype(np.float32)


def _diagnose_nida_right_portrait(id_img: np.ndarray) -> Optional[str]:
    """
    Quality check on TZ NIDA right portrait zone only.
    Returns prefixed reason e.g. id_front:image_blurry, or None if portrait looks OK.
    """
    if id_img is None or id_img.size == 0:
        return "id_front:invalid_image"

    enhanced = _enhance_id_image(id_img)
    saw_face = False

    for _label, fx0, fy0, fx1, fy1 in NIDA_RIGHT_ROIS:
        crop = _maybe_upscale_for_id(_roi_xywh(enhanced, fx0, fy0, fx1, fy1))
        reason, _emb = _inspect_image_role(crop, role="id_front")
        if reason is None:
            return None
        if reason != "no_face_detected":
            saw_face = True
            return f"id_front:{reason}"

    if not saw_face:
        return "id_front:no_face_detected"
    return None


def _diagnose_headshot_quality(hs_img: np.ndarray) -> Optional[str]:
    """Return prefixed headshot quality reason, or None if selfie looks OK."""
    if hs_img is None or hs_img.size == 0:
        return "headshot:invalid_image"

    last_reason = "no_face_detected"
    for _label, cand in _headshot_candidate_images(hs_img):
        reason, _emb = _inspect_image_role(cand, role="headshot")
        if reason is None:
            return None
        last_reason = reason
    return f"headshot:{last_reason}"


def _resolve_outcome_reason(
    score: float,
    status: str,
    id_img: np.ndarray,
    hs_img: np.ndarray,
) -> Optional[str]:
    """
    When match did not pass, explain whether NIDA, selfie, or similarity is the issue.
    """
    if status == "passed":
        return None

    id_issue = _diagnose_nida_right_portrait(id_img)
    if id_issue is not None:
        return id_issue

    hs_issue = _diagnose_headshot_quality(hs_img)
    if hs_issue is not None:
        return hs_issue

    if score < REVIEW_THRESHOLD:
        return "match:low_similarity"
    if status == "review":
        return "match:low_similarity"
    return "match:low_similarity"


def _face_embeddings_from_image(
    img: np.ndarray, role: str
) -> Tuple[List[np.ndarray], str]:
    reason, emb = _inspect_image_role(img, role)
    if emb is None:
        return [], reason or "no_face_detected"
    return [emb], "ok"


def _headshot_candidate_images(hs_img: np.ndarray) -> List[Tuple[str, np.ndarray]]:
    out: List[Tuple[str, np.ndarray]] = []
    if hs_img is None or hs_img.size == 0:
        return out

    def add(label: str, im: np.ndarray) -> None:
        if im is None or im.size == 0 or im.shape[0] < 48 or im.shape[1] < 48:
            return
        out.append((label, im))

    add("full_raw", np.ascontiguousarray(hs_img))
    add("full_enhanced", _enhance_headshot_image(hs_img))
    add("center_85", _center_crop(hs_img, 0.85))
    add("center_85_enhanced", _enhance_headshot_image(_center_crop(hs_img, 0.85)))
    add("center_72_enhanced", _enhance_headshot_image(_center_crop(hs_img, 0.72)))
    return out


def _id_front_candidate_embeddings(id_img: np.ndarray) -> Tuple[List[Tuple[str, np.ndarray]], str]:
    embeddings: List[Tuple[str, np.ndarray]] = []
    last_reason = "no_face_detected"

    for label, cand in _id_front_candidate_images(id_img):
        embs, reason = _face_embeddings_from_image(cand, role="id_front")
        if not embs:
            last_reason = reason
            continue
        for emb in embs:
            embeddings.append((f"{label}", emb))
        last_reason = "ok"

    return embeddings, last_reason


def _headshot_candidate_embeddings(hs_img: np.ndarray) -> Tuple[List[Tuple[str, np.ndarray]], str]:
    embeddings: List[Tuple[str, np.ndarray]] = []
    last_reason = "no_face_detected"

    for label, cand in _headshot_candidate_images(hs_img):
        embs, reason = _face_embeddings_from_image(cand, role="headshot")
        if not embs:
            last_reason = reason
            continue
        for emb in embs:
            embeddings.append((label, emb))
        last_reason = "ok"

    return embeddings, last_reason


def _best_match_score(
    id_img: np.ndarray, hs_img: np.ndarray
) -> Tuple[float, str, str]:
    """
    Score-driven matching: try every ID crop × headshot variant, keep max cosine.
    """
    id_embs, id_reason = _id_front_candidate_embeddings(id_img)
    if not id_embs:
        prefixed = id_reason if ":" in id_reason else f"id_front:{id_reason}"
        if not prefixed.startswith("id_front:"):
            nida_issue = _diagnose_nida_right_portrait(id_img)
            prefixed = nida_issue or prefixed
        return 0.0, prefixed, ""

    hs_embs, hs_reason = _headshot_candidate_embeddings(hs_img)
    if not hs_embs:
        prefixed = hs_reason if ":" in hs_reason else f"headshot:{hs_reason}"
        if not prefixed.startswith("headshot:"):
            hs_issue = _diagnose_headshot_quality(hs_img)
            prefixed = hs_issue or prefixed
        return 0.0, prefixed, ""

    best_score = 0.0
    best_pair = ("", "")
    for id_label, id_emb in id_embs:
        for hs_label, hs_emb in hs_embs:
            score = _cosine(id_emb, hs_emb)
            if score > best_score:
                best_score = score
                best_pair = (id_label, hs_label)

    return best_score, "ok", f"{best_pair[0]}|{best_pair[1]}"


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

        score, match_reason, pair = _best_match_score(id_img, hs_img)
        if match_reason != "ok":
            _match_log_line(
                f"face_match match outcome=review score=0.0 reason={match_reason}",
            )
            return {
                "status": "review",
                "score": 0.0,
                "reason": match_reason,
            }

        # Normalize to 0..1 (cosine can be [-1,1] but for face embeddings it’s usually [0,1])
        score = max(0.0, min(1.0, score))

        if score >= PASS_THRESHOLD:
            status = "passed"
        elif score >= REVIEW_THRESHOLD:
            status = "review"
        else:
            status = "failed"

        reason = _resolve_outcome_reason(score, status, id_img, hs_img)

        rounded = round(score, 4)
        _match_log_line(
            f"face_match match outcome={status} score={rounded} pair={pair} reason={reason}",
        )
        return {"status": status, "score": rounded, "reason": reason}
    except Exception:
        logging.exception("face_match internal_error")
        _match_log_line("face_match match outcome=review score=0.0 reason=internal_error")
        return JSONResponse(
            status_code=500,
            content={"status": "review", "score": 0.0, "reason": "internal_error"},
        )
