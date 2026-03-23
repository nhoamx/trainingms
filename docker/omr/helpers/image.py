import cv2


def load_image(path):
    image = cv2.imread(path)
    if image is None:
        raise ValueError(f"No se pudo cargar la imagen: {path}")
    return image


def normalize_size(image, width=1000, height=1400, page_number=None, save=False, storage=None):
    """
    Normaliza el tamano de una imagen.

    Args:
        image (numpy.ndarray): Imagen a normalizar
        width (int): Ancho destino
        height (int): Alto destino
        page_number (int): Numero de pagina (requerido si save=True)
        save (bool): Si es True, guarda la imagen normalizada

    Returns:
        numpy.ndarray: Imagen normalizada
    """
    normalized = cv2.resize(image, (width, height))

    if save and page_number is not None:
        if storage is None:
            raise ValueError("storage es requerido cuando save=True")
        storage.save_normalized_image(normalized, page_number, "normalized")

    return normalized
