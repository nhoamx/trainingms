import json
import logging

import cv2

logger = logging.getLogger(__name__)


def load_folio_annotation(path="folio-annotation.json"):
    with open(path, "r") as file:
        return json.load(file)


def get_grid(annotation):
    return annotation["folio_annotation"]["grid_points"]


def normalize_grid_to_roi(annotation, grid, roi):
    """
    Normaliza el grid para que siempre este en coordenadas relativas al ROI.

    Si el grid fue guardado en coordenadas absolutas de la imagen normalizada,
    se convierte restando el origen de la region.
    """
    if not grid or "folio_annotation" not in annotation:
        return grid

    region = annotation["folio_annotation"].get("region")
    if not region:
        return grid

    max_x = max(point["x"] for point in grid.values())
    max_y = max(point["y"] for point in grid.values())

    roi_h, roi_w = roi.shape[:2]

    is_absolute = max_x > roi_w or max_y > roi_h
    if not is_absolute:
        return grid

    offset_x = int(region["x"])
    offset_y = int(region["y"])

    return {
        key: {
            "x": int(point["x"]) - offset_x,
            "y": int(point["y"]) - offset_y,
        }
        for key, point in grid.items()
    }


def get_fill_ratio(thresh, x, y, size=12):
    roi = thresh[y - size:y + size, x - size:x + size]

    if roi.size == 0:
        return 0

    total = cv2.countNonZero(roi)
    area = roi.shape[0] * roi.shape[1]

    return total / float(area)


def crop_folio_region(image, annotation):
    region = annotation["folio_annotation"]["region"]

    x = int(region["x"])
    y = int(region["y"])
    w = int(region["w"])
    h = int(region["h"])

    return image[y:y + h, x:x + w]


def debug_folio_roi(image, annotation, page_number, page_image=None, storage=None):
    """
    Guarda la region del folio como imagen de debug.

    Args:
        image (numpy.ndarray): Imagen del folio
        annotation (dict): Anotacion con region del folio
        page_number (int): Numero de pagina
        page_image (numpy.ndarray): (Opcional) Imagen completa de la pagina para referencia
    """
    roi = crop_folio_region(image, annotation)
    if storage is None:
        raise ValueError("storage es requerido para guardar folio_roi")
    storage.save_debug_image(roi, page_number, "folio_roi")


def read_folio(image, annotation) -> str | None:
    """
    Lee el folio de la imagen usando el grid de anotación.

    Returns:
        str con el folio detectado, o None si la confianza promedio
        es demasiado baja o hay demasiados dígitos ambiguos.
    """
    logger.debug("Leyendo folio (modo grid fijo)")

    folio_annotation = annotation["folio_annotation"]

    if "region" in folio_annotation:
        roi = crop_folio_region(image, annotation)
    else:
        roi = image

    gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)

    thresh = cv2.threshold(
        gray,
        0,
        255,
        cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU,
    )[1]

    grid = get_grid(annotation)
    grid = normalize_grid_to_roi(annotation, grid, roi)

    folio = ""
    confidence_scores = []

    for col in range(1, 12):
        values = []

        for row in range(10):
            key = f"C{col}R{row}"
            point = grid[key]

            x = int(point["x"])
            y = int(point["y"])

            fill = get_fill_ratio(thresh, x, y)
            values.append(fill)

        max_val = max(values)
        digit = values.index(max_val)

        confidence_scores.append(max_val)

        if max_val < 0.3:
            folio += "?"
        else:
            folio += str(digit)

    avg_conf = sum(confidence_scores) / len(confidence_scores)

    if avg_conf < 0.2:
        return None

    if folio.count("?") > 5:
        return None

    return folio
