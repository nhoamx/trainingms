import cv2
import numpy as np
from matplotlib import pyplot as plt

class BubbleSheetProcessor:
    def __init__(self, image_path):
        self.image_path = image_path
        self.image = cv2.imread(image_path)
        self.gray = cv2.cvtColor(self.image, cv2.COLOR_BGR2GRAY)
        self.thresh = None
        self.bubble_contours = []
        self.filled_bubble_locations = []

    def preprocess_image(self):
        """Aplica preprocesamiento a la imagen para reducir ruido y binarizarla."""
        self.gray = cv2.GaussianBlur(self.gray, (5, 5), 0)
        _, self.thresh = cv2.threshold(self.gray, 150, 255, cv2.THRESH_BINARY_INV)

    def detect_bubbles(self):
        """Detecta los contornos de las burbujas y filtra las válidas."""
        contours, _ = cv2.findContours(self.thresh, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        y_limit = int(self.gray.shape[0] * 0.15)  # Limitar detección al 15% inferior

        for contour in contours:
            area = cv2.contourArea(contour)
            perimeter = cv2.arcLength(contour, True)
            if perimeter == 0:
                continue
            circularity = 4 * np.pi * (area / (perimeter ** 2))
            x, y, w, h = cv2.boundingRect(contour)
            aspect_ratio = w / h

            # Filtro por área, circularidad, relación de aspecto y posición
            if 50 < area < 150 and 0.7 < circularity < 1.2 and 0.8 < aspect_ratio < 1.2 and y > y_limit:
                self.bubble_contours.append(contour)

    def draw_detected_bubbles(self):
        """Dibuja los contornos detectados en la imagen original."""
        output_image = self.image.copy()
        cv2.drawContours(output_image, self.bubble_contours, -1, (0, 255, 0), 2)
        plt.figure(figsize=(10, 10))
        plt.imshow(cv2.cvtColor(output_image, cv2.COLOR_BGR2RGB))
        plt.title("Burbujas Detectadas (Filtradas)")
        plt.axis('off')
        plt.show()

    def detect_filled_bubbles(self):
        """Detecta las burbujas llenas basándose en el nivel de relleno."""
        for contour in self.bubble_contours:
            x, y, w, h = cv2.boundingRect(contour)
            roi = self.thresh[y:y + h, x:x + w]
            total_pixels = w * h
            filled_pixels = cv2.countNonZero(roi)
            fill_ratio = filled_pixels / total_pixels

            # Considerar burbuja llena si el ratio supera el umbral
            if fill_ratio > 0.5:
                center_x = x + w // 2
                center_y = y + h // 2
                self.filled_bubble_locations.append((center_x, center_y))

    def get_filled_bubble_locations(self):
        """Devuelve las ubicaciones de las burbujas llenas."""
        return self.filled_bubble_locations

# Ejemplo de uso
if __name__ == "__main__":
    processor = BubbleSheetProcessor('test.png')
    processor.preprocess_image()
    processor.detect_bubbles()
    processor.draw_detected_bubbles()
    processor.detect_filled_bubbles()
    filled_locations = processor.get_filled_bubble_locations()
    print("Ubicaciones de burbujas llenas:", filled_locations)
