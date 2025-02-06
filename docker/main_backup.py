from pdf_to_image_converter import PDFToImageConverter
from bubble_detector import BubbleDetector
import os
import shutil
import config
import json

output_folder = "/app/output_images"
output_json_folder = "/app/output"

# Crear la carpeta de salida si no existe
os.makedirs(output_json_folder, exist_ok=True)

def get_main_answers(image_file, detector):
    """Obtiene las respuestas de la evaluación y las guarda en un JSON."""
    try:
        folio_data = detector.detect_bubbles(image_file, config.folio_configuration)
        answers = detector.detect_bubbles(image_file, config.evaluation_01)

        # Convertir los valores del diccionario `folio_data` en un solo string (folio único)
        folio = "".join(str(value) for value in folio_data.values() if value is not None)
        if not folio:
            folio = "unknown"  # Asignar un folio por defecto si está vacío

        json_filename = f"{folio}.json"
        json_output_path = os.path.join(output_json_folder, json_filename)

        with open(json_output_path, 'w') as json_file:
            json.dump(answers, json_file, indent=4)

        print(f"Resultados guardados en: {json_output_path}")

    except Exception as e:
        print(f"Error procesando {image_file}: {e}")


# Verifica si output_images está vacío, si no, lo vacía
if os.path.exists(output_folder):
    if os.listdir(output_folder):
        print(f"{output_folder} no está vacío. Vaciando la carpeta...")
        shutil.rmtree(output_folder)
os.makedirs(output_folder, exist_ok=True)

# Convertir PDF en imágenes
print(f"La carpeta {output_folder} está vacía. Convirtiendo PDF en imágenes...")
pdf_path = "/app/input/evaluation.pdf"  # Cambiar por la ruta real del PDF
converter = PDFToImageConverter(pdf_path, output_folder)
image_files = converter.convert()
print(f"{len(image_files)} imágenes generadas.")

# Instanciar el detector de burbujas
detector = BubbleDetector()

# Iterar sobre las imágenes y procesarlas
print("Obteniendo las respuestas de las evaluaciones...")
for image_file in image_files:
    print(f"Procesando imagen: {image_file}")
    get_main_answers(image_file, detector)



# for image_file in image_files:
#     print(f"Procesando imagen: {image_file}")
    # results = detect_bubbles(image_file, config.evaluation_01)
    # draw_bubble_positions(image_file, config.evaluation_01, f"output/{os.path.basename(image_file)}")

    # # Guardar resultados en un archivo JSON
    # json_output_path = f"output/{os.path.basename(image_file).replace('.png', '.json')}"
    # with open(json_output_path, 'w') as json_file:
    #     json.dump(results, json_file, indent=4)
    # print(f"Resultados guardados en: {json_output_path}")
