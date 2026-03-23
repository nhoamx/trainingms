import json
import os
import traceback
from datetime import UTC, datetime
from pathlib import Path

from omr.pipeline import process_pdf_job

JOBS_BASE_DIR = Path(os.getenv("OCR_JOBS_DIR", str(Path(__file__).parent / "jobs")))


def _now_iso() -> str:
    return datetime.now(UTC).isoformat()


def _write_json(file_path: Path, payload: dict) -> None:
    file_path.parent.mkdir(parents=True, exist_ok=True)
    with file_path.open("w", encoding="utf-8") as file:
        json.dump(payload, file, ensure_ascii=False, indent=2)


def process_pdf_task(pdf_path: str, instrument: str) -> dict:
    try:
        from rq import get_current_job
    except Exception:
        get_current_job = None

    job = get_current_job() if get_current_job else None
    job_id = job.id if job else "standalone"
    job_dir = JOBS_BASE_DIR / job_id
    job_dir.mkdir(parents=True, exist_ok=True)

    metadata = {
        "job_id": job_id,
        "pdf_path": pdf_path,
        "instrument": instrument,
        "started_at": _now_iso(),
        "status": "started",
    }
    _write_json(job_dir / "metadata.json", metadata)

    if job is not None:
        job.meta["artifacts_dir"] = str(job_dir)
        job.meta["metadata_path"] = str(job_dir / "metadata.json")
        job.save_meta()

    try:
        result = process_pdf_job(pdf_path=pdf_path, instrument=instrument)

        _write_json(job_dir / "result.json", result)
        metadata["status"] = "finished"
        metadata["finished_at"] = _now_iso()
        metadata["result_path"] = str(job_dir / "result.json")
        _write_json(job_dir / "metadata.json", metadata)

        if job is not None:
            job.meta["result_path"] = str(job_dir / "result.json")
            job.save_meta()

        return {
            "job_id": job_id,
            "status": "finished",
            "artifacts_dir": str(job_dir),
            "result_path": str(job_dir / "result.json"),
            "result": result,
        }
    except Exception as exc:
        error_payload = {
            "job_id": job_id,
            "status": "failed",
            "error": str(exc),
            "traceback": traceback.format_exc(),
            "failed_at": _now_iso(),
        }
        _write_json(job_dir / "error.json", error_payload)

        metadata["status"] = "failed"
        metadata["failed_at"] = _now_iso()
        metadata["error_path"] = str(job_dir / "error.json")
        _write_json(job_dir / "metadata.json", metadata)

        if job is not None:
            job.meta["error_path"] = str(job_dir / "error.json")
            job.save_meta()

        raise
