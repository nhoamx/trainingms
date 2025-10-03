"""
Script para depurar la detección del folio.
Visualiza las burbujas del folio en una imagen alineada.
"""

import cv2
import config

# Cargar imagen alineada
image_path = "/app/outputs_aligned/page_1_aligned.png"
img = cv2.imread(image_path)

if img is None:
    print(f"Error: No se pudo cargar la imagen {image_path}")
    exit(1)

print(f"Imagen cargada: {img.shape}")
print(f"Dibujando burbujas del folio...")

# Dibujar las burbujas del folio
colors = {
    'F1': (255, 0, 0),    # Azul - Template Type (posición 1)
    'F2': (255, 0, 0),    # Azul - Template Type (posición 2)
    'F3': (0, 255, 0),    # Verde - Org Code (posición 1)
    'F4': (0, 255, 0),    # Verde - Org Code (posición 2)
    'F5': (0, 255, 0),    # Verde - Org Code (posición 3)
    'F6': (0, 255, 255),  # Amarillo - Person Code (posición 1)
    'F7': (0, 255, 255),  # Amarillo - Person Code (posición 2)
    'F8': (0, 255, 255),  # Amarillo - Person Code (posición 3)
    'F9': (0, 255, 255),  # Amarillo - Person Code (posición 4)
}

# Dibujar todas las burbujas
for column_name, column_bubbles in config.folio_configuration.items():
    color = colors.get(column_name, (128, 128, 128))
    for digit, (x, y, w, h) in column_bubbles.items():
        # Dibujar rectángulo
        cv2.rectangle(img, (x, y), (x + w, y + h), color, 2)
        # Dibujar número del dígito
        cv2.putText(img, str(digit), (x + 5, y + 25), 
                    cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2)

# Agregar leyenda
legend_y = 50
cv2.putText(img, "F1-F2: Template Type (Azul)", (50, legend_y), 
            cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 0, 0), 2)
cv2.putText(img, "F3-F5: Org Code (Verde)", (50, legend_y + 40), 
            cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)
cv2.putText(img, "F6-F9: Person Code (Amarillo)", (50, legend_y + 80), 
            cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 255), 2)

# Guardar imagen
output_path = "/app/debug_folio_bubbles.png"
cv2.imwrite(output_path, img)
print(f"Imagen guardada en: {output_path}")
print("\nNOTA: Las burbujas del folio deberían estar alineadas con")
print("los círculos impresos en el PDF. Si no lo están, necesitas")
print("recalibrar las coordenadas en config_legacy.py")
