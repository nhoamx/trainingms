import logging
import os
import re
import unicodedata
from datetime import datetime
from pathlib import Path

import cv2

logger = logging.getLogger(__name__)


def slugify(value, separator="-"):
    normalized = unicodedata.normalize("NFKD", str(value)).encode("ascii", "ignore").decode("ascii")
    slug = re.sub(r"[^a-zA-Z0-9]+", separator, normalized).strip(separator)
    return slug.lower()


class ImageStorage:
    """
    Gestiona el almacenamiento organizado de imagenes de debug y procesadas.
    Estructura: output_tracking/documento-timestamp/pagina/output_type/
    """

    def __init__(self, base_path="output_tracking"):
        self.base_path = Path(base_path)
        self.document_name = None
        self.document_path = None

    def initialize_document(self, document_name):
        """
        Inicializa el almacenamiento para un documento especifico.

        Args:
            document_name (str): Nombre del documento (se convertira a slug)

        Returns:
            Path: Ruta del documento (documento-timestamp/)
        """
        self.document_name = document_name
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        slug = slugify(document_name, separator="-")
        doc_folder = f"{slug}_{timestamp}"

        self.document_path = self.base_path / doc_folder
        self.document_path.mkdir(parents=True, exist_ok=True)

        logger.debug("Documento inicializado: %s", self.document_path)
        return self.document_path

    def get_page_path(self, page_number):
        """
        Obtiene la ruta para una pagina especifica.

        Args:
            page_number (int): Numero de pagina

        Returns:
            Path: Ruta de la pagina (documento/pagina/)
        """
        if self.document_path is None:
            raise ValueError("Document no inicializado. Llamar initialize_document() primero.")

        page_path = self.document_path / f"page_{page_number:03d}"
        page_path.mkdir(parents=True, exist_ok=True)
        return page_path

    def get_output_path(self, page_number, output_type="debug"):
        """
        Obtiene la ruta para guardar imagenes de un tipo especifico.

        Args:
            page_number (int): Numero de pagina
            output_type (str): Tipo de output ('debug', 'normalized', 'warped', etc.)

        Returns:
            Path: Ruta del output (documento/pagina/output_type/)
        """
        page_path = self.get_page_path(page_number)
        output_path = page_path / output_type
        output_path.mkdir(parents=True, exist_ok=True)
        return output_path

    def save_image(self, image, page_number, output_type="debug", filename=None):
        """
        Guarda una imagen en la estructura de directorios correcta.

        Args:
            image (numpy.ndarray): Imagen a guardar
            page_number (int): Numero de pagina
            output_type (str): Tipo de output ('debug', 'normalized', 'warped', etc.)
            filename (str): Nombre del archivo (sin extension). Si es None, usa timestamp.

        Returns:
            Path: Ruta completa del archivo guardado
        """
        output_path = self.get_output_path(page_number, output_type)

        if filename is None:
            timestamp = datetime.now().strftime("%H%M%S_%f")[:-3]
            filename = f"image_{timestamp}"

        filepath = output_path / f"{filename}.jpg"

        success = cv2.imwrite(str(filepath), image)

        if success:
            logger.debug("Imagen guardada: %s", filepath)
        else:
            logger.error("Error al guardar: %s", filepath)

        return filepath

    def save_debug_image(self, image, page_number, filename=None):
        """Conveniencia para guardar imagenes de debug."""
        return self.save_image(image, page_number, "debug", filename)

    def save_normalized_image(self, image, page_number, filename=None):
        """Conveniencia para guardar imagenes normalizadas."""
        return self.save_image(image, page_number, "normalized", filename)

    def save_warped_image(self, image, page_number, filename=None):
        """Conveniencia para guardar imagenes warpificadas."""
        return self.save_image(image, page_number, "warped", filename)

    def get_document_structure(self):
        """
        Retorna la estructura de directorios del documento actual como string.
        Util para debugging.
        """
        if self.document_path is None:
            return "No document initialized"

        structure = []
        for root, _, files in os.walk(self.document_path):
            level = root.replace(str(self.document_path), "").count(os.sep)
            indent = " " * 2 * level
            structure.append(f"{indent}{os.path.basename(root)}/")
            subindent = " " * 2 * (level + 1)
            for file in files:
                structure.append(f"{subindent}{file}")

        return "\n".join(structure)


def get_storage(base_path="output_tracking"):
    return ImageStorage(base_path=base_path)


def save_processing_stage(storage, image, page_number, stage_name, filename=None):
    """
    Guarda una imagen en una etapa de procesamiento especifica.

    Args:
        image (numpy.ndarray): Imagen a guardar
        page_number (int): Numero de pagina
        stage_name (str): Nombre de la etapa ('original', 'threshold', 'contours', etc.)
        filename (str): Nombre del archivo (opcional)

    Returns:
        Path: Ruta del archivo guardado
    """
    return storage.save_image(image, page_number, stage_name, filename)


def save_comparison_images(storage, original, processed, page_number, stage_name, prefix=""):
    """
    Guarda tanto la imagen original como la procesada para comparacion.

    Args:
        original (numpy.ndarray): Imagen original
        processed (numpy.ndarray): Imagen procesada
        page_number (int): Numero de pagina
        stage_name (str): Nombre de la etapa
        prefix (str): Prefijo para los nombres de archivo

    Returns:
        tuple: (Path original, Path procesada)
    """
    filename_orig = f"{prefix}_original" if prefix else "original"
    filename_proc = f"{prefix}_processed" if prefix else "processed"

    path_orig = storage.save_image(original, page_number, stage_name, filename_orig)
    path_proc = storage.save_image(processed, page_number, stage_name, filename_proc)

    return path_orig, path_proc


def get_current_document_path(storage):
    """Retorna la ruta actual del documento siendo procesado."""
    if storage.document_path is None:
        raise ValueError("No hay documento inicializado. Llamar a initialize_document() primero.")
    return storage.document_path


def print_document_structure(storage):
    """Registra la estructura de directorios del documento actual."""
    separator = "=" * 60
    logger.info("\n%s\nESTRUCTURA DE DIRECTORIOS DEL DOCUMENTO\n%s\n%s\n%s",
               separator, separator, storage.get_document_structure(), separator)
