#!/usr/bin/env python3
"""
Calibration script to help identify bubble positions in OMR templates.
This script displays an aligned image and allows clicking to get coordinates.
"""

import cv2
import numpy as np
import sys

# Global variables
coordinates = []
current_position = None

def mouse_callback(event, x, y, flags, param):
    """Callback function to capture mouse clicks and movements."""
    global current_position, coordinates
    
    if event == cv2.EVENT_MOUSEMOVE:
        current_position = (x, y)
    
    elif event == cv2.EVENT_LBUTTONDOWN:
        coordinates.append((x, y))
        print(f"Coordenada capturada: ({x}, {y})")
        print(f"Total de coordenadas: {len(coordinates)}")
        
        # Si tenemos 4 coordenadas (para un bubble), calcular el rectángulo
        if len(coordinates) % 4 == 0:
            start_idx = len(coordinates) - 4
            x1, y1 = coordinates[start_idx]
            x2, y2 = coordinates[start_idx + 2]
            
            # Calcular x, y, ancho, alto
            x_min = min(x1, x2)
            y_min = min(y1, y2)
            width = abs(x2 - x1)
            height = abs(y2 - y1)
            
            print(f"  → Rectángulo: ({x_min}, {y_min}, {width}, {height})")
            print()

def main():
    if len(sys.argv) < 2:
        print("Uso: python calibrate_bubbles.py <imagen_alineada.png>")
        print("  Haz clic en las esquinas de cada burbuja para obtener las coordenadas.")
        print("  Cada 4 clics se calculará automáticamente el rectángulo (x, y, ancho, alto).")
        sys.exit(1)
    
    image_path = sys.argv[1]
    
    # Cargar imagen
    img = cv2.imread(image_path)
    if img is None:
        print(f"Error: No se pudo cargar la imagen '{image_path}'")
        sys.exit(1)
    
    print(f"Imagen cargada: {img.shape[1]}x{img.shape[0]}")
    print()
    print("INSTRUCCIONES:")
    print("- Haz clic en las esquinas de cada burbuja")
    print("- Cada 4 clics se mostrará el rectángulo calculado")
    print("- Presiona 'q' para salir")
    print("- Presiona 'r' para reiniciar las coordenadas")
    print()
    
    # Crear ventana y configurar callback
    window_name = "Calibración de Burbujas"
    cv2.namedWindow(window_name, cv2.WINDOW_NORMAL)
    cv2.setMouseCallback(window_name, mouse_callback)
    
    while True:
        # Crear copia de la imagen para dibujar
        display_img = img.copy()
        
        # Dibujar coordenadas capturadas
        for i, (x, y) in enumerate(coordinates):
            cv2.circle(display_img, (x, y), 5, (0, 255, 0), -1)
            cv2.putText(display_img, str(i+1), (x+10, y+10), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 1)
        
        # Dibujar rectángulos completos
        for i in range(0, len(coordinates) - 3, 4):
            x1, y1 = coordinates[i]
            x2, y2 = coordinates[i + 2]
            cv2.rectangle(display_img, (x1, y1), (x2, y2), (255, 0, 0), 2)
        
        # Dibujar posición actual del mouse
        if current_position:
            x, y = current_position
            cv2.line(display_img, (x, 0), (x, img.shape[0]), (0, 0, 255), 1)
            cv2.line(display_img, (0, y), (img.shape[1], y), (0, 0, 255), 1)
            cv2.putText(display_img, f"({x}, {y})", (x+10, y-10),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 255), 1)
        
        # Mostrar imagen
        cv2.imshow(window_name, display_img)
        
        # Esperar tecla
        key = cv2.waitKey(1) & 0xFF
        
        if key == ord('q'):
            break
        elif key == ord('r'):
            coordinates = []
            print("\n--- Coordenadas reiniciadas ---\n")
    
    cv2.destroyAllWindows()
    
    # Imprimir resumen
    print("\n" + "="*60)
    print("RESUMEN DE COORDENADAS CAPTURADAS:")
    print("="*60)
    
    for i in range(0, len(coordinates), 4):
        if i + 3 < len(coordinates):
            x1, y1 = coordinates[i]
            x2, y2 = coordinates[i + 2]
            
            x_min = min(x1, x2)
            y_min = min(y1, y2)
            width = abs(x2 - x1)
            height = abs(y2 - y1)
            
            print(f"Burbuja {i//4 + 1}: ({x_min}, {y_min}, {width}, {height})")

if __name__ == "__main__":
    main()
