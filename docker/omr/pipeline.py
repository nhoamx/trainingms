import cv2
import json
import os
from pathlib import Path
from PyPDF2 import PdfReader

from omr.helpers import classify_markers
from omr.helpers import debug_answers_rois
from omr.helpers import debug_folio_roi
from omr.helpers import detect_markers
from omr.helpers import draw_markers
from omr.helpers import get_storage
from omr.helpers import load_answers_annotation
from omr.helpers import load_answers_mapping
from omr.helpers import load_folio_annotation
from omr.helpers import normalize_size
from omr.helpers import pdf_to_images
from omr.helpers import read_answers
from omr.helpers import read_folio
from omr.helpers import warp_from_markers

SCRIPT_DIR = Path(__file__).parent
ANNOTATIONS_DIR = SCRIPT_DIR.parent / "annotator" / "annotations"
ANSWERS_MAPPING = str(ANNOTATIONS_DIR / "answers-mapping.json")
OUTPUT_DIR = SCRIPT_DIR.parent / "output"
OUTPUT_TRACKING_DIR = SCRIPT_DIR / "output_tracking"

INSTRUMENT_CONFIG = {
    "gri": {
        "folio_annotation": "gri-folio-annotator.json",
        "answers_annotation": "gri-answers-annotator.json",
    },
    "griii": {
        "folio_annotation": "griii-folio-annotator.json",
        "answers_annotation": "griii-answers-annotator.json",
    },
    "grv": {
        "folio_annotation": "grv-folio-annotator.json",
        "answers_annotation": "grv-answers-annotator.json",
    },
    "clima-laboral": {
        "folio_annotation": "clima-laboral-folio-annotator.json",
        "answers_annotation": "clima-laboral-answers-annotator.json",
    },
}

DEFAULT_INSTRUMENT = "clima-laboral"
DEFAULT_PDF = str(SCRIPT_DIR / "file-tests/check.pdf")
LARGE_PDF_THRESHOLD_PAGES = int(os.getenv("OMR_LARGE_PDF_THRESHOLD_PAGES", "25"))
LARGE_PDF_CHUNK_SIZE = int(os.getenv("OMR_LARGE_PDF_CHUNK_SIZE", "10"))


def _get_instrument_annotation_paths(instrument: str) -> tuple[str, str]:
    instrument_key = str(instrument).lower().strip()
    config = INSTRUMENT_CONFIG.get(instrument_key)
    if config is None:
        supported = ", ".join(sorted(INSTRUMENT_CONFIG.keys()))
        raise ValueError(f"Instrumento no soportado: {instrument}. Soportados: {supported}")

    folio_path = str(ANNOTATIONS_DIR / config["folio_annotation"])
    answers_path = str(ANNOTATIONS_DIR / config["answers_annotation"])
    return folio_path, answers_path


def get_pdf_page_count(pdf_path: str) -> int:
    reader = PdfReader(pdf_path)
    return len(reader.pages)


def build_page_chunks(total_pages: int, threshold_pages: int, chunk_size: int) -> list[tuple[int, int]]:
    if total_pages <= 0:
        return []

    if chunk_size <= 0:
        raise ValueError("chunk_size debe ser mayor a 0")

    if threshold_pages < 1:
        threshold_pages = 1

    if total_pages <= threshold_pages:
        return [(1, total_pages)]

    chunks = []
    start_page = 1
    while start_page <= total_pages:
        end_page = min(start_page + chunk_size - 1, total_pages)
        chunks.append((start_page, end_page))
        start_page = end_page + 1

    return chunks


def process_pdf(
    pdf_path: str,
    instrument: str,
    output_dir: Path | None = None,
    output_tracking_dir: Path | None = None,
    save_debug: bool = True,
) -> dict:
    output_dir = output_dir or OUTPUT_DIR
    output_tracking_dir = output_tracking_dir or OUTPUT_TRACKING_DIR

    folio_annotation_path, answers_annotation_path = _get_instrument_annotation_paths(instrument)

    page_count = get_pdf_page_count(pdf_path)
    page_chunks = build_page_chunks(
        total_pages=page_count,
        threshold_pages=LARGE_PDF_THRESHOLD_PAGES,
        chunk_size=LARGE_PDF_CHUNK_SIZE,
    )

    folio_annotation = load_folio_annotation(folio_annotation_path)
    answers_annotation = load_answers_annotation(answers_annotation_path)
    answers_mapping = load_answers_mapping(ANSWERS_MAPPING)

    output_dir.mkdir(parents=True, exist_ok=True)
    storage = get_storage(base_path=output_tracking_dir)
    storage.initialize_document(Path(pdf_path).stem)

    page_results = []

    for first_page, last_page in page_chunks:
        images = pdf_to_images(pdf_path, first_page=first_page, last_page=last_page)

        for chunk_offset, image in enumerate(images):
            page_index = (first_page - 1) + chunk_offset
            print(f"\n📄 Procesando página {page_index}")

            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
            thresh = cv2.threshold(
                gray,
                0,
                255,
                cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU,
            )[1]

            top_markers, bottom_markers = detect_markers(thresh)
            draw_markers(image, top_markers + bottom_markers, page_index, save=save_debug, storage=storage)

            tl, tr, bl = classify_markers(top_markers, bottom_markers)
            warped = warp_from_markers(image, tl, tr, bl, page_index, save=save_debug, storage=storage)

            if warped is None:
                print(f"⚠️ Página {page_index} sin warp")
                page_results.append(
                    {
                        "page": page_index,
                        "status": "failed",
                        "reason": "warp_failed",
                    }
                )
                continue

            normalized = normalize_size(warped, page_number=page_index, save=save_debug, storage=storage)

            if save_debug:
                debug_folio_roi(normalized, folio_annotation, page_index, storage=storage)
                debug_answers_rois(normalized, answers_annotation, page_index, storage=storage)

            folio = read_folio(normalized, folio_annotation)
            answers = read_answers(normalized, answers_annotation, mapping_config=answers_mapping)

            print(f"📌 Página {page_index} folio: {folio}")

            output_json_path = None
            if folio and "?" not in folio:
                output_file = output_dir / f"{folio}.json"
                with output_file.open("w", encoding="utf-8") as file:
                    json.dump(answers, file, ensure_ascii=False, indent=2)
                output_json_path = str(output_file)

            page_results.append(
                {
                    "page": page_index,
                    "status": "completed",
                    "folio": folio,
                    "answers": answers,
                    "output_json_path": output_json_path,
                }
            )

    return {
        "instrument": str(instrument).lower().strip(),
        "pdf_path": str(pdf_path),
        "page_count": page_count,
        "page_chunks": [{"from": chunk[0], "to": chunk[1]} for chunk in page_chunks],
        "pages": page_results,
        "processed_pages": len(page_results),
    }


def process_pdf_job(pdf_path: str, instrument: str) -> dict:
    return process_pdf(pdf_path=pdf_path, instrument=instrument)


def main() -> int:
    process_pdf(pdf_path=DEFAULT_PDF, instrument=DEFAULT_INSTRUMENT)
    return 0