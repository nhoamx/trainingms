import os

QUEUE_NAME = os.getenv("OCR_QUEUE_NAME", "omr")
REDIS_URL = os.getenv("OCR_REDIS_URL", "redis://localhost:6379/0")
WORKER_NAME = os.getenv("OCR_WORKER_NAME", "ocr-worker")
WITH_SCHEDULER = os.getenv("OCR_WORKER_WITH_SCHEDULER", "true").lower() == "true"


def main() -> int:
    try:
        from redis import Redis
        from rq import Worker
    except Exception as exc:
        raise RuntimeError(f"Queue backend no disponible: {exc}") from exc

    connection = Redis.from_url(REDIS_URL)
    worker = Worker([QUEUE_NAME], name=WORKER_NAME, connection=connection)
    worker.work(with_scheduler=WITH_SCHEDULER)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
