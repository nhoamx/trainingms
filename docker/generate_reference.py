#!/usr/bin/env python3
"""
Script to generate a reference image from the test PDF.
This will be used as the reference for alignment in main.py.
"""

import sys
import os
from pdf_to_image_converter import PDFToImageConverter

def main():
    if len(sys.argv) < 3:
        print("Uso: python generate_reference.py <pdf_path> <output_png>")
        print("Ejemplo: python generate_reference.py input/evaluation.pdf reference-test-page.png")
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    output_path = sys.argv[2]
    
    if not os.path.exists(pdf_path):
        print(f"Error: El archivo PDF '{pdf_path}' no existe")
        sys.exit(1)
    
    print(f"Convirtiendo PDF a imagen...")
    print(f"  PDF: {pdf_path}")
    print(f"  Salida: {output_path}")
    
    # Crear carpeta temporal
    temp_folder = "/app/temp_reference"
    os.makedirs(temp_folder, exist_ok=True)
    
    try:
        # Convertir solo la primera página
        converter = PDFToImageConverter(pdf_path, temp_folder)
        images = converter.convert()
        
        if not images:
            print("Error: No se generaron imágenes")
            sys.exit(1)
        
        # Copiar la primera imagen al destino
        import shutil
        shutil.copy(images[0], output_path)
        
        print(f"✅ Imagen de referencia generada: {output_path}")
        
        # Limpiar carpeta temporal
        shutil.rmtree(temp_folder)
        
    except Exception as e:
        print(f"❌ Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
