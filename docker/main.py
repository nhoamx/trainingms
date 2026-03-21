from pdf_to_image_converter import PDFToImageConverter
from bubble_detector import BubbleDetector
from alinear_con_marcadores import detectar_marcadores_4_esquinas, alinear_imagen, IDEAL_POSITIONS, LABELS, markers_logger
import os
import shutil
import base64
import logging
import tempfile
import config_legacy as config
import json
import cv2
import numpy as np

# Definir rutas absolutas dentro del contenedor
output_folder = "/app/output_images"
output_json_folder = "/app/output"
output_with_markers_folder = "/app/output_with_markers"
input_folder = "/app/input"

# Crear las carpetas de salida si no existen
os.makedirs(output_folder, exist_ok=True)
os.makedirs(output_json_folder, exist_ok=True)
os.makedirs(output_with_markers_folder, exist_ok=True)
os.makedirs(input_folder, exist_ok=True)

def detect_folio(image_file, detector):
    """
    Detecta el folio a partir de la imagen usando la configuración de folio.
    Retorna el folio como string o "unknown" en caso de error.
    """
    import logging
    try:
        logging.info(f"Intentando detectar folio en {image_file}")
        # validate_single_answer=False para folio porque pueden haber datos demograficos que no sean tipo selección única
        folio_data = detector.detect_bubbles(image_file, config.folio_configuration, validate_single_answer=False)
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
        return answers
    except Exception as e:
        print(f"Error procesando {image_file}: {e}")
        return {}

def get_referencia_iii_complete_answers(image_file, detector, folio):
    """
    Obtiene las respuestas completas de Referencia III con todas sus 5 secciones:
    1. referencia_iii - 46 preguntas principales
    2. customer_service_conditional - pregunta condicional SI|NO 
    3. customer_service_questions - preguntas 65-68 con opciones A-E
    4. conditional_management - pregunta condicional gestión SI|NO
    5. management_questions - preguntas 69-72 con opciones A-E
    6. citsats_s1 - 6 preguntas con SI|NO
    
    Si la respuesta condicional es NO, las preguntas relacionadas se ponen en null
    """
    import logging
    try:
        complete_answers = {}
        
        # 1. Sección principal - 46 preguntas
        logging.info("Detectando sección principal (46 preguntas)...")
        main_answers = detector.detect_bubbles(image_file, config.referencia_iii)
        complete_answers['referencia_iii'] = main_answers
        
        # 2. Pregunta condicional servicio cliente (primera definición - solo SI|NO)
        logging.info("Detectando pregunta condicional servicio cliente...")
        cs_conditional = detector.detect_bubbles(image_file, config.conditional_customer_service)
        complete_answers['customer_service_conditional'] = cs_conditional
        
        # 3. Preguntas de servicio cliente 65-68 (segunda definición completa)
        logging.info("Detectando preguntas de servicio cliente...")
        cs_answers = detector.detect_bubbles(image_file, config.customer_service_questions)
        
        # Si la respuesta condicional es NO, poner null todas las preguntas de servicio cliente
        if cs_conditional.get('condition') == 'NO':
            logging.info("Pregunta condicional de servicio cliente es NO → poniendo preguntas en null")
            # Crear un dict con todos los valores en null
            cs_answers_null = {key: None for key in cs_answers.keys()}
            complete_answers['customer_service_questions'] = cs_answers_null
        else:
            complete_answers['customer_service_questions'] = cs_answers
        
        # 4. Pregunta condicional gestión
        logging.info("Detectando pregunta condicional gestión...")
        cm_answers = detector.detect_bubbles(image_file, config.conditional_management)
        complete_answers['conditional_management'] = cm_answers
        
        # 5. Preguntas de gestión 69-72
        logging.info("Detectando preguntas de gestión...")
        mg_answers = detector.detect_bubbles(image_file, config.management_questions)
        
        # Si la respuesta condicional de gestión es NO, poner null todas las preguntas de gestión
        if cm_answers.get('condition') == 'NO':
            logging.info("Pregunta condicional de gestión es NO → poniendo preguntas en null")
            # Crear un dict con todos los valores en null
            mg_answers_null = {key: None for key in mg_answers.keys()}
            complete_answers['management_questions'] = mg_answers_null
        else:
            complete_answers['management_questions'] = mg_answers
        
        # 6. CITSATS-s1
        logging.info("Detectando sección CITSATS-s1...")
        citsats_answers = detector.detect_bubbles(image_file, config.citsats_s1)
        complete_answers['citsats_s1'] = citsats_answers
        
        # Guardar el JSON completo
        json_filename = f"{folio}.json"
        json_output_path = os.path.join(output_json_folder, json_filename)
        with open(json_output_path, 'w') as json_file:
            json.dump(complete_answers, json_file, indent=4)
        
        logging.info(f"Resultados completos de Referencia III guardados en: {json_output_path}")
        logging.info(f"Secciones detectadas: {list(complete_answers.keys())}")
        return complete_answers

    except Exception as e:
        logging.error(f"Error procesando Referencia III completa en {image_file}: {e}")
        import traceback
        logging.error(traceback.format_exc())
        return {}

def get_likert_complete_answers(image_file, detector, folio, min_fill_threshold=400):
    """
    Obtiene las respuestas completas de Likert (template 05 y 06) automáticamente.
    Soporta tanto Likert estándar (05) como Likert Planta 3 (06).
    
    Estructura común:
    - 23 preguntas con opciones A/B/C/D
    - Demografía: género, turno, tipo de contrato
    - Listas verticales: puestos y áreas (cantidad varía según template)
    
    Args:
        folio: Incluye el template type en los primeros 2 dígitos (05 o 06)
        min_fill_threshold: Umbral mínimo de píxeles para considerar una burbuja marcada (default 400)
    """
    import logging
    try:
        # Detectar template type desde el folio (primeros 2 dígitos)
        template_type = folio[:2]
        is_planta_3 = template_type == '06'
        
        # Construir mapeos dinámicos según template
        config_prefix = 'likert_planta_3' if is_planta_3 else 'likert'
        
        logging.info(f"Procesando template {template_type} ({config_prefix})")
        markers_logger.info(f"  === get_likert_complete_answers INICIO ===")
        markers_logger.info(f"  Imagen: {os.path.basename(image_file)}")
        markers_logger.info(f"  Folio: {folio}")
        markers_logger.info(f"  Template: {template_type} ({'Planta 3' if is_planta_3 else 'Estándar'})")
        markers_logger.info(f"  Config prefix: {config_prefix}")
        markers_logger.info(f"  Min fill threshold: {min_fill_threshold}")
        
        complete_answers = {}
        
        # 1. Procesar preguntas principales Likert (1-23, A/B/C/D)
        logging.info(f"  Sección 1: Preguntas Likert (1-23) usando config '{config_prefix}'...")
        markers_logger.info(f"  SECCIÓN 1: Preguntas Likert (1-23)")
        questions_config = getattr(config, config_prefix, None)
        if questions_config is not None:
            markers_logger.info(f"    Config encontrado con {len(questions_config)} preguntas")
            likert_answers = detector.detect_bubbles(image_file, questions_config, min_fill_threshold=min_fill_threshold)
            markers_logger.info(f"    Respuestas detectadas:")
            
            # REGLA ESPECIAL LIKERT: Si una pregunta tiene más de 1 respuesta o está vacía (None),
            # marcarla como 'A' (Totalmente de Acuerdo)
            for question_num in range(1, 24):  # Preguntas 1-23
                q_key = str(question_num)
                original_answer = likert_answers.get(q_key)
                if q_key in likert_answers and likert_answers[q_key] is None:
                    logging.info(f"    Pregunta {q_key}: sin respuesta → Asignando 'A'")
                    markers_logger.info(f"      Q{q_key}: None → 'A' (regla especial)")
                    likert_answers[q_key] = 'A'
                else:
                    markers_logger.info(f"      Q{q_key}: {original_answer}")
                    
            # Guardar bajo la clave correcta según el template
            complete_answers[config_prefix] = likert_answers
            logging.info(f"  ✓ Preguntas detectadas: {len(likert_answers)}")
            markers_logger.info(f"    Total preguntas: {len(likert_answers)}")
        else:
            logging.warning(f"  ✗ No se encontró config.{config_prefix}, omitiendo preguntas")
            markers_logger.warning(f"    Config NO encontrado: config.{config_prefix}")
        
        # 2. Procesar demografía simple (3 secciones: género, turno, tipo de contrato)
        logging.info(f"  Sección 2: Demografía usando prefijo '{config_prefix}'...")
        markers_logger.info(f"  SECCIÓN 2: Demografía")
        demographic_mapping = [
            ('genero', 'genero'),
            ('turno', 'turno'),
            ('tipo_contrato', 'tipo_contrato'),
        ]
        
        for config_suffix, result_key in demographic_mapping:
            config_attr = f'{config_prefix}_{config_suffix}'
            logging.info(f"    Detectando {result_key} (config_attr={config_attr})...")
            
            if hasattr(config, config_attr):
                section_config = getattr(config, config_attr)
                section_answer = detector.detect_bubbles(image_file, {result_key: section_config}, min_fill_threshold=min_fill_threshold)
                detected_value = section_answer.get(result_key)
                complete_answers[result_key] = detected_value
                logging.info(f"      ✓ Detectado: {detected_value}")
                markers_logger.info(f"    {result_key}: {detected_value}")
            else:
                complete_answers[result_key] = None
                logging.warning(f"      ✗ No se encontró config.{config_attr}")
                markers_logger.warning(f"    {result_key}: Config NO encontrado ({config_attr})")
        
        # 3. Procesar listas verticales (puestos y áreas)
        # El número de items varía según template: 05 usa 24 puestos/17 áreas, 06 usa 19 puestos/10 áreas
        logging.info(f"  Sección 3: Listas verticales usando prefijo '{config_prefix}'...")
        markers_logger.info(f"  SECCIÓN 3: Listas verticales (puestos/áreas)")
        
        # Procesar Puestos
        puestos_config_attr = f'{config_prefix}_puestos'
        logging.info(f"    Detectando puestos (config_attr={puestos_config_attr})...")
        if hasattr(config, puestos_config_attr):
            puestos_config = getattr(config, puestos_config_attr)
            num_puestos = len(puestos_config)
            logging.info(f"      Config encontrado con {num_puestos} items")
            markers_logger.info(f"    Puestos: buscando en {num_puestos} posiciones")
            puesto_detectado = None
            
            # Iterar sobre todas las coordenadas (claves '1' a N)
            for i in range(1, num_puestos + 1):
                key = str(i)
                if key in puestos_config:
                    x, y, w, h = puestos_config[key]
                    
                    # Detectar si la burbuja está marcada
                    temp_config = {'puesto': {'temp': (x, y, w, h)}}
                    temp_answer = detector.detect_bubbles(image_file, temp_config, min_fill_threshold=min_fill_threshold)
                    
                    if temp_answer.get('puesto') == 'temp':
                        puesto_detectado = i  # Número 1-based
                        logging.info(f"      ✓ Puesto {i} DETECTADO")
                        markers_logger.info(f"      Puesto {i} MARCADO (pos: {x},{y},{w},{h})")
                        break
            
            complete_answers['puestos'] = puesto_detectado
            if puesto_detectado:
                logging.info(f"    Puesto seleccionado: {puesto_detectado}")
            else:
                logging.info(f"    Ningún puesto seleccionado (null)")
                markers_logger.info(f"      Ningún puesto detectado")
        else:
            complete_answers['puestos'] = None
            logging.warning(f"      ✗ No se encontró config.{puestos_config_attr}")
            markers_logger.warning(f"    Puestos: Config NO encontrado ({puestos_config_attr})")
        
        # Procesar Áreas
        areas_config_attr = f'{config_prefix}_areas'
        logging.info(f"    Detectando áreas (config_attr={areas_config_attr})...")
        if hasattr(config, areas_config_attr):
            areas_config = getattr(config, areas_config_attr)
            num_areas = len(areas_config)
            logging.info(f"      Config encontrado con {num_areas} items")
            markers_logger.info(f"    Áreas: buscando en {num_areas} posiciones")
            area_detectada = None
            
            # Iterar sobre todas las coordenadas (claves '1' a N)
            for i in range(1, num_areas + 1):
                key = str(i)
                if key in areas_config:
                    x, y, w, h = areas_config[key]
                    
                    # Detectar si la burbuja está marcada
                    temp_config = {'area': {'temp': (x, y, w, h)}}
                    temp_answer = detector.detect_bubbles(image_file, temp_config, min_fill_threshold=min_fill_threshold)
                    
                    if temp_answer.get('area') == 'temp':
                        area_detectada = i  # Número 1-based
                        logging.info(f"      ✓ Área {i} DETECTADA")
                        markers_logger.info(f"      Área {i} MARCADA (pos: {x},{y},{w},{h})")
                        break
            
            complete_answers['areas'] = area_detectada
            if area_detectada:
                logging.info(f"    Área seleccionada: {area_detectada}")
            else:
                logging.info(f"    Ningún área seleccionada (null)")
                markers_logger.info(f"      Ningún área detectada")
        else:
            complete_answers['areas'] = None
            logging.warning(f"      ✗ No se encontró config.{areas_config_attr}")
            markers_logger.warning(f"    Áreas: Config NO encontrado ({areas_config_attr})")
        
        # Guardar el JSON completo
        json_filename = f"{folio}.json"
        json_output_path = os.path.join(output_json_folder, json_filename)
        with open(json_output_path, 'w') as json_file:
            json.dump(complete_answers, json_file, indent=4)
        
        markers_logger.info(f"  JSON guardado: {json_filename}")
        markers_logger.info(f"  === get_likert_complete_answers FIN ===")

        logging.info(f"✓ Resultados de {config_prefix} guardados en: {json_output_path}")
        logging.info(f"  Secciones detectadas: {list(complete_answers.keys())}")
        return complete_answers

    except Exception as e:
        logging.error(f"Error procesando Likert en {image_file}: {e}")
        import traceback
        logging.error(traceback.format_exc())
        return {}

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
            if hasattr(config, config_attr):
                section_config = getattr(config, config_attr)
                
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
                logging.warning(f"No se encontró config.{config_attr}")
        
        # --- Procesamiento especial para nivel_estudios ---
        logging.info("Procesando nivel_estudios (consolidado)...")
        if hasattr(config, 'referencia_v') and 'nivel_estudios' in config.referencia_v:
            nivel_estudios_config = config.referencia_v['nivel_estudios']
            
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
        return complete_answers

    except Exception as e:
        logging.error(f"Error procesando Referencia V completa en {image_file}: {e}")
        import traceback
        logging.error(traceback.format_exc())
        return {}

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
        return answers
    except Exception as e:
        print(f"Error procesando {image_file}: {e}")
        return {}

def save_image_with_markers(image_path, folio, marker_positions=None, bubble_configs=None, template_type=None, output_dir=None):
    """
    Genera y guarda una imagen con burbujas detectadas en colores diferentes según la sección.
    
    Para Referencia III:
    - referencia_iii: RED (sin letra)
    - conditional_customer_service: BLUE + letra "C" arriba
    - customer_service_questions: BLUE + letra "C" arriba
    - conditional_management: BLUE + letra "C" arriba
    - management_questions: BLUE + letra "C" arriba
    - citsats_s1: GREEN + letra "S" abajo
    
    Para Referencia I, V, Cisneros: BLUE (sin letra especial)
    
    Args:
        image_path: Ruta a la imagen procesada
        folio: Folio de la evaluación
        marker_positions: No se usa (mantenido por compatibilidad)
        bubble_configs: Lista de dicts con configuración de burbujas {pregunta: {opcion: (x,y,w,h)}}
        template_type: Tipo de template ('01', '02', '03', '04') para aplicar colores apropiados
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

        # 1) Detectar y dibujar marcadores de alineación (4 esquinas) sobre la imagen alineada
        try:
            from alinear_con_marcadores import detectar_marcadores_4_esquinas, LABELS_4_CORNERS
            debug_align_path = os.path.join(output_with_markers_folder, f"{folio}_alignment_debug.png")
            esquinas = detectar_marcadores_4_esquinas(img, debug_path=debug_align_path)
            # Dibujar marcadores detectados (círculos y etiquetas TL/TR/BL/BR)
            for i, (cx, cy) in enumerate(esquinas):
                cv2.circle(img_marked, (cx, cy), 25, (0, 255, 255), 4)  # amarillo
                label = LABELS_4_CORNERS[i] if i < len(LABELS_4_CORNERS) else '?'
                cv2.putText(img_marked, label, (cx + 10, cy - 10), cv2.FONT_HERSHEY_SIMPLEX, 1.0, (0, 255, 255), 3)
        except Exception as e:
            logging = __import__('logging')
            logging.warning(f"No se pudieron detectar/dibujar marcadores de alineación: {e}")
        
        # Definir colores y configuración para cada sección
        # BGR format para OpenCV
        section_config_ref3 = {
            'folio_configuration': {
                'color': (0, 255, 255),    # YELLOW (folio)
                'letter': 'F',
                'letter_position': 'left'
            },
            'referencia_iii': {
                'color': (0, 0, 255),      # RED
                'letter': None,             # Sin letra
                'letter_position': None
            },
            'conditional_customer_service': {
                'color': (255, 0, 0),      # BLUE
                'letter': 'C',
                'letter_position': 'top'   # Arriba
            },
            'customer_service_questions': {
                'color': (255, 0, 0),      # BLUE
                'letter': 'C',
                'letter_position': 'top'   # Arriba
            },
            'conditional_management': {
                'color': (255, 0, 0),      # BLUE
                'letter': 'C',
                'letter_position': 'top'   # Arriba
            },
            'management_questions': {
                'color': (255, 0, 0),      # BLUE
                'letter': 'C',
                'letter_position': 'top'   # Arriba
            },
            'citsats_s1': {
                'color': (0, 255, 0),      # GREEN
                'letter': 'S',
                'letter_position': 'bottom' # Abajo
            }
        }
        
        # Configuración genérica para otras referencias
        section_config_generic = {
            'folio_configuration': {
                'color': (0, 255, 255),    # YELLOW (folio)
                'letter': 'F',
                'letter_position': 'left'
            },
            'default': {
                'color': (255, 0, 0),      # BLUE
                'letter': None,
                'letter_position': None
            }
        }
        
        # Configuración para Likert (template 05)
        section_config_likert = {
            'folio_configuration': {
                'color': (0, 255, 255),    # YELLOW (folio)
                'letter': 'F',
                'letter_position': 'left'
            },
            'likert': {
                'color': (0, 165, 255),    # ORANGE (preguntas principales)
                'letter': None,
                'letter_position': None
            },
            'likert_genero': {
                'color': (255, 0, 255),    # MAGENTA (demografía)
                'letter': 'G',
                'letter_position': 'top'
            },
            'likert_turno': {
                'color': (255, 0, 255),    # MAGENTA (demografía)
                'letter': 'T',
                'letter_position': 'top'
            },
            'likert_tipo_contrato': {
                'color': (255, 0, 255),    # MAGENTA (demografía)
                'letter': 'C',
                'letter_position': 'top'
            },
            'likert_puestos': {
                'color': (0, 255, 255),    # CYAN (listas)
                'letter': 'P',
                'letter_position': 'left'
            },
            'likert_areas': {
                'color': (0, 255, 255),    # CYAN (listas)
                'letter': 'A',
                'letter_position': 'left'
            }
        }
        
        # Seleccionar configuración según el tipo de template
        if template_type == '02':  # Referencia III
            section_config = section_config_ref3
            section_names = list(section_config.keys())
        elif template_type == '05':  # Likert
            section_config = section_config_likert
            section_names = list(section_config.keys())
        else:
            # Para Referencia I, V, Cisneros: usar color azul simple
            section_config = section_config_generic
            section_names = ['default']
        
        # Ajuste vertical para bajar los círculos (en píxeles)
        vertical_offset = 8
        
        # Procesar las configuraciones de burbujas
        bubble_configs_list = []
        if isinstance(bubble_configs, dict):
            bubble_configs_list.append(bubble_configs)
        elif isinstance(bubble_configs, list):
            bubble_configs_list = bubble_configs
        
        logging.info(f"Dibujando burbujas - Template: {template_type}, Configs: {len(bubble_configs_list)}")
        
        for idx, config_dict in enumerate(bubble_configs_list):
            if config_dict is None:
                continue
            
            # Determinar el nombre de la sección basado en el tipo de template
            if template_type == '02':  # Referencia III - mapeo por índice
                if idx < len(section_names):
                    section_name = section_names[idx]
                    color_info = section_config[section_name]
                else:
                    # Fallback
                    color_info = {'color': (100, 100, 100), 'letter': None, 'letter_position': None}
            elif template_type == '05':  # Likert - mapeo por índice
                if idx < len(section_names):
                    section_name = section_names[idx]
                    color_info = section_config[section_name]
                else:
                    # Fallback
                    color_info = {'color': (100, 100, 100), 'letter': None, 'letter_position': None}
            else:
                # Para otras referencias, usar la configuración genérica
                color_info = section_config_generic['default']
            
            color = color_info['color']
            letter = color_info.get('letter')
            letter_position = color_info.get('letter_position')
            
            # Procesar cada burbuja en esta sección
            for question, positions in config_dict.items():
                # Caso especial: si positions es directamente una tupla (x,y,w,h)
                # Esto aplica para likert_puestos y likert_areas que tienen claves '1', '2', etc.
                if isinstance(positions, tuple) and len(positions) == 4:
                    x, y, w, h = positions
                    center_x = x + w // 2
                    center_y = y + h // 2 + vertical_offset
                    radius = max(w, h) // 2
                    cv2.circle(img_marked, (center_x, center_y), radius, color, 2)
                    
                    # Dibujar letra a la izquierda para listas
                    if letter and letter_position == 'left':
                        letter_x = center_x - radius - 25
                        letter_y = center_y + 8
                        font = cv2.FONT_HERSHEY_SIMPLEX
                        font_scale = 0.8
                        font_thickness = 2
                        cv2.putText(img_marked, letter, (letter_x, letter_y), font, font_scale, color, font_thickness)
                elif isinstance(positions, dict):
                    for option, coords in positions.items():
                        if isinstance(coords, tuple) and len(coords) == 4:
                            x, y, w, h = coords
                            # Dibujar círculo con color correspondiente
                            center_x = x + w // 2
                            center_y = y + h // 2 + vertical_offset
                            radius = max(w, h) // 2
                            cv2.circle(img_marked, (center_x, center_y), radius, color, 2)
                            
                            # Dibujar letra si corresponde
                            if letter and template_type in ['02', '05']:
                                # Calcular posición de la letra
                                if letter_position == 'top':
                                    # Arriba del círculo
                                    letter_y = center_y - radius - 15
                                elif letter_position == 'bottom':
                                    # Abajo del círculo
                                    letter_y = center_y + radius + 25
                                elif letter_position == 'left':
                                    # A la izquierda del círculo
                                    letter_x = center_x - radius - 25
                                    letter_y = center_y + 8
                                    font = cv2.FONT_HERSHEY_SIMPLEX
                                    font_scale = 0.8
                                    font_thickness = 2
                                    cv2.putText(img_marked, letter, (letter_x, letter_y), font, font_scale, color, font_thickness)
                                    continue
                                else:
                                    letter_y = center_y
                                
                                letter_x = center_x - 8  # Centrar horizontalmente aproximadamente
                                
                                # Dibujar la letra
                                font = cv2.FONT_HERSHEY_SIMPLEX
                                font_scale = 1.0
                                font_thickness = 2
                                cv2.putText(img_marked, letter, (letter_x, letter_y), font, font_scale, color, font_thickness)
        
    # Guardar la imagen con marcadores
        effective_output_dir = output_dir if output_dir is not None else output_with_markers_folder
        os.makedirs(effective_output_dir, exist_ok=True)
        output_path = os.path.join(effective_output_dir, f"{folio}.png")
        cv2.imwrite(output_path, img_marked)
        logging.info(f"Imagen con burbujas detectadas guardada: {output_path}")
        return output_path

    except Exception as e:
        logging.error(f"Error guardando imagen con marcadores para folio {folio}: {e}")
        import traceback
        logging.error(traceback.format_exc())
        return None

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
limpiar_carpeta("/app/output_with_markers")

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
from alinear_con_marcadores import detectar_marcadores_4_esquinas, alinear_imagen, IDEAL_POSITIONS, LABELS, markers_logger
from datetime import datetime

# Configurar logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)s: %(message)s')

# Log de inicio de ejecución en markers.log
markers_logger.info("=" * 80)
markers_logger.info(f"NUEVA EJECUCIÓN: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
markers_logger.info(f"Total imágenes a procesar: {len(image_files)}")
markers_logger.info("=" * 80)

def detect_valid_folio_quick(image_path, detector):
    """
    Intenta detectar un folio válido (que inicie con 01-06) de forma rápida.
    Retorna el folio si es válido, None si no lo es.
    """
    valid_prefixes = ['01', '02', '03', '04', '05', '06']
    try:
        folio_data = detector.detect_bubbles(image_path, config.folio_configuration, validate_single_answer=False)
        folio = "".join(str(value) for value in folio_data.values() if value is not None)
        
        if folio and len(folio) >= 2:
            if any(folio.startswith(prefix) for prefix in valid_prefixes):
                return folio
    except Exception as e:
        logging.debug(f"Error detectando folio: {e}")
    
    return None

def auto_rotate_landscape_image(image_path, detector):
    """
    Detecta si una imagen está en orientación landscape (horizontal) y la rota
    automáticamente a portrait (vertical) en la dirección correcta.
    
    Lógica:
    1. Si la imagen ya es portrait (height >= width), no hacer nada
    2. Si es landscape, rotar 90° a la izquierda (counterclockwise)
    3. Intentar detectar folio válido
    4. Si no detecta folio válido, rotar 90° a la derecha (clockwise desde original)
    5. Si detecta folio en cualquier dirección, guardar esa orientación
    
    Returns:
        bool: True si se rotó la imagen, False si no fue necesario
    """
    img = cv2.imread(image_path)
    if img is None:
        logging.error(f"No se pudo leer la imagen: {image_path}")
        return False
    
    height, width = img.shape[:2]
    
    # Si ya es portrait, no hacer nada
    if height >= width:
        logging.info(f"Imagen ya está en portrait ({width}x{height}), no requiere rotación")
        return False
    
    logging.info(f"Imagen en landscape ({width}x{height}), intentando auto-rotación...")
    
    # Crear archivo temporal para probar rotaciones
    temp_path = image_path.replace(".png", "_temp_rotation.png")
    
    # Intento 1: Rotar 90° a la izquierda (counterclockwise)
    logging.info("  Intentando rotación 90° counterclockwise (izquierda)...")
    rotated_ccw = cv2.rotate(img, cv2.ROTATE_90_COUNTERCLOCKWISE)
    cv2.imwrite(temp_path, rotated_ccw)
    
    folio = detect_valid_folio_quick(temp_path, detector)
    if folio:
        logging.info(f"  ✓ Folio válido detectado con rotación izquierda: {folio}")
        # Guardar la imagen rotada en la ruta original
        cv2.imwrite(image_path, rotated_ccw)
        # Limpiar archivo temporal
        if os.path.exists(temp_path):
            os.remove(temp_path)
        return True
    
    # Intento 2: Rotar 90° a la derecha (clockwise)
    logging.info("  Intentando rotación 90° clockwise (derecha)...")
    rotated_cw = cv2.rotate(img, cv2.ROTATE_90_CLOCKWISE)
    cv2.imwrite(temp_path, rotated_cw)
    
    folio = detect_valid_folio_quick(temp_path, detector)
    if folio:
        logging.info(f"  ✓ Folio válido detectado con rotación derecha: {folio}")
        # Guardar la imagen rotada en la ruta original
        cv2.imwrite(image_path, rotated_cw)
        # Limpiar archivo temporal
        if os.path.exists(temp_path):
            os.remove(temp_path)
        return True
    
    # Si ninguna rotación funciona, usar counterclockwise como fallback
    logging.warning("  ⚠ No se detectó folio válido en ninguna rotación, usando counterclockwise como fallback")
    cv2.imwrite(image_path, rotated_ccw)
    
    # Limpiar archivo temporal
    if os.path.exists(temp_path):
        os.remove(temp_path)
    
    return True

# --- Auto-rotar imágenes landscape antes de procesarlas ---
print("Verificando orientación de imágenes...")
rotated_count = 0
for image_file in image_files:
    if auto_rotate_landscape_image(image_file, detector):
        rotated_count += 1

if rotated_count > 0:
    print(f"{rotated_count} imágenes fueron rotadas de landscape a portrait.")
else:
    print("Todas las imágenes ya estaban en orientación portrait.")

# Mapeo de template types a archivos de referencia
REFERENCE_IMAGES = {
    '01': '/app/reference-referencia-i.png',      # Referencia I
    '02': '/app/reference-referencia-iii.png',    # Referencia III
    '03': '/app/reference-referencia-v.png',      # Referencia V
    '04': '/app/reference-referencia-i.png',      # Escala Cisneros (usar Ref I como fallback)
    '05': '/app/reference-referencia-iii.png',    # Clima laboral (Likert) - similar layout a Ref III
    '06': '/app/reference-referencia-likert-planta-3.png',  # Likert Planta 3
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
        # validate_single_answer=False para folio porque pueden haber datos demograficos que no sean tipo selección única
        folio_data = detector.detect_bubbles(image_file, config.folio_configuration, validate_single_answer=False)
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
        
        # --- PRE: Detectar y dibujar marcadores ANTES de alinear (debug temprana) ---
        try:
            img_raw = cv2.imread(image_file)
            pre_debug_path = os.path.join(output_with_markers_folder, f"{os.path.splitext(os.path.basename(image_file))[0]}_prealign_markers.png")
            # Guardará líneas de recorte y marcadores detectados sobre la imagen original
            _ = detectar_marcadores_4_esquinas(img_raw, debug_path=pre_debug_path, image_name=f"{os.path.basename(image_file)}_PRE")
            logging.info(f"Pre-alineación: debug de marcadores guardado en {pre_debug_path}")
        except Exception as e:
            logging.warning(f"No se pudieron detectar marcadores en imagen original (pre-alineación): {e}")

        # --- Paso 1: Detectar template type de forma preliminar ---
        template_type = detect_template_type_from_image(image_file, detector)
        
        # --- Paso 2: Cargar referencia específica para ese template ---
        ref_img = get_reference_image_for_template(template_type)
        ref_marcadores = detectar_marcadores_4_esquinas(ref_img, debug_path=None, image_name=f"REFERENCIA_{template_type}")
        ref_esquinas = ref_marcadores  # Usar los 4 marcadores directamente
        
        # --- Paso 3: Alinear la imagen con la referencia específica ---
        img = cv2.imread(image_file)
        img_marcadores = detectar_marcadores_4_esquinas(img, debug_path=None, image_name=f"{os.path.basename(image_file)}_ALIGN")
        img_esquinas = img_marcadores  # Usar los 4 marcadores directamente
        alineada = alinear_imagen(img, ref_esquinas, img_esquinas, (ref_img.shape[1], ref_img.shape[0]))
        aligned_filename = os.path.basename(image_file).replace(".png", "_aligned.png")
        aligned_save_path = os.path.join(outputs_aligned_folder, aligned_filename)
        cv2.imwrite(aligned_save_path, alineada)
        logging.info(f"Imagen alineada guardada: {aligned_filename}")
        aligned_image_files.append(aligned_save_path)
        
        # Log detallado de la transformación aplicada
        markers_logger.info("  TRANSFORMACIÓN APLICADA:")
        markers_logger.info(f"    Referencia esquinas: {ref_esquinas}")
        markers_logger.info(f"    Imagen esquinas: {img_esquinas}")
        markers_logger.info(f"    Tamaño salida: {ref_img.shape[1]}x{ref_img.shape[0]}")
        
    except Exception as e:
        logging.warning(f"No se pudo alinear {image_file}: {e}. Se omite esta imagen.")
        markers_logger.error(f"ERROR ALINEACIÓN {os.path.basename(image_file)}: {e}")
        continue

# --- El resto del pipeline usa las imágenes alineadas ---
markers_logger.info("=" * 80)
markers_logger.info("FASE 2: DETECCIÓN DE FOLIOS Y RESPUESTAS")
markers_logger.info("=" * 80)

for image_file in aligned_image_files:
    logging.info(f"Procesando imagen alineada: {image_file}")
    markers_logger.info(f"\n>>> PROCESANDO: {os.path.basename(image_file)}")

    # Detectar el folio a partir de la imagen alineada
    folio = detect_folio(image_file, detector)
    markers_logger.info(f"    Folio detectado: '{folio}'")

    # Validar que el folio inicie con 01, 02, 03, 04, 05, 06 (template types)
    valid_prefixes = ['01', '02', '03', '04', '05', '06']
    is_valid = any(folio.startswith(prefix) for prefix in valid_prefixes)
    
    if not is_valid:
        logging.warning(f"Imagen {image_file} skipeada: folio '{folio}' no inicia con template válido {', '.join(valid_prefixes)}.")
        markers_logger.warning(f"    SKIP: Folio inválido '{folio}'")
        continue
    
    logging.info(f"Folio válido detectado: {folio}")
    markers_logger.info(f"    Folio VÁLIDO: {folio} (template: {folio[:2]})")

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
    markers_logger.info(f"    Template type: {template_type}")
    bubble_configs_list = []
    
    # SIEMPRE agregar el folio al inicio para visualización
    if hasattr(config, 'folio_configuration'):
        bubble_configs_list.append(config.folio_configuration)
    
    if template_type == "01":
        # Referencia I - Acontecimientos traumáticos
        evaluation_config = config.referencia_i if hasattr(config, 'referencia_i') else config.referencia_iii
        bubble_configs_list.append(evaluation_config)
        logging.info(f"Folio {folio} → Referencia I (Acontecimientos Traumáticos)")
        get_main_answers_legacy(new_image_path, detector, evaluation_config, folio)
    elif template_type == "02":
        # Referencia III - Evaluación principal COMPLETA (6 secciones)
        # Incluir TODAS las configuraciones de burbujas de las 6 secciones
        bubble_configs_list.append(config.referencia_iii)
        bubble_configs_list.append(config.conditional_customer_service)
        bubble_configs_list.append(config.customer_service_questions)
        bubble_configs_list.append(config.conditional_management)
        bubble_configs_list.append(config.management_questions)
        bubble_configs_list.append(config.citsats_s1)
        
        logging.info(f"Folio {folio} → Referencia III COMPLETA (6 secciones)")
        get_referencia_iii_complete_answers(new_image_path, detector, folio)
    elif template_type == "03":
        # Referencia V - Datos del evaluado (20 subsecciones)
        # Incluir todas las subsecciones demográficas
        demographic_sections = ['sexo', 'edad', 'estado_civil', 'nivel_estudios', 'tiempo_puesto_actual', 
                               'tipo_personal', 'tipo_puesto', 'tipo_contratacion', 'tipo_jornada', 
                               'rotacion_turnos', 'experiencia_laboral', 'ocupacion', 'departamento',
                               'numero_trabajadores', 'subordinados', 'horas_laborales', 'salario']
        for section in demographic_sections:
            if hasattr(config, section):
                section_config = getattr(config, section)
                if isinstance(section_config, dict):
                    bubble_configs_list.append(section_config)
        
        logging.info(f"Folio {folio} → Referencia V COMPLETA (20 subsecciones demográficas)")
        get_referencia_v_complete_answers(new_image_path, detector, folio)
    elif template_type == "04":
        # Escala Cisneros
        evaluation_config = config.escala_cisneros if hasattr(config, 'escala_cisneros') else config.referencia_iii
        bubble_configs_list.append(evaluation_config)
        logging.info(f"Folio {folio} → Escala Cisneros (Mobbing)")
        get_main_answers_legacy(new_image_path, detector, evaluation_config, folio)
    elif template_type == "05":
        # Clima laboral - Likert (23 preguntas + demografía + listas)
        # Agregar configuraciones de Likert a la visualización
        if hasattr(config, 'likert'):
            bubble_configs_list.append(config.likert)
        
        # Agregar demografía simple
        for attr in ['likert_genero', 'likert_turno', 'likert_tipo_contrato']:
            if hasattr(config, attr):
                bubble_configs_list.append(getattr(config, attr))
        
        # Agregar listas (solo first bubble, la iteración la hace get_likert_complete_answers)
        for attr in ['likert_puestos', 'likert_areas']:
            if hasattr(config, attr):
                bubble_configs_list.append(getattr(config, attr))
        
        logging.info(f"Folio {folio} → Clima Laboral (Likert) COMPLETO")
        get_likert_complete_answers(new_image_path, detector, folio)
    elif template_type == "06":
        # Referencia 06 - Likert Planta 3
        markers_logger.info(f"    Procesando como LIKERT PLANTA 3 (template 06)")
        
        # Agregar configuraciones de Likert Planta 3 a la visualización
        if hasattr(config, 'likert_planta_3'):
            bubble_configs_list.append(config.likert_planta_3)
        
        # Agregar demografía simple para Planta 3
        for attr in ['likert_planta_3_genero', 'likert_planta_3_turno', 'likert_planta_3_tipo_contrato']:
            if hasattr(config, attr):
                bubble_configs_list.append(getattr(config, attr))
        
        # Agregar listas (solo first bubble, la iteración la hace get_likert_complete_answers)
        for attr in ['likert_planta_3_puestos', 'likert_planta_3_areas']:
            if hasattr(config, attr):
                bubble_configs_list.append(getattr(config, attr))
        
        logging.info(f"Folio {folio} → Likert Planta 3 COMPLETO")
        markers_logger.info(f"    Llamando get_likert_complete_answers para {folio}")
        get_likert_complete_answers(new_image_path, detector, folio)
        markers_logger.info(f"    COMPLETADO: {folio}")
    else:
        logging.warning(f"Template type '{template_type}' no reconocido, usando evaluación por defecto")
        markers_logger.warning(f"    Template DESCONOCIDO: {template_type}")
        evaluation_config = config.referencia_iii
        bubble_configs_list.append(evaluation_config)
        get_main_answers_legacy(new_image_path, detector, evaluation_config, folio)
    
    # Generar imagen con marcadores y burbujas
    logging.info(f"Generando imagen con marcadores para folio {folio}... ({len(bubble_configs_list)} configuraciones)")
    markers_logger.info(f"    Generando imagen con {len(bubble_configs_list)} configuraciones de burbujas")
    save_image_with_markers(new_image_path, folio, marker_positions=None, bubble_configs=bubble_configs_list, template_type=template_type)

markers_logger.info("=" * 80)
markers_logger.info("PROCESAMIENTO COMPLETADO")
markers_logger.info("=" * 80)
