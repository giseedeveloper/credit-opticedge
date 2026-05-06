from __future__ import annotations

import os
from typing import Any, Optional, Tuple

import cv2
import numpy as np
from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse
from insightface.app import FaceAnalysis

app = FastAPI(title="OpticEdge Face Match", version="1.0.0")


_face_app: Optional[FaceAnalysis] = None


def _env_float(name: str, default: float) -> float:
    raw = os.getenv(name)
    if raw is None:
        return default
    try:
        return float(raw)
    except ValueError:
        return default


# Tunable thresholds (override via container env in production if needed)
PASS_THRESHOLD = _env_float("FACE_MATCH_PASS_THRESHOLD", 0.80)
REVIEW_THRESHOLD = _env_float("FACE_MATCH_REVIEW_THRESHOLD", 0.60)
MIN_FACE_AREA_RATIO = _env_float("FACE_MATCH_MIN_FACE_AREA_RATIO", 0.045)
MIN_SHARPNESS = _env_float("FACE_MATCH_MIN_SHARPNESS", 35.0)
MIN_BRIGHTNESS = _env_float("FACE_MATCH_MIN_BRIGHTNESS", 35.0)
MAX_BRIGHTNESS = _env_float("FACE_MATCH_MAX_BRIGHTNESS", 225.0)


@app.get("/health")
async def health():
    """Lightweight liveness check for Docker / load balancers (no model load)."""
    return {"status": "ok"}


def _get_face_app() -> FaceAnalysis:
    global _face_app
    if _face_app is None:
        # CPU-only. InsightFace will download model weights on first run into ~/.insightface
        _face_app = FaceAnalysis(name="buffalo_l", providers=["CPUExecutionProvider"])
        _face_app.prepare(ctx_id=0, det_size=(640, 640))
    return _face_app


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


def _quality_reason(img: np.ndarray, face: Any) -> Optional[str]:
    ratio = _face_area_ratio(face, img)
    if ratio < MIN_FACE_AREA_RATIO:
        return "face_too_small"

    crop = _crop_from_bbox(img, face)
    if crop is None or crop.size == 0:
        return "invalid_face_crop"

    gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
    sharpness = float(cv2.Laplacian(gray, cv2.CV_64F).var())
    if sharpness < MIN_SHARPNESS:
        return "image_blurry"

    brightness = float(gray.mean())
    if brightness < MIN_BRIGHTNESS:
        return "image_too_dark"
    if brightness > MAX_BRIGHTNESS:
        return "image_too_bright"

    return None


def _best_face_embedding(img: np.ndarray, role: str) -> Tuple[Optional[np.ndarray], str]:
    faces = _get_face_app().get(img)
    if not faces:
        return None, "no_face_detected"

    # Headshot should be a single person frame.
    if role == "headshot" and len(faces) > 1:
        return None, "multiple_faces_detected"

    # Pick the largest face by bbox area.
    def area(f):
        x1, y1, x2, y2 = f.bbox
        return max(0.0, (x2 - x1)) * max(0.0, (y2 - y1))

    best = max(faces, key=area)

    quality_error = _quality_reason(img, best)
    if quality_error is not None:
        return None, quality_error

    emb = getattr(best, "embedding", None)
    if emb is None:
        return None, "embedding_unavailable"
    return emb.astype(np.float32), "ok"


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
            return JSONResponse(
                status_code=400,
                content={"status": "review", "score": 0.0, "reason": "invalid_image"},
            )

        id_emb, id_reason = _best_face_embedding(id_img, role="id_front")
        if id_emb is None:
            return {"status": "review", "score": 0.0, "reason": f"id_front:{id_reason}"}

        hs_emb, hs_reason = _best_face_embedding(hs_img, role="headshot")
        if hs_emb is None:
            return {"status": "review", "score": 0.0, "reason": f"headshot:{hs_reason}"}

        score = _cosine(id_emb, hs_emb)

        # Normalize to 0..1 (cosine can be [-1,1] but for face embeddings it’s usually [0,1])
        score = max(0.0, min(1.0, score))

        # Conservative thresholds: keep "review" lane wide to avoid false rejects
        if score >= PASS_THRESHOLD:
            status = "passed"
        elif score >= REVIEW_THRESHOLD:
            status = "review"
        else:
            status = "failed"

        return {"status": status, "score": round(score, 4), "reason": None}
    except Exception as e:
        return JSONResponse(
            status_code=500,
            content={"status": "review", "score": 0.0, "reason": "internal_error"},
        )

