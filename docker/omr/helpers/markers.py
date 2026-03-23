import logging

import cv2
import imutils
import numpy as np

logger = logging.getLogger(__name__)


def detect_markers(thresh, min_area=1000):
    contours = cv2.findContours(thresh, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    contours = imutils.grab_contours(contours)

    img_area = thresh.shape[0] * thresh.shape[1]

    top_markers = []
    bottom_markers = []

    for c in contours:
        area = cv2.contourArea(c)

        if area < min_area:
            continue

        if area > img_area * 0.02:
            continue

        x, y, w, h = cv2.boundingRect(c)
        aspect_ratio = w / float(h)

        if not (0.8 < aspect_ratio < 1.2):
            continue

        peri = cv2.arcLength(c, True)
        approx = cv2.approxPolyDP(c, 0.04 * peri, True)

        if len(approx) != 4:
            continue

        cx = x + w // 2
        cy = y + h // 2

        if cy < thresh.shape[0] * 0.3:
            top_markers.append((c, x, y, w, h, area))
        elif cy > thresh.shape[0] * 0.6 and cx < thresh.shape[1] * 0.4:
            bottom_markers.append((c, x, y, w, h, area))

    top_markers = sorted(top_markers, key=lambda marker: marker[5], reverse=True)
    bottom_markers = sorted(bottom_markers, key=lambda marker: marker[5], reverse=True)

    return top_markers[:2], bottom_markers[:1]


def classify_markers(top_markers, bottom_markers):
    if len(top_markers) < 2 or len(bottom_markers) < 1:
        return None, None, None

    def center(marker):
        _, x, y, w, h, _ = marker
        return (x + w // 2, y + h // 2)

    top_sorted = sorted(top_markers, key=lambda marker: marker[1])

    tl = center(top_sorted[0])
    tr = center(top_sorted[1])
    bl = center(bottom_markers[0])

    return tl, tr, bl


def warp_from_markers(image, TL, TR, BL, page_number=None, save=False, storage=None):
    """
    Aplica transformacion de perspectiva usando los markers.

    Args:
        image (numpy.ndarray): Imagen original
        TL (tuple): Coordenada del marker superior izquierdo
        TR (tuple): Coordenada del marker superior derecho
        BL (tuple): Coordenada del marker inferior izquierdo
        page_number (int): Numero de pagina (requerido si save=True)
        save (bool): Si es True, guarda la imagen warpificada

    Returns:
        numpy.ndarray: Imagen despues de la transformacion, o None si falla
    """
    if TL is None or TR is None or BL is None:
        logger.warning("Markers insuficientes: TL=%s TR=%s BL=%s", TL, TR, BL)
        return None

    br = (TR[0], BL[1])

    pts_src = np.array([TL, TR, br, BL], dtype="float32")

    width = int(max(
        np.linalg.norm(np.array(TR) - np.array(TL)),
        np.linalg.norm(np.array(br) - np.array(BL)),
    ))

    height = int(max(
        np.linalg.norm(np.array(BL) - np.array(TL)),
        np.linalg.norm(np.array(br) - np.array(TR)),
    ))

    pts_dst = np.array([
        [0, 0],
        [width - 1, 0],
        [width - 1, height - 1],
        [0, height - 1],
    ], dtype="float32")

    transform_matrix = cv2.getPerspectiveTransform(pts_src, pts_dst)
    warped = cv2.warpPerspective(image, transform_matrix, (width, height))

    if save and page_number is not None:
        if storage is None:
            raise ValueError("storage es requerido cuando save=True")
        storage.save_warped_image(warped, page_number, "warped")

    return warped


def draw_markers(image, markers, page_number=None, save=False, storage=None):
    """
    Dibuja los markers detectados en la imagen.

    Args:
        image (numpy.ndarray): Imagen original
        markers (list): Lista de markers detectados
        page_number (int): Numero de pagina (requerido si save=True)
        save (bool): Si es True, guarda la imagen con markers dibujados

    Returns:
        numpy.ndarray: Imagen con markers dibujados
    """
    debug = image.copy()

    for marker in markers:
        _, x, y, w, h, _ = marker
        cv2.rectangle(debug, (x, y), (x + w, y + h), (0, 255, 0), 2)

    if save and page_number is not None:
        if storage is None:
            raise ValueError("storage es requerido cuando save=True")
        storage.save_debug_image(debug, page_number, "markers_detected")

    return debug
