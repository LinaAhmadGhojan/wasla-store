"""
Wasla CLIP visual search microservice.

Setup (Windows):
  py -3.14 -m venv venv
  .\\venv\\Scripts\\pip install -r requirements.txt
  .\\venv\\Scripts\\uvicorn.exe main:app --host 127.0.0.1 --port 3010

Laravel:
  IMAGE_SEARCH_DRIVER=clip
  IMAGE_SEARCH_CLIP_URL=http://127.0.0.1:3010
  php artisan products:index-visual --driver=clip
"""

from __future__ import annotations

import os
from io import BytesIO
from pathlib import Path
from typing import Any

import numpy as np
from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

app = FastAPI(title="Wasla CLIP Image Search", version="0.2.0")
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

DATA_DIR = Path(__file__).resolve().parent / "data"
DATA_DIR.mkdir(exist_ok=True)
INDEX_PATH = DATA_DIR / "index.npz"

_model = None
_processor = None
_device = "cpu"
_ids: list[int] = []
_vectors: np.ndarray | None = None


class IndexItem(BaseModel):
    product_id: int
    path: str


class IndexRequest(BaseModel):
    items: list[IndexItem] = Field(default_factory=list)


def load_model() -> None:
    global _model, _processor, _device
    if _model is not None:
        return

    import torch
    from transformers import CLIPModel, CLIPProcessor

    name = os.getenv("CLIP_MODEL", "openai/clip-vit-base-patch32")
    _device = "cuda" if torch.cuda.is_available() else "cpu"
    _processor = CLIPProcessor.from_pretrained(name)
    _model = CLIPModel.from_pretrained(name)
    _model.to(_device)
    _model.eval()


def _embed_pil(image) -> np.ndarray:
    import torch

    load_model()
    inputs = _processor(images=image, return_tensors="pt")
    inputs = {k: v.to(_device) for k, v in inputs.items()}
    with torch.no_grad():
        feats = _model.get_image_features(**inputs)
        feats = feats / feats.norm(dim=-1, keepdim=True)
    return feats.detach().cpu().numpy().astype("float32")[0]


def embed_image_file(path: str) -> np.ndarray:
    from PIL import Image

    with Image.open(path) as image:
        return _embed_pil(image.convert("RGB"))


def embed_upload(file_bytes: bytes) -> np.ndarray:
    from PIL import Image

    with Image.open(BytesIO(file_bytes)) as image:
        return _embed_pil(image.convert("RGB"))


def persist_index(ids: list[int], vectors: np.ndarray) -> None:
    np.savez_compressed(INDEX_PATH, ids=np.array(ids, dtype=np.int64), vectors=vectors)


def load_index() -> None:
    global _ids, _vectors
    if not INDEX_PATH.exists():
        _ids, _vectors = [], None
        return
    data = np.load(INDEX_PATH)
    _ids = [int(x) for x in data["ids"].tolist()]
    _vectors = data["vectors"].astype("float32")


@app.on_event("startup")
def startup() -> None:
    load_index()


@app.get("/health")
def health() -> dict[str, Any]:
    return {
        "ok": True,
        "indexed": len(_ids),
        "model_loaded": _model is not None,
        "device": _device,
    }


@app.post("/index")
def rebuild_index(body: IndexRequest) -> dict[str, Any]:
    load_model()
    ids: list[int] = []
    vecs: list[np.ndarray] = []
    errors = 0
    for item in body.items:
        try:
            if not os.path.isfile(item.path):
                errors += 1
                continue
            vecs.append(embed_image_file(item.path))
            ids.append(item.product_id)
        except Exception:
            errors += 1
    if not ids:
        raise HTTPException(400, "No images indexed")
    matrix = np.vstack(vecs)
    persist_index(ids, matrix)
    load_index()
    return {"indexed": len(ids), "errors": errors}


@app.post("/search")
async def search(
    image: UploadFile = File(...),
    limit: int = Form(24),
) -> dict[str, Any]:
    if _vectors is None or not len(_ids):
        raise HTTPException(503, "Index empty. Call /index first (php artisan products:index-visual --driver=clip).")
    raw = await image.read()
    try:
        q = embed_upload(raw)
    except Exception as exc:
        raise HTTPException(400, f"Invalid image: {exc}") from exc

    scores = _vectors @ q
    best: dict[int, float] = {}
    for pid, score in zip(_ids, scores.tolist()):
        if pid not in best or score > best[pid]:
            best[pid] = float(score)

    ranked = sorted(best.items(), key=lambda x: x[1], reverse=True)[: max(1, min(int(limit or 24), 48))]
    return {
        "results": [{"product_id": pid, "score": round(score, 4)} for pid, score in ranked],
    }
