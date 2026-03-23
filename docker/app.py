import hashlib
import os
import uuid
from pathlib import Path

from flask import Flask, jsonify, request

from omr.pipeline import INSTRUMENT_CONFIG
from tasks import process_pdf_task

app = Flask(__name__)

MAX_UPLOAD_MB = int(os.getenv("OCR_MAX_UPLOAD_MB", "30"))
UPLOAD_BASE_DIR = Path(os.getenv("OCR_UPLOAD_DIR", str(Path(__file__).parent / "input" / "api")))
REDIS_URL = os.getenv("OCR_REDIS_URL", "redis://localhost:6379/0")
QUEUE_NAME = os.getenv("OCR_QUEUE_NAME", "omr")
JOB_TIMEOUT_SEC = int(os.getenv("OCR_JOB_TIMEOUT_SEC", "900"))
MAX_RETRIES = int(os.getenv("OCR_MAX_RETRIES", "3"))
RETRY_BACKOFF_SEC = [
    int(value.strip())
    for value in os.getenv("OCR_RETRY_BACKOFF_SEC", "10,30,90").split(",")
    if value.strip()
]
IDEMPOTENCY_TTL_SEC = int(os.getenv("OCR_IDEMPOTENCY_TTL_SEC", "86400"))


def get_queue():
    try:
        from redis import Redis
        from rq import Queue
    except Exception as exc:  # pragma: no cover - env-specific import fallback
        raise RuntimeError(f"Queue backend no disponible: {exc}") from exc

    redis_connection = Redis.from_url(REDIS_URL)
    return Queue(QUEUE_NAME, connection=redis_connection)


def _build_idempotency_key(instrument: str, content_hash: str) -> str:
    return f"ocr:idempotency:{instrument}:{content_hash}"


def _build_retry_object():
    from rq import Retry

    if MAX_RETRIES <= 1:
        return None

    if RETRY_BACKOFF_SEC:
        return Retry(max=MAX_RETRIES, interval=RETRY_BACKOFF_SEC)

    return Retry(max=MAX_RETRIES)


def _serialize_job_status(job) -> dict:
    payload = {
        "job_id": job.id,
        "status": job.get_status(refresh=True),
        "instrument": job.meta.get("instrument"),
        "filename": job.meta.get("filename"),
        "content_hash": job.meta.get("content_hash"),
        "artifacts_dir": job.meta.get("artifacts_dir"),
        "result_path": job.meta.get("result_path"),
        "error_path": job.meta.get("error_path"),
        "retry_count": job.meta.get("retry_count", 0),
    }

    if job.is_finished:
        payload["result"] = job.result
    elif job.is_failed:
        payload["error"] = "job_failed"
        payload["detail"] = job.exc_info

    return payload


def _allowed_instrument(instrument: str) -> bool:
    return str(instrument).lower().strip() in INSTRUMENT_CONFIG


def _save_upload(file_storage, instrument: str) -> Path:
    UPLOAD_BASE_DIR.mkdir(parents=True, exist_ok=True)

    suffix = Path(file_storage.filename or "upload.pdf").suffix.lower() or ".pdf"
    if suffix != ".pdf":
        suffix = ".pdf"

    random_id = uuid.uuid4().hex
    safe_name = f"{instrument}-{random_id}{suffix}"
    destination = UPLOAD_BASE_DIR / safe_name
    file_storage.save(destination)
    return destination


def _file_sha256(file_path: Path) -> str:
    hasher = hashlib.sha256()
    with file_path.open("rb") as file:
        for chunk in iter(lambda: file.read(1024 * 1024), b""):
            hasher.update(chunk)
    return hasher.hexdigest()


@app.get("/health")
def health():
    return jsonify({"status": "ok", "service": "ocr-api", "queue": QUEUE_NAME})


@app.post("/jobs")
def create_job():
    if "file" not in request.files:
        return jsonify({"error": "missing_file", "detail": "Debe enviar el archivo PDF en el campo 'file'."}), 400

    instrument = str(request.form.get("instrument", "")).lower().strip()
    if not _allowed_instrument(instrument):
        supported = sorted(INSTRUMENT_CONFIG.keys())
        return jsonify({"error": "invalid_instrument", "detail": "Instrumento no soportado.", "supported": supported}), 400

    pdf_file = request.files["file"]
    if not pdf_file.filename:
        return jsonify({"error": "empty_filename", "detail": "El archivo no tiene nombre válido."}), 400

    content_length = request.content_length or 0
    if content_length > MAX_UPLOAD_MB * 1024 * 1024:
        return jsonify({"error": "file_too_large", "detail": f"Archivo excede {MAX_UPLOAD_MB}MB."}), 413

    saved_pdf_path = _save_upload(pdf_file, instrument)
    content_hash = _file_sha256(saved_pdf_path)

    try:
        queue = get_queue()
        from rq.job import Job
    except RuntimeError as exc:
        return jsonify({"error": "queue_unavailable", "detail": str(exc)}), 503
    except Exception as exc:
        return jsonify({"error": "queue_unavailable", "detail": str(exc)}), 503

    idempotency_key = _build_idempotency_key(instrument, content_hash)
    existing_job_id = queue.connection.get(idempotency_key)
    if existing_job_id:
        try:
            existing_job = Job.fetch(existing_job_id.decode("utf-8"), connection=queue.connection)
            payload = _serialize_job_status(existing_job)
            payload["deduplicated"] = True
            return jsonify(payload), 200
        except Exception:
            queue.connection.delete(idempotency_key)

    job = queue.enqueue(
        process_pdf_task,
        str(saved_pdf_path),
        instrument,
        job_timeout=JOB_TIMEOUT_SEC,
        retry=_build_retry_object(),
        meta={
            "instrument": instrument,
            "filename": pdf_file.filename,
            "content_hash": content_hash,
            "saved_pdf_path": str(saved_pdf_path),
            "retry_count": 0,
        },
    )

    queue.connection.setex(idempotency_key, IDEMPOTENCY_TTL_SEC, job.id)

    return (
        jsonify(
            {
                "job_id": job.id,
                "status": "queued",
                "instrument": instrument,
                "status_url": f"/jobs/{job.id}",
                "content_hash": content_hash,
                "retry": {
                    "max_retries": MAX_RETRIES,
                    "backoff_seconds": RETRY_BACKOFF_SEC,
                },
            }
        ),
        202,
    )


@app.get("/jobs/<job_id>")
def job_status(job_id: str):
    try:
        queue = get_queue()
        from rq.job import Job
    except RuntimeError as exc:
        return jsonify({"error": "queue_unavailable", "detail": str(exc)}), 503
    except Exception as exc:  # pragma: no cover - env-specific import fallback
        return jsonify({"error": "queue_unavailable", "detail": str(exc)}), 503

    try:
        job = Job.fetch(job_id, connection=queue.connection)
    except Exception:
        return jsonify({"error": "job_not_found", "detail": "No existe un job con ese id."}), 404

    return jsonify(_serialize_job_status(job))


if __name__ == "__main__":
    host = os.getenv("OCR_API_HOST", "0.0.0.0")
    port = int(os.getenv("OCR_API_PORT", "5000"))
    app.run(host=host, port=port, debug=False)
