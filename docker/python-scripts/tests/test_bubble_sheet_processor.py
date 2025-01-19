import cv2
import numpy as np
import pytest
from answer_detector import BubbleSheetProcessor

@pytest.fixture
def generate_test_image():
    """Genera una imagen de prueba con burbujas simuladas."""
    # Crear una imagen en blanco
    image = np.ones((500, 500, 3), dtype=np.uint8) * 255

    # Dibujar burbujas (círculos negros)
    cv2.circle(image, (100, 400), 20, (0, 0, 0), -1)  # Burbujas llenas
    cv2.circle(image, (200, 400), 20, (0, 0, 0), -1)
    cv2.circle(image, (300, 400), 20, (0, 0, 0), -1)
    cv2.circle(image, (400, 400), 20, (255, 255, 255), -1)  # Burbujas vacías

    # Guardar la imagen de prueba
    test_image_path = "test_image.png"
    cv2.imwrite(test_image_path, image)
    return test_image_path

def test_bubble_detection(generate_test_image):
    """Prueba la detección de burbujas y burbujas llenas."""
    image_path = generate_test_image
    processor = BubbleSheetProcessor(image_path)

    # Preprocesar la imagen
    processor.preprocess_image()

    # Detectar burbujas
    processor.detect_bubbles()
    assert len(processor.bubble_contours) == 4, "Debería detectar 4 burbujas"

    # Detectar burbujas llenas
    processor.detect_filled_bubbles()
    filled_locations = processor.get_filled_bubble_locations()
    assert len(filled_locations) == 3, "Debería detectar 3 burbujas llenas"

    # Comprobar ubicaciones
    expected_locations = [(100, 400), (200, 400), (300, 400)]
    detected_locations = sorted(filled_locations)
    for loc, exp_loc in zip(detected_locations, expected_locations):
        assert abs(loc[0] - exp_loc[0]) < 5 and abs(loc[1] - exp_loc[1]) < 5, \
            f"Ubicación {loc} no coincide con {exp_loc}"

