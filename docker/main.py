from pdf_to_image_converter import PDFToImageConverter
from bubble_detector import BubbleDetector
import os
import shutil
import config
import json
import cv2
import numpy as np

# Definir rutas absolutas dentro del contenedor
output_folder = "/app/output_images"
output_json_folder = "/app/output"
output_with_markers_folder = "/app/output_with_markers"

# Crear las carpetas de salida si no existen
os.makedirs(output_folder, exist_ok=True)
os.makedirs(output_json_folder, exist_ok=True)
os.makedirs(output_with_markers_folder, exist_ok=True)

def detect_folio(image_file, detector):
    """
    Detecta el folio a partir de la imagen usando la configuración de folio.
    Retorna el folio como string o "unknown" en caso de error.
    """
    import logging
    try:
        logging.info(f"Intentando detectar folio en {image_file}")
        folio_data = detector.detect_bubbles(image_file, config.folio_configuration)
        logging.debug(f"Datos de folio detectados: {folio_data}")
        
        # Combinar los valores detectados en un único string
        folio = "".join(str(value) for value in folio_data.values() if value is not None)
        logging.info(f"Folio detectado en {image_file}: {folio}")
        
        if not folio or len(folio) < 9:
            logging.warning(f"Folio incompleto o inválido: '{folio}' (longitud: {len(folio) if folio else 0})")
            logging.debug(f"Datos completos de folio: {folio_data}")
            folio = "unknown"
        return folio
    except Exception as e:
        logging.error(f"Error detectando folio en {image_file}: {e}", exc_info=True)
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

def get_referencia_iii_complete_answers(image_file, detector, folio):
    """
    Obtiene las respuestas completas de Referencia III con todas sus 5 secciones:
    1. referencia_iii - 46 preguntas principales
    2. customer_service_conditional - pregunta condicional SI|NO 
    3. customer_service_questions - preguntas 65-68 con opciones A-E
    4. conditional_management - pregunta condicional gestión SI|NO
    5. management_questions - preguntas 69-72 con opciones A-E
    6. citsats_s1 - 6 preguntas con SI|NO
    """
    import logging
    try:
        complete_answers = {}
        
        # 1. Sección principal - 46 preguntas
        logging.info("Detectando sección principal (46 preguntas)...")
        main_answers = detector.detect_bubbles(image_file, config.config_legacy.referencia_iii)
        complete_answers['referencia_iii'] = main_answers
        
        # 2. Pregunta condicional servicio cliente (primera definición - solo SI|NO)
        logging.info("Detectando pregunta condicional servicio cliente...")
        customer_service_conditional = {
            'condition': {
                'SI': (1719, 2306, 45, 45),  # A de la primera definición
                'NO': (1874, 2306, 45, 45),  # B de la primera definición
            }
        }
        cs_conditional = detector.detect_bubbles(image_file, customer_service_conditional)
        complete_answers['customer_service_conditional'] = cs_conditional
        
        # 3. Preguntas de servicio cliente 65-68 (segunda definición completa)
        logging.info("Detectando preguntas de servicio cliente...")
        if hasattr(config, 'config_legacy') and hasattr(config.config_legacy, 'customer_service_questions'):
            cs_answers = detector.detect_bubbles(image_file, config.config_legacy.customer_service_questions)
            complete_answers['customer_service_questions'] = cs_answers
        else:
            logging.warning("No se encontró config.config_legacy.customer_service_questions")
        
        # 4. Pregunta condicional gestión
        logging.info("Detectando pregunta condicional gestión...")
        if hasattr(config, 'config_legacy') and hasattr(config.config_legacy, 'conditional_management'):
            cm_answers = detector.detect_bubbles(image_file, config.config_legacy.conditional_management)
            complete_answers['conditional_management'] = cm_answers
        else:
            logging.warning("No se encontró config.config_legacy.conditional_management")
        
        # 5. Preguntas de gestión 69-72
        logging.info("Detectando preguntas de gestión...")
        if hasattr(config, 'config_legacy') and hasattr(config.config_legacy, 'management_questions'):
            mg_answers = detector.detect_bubbles(image_file, config.config_legacy.management_questions)
            complete_answers['management_questions'] = mg_answers
        else:
            logging.warning("No se encontró config.config_legacy.management_questions")
        
        # 6. CITSATS-s1
        logging.info("Detectando sección CITSATS-s1...")
        if hasattr(config, 'config_legacy') and hasattr(config.config_legacy, 'citsats_s1'):
            citsats_answers = detector.detect_bubbles(image_file, config.config_legacy.citsats_s1)
            complete_answers['citsats_s1'] = citsats_answers
        else:
            logging.warning("No se encontró config.config_legacy.citsats_s1")
        
        # Guardar el JSON completo
        json_filename = f"{folio}.json"
        json_output_path = os.path.join(output_json_folder, json_filename)
        with open(json_output_path, 'w') as json_file:
            json.dump(complete_answers, json_file, indent=4)
        
        logging.info(f"Resultados completos de Referencia III guardados en: {json_output_path}")
        logging.info(f"Secciones detectadas: {list(complete_answers.keys())}")
        
    except Exception as e:
        logging.error(f"Error procesando Referencia III completa en {image_file}: {e}")
        import traceback
        logging.error(traceback.format_exc())

def get_referencia_v_complete_answers(image_file, detector, folio, min_fill_threshold=800):
    """
    Obtiene las respuestas completas de Referencia V con todas sus subsecciones demográficas.
    A diferencia de las otras referencias, aquí cada subsección es independiente.
    
    Args:
        min_fill_threshold: Umbral mínimo de píxeles para considerar una burbuja marcada (default 800)
    """
    import logging
    try:
        complete_answers = {}
        
        # Procesar subsecciones simples
        simple_subsections = [
            ('sexo', 'sexo'),
            ('edad', 'edad'),
            ('estado_civil', 'estado_civil'),
            ('tipo_personal', 'tipo_personal'),
            ('tipo_puesto', 'tipo_puesto'),
            ('tipo_contratacion', 'tipo_contratacion'),
            ('tipo_jornada', 'tipo_jornada'),
            ('rotacion_turnos', 'rotacion_turnos'),
            ('tiempo_puesto_actual', 'tiempo_puesto_actual'),
            ('experiencia_laboral', 'experiencia_laboral'),
            ('ocupacion', 'ocupacion'),
            ('departamento', 'departamento'),
        ]
        
        for section_name, config_attr in simple_subsections:
            logging.info(f"Detectando subsección: {section_name}...")
            if hasattr(config.config_legacy, config_attr):
                section_config = getattr(config.config_legacy, config_attr)
                
                # Para secciones especiales con estructura anidada (edad, ocupacion, departamento)
                if section_name == 'edad':
                    # Edad tiene estructura especial: {'decenas': {...}, 'unidades': {...}}
                    edad_result = {}
                    if 'decenas' in section_config:
                        decenas_answer = detector.detect_bubbles(image_file, {'decenas': section_config['decenas']}, min_fill_threshold=min_fill_threshold)
                        edad_result['decenas'] = decenas_answer.get('decenas')
                    if 'unidades' in section_config:
                        unidades_answer = detector.detect_bubbles(image_file, {'unidades': section_config['unidades']}, min_fill_threshold=min_fill_threshold)
                        edad_result['unidades'] = unidades_answer.get('unidades')
                    complete_answers[section_name] = edad_result
                elif section_name in ['ocupacion', 'departamento']:
                    # Ocupación y departamento tienen estructura: {'fila1': {...}, 'fila2': {...}}
                    result = {}
                    if 'fila1' in section_config:
                        fila1_answer = detector.detect_bubbles(image_file, {'fila1': section_config['fila1']}, min_fill_threshold=min_fill_threshold)
                        result['fila1'] = fila1_answer.get('fila1')
                    if 'fila2' in section_config:
                        fila2_answer = detector.detect_bubbles(image_file, {'fila2': section_config['fila2']}, min_fill_threshold=min_fill_threshold)
                        result['fila2'] = fila2_answer.get('fila2')
                    complete_answers[section_name] = result
                else:
                    # Secciones normales
                    section_answer = detector.detect_bubbles(image_file, {section_name: section_config}, min_fill_threshold=min_fill_threshold)
                    complete_answers[section_name] = section_answer.get(section_name)
            else:
                logging.warning(f"No se encontró config.config_legacy.{config_attr}")
        
        # --- Procesamiento especial para nivel_estudios ---
        logging.info("Procesando nivel_estudios (consolidado)...")
        if hasattr(config.config_legacy, 'referencia_v') and 'nivel_estudios' in config.config_legacy.referencia_v:
            nivel_estudios_config = config.config_legacy.referencia_v['nivel_estudios']
            
            # Estructura para el resultado consolidado
            nivel_estudios_result = {
                'sin_formacion': False,
                'primaria': False,
                'secundaria': False,
                'preparatoria': False,
                'tecnico_superior': False,
                'licenciatura': False,
                'maestria': False,
                'doctorado': False,
            }
            
            # Procesar cada nivel educativo
            niveles = ['sin_formacion', 'primaria', 'secundaria', 'preparatoria', 
                      'tecnico_superior', 'licenciatura', 'maestria', 'doctorado']
            
            logging.info("Procesando nivel_estudios (consolidado)...")
            for nivel in niveles:
                if nivel in nivel_estudios_config:
                    nivel_config = nivel_estudios_config[nivel]
                    
                    if nivel == 'sin_formacion':
                        # sin_formacion solo tiene una opción
                        answer = detector.detect_bubbles(image_file, {nivel: nivel_config}, min_fill_threshold=min_fill_threshold)
                        detected_option = answer.get(nivel)
                        # Solo marcar como True si se detectó algo (no None)
                        if detected_option == 'sin_formacion':
                            nivel_estudios_result['sin_formacion'] = True
                        # Si es None, queda en False (default)
                    else:
                        # Otros niveles tienen 'terminada' e 'incompleta'
                        answer = detector.detect_bubbles(image_file, {nivel: nivel_config}, min_fill_threshold=min_fill_threshold)
                        detected_option = answer.get(nivel)
                        
                        # Solo crear estructura si realmente se detectó algo
                        if detected_option == 'terminada':
                            nivel_estudios_result[nivel] = {'seleccionado': True, 'completado': 'completo'}
                        elif detected_option == 'incompleta':
                            nivel_estudios_result[nivel] = {'seleccionado': True, 'completado': 'incompleto'}
                        # Si detected_option es None, queda en False (default)
            
            complete_answers['nivel_estudios'] = nivel_estudios_result
        else:
            logging.warning("No se encontró nivel_estudios en referencia_v")
        
        # Guardar el JSON completo
        json_filename = f"{folio}.json"
        json_output_path = os.path.join(output_json_folder, json_filename)
        with open(json_output_path, 'w') as json_file:
            json.dump(complete_answers, json_file, indent=4)
        
        logging.info(f"Resultados completos de Referencia V guardados en: {json_output_path}")
        logging.info(f"Subsecciones detectadas: {list(complete_answers.keys())}")
        
    except Exception as e:
        logging.error(f"Error procesando Referencia V completa en {image_file}: {e}")
        import traceback
        logging.error(traceback.format_exc())

def get_main_answers_legacy(image_file, detector, evaluation_config, folio):
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

def save_image_with_markers(image_path, folio, marker_positions=None, bubble_configs=None):
    """
    Genera y guarda una imagen con marcadores de alineación y burbujas destacadas.
    
    Args:
        image_path: Ruta a la imagen procesada
        folio: Folio de la evaluación
        marker_positions: Lista de tuplas (x, y) con las posiciones de los marcadores de alineación
        bubble_configs: Dict con configuración de burbujas {pregunta: {opcion: (x,y,w,h)}}
    """
    import logging
    try:
        # Leer la imagen
        img = cv2.imread(image_path)
        if img is None:
            logging.error(f"No se pudo leer la imagen: {image_path}")
            return
        
        # Crear una copia para dibujar
        img_marked = img.copy()
        
        # Dibujar marcadores de alineación (4 esquinas) en verde
        if marker_positions is None:
            # Usar posiciones estimadas de las 4 esquinas basadas en el tamaño de la imagen
            h, w = img.shape[:2]
            margin = 20  # Margen desde el borde
            marker_size = 30
            marker_positions = [
                (margin, margin),  # Superior izquierda
                (w - margin - marker_size, margin),  # Superior derecha
                (margin, h - margin - marker_size),  # Inferior izquierda
                (w - margin - marker_size, h - margin - marker_size)  # Inferior derecha
            ]
        
        # Dibujar marcadores de alineación como rectángulos verdes
        for (x, y) in marker_positions:
            cv2.rectangle(img_marked, (x, y), (x + 30, y + 30), (0, 255, 0), 3)
        
        # Dibujar burbujas detectadas en azul
        if bubble_configs:
            for question, positions in bubble_configs.items():
                for option, (x, y, w, h) in positions.items():
                    # Dibujar círculo azul para cada burbuja
                    center_x = x + w // 2
                    center_y = y + h // 2
                    radius = max(w, h) // 2
                    cv2.circle(img_marked, (center_x, center_y), radius, (255, 0, 0), 2)
        
        # Guardar la imagen con marcadores
        output_path = os.path.join(output_with_markers_folder, f"{folio}.png")
        cv2.imwrite(output_path, img_marked)
        logging.info(f"Imagen con marcadores guardada: {output_path}")
        
    except Exception as e:
        logging.error(f"Error guardando imagen con marcadores para folio {folio}: {e}")
        import traceback
        logging.error(traceback.format_exc())

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
from alinear_con_marcadores import detectar_marcadores_4_esquinas, alinear_imagen, IDEAL_POSITIONS, LABELS

# Configurar logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)s: %(message)s')

# Mapeo de template types a archivos de referencia
REFERENCE_IMAGES = {
    '01': '/app/reference-referencia-i.png',      # Referencia I
    '02': '/app/reference-referencia-iii.png',    # Referencia III
    '03': '/app/reference-referencia-v.png',      # Referencia V
    '04': '/app/reference-referencia-i.png',      # Escala Cisneros (usar Ref I como fallback)
}

def get_reference_image_for_template(template_type):
    """
    Obtiene la imagen de referencia correcta según el template type.
    """
    ref_path = REFERENCE_IMAGES.get(template_type, '/app/reference-referencia-i.png')
    
    ref_img = cv2.imread(ref_path)
    if ref_img is None:
        logging.warning(f"No se pudo cargar {ref_path}, usando referencia por defecto")
        ref_img = cv2.imread('/app/reference-referencia-i.png')
        if ref_img is None:
            # Último fallback
            ref_img = cv2.imread('/app/reference-test-page.png')
            logging.warning("Usando referencia legacy")
    else:
        logging.info(f"Usando referencia para template {template_type}: {ref_path}")
    
    return ref_img

def detect_template_type_from_image(image_file, detector):
    """
    Intenta detectar el template type leyendo los primeros 2 dígitos del folio.
    Esto se hace ANTES de la alineación precisa, por lo que puede tener errores.
    Si no se puede detectar, retorna '01' (Referencia I) como default.
    """
    try:
        folio_data = detector.detect_bubbles(image_file, config.folio_configuration)
        folio = "".join(str(value) for value in folio_data.values() if value is not None)
        
        if folio and len(folio) >= 2:
            template_type = folio[:2]
            if template_type in REFERENCE_IMAGES:
                logging.info(f"Template type detectado: {template_type} del folio preliminar: {folio}")
                return template_type
        
        logging.warning(f"No se pudo detectar template type del folio '{folio}', usando default '02' (Ref III)")
        return '02'  # Default a Referencia III que es la más común
    except Exception as e:
        logging.warning(f"Error detectando template type: {e}, usando default '02'")
        return '02'

aligned_image_files = []
outputs_aligned_folder = "/app/outputs_aligned"
output_original_folder = "/app/output_original"
os.makedirs(outputs_aligned_folder, exist_ok=True)
os.makedirs(output_original_folder, exist_ok=True)

for image_file in image_files:
    logging.info("="*50)
    logging.info(f"Procesando imagen: {image_file}")
    try:
        # Guardar copia de la imagen original
        original_save_path = os.path.join(output_original_folder, os.path.basename(image_file))
        if not os.path.exists(original_save_path):
            import shutil
            shutil.copy(image_file, original_save_path)
            logging.info(f"Imagen original guardada")
        
        # --- Paso 1: Detectar template type de forma preliminar ---
        template_type = detect_template_type_from_image(image_file, detector)
        
        # --- Paso 2: Cargar referencia específica para ese template ---
        ref_img = get_reference_image_for_template(template_type)
        ref_marcadores = detectar_marcadores_4_esquinas(ref_img, debug_path=None)
        ref_esquinas = ref_marcadores  # Usar los 4 marcadores directamente
        
        # --- Paso 3: Alinear la imagen con la referencia específica ---
        img = cv2.imread(image_file)
        img_marcadores = detectar_marcadores_4_esquinas(img, debug_path=None)
        img_esquinas = img_marcadores  # Usar los 4 marcadores directamente
        alineada = alinear_imagen(img, ref_esquinas, img_esquinas, (ref_img.shape[1], ref_img.shape[0]))
        aligned_filename = os.path.basename(image_file).replace(".png", "_aligned.png")
        aligned_save_path = os.path.join(outputs_aligned_folder, aligned_filename)
        cv2.imwrite(aligned_save_path, alineada)
        logging.info(f"Imagen alineada guardada: {aligned_filename}")
        aligned_image_files.append(aligned_save_path)
    except Exception as e:
        logging.warning(f"No se pudo alinear {image_file}: {e}. Se omite esta imagen.")
        continue

# --- El resto del pipeline usa las imágenes alineadas ---
for image_file in aligned_image_files:
    logging.info(f"Procesando imagen alineada: {image_file}")

    # Detectar el folio a partir de la imagen alineada
    folio = detect_folio(image_file, detector)

    # Validar que el folio inicie con 01, 02, 03, 04 (template types)
    valid_prefixes = ['01', '02', '03', '04']
    is_valid = any(folio.startswith(prefix) for prefix in valid_prefixes)
    
    if not is_valid:
        logging.warning(f"Imagen {image_file} skipeada: folio '{folio}' no inicia con template válido {', '.join(valid_prefixes)}.")
        continue
    
    logging.info(f"Folio válido detectado: {folio}")

    # Guardar la imagen alineada con el folio en output_images
    new_image_path = os.path.join(output_folder, f"{folio}.png")
    try:
        import shutil
        shutil.copy(image_file, new_image_path)
        logging.info(f"Imagen alineada copiada y guardada como: {new_image_path}")
    except Exception as e:
        logging.error(f"No se pudo copiar {image_file} a {new_image_path}: {e}")
        continue

    # --- Limpiar archivos page_#.png de output_images y dejar solo los {folio}.png ---
    import glob
    page_pattern = os.path.join(output_folder, "page_*.png")
    for page_file in glob.glob(page_pattern):
        try:
            os.remove(page_file)
        except Exception as e:
            pass  # O puedes loguear el error si lo deseas

    # Seleccionar la configuración de evaluación según el template type (primeros 2 dígitos)
    template_type = folio[:2]
    bubble_config = None
    
    if template_type == "01":
        # Referencia I - Acontecimientos traumáticos
        evaluation_config = config.config_legacy.referencia_i if hasattr(config.config_legacy, 'referencia_i') else config.config_legacy.referencia_iii
        bubble_config = evaluation_config
        logging.info(f"Folio {folio} → Referencia I (Acontecimientos Traumáticos)")
        get_main_answers_legacy(new_image_path, detector, evaluation_config, folio)
    elif template_type == "02":
        # Referencia III - Evaluación principal COMPLETA (6 secciones)
        bubble_config = config.config_legacy.referencia_iii if hasattr(config.config_legacy, 'referencia_iii') else None
        logging.info(f"Folio {folio} → Referencia III COMPLETA (6 secciones)")
        get_referencia_iii_complete_answers(new_image_path, detector, folio)
    elif template_type == "03":
        # Referencia V - Datos del evaluado (20 subsecciones)
        logging.info(f"Folio {folio} → Referencia V COMPLETA (20 subsecciones demográficas)")
        get_referencia_v_complete_answers(new_image_path, detector, folio)
    elif template_type == "04":
        # Escala Cisneros
        evaluation_config = config.config_legacy.escala_cisneros if hasattr(config.config_legacy, 'escala_cisneros') else config.config_legacy.referencia_iii
        bubble_config = evaluation_config
        logging.info(f"Folio {folio} → Escala Cisneros (Mobbing)")
        get_main_answers_legacy(new_image_path, detector, evaluation_config, folio)
    else:
        logging.warning(f"Template type '{template_type}' no reconocido, usando evaluación por defecto")
        evaluation_config = config.config_legacy.referencia_iii
        bubble_config = evaluation_config
        get_main_answers_legacy(new_image_path, detector, evaluation_config, folio)
    
    # Generar imagen con marcadores y burbujas
    logging.info(f"Generando imagen con marcadores para folio {folio}...")
    save_image_with_markers(new_image_path, folio, marker_positions=None, bubble_configs=bubble_config)
