from pdf_to_image_converter import PDFToImageConverter
from bubble_detector import BubbleDetector
import os
import shutil
import config
import json

# Definir rutas absolutas dentro del contenedor
output_folder = "/app/output_images"
output_json_folder = "/app/output"

# Crear las carpetas de salida si no existen
os.makedirs(output_folder, exist_ok=True)
os.makedirs(output_json_folder, exist_ok=True)

def detect_folio(image_file, detector):
    """
    Detecta el folio a partir de la imagen usando la configuración de folio.
    Retorna el folio como string o "unknown" en caso de error.
    """
    try:
        folio_data = detector.detect_bubbles(image_file, config.folio_configuration)
        # Combinar los valores detectados en un único string
        folio = "".join(str(value) for value in folio_data.values() if value is not None)
        if not folio:
            folio = "unknown"
        return folio
    except Exception as e:
        print(f"Error detectando folio en {image_file}: {e}")
        return "unknown"

def get_main_answers(image_file, detector, evaluation_config, folio):
    """
    Obtiene las respuestas de la evaluación utilizando la configuración de evaluación
    y guarda el resultado en un JSON con el nombre basado en el folio.
    """
    try:
        answers = detector.detect_bubbles(image_file, evaluation_config)
        json_filename = f"{folio}.json"
        json_output_path = os.path.join(output_json_folder, json_filename)
        with open(json_output_path, 'w') as json_file:
            json.dump(answers, json_file, indent=4)
        print(f"Resultados guardados en: {json_output_path}")
    except Exception as e:
        print(f"Error procesando {image_file}: {e}")

# Limpieza de la carpeta de imágenes de salida
if os.path.exists(output_folder):
    if os.listdir(output_folder):
        print(f"{output_folder} no está vacío. Vaciando la carpeta...")
        shutil.rmtree(output_folder)
os.makedirs(output_folder, exist_ok=True)

# Convertir PDF en imágenes
print(f"La carpeta {output_folder} está vacía. Convirtiendo PDF en imágenes...")
pdf_path = "/app/input/evaluation.pdf"  # Ruta del PDF
converter = PDFToImageConverter(pdf_path, output_folder)
image_files = converter.convert()
print(f"{len(image_files)} imágenes generadas.")

# Instanciar el detector de burbujas
detector = BubbleDetector()

# Procesar cada imagen generada
print("Procesando imágenes y detectando folios...")
for image_file in image_files:
    print(f"Procesando imagen: {image_file}")
    
    # Detectar el folio a partir de la imagen
    folio = detect_folio(image_file, detector)
    
    # Renombrar la imagen utilizando el folio (se asume formato PNG)
    new_image_path = os.path.join(output_folder, f"{folio}.png")
    try:
        os.rename(image_file, new_image_path)
        print(f"Imagen renombrada a: {new_image_path}")
    except Exception as e:
        print(f"Error renombrando {image_file} a {new_image_path}: {e}")
        new_image_path = image_file  # Continuar con el nombre original en caso de error

    # Seleccionar la configuración de evaluación según el prefijo del folio
    if folio.startswith("12"):
        evaluation_config = config.evaluation_01
    elif folio.startswith("13"):
        #evaluation_config = config.evaluation_02
        print(f"Folio {folio} en desarrollo")
        continue
    elif folio.startswith("17"):
        #evaluation_config = config.evaluation_03
        print(f"Folio {folio} en desarrollo")
        continue
    else:
        # Si el folio no empieza con 12, 13 o 17, se puede asignar una configuración por defecto
        #evaluation_config = config.evaluation_01
        print(f"Folio {folio} no corresponde a evaluación 01, 02 o 03. Usando evaluation_01 por defecto.")
        continue
    
    # Obtener las respuestas de la evaluación y guardar el JSON
    get_main_answers(new_image_path, detector, evaluation_config, folio)
