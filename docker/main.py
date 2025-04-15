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
        print(f"Folio detectado en {image_file}: {folio}")
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

# Limpieza de las carpetas de salida (output, outputs_aligned, output_original)
def limpiar_carpeta(path):
    import shutil, os
    if os.path.exists(path):
        if os.listdir(path):
            print(f"{path} no está vacío. Vaciando la carpeta...")
            shutil.rmtree(path)
    os.makedirs(path, exist_ok=True)

limpiar_carpeta(output_folder)
limpiar_carpeta(output_json_folder)
limpiar_carpeta("/app/outputs_aligned")
limpiar_carpeta("/app/output_original")

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
import cv2
import logging
from alinear_con_marcadores import detectar_marcadores, alinear_imagen, IDEAL_POSITIONS, LABELS

# Configurar logging
#logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)s: %(message)s')

# Cargar la imagen de referencia una sola vez
ref_img = cv2.imread("/app/reference-test-page.png")  # Cambia por tu imagen de referencia real
ref_marcadores = detectar_marcadores(ref_img, debug_path=None, n_points=6)
ref_esquinas = [ref_marcadores[0], ref_marcadores[1], ref_marcadores[4], ref_marcadores[5]]

aligned_image_files = []
outputs_aligned_folder = "/app/outputs_aligned"
output_original_folder = "/app/output_original"
os.makedirs(outputs_aligned_folder, exist_ok=True)
os.makedirs(output_original_folder, exist_ok=True)

for image_file in image_files:
#    logging.info("==============================")
#    logging.info(f"Procesando imagen: {image_file}")
    try:
        # Guardar copia de la imagen original
        original_save_path = os.path.join(output_original_folder, os.path.basename(image_file))
        if not os.path.exists(original_save_path):
            import shutil
            shutil.copy(image_file, original_save_path)
#            logging.info(f"Imagen original guardada en: {original_save_path}")
        # --- Paso 1: Alinear la imagen ---
        img = cv2.imread(image_file)
        img_marcadores = detectar_marcadores(img, debug_path=None, n_points=6)
        img_esquinas = [img_marcadores[0], img_marcadores[1], img_marcadores[4], img_marcadores[5]]
        alineada = alinear_imagen(img, ref_esquinas, img_esquinas, (ref_img.shape[1], ref_img.shape[0]))
        aligned_filename = os.path.basename(image_file).replace(".png", "_aligned.png")
        aligned_save_path = os.path.join(outputs_aligned_folder, aligned_filename)
        cv2.imwrite(aligned_save_path, alineada)
#        logging.info(f"Imagen alineada guardada en: {aligned_save_path}")
        aligned_image_files.append(aligned_save_path)
    except Exception as e:
#        logging.warning(f"No se pudo alinear {image_file}: {e}. Se omite esta imagen.")
        continue

# --- El resto del pipeline usa las imágenes alineadas ---
for image_file in aligned_image_files:
#    logging.info(f"Procesando imagen alineada: {image_file}")

    # Detectar el folio a partir de la imagen alineada
    folio = detect_folio(image_file, detector)

    # Validar que el folio inicie con 12, 13 o 17
    if not (folio.startswith('12') or folio.startswith('13') or folio.startswith('17')):
#        logging.warning(f"Imagen {image_file} skipeada: folio '{folio}' no inicia con 12, 13 o 17.")
        continue

    # Guardar la imagen alineada con el folio en output_images
    new_image_path = os.path.join(output_folder, f"{folio}.png")
    try:
        import shutil
        shutil.copy(image_file, new_image_path)
#        logging.info(f"Imagen alineada copiada y guardada como: {new_image_path}")
    except Exception as e:
#        logging.error(f"No se pudo copiar {image_file} a {new_image_path}: {e}")
        continue

    # --- Limpiar archivos page_#.png de output_images y dejar solo los {folio}.png ---
    import glob
    page_pattern = os.path.join(output_folder, "page_*.png")
    for page_file in glob.glob(page_pattern):
        try:
            os.remove(page_file)
        except Exception as e:
            pass  # O puedes loguear el error si lo deseas

    # Seleccionar la configuración de evaluación según el prefijo del folio
    if folio.startswith("12"):
        # Referencia de evaluacion principal
        evaluation_config = config.evaluation_01
        print(f"Folio {folio} referencia III")
    elif folio.startswith("13"):
        # Referencia de acontecimientos
        evaluation_config = config.reference_i
        print(f"Folio {folio} referencia I")
    elif folio.startswith("17"):
        # Datos del evaluado
        evaluation_config = config.reference_v
        print(f"Folio {folio} referencia V")
    else:
        # Si el folio no empieza con 12, 13 o 17, se puede asignar una configuración por defecto
        #evaluation_config = config.evaluation_01
        print(f"Folio {folio} no hace match cno ninguna referencia")
        continue

    # Obtener las respuestas de la evaluación y guardar el JSON
    get_main_answers(new_image_path, detector, evaluation_config, folio)
