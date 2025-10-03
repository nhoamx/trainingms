#!/usr/bin/env python3
"""
Script para detectar automáticamente las posiciones de las burbujas en un template OMR.
Este script NO requiere interfaz gráfica y puede ejecutarse en Docker.
"""

import cv2
import numpy as np
import sys
import json

def detect_bubble_grid(image_path, output_file="bubble_coordinates.json"):
    """
    Detecta automáticamente las burbujas en una imagen alineada.
    """
    print(f"Analizando imagen: {image_path}")
    
    # Leer imagen
    img = cv2.imread(image_path)
    if img is None:
        print(f"Error: No se pudo leer la imagen {image_path}")
        return None
    
    print(f"Dimensiones de imagen: {img.shape[1]}x{img.shape[0]}")
    
    # Convertir a escala de grises
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    
    # Aplicar threshold inverso para detectar círculos oscuros
    _, binary = cv2.threshold(gray, 200, 255, cv2.THRESH_BINARY_INV)
    
    # Detectar contornos
    contours, _ = cv2.findContours(binary, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    
    print(f"Contornos detectados: {len(contours)}")
    
    # Filtrar contornos que parezcan burbujas (circulares, tamaño apropiado)
    bubbles = []
    for cnt in contours:
        area = cv2.contourArea(cnt)
        
        # Filtrar por área (burbujas típicas: 500-2000 px²)
        if 300 < area < 3000:
            # Verificar circularidad
            perimeter = cv2.arcLength(cnt, True)
            if perimeter > 0:
                circularity = 4 * np.pi * area / (perimeter * perimeter)
                
                if circularity > 0.5:  # Razonablemente circular
                    x, y, w, h = cv2.boundingRect(cnt)
                    
                    # Verificar que sea aproximadamente cuadrado
                    aspect_ratio = float(w) / h if h > 0 else 0
                    if 0.7 < aspect_ratio < 1.3:
                        bubbles.append({
                            'x': int(x),
                            'y': int(y),
                            'width': int(w),
                            'height': int(h),
                            'area': int(area),
                            'circularity': float(circularity)
                        })
    
    print(f"Burbujas detectadas: {len(bubbles)}")
    
    # Ordenar por posición (primero por Y, luego por X)
    bubbles_sorted = sorted(bubbles, key=lambda b: (b['y'] // 50, b['x']))
    
    # Analizar estructura de grid
    print("\n=== ANÁLISIS DE ESTRUCTURA ===")
    analyze_bubble_structure(bubbles_sorted)
    
    # Guardar resultados
    with open(output_file, 'w') as f:
        json.dump({
            'total_bubbles': len(bubbles_sorted),
            'bubbles': bubbles_sorted,
            'image_dimensions': {
                'width': img.shape[1],
                'height': img.shape[0]
            }
        }, f, indent=2)
    
    print(f"\n✅ Coordenadas guardadas en: {output_file}")
    
    return bubbles_sorted

def analyze_bubble_structure(bubbles):
    """
    Analiza la estructura de las burbujas para identificar patrones (filas, columnas).
    """
    if not bubbles:
        return
    
    # Agrupar por filas (tolerancia de ±25px en Y)
    rows = []
    current_row = [bubbles[0]]
    
    for i in range(1, len(bubbles)):
        if abs(bubbles[i]['y'] - current_row[0]['y']) < 25:
            current_row.append(bubbles[i])
        else:
            rows.append(current_row)
            current_row = [bubbles[i]]
    
    if current_row:
        rows.append(current_row)
    
    print(f"Filas detectadas: {len(rows)}")
    
    # Mostrar primeras 10 filas
    for i, row in enumerate(rows[:10]):
        avg_y = sum(b['y'] for b in row) / len(row)
        x_positions = sorted([b['x'] for b in row])
        print(f"  Fila {i+1}: {len(row)} burbujas, Y≈{int(avg_y)}, X: {x_positions[:5]}...")
    
    if len(rows) > 10:
        print(f"  ... y {len(rows) - 10} filas más")

def generate_folio_config_from_bubbles(bubbles_file="bubble_coordinates.json"):
    """
    Genera configuración de folio a partir de las burbujas detectadas.
    Asume que las primeras 90 burbujas son el folio (9 columnas x 10 filas).
    """
    with open(bubbles_file, 'r') as f:
        data = json.load(f)
    
    bubbles = data['bubbles']
    
    if len(bubbles) < 90:
        print(f"⚠️ Advertencia: Se esperaban al menos 90 burbujas para el folio, se encontraron {len(bubbles)}")
    
    print("\n=== GENERANDO CONFIGURACIÓN DE FOLIO ===")
    
    # Agrupar por columnas (primeras 90 burbujas)
    folio_bubbles = bubbles[:90]
    
    # Ordenar por X para identificar columnas
    x_positions = sorted(set([b['x'] for b in folio_bubbles]))
    
    if len(x_positions) < 9:
        print(f"⚠️ Se encontraron solo {len(x_positions)} columnas, se esperaban 9")
    
    print(f"\nColumnas detectadas en X: {x_positions[:9]}")
    
    # Generar configuración
    print("\n# Pega esto en docker/config.py:\n")
    print("folio_configuration = {")
    
    for col_idx, x_pos in enumerate(x_positions[:9], 1):
        # Filtrar burbujas de esta columna (±15px de tolerancia)
        col_bubbles = [b for b in folio_bubbles if abs(b['x'] - x_pos) < 15]
        col_bubbles_sorted = sorted(col_bubbles, key=lambda b: b['y'])
        
        print(f"    'F{col_idx}': {{")
        for digit, bubble in enumerate(col_bubbles_sorted[:10]):
            print(f"        '{digit}': ({bubble['x']}, {bubble['y']}, {bubble['width']}, {bubble['height']}),")
        print("    },")
    
    print("}")

def main():
    if len(sys.argv) < 2:
        print("Uso: python auto_detect_bubbles.py <imagen_alineada.png>")
        print("\nEjemplo:")
        print("  python auto_detect_bubbles.py /app/outputs_aligned/page_1_aligned.png")
        print("\nEsto generará:")
        print("  1. bubble_coordinates.json con todas las burbujas detectadas")
        print("  2. Configuración Python para folio_configuration")
        sys.exit(1)
    
    image_path = sys.argv[1]
    
    # Detectar burbujas
    bubbles = detect_bubble_grid(image_path)
    
    if bubbles:
        # Generar configuración de folio
        generate_folio_config_from_bubbles()
        
        print("\n\n📝 PASOS SIGUIENTES:")
        print("1. Revisa bubble_coordinates.json para ver todas las burbujas detectadas")
        print("2. Copia la configuración generada a docker/config.py")
        print("3. Ajusta manualmente si es necesario")
        print("4. Ejecuta pruebas con PDFs reales")

if __name__ == "__main__":
    main()
