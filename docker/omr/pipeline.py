
import cv2
from pathlib import Path

from omr.helpers import load_image
from omr.helpers import pdf_to_images
from omr.helpers import detect_markers
from omr.helpers import classify_markers
from omr.helpers import draw_markers
from omr.helpers import warp_from_markers
from omr.helpers import normalize_size
from omr.helpers import load_folio_annotation
from omr.helpers import debug_folio_roi
from omr.helpers import read_folio
from omr.helpers import get_storage

SCRIPT_DIR = Path(__file__).parent
IMAGE = "ref1.png"
PDF = str(SCRIPT_DIR / "file-tests/nom-035-all.pdf")
ANNOTATIONS_DIR = SCRIPT_DIR.parent / "annotator" / "annotations"
ANNOTATION = str(ANNOTATIONS_DIR / "gri-folio-annotator.json")



def main():
    images = pdf_to_images(PDF)
    annotation = load_folio_annotation(ANNOTATION)
    storage = get_storage(base_path=SCRIPT_DIR / "output_tracking")
    storage.initialize_document(Path(PDF).stem)

    for i, image in enumerate(images):
        print(f"\n📄 Procesando página {i}")

        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

        thresh = cv2.threshold(
            gray, 
            0, 
            255, 
            cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU
        )[1]

        top_markers, bottom_markers = detect_markers(thresh)

        draw_markers(image, top_markers + bottom_markers, i, save=True, storage=storage)

        TL, TR, BL = classify_markers(top_markers, bottom_markers)

        warped = warp_from_markers(image, TL, TR, BL, i, save=True, storage=storage)

        if warped is None:
            print(f"⚠️ Página {i} sin warp")
            continue

        normalized = normalize_size(warped, page_number=i, save=True, storage=storage)

        debug_folio_roi(normalized, annotation, i, storage=storage)

        folio = read_folio(normalized, annotation)

        print(f"📌 Página {i} folio: {folio}")