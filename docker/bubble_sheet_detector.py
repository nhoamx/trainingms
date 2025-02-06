import cv2
import numpy as np
import config
import os
import json

def create_directory(path):
    """Create directory if it doesn't exist."""
    os.makedirs(path, exist_ok=True)

def save_image(path, img):
    """Save image to the specified path."""
    create_directory(os.path.dirname(path))
    cv2.imwrite(path, img)

def preprocess_image(img_path):
    """Load and preprocess the image."""
    img = cv2.imread(img_path, cv2.IMREAD_GRAYSCALE)
    save_image('output/gray/gray.png', img)
    
    blurred_img = cv2.GaussianBlur(img, (5, 5), 0)
    save_image('output/blurred/blurred.png', blurred_img)
    
    return blurred_img

def threshold_image(img, threshold):
    """Threshold the image to create a binary image."""
    _, binary_img = cv2.threshold(img, threshold, 255, cv2.THRESH_BINARY_INV)
    save_image('output/binary/binary.png', binary_img)
    return binary_img

def detect_bubbles(img_path, bubble_positions, threshold=150):
    """Detect filled bubbles in the image."""
    try:
        blurred_img = preprocess_image(img_path)
        binary_img = threshold_image(blurred_img, threshold)
        
        results = {}
        skip_to_next_pre = False

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

        print(results.get(66))
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

def draw_bubble_positions(img_path, bubble_positions, output_path):
    """Draw bubble positions on the image."""
    try:
        img = cv2.imread(img_path)
        for question, positions in bubble_positions.items():
            for option, pos in positions.items():
                x, y, w, h = pos
                cv2.rectangle(img, (x, y), (x + w, y + h), (0, 255, 0), 2)
                cv2.putText(img, f"{question}-{option}", (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 1)
        save_image(output_path, img)
    except Exception as e:
        print(f"Error drawing bubble positions: {e}")

if __name__ == "__main__":
    img_path = 'test_pages-to-jpg-0004.jpg'
    folio = config.folio_configuration
    evaluation_01 = config.evaluation_01

    results = detect_bubbles(img_path, evaluation_01)
    draw_bubble_positions(img_path, evaluation_01, 'output/output_with_bubbles.png')

    # Guardar resultados en un archivo JSON
    output_json_path = "output/results.json"
    os.makedirs(os.path.dirname(output_json_path), exist_ok=True)  # Asegurar que el directorio exista
    
    with open(output_json_path, "w", encoding="utf-8") as json_file:
        json.dump(results, json_file, indent=4, ensure_ascii=False)  # Guardar con formato legible

    print(f"Resultados guardados en {output_json_path}")

    # for question, answer in results.items():
    #     print(f"{question}: {answer}")