import cv2
import numpy as np
import os
import json

class BubbleDetector:
    def __init__(self, output_dir="output", threshold=150):
        """Inicializa la clase con la carpeta de salida y el umbral de binarización."""
        self.output_dir = output_dir
        self.threshold = threshold
        self._create_directory(self.output_dir)
    
    def _create_directory(self, path):
        """Crea un directorio si no existe."""
        os.makedirs(path, exist_ok=True)
    
    def _save_image(self, path, img):
        """Guarda una imagen en la ruta especificada."""
        self._create_directory(os.path.dirname(path))
        cv2.imwrite(path, img)
    
    def preprocess_image(self, img_path):
        """Carga y preprocesa la imagen."""
        img = cv2.imread(img_path, cv2.IMREAD_GRAYSCALE)
        gray_path = os.path.join(self.output_dir, 'gray.png')
        self._save_image(gray_path, img)
        
        blurred_img = cv2.GaussianBlur(img, (5, 5), 0)
        blurred_path = os.path.join(self.output_dir, 'blurred.png')
        self._save_image(blurred_path, blurred_img)
        
        return blurred_img
    
    def threshold_image(self, img):
        """Binariza la imagen."""
        _, binary_img = cv2.threshold(img, self.threshold, 255, cv2.THRESH_BINARY_INV)
        binary_path = os.path.join(self.output_dir, 'binary.png')
        self._save_image(binary_path, binary_img)
        return binary_img
    
    def detect_bubbles(self, img_path, bubble_positions):
        """Detecta burbujas seleccionadas en la imagen."""
        print(f"Detecting bubbles in {img_path}...")
        try:
            blurred_img = self.preprocess_image(img_path)
            binary_img = self.threshold_image(blurred_img)
            
            results = {}
            
            for question, positions in bubble_positions.items():
                selected_option = None
                max_non_white_pixels = 0
                for option, pos in positions.items():
                    x, y, w, h = pos
                    bubble_roi = binary_img[y:y+h, x:x+w]
                    non_white_pixels = cv2.countNonZero(bubble_roi)
                    
                    if non_white_pixels > max_non_white_pixels:
                        max_non_white_pixels = non_white_pixels
                        selected_option = option
                
                results[question] = selected_option
            
            if results.get('pre1') == 'no':
                for q in range(65, 69):
                    results[str(q)] = None
            
            if results.get('pre2') == 'no':
                for q in range(69, 73):
                    results[str(q)] = None
            
            return results
        except Exception as e:
            print(f"Error detecting bubbles: {e}")
            return {}
    
    def draw_bubble_positions(self, img_path, bubble_positions, output_path):
        """Dibuja las posiciones de las burbujas en la imagen."""
        try:
            img = cv2.imread(img_path)
            for question, positions in bubble_positions.items():
                for option, pos in positions.items():
                    x, y, w, h = pos
                    cv2.rectangle(img, (x, y), (x + w, y + h), (0, 255, 0), 2)
                    cv2.putText(img, f"{question}-{option}", (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 1)
            self._save_image(output_path, img)
        except Exception as e:
            print(f"Error drawing bubble positions: {e}")
