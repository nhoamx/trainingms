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
    
    def detect_bubbles(self, img_path, bubble_positions, min_fill_threshold=100, debug=False, validate_single_answer=True):
        """
        Detecta burbujas seleccionadas en la imagen.
        
        Args:
            img_path: Ruta a la imagen
            bubble_positions: Dict con posiciones de burbujas {pregunta: {opcion: (x,y,w,h)}}
            min_fill_threshold: Mínimo de píxeles oscuros para considerar una burbuja marcada
                               (default 100, ajustar según tamaño de burbujas)
            debug: Si es True, imprime información de detección
            validate_single_answer: Si es True, rechaza respuestas múltiples (returns None si >1 marcada).
                                    Si es False, selecciona la opción con más píxeles (comportamiento anterior).
                                    Default True para evaluaciones, False para folio/datos demográficos.
        
        Returns:
            Dict con resultados {pregunta: opcion_seleccionada o None}
            Si validate_single_answer=True y hay múltiples respuestas marcadas, retorna None
        """
        print(f"Detecting bubbles in {img_path}...")
        try:
            blurred_img = self.preprocess_image(img_path)
            binary_img = self.threshold_image(blurred_img)
            
            results = {}
            
            for question, positions in bubble_positions.items():
                selected_option = None
                max_non_white_pixels = 0
                pixel_counts = {}  # Para debug
                marked_bubbles = []  # Lista de burbujas que superan el umbral
                
                for option, pos in positions.items():
                    x, y, w, h = pos
                    bubble_roi = binary_img[y:y+h, x:x+w]
                    non_white_pixels = cv2.countNonZero(bubble_roi)
                    pixel_counts[option] = non_white_pixels
                    
                    # Verificar si esta burbuja supera el umbral
                    if non_white_pixels >= min_fill_threshold:
                        marked_bubbles.append((option, non_white_pixels))
                    
                    if non_white_pixels > max_non_white_pixels:
                        max_non_white_pixels = non_white_pixels
                        selected_option = option
                
                if debug:
                    print(f"  Question '{question}': pixel counts = {pixel_counts}")
                    print(f"    Marked bubbles: {len(marked_bubbles)}, Max pixels: {max_non_white_pixels}, Threshold: {min_fill_threshold}")
                
                # VALIDACIÓN: Si validate_single_answer=True y hay más de una burbuja marcada
                if validate_single_answer and len(marked_bubbles) > 1:
                    results[question] = None
                    if debug:
                        print(f"    ⚠ MULTIPLE ANSWERS DETECTED ({len(marked_bubbles)} bubbles) → Setting to NULL")
                        print(f"       Marked: {[opt for opt, _ in marked_bubbles]}")
                # Si hay exactamente una burbuja marcada, asignar esa opción
                elif len(marked_bubbles) == 1:
                    results[question] = marked_bubbles[0][0]
                    if debug:
                        print(f"    ✓ MARKED as '{marked_bubbles[0][0]}'")
                # Si validate_single_answer=False y hay >1 burbuja, seleccionar la con más píxeles (comportamiento anterior)
                elif validate_single_answer is False and max_non_white_pixels >= min_fill_threshold:
                    results[question] = selected_option
                    if debug:
                        print(f"    ✓ MARKED as '{selected_option}' (validate_single_answer=False, multiple detected)")
                # Si no hay burbujas marcadas, retornar None
                else:
                    results[question] = None
                    if debug:
                        print(f"    ✗ NOT MARKED (below threshold)")
            
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
