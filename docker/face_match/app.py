from __future__ import annotations

import io
import math
from typing import Optional, Tuple

import cv2
import numpy as np
from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse
from insightface.app import FaceAnalysis

app = FastAPI(title="OpticEdge Face Match", version="1.0.0")


_face_app: Optional[FaceAnalysis] = None


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


def _best_face_embedding(img: np.ndarray) -> Tuple[Optional[np.ndarray], str]:
    faces = _get_face_app().get(img)
    if not faces:
        return None, "no_face_detected"
    # pick the largest face by bbox area
    def area(f):
        x1, y1, x2, y2 = f.bbox
        return max(0.0, (x2 - x1)) * max(0.0, (y2 - y1))

    best = max(faces, key=area)
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

        id_emb, id_reason = _best_face_embedding(id_img)
        if id_emb is None:
            return {"status": "review", "score": 0.0, "reason": f"id_front:{id_reason}"}

        hs_emb, hs_reason = _best_face_embedding(hs_img)
        if hs_emb is None:
            return {"status": "review", "score": 0.0, "reason": f"headshot:{hs_reason}"}

        score = _cosine(id_emb, hs_emb)

        # Normalize to 0..1 (cosine can be [-1,1] but for face embeddings it’s usually [0,1])
        score = max(0.0, min(1.0, score))

        # Conservative thresholds: keep "review" lane wide to avoid false rejects
        if score >= 0.80:
            status = "passed"
        elif score >= 0.60:
            status = "review"
        else:
            status = "failed"

        return {"status": status, "score": round(score, 4), "reason": None}
    except Exception as e:
        return JSONResponse(
            status_code=500,
            content={"status": "review", "score": 0.0, "reason": "internal_error"},
        )

