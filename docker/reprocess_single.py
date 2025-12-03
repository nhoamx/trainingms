"""
Script para reprocesar una imagen ya alineada.
Detecta el folio y analiza las respuestas SIN hacer alineación.

Uso: python reprocess_single.py <image_path> <output_json_path>
"""
import sys
import os
import json
import logging
import time

# Configurar logging - archivo y consola
LOG_FILE = "/app/reprocessing.log"

# Crear handlers
file_handler = logging.FileHandler(LOG_FILE, mode='a', encoding='utf-8')
file_handler.setLevel(logging.DEBUG)
file_handler.setFormatter(logging.Formatter('%(asctime)s %(levelname)s: %(message)s'))

console_handler = logging.StreamHandler()
console_handler.setLevel(logging.INFO)
console_handler.setFormatter(logging.Formatter('%(asctime)s %(levelname)s: %(message)s'))

# Configurar logger
logger = logging.getLogger()
logger.setLevel(logging.DEBUG)
logger.addHandler(file_handler)
logger.addHandler(console_handler)

from bubble_detector import BubbleDetector
import config_legacy as config

# Definir rutas absolutas dentro del contenedor
output_with_markers_folder = "/app/output_with_markers"

def detect_folio(image_file, detector):
    """
    Detecta el folio a partir de la imagen usando la configuración de folio.
    Retorna el folio como string o "unknown" en caso de error.
    """
    try:
        logging.info(f"Detectando folio en {image_file}")
        folio_data = detector.detect_bubbles(image_file, config.folio_configuration, validate_single_answer=False)
        
        folio = "".join(str(value) for value in folio_data.values() if value is not None)
        logging.info(f"Folio detectado: {folio}")
        
        if not folio or len(folio) < 9:
            logging.warning(f"Folio incompleto o inválido: '{folio}'")
            folio = "unknown"
        return folio
    except Exception as e:
        logging.error(f"Error detectando folio: {e}")
        return "unknown"

def get_referencia_iii_complete_answers(image_file, detector):
    """Obtiene las respuestas completas de Referencia III."""
    complete_answers = {}
    
    # 1. Sección principal - 46 preguntas
    main_answers = detector.detect_bubbles(image_file, config.referencia_iii)
    complete_answers['referencia_iii'] = main_answers
    
    # 2. Pregunta condicional servicio cliente
    cs_conditional = detector.detect_bubbles(image_file, config.conditional_customer_service)
    complete_answers['customer_service_conditional'] = cs_conditional
    
    # 3. Preguntas de servicio cliente 65-68
    cs_answers = detector.detect_bubbles(image_file, config.customer_service_questions)
    if cs_conditional.get('condition') == 'NO':
        cs_answers_null = {key: None for key in cs_answers.keys()}
        complete_answers['customer_service_questions'] = cs_answers_null
    else:
        complete_answers['customer_service_questions'] = cs_answers
    
    # 4. Pregunta condicional gestión
    cm_answers = detector.detect_bubbles(image_file, config.conditional_management)
    complete_answers['conditional_management'] = cm_answers
    
    # 5. Preguntas de gestión 69-72
    mg_answers = detector.detect_bubbles(image_file, config.management_questions)
    if cm_answers.get('condition') == 'NO':
        mg_answers_null = {key: None for key in mg_answers.keys()}
        complete_answers['management_questions'] = mg_answers_null
    else:
        complete_answers['management_questions'] = mg_answers
    
    # 6. CITSATS-s1
    citsats_answers = detector.detect_bubbles(image_file, config.citsats_s1)
    complete_answers['citsats_s1'] = citsats_answers
    
    return complete_answers

def get_likert_complete_answers(image_file, detector, folio, min_fill_threshold=400):
    """Obtiene las respuestas completas de Likert."""
    template_type = folio[:2]
    is_planta_3 = template_type == '06'
    config_prefix = 'likert_planta_3' if is_planta_3 else 'likert'
    
    complete_answers = {}
    
    # 1. Preguntas principales Likert (1-23)
    questions_config = getattr(config, config_prefix, None)
    if questions_config is not None:
        likert_answers = detector.detect_bubbles(image_file, questions_config, min_fill_threshold=min_fill_threshold)
        complete_answers[config_prefix] = likert_answers
    
    # 2. Demografía simple
    demographic_mapping = [
        ('genero', 'genero'),
        ('turno', 'turno'),
        ('tipo_contrato', 'tipo_contrato'),
    ]
    
    for config_suffix, result_key in demographic_mapping:
        config_attr = f'{config_prefix}_{config_suffix}'
        if hasattr(config, config_attr):
            section_config = getattr(config, config_attr)
            section_answer = detector.detect_bubbles(image_file, {result_key: section_config}, min_fill_threshold=min_fill_threshold)
            detected_value = section_answer.get(result_key)
            complete_answers[result_key] = detected_value
        else:
            complete_answers[result_key] = None
    
    # 3. Puestos
    puestos_config_attr = f'{config_prefix}_puestos'
    if hasattr(config, puestos_config_attr):
        puestos_config = getattr(config, puestos_config_attr)
        num_puestos = len(puestos_config)
        puesto_detectado = None
        
        for i in range(1, num_puestos + 1):
            key = str(i)
            if key in puestos_config:
                x, y, w, h = puestos_config[key]
                temp_config = {'puesto': {'temp': (x, y, w, h)}}
                temp_answer = detector.detect_bubbles(image_file, temp_config, min_fill_threshold=min_fill_threshold)
                
                if temp_answer.get('puesto') == 'temp':
                    puesto_detectado = i
                    break
        
        complete_answers['puestos'] = puesto_detectado
    else:
        complete_answers['puestos'] = None
    
    # 4. Áreas
    areas_config_attr = f'{config_prefix}_areas'
    if hasattr(config, areas_config_attr):
        areas_config = getattr(config, areas_config_attr)
        num_areas = len(areas_config)
        area_detectada = None
        
        for i in range(1, num_areas + 1):
            key = str(i)
            if key in areas_config:
                x, y, w, h = areas_config[key]
                temp_config = {'area': {'temp': (x, y, w, h)}}
                temp_answer = detector.detect_bubbles(image_file, temp_config, min_fill_threshold=min_fill_threshold)
                
                if temp_answer.get('area') == 'temp':
                    area_detectada = i
                    break
        
        complete_answers['areas'] = area_detectada
    else:
        complete_answers['areas'] = None
    
    return complete_answers

def get_referencia_v_complete_answers(image_file, detector, min_fill_threshold=800):
    """Obtiene las respuestas completas de Referencia V."""
    complete_answers = {}
    
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
        if hasattr(config, config_attr):
            section_config = getattr(config, config_attr)
            
            if section_name == 'edad':
                edad_result = {}
                if 'decenas' in section_config:
                    decenas_answer = detector.detect_bubbles(image_file, {'decenas': section_config['decenas']}, min_fill_threshold=min_fill_threshold)
                    edad_result['decenas'] = decenas_answer.get('decenas')
                if 'unidades' in section_config:
                    unidades_answer = detector.detect_bubbles(image_file, {'unidades': section_config['unidades']}, min_fill_threshold=min_fill_threshold)
                    edad_result['unidades'] = unidades_answer.get('unidades')
                complete_answers[section_name] = edad_result
            elif section_name in ['ocupacion', 'departamento']:
                result = {}
                if 'fila1' in section_config:
                    fila1_answer = detector.detect_bubbles(image_file, {'fila1': section_config['fila1']}, min_fill_threshold=min_fill_threshold)
                    result['fila1'] = fila1_answer.get('fila1')
                if 'fila2' in section_config:
                    fila2_answer = detector.detect_bubbles(image_file, {'fila2': section_config['fila2']}, min_fill_threshold=min_fill_threshold)
                    result['fila2'] = fila2_answer.get('fila2')
                complete_answers[section_name] = result
            else:
                section_answer = detector.detect_bubbles(image_file, {section_name: section_config}, min_fill_threshold=min_fill_threshold)
                complete_answers[section_name] = section_answer.get(section_name)
    
    # Nivel estudios
    if hasattr(config, 'referencia_v') and 'nivel_estudios' in config.referencia_v:
        nivel_estudios_config = config.referencia_v['nivel_estudios']
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
        
        niveles = ['sin_formacion', 'primaria', 'secundaria', 'preparatoria', 
                  'tecnico_superior', 'licenciatura', 'maestria', 'doctorado']
        
        for nivel in niveles:
            if nivel in nivel_estudios_config:
                nivel_config = nivel_estudios_config[nivel]
                
                if nivel == 'sin_formacion':
                    answer = detector.detect_bubbles(image_file, {nivel: nivel_config}, min_fill_threshold=min_fill_threshold)
                    detected_option = answer.get(nivel)
                    if detected_option == 'sin_formacion':
                        nivel_estudios_result['sin_formacion'] = True
                else:
                    answer = detector.detect_bubbles(image_file, {nivel: nivel_config}, min_fill_threshold=min_fill_threshold)
                    detected_option = answer.get(nivel)
                    
                    if detected_option == 'terminada':
                        nivel_estudios_result[nivel] = {'seleccionado': True, 'completado': 'completo'}
                    elif detected_option == 'incompleta':
                        nivel_estudios_result[nivel] = {'seleccionado': True, 'completado': 'incompleto'}
        
        complete_answers['nivel_estudios'] = nivel_estudios_result
    
    return complete_answers

def process_image(image_path, output_json_path):
    """
    Procesa una imagen ya alineada y guarda el resultado en JSON.
    """
    detector = BubbleDetector()
    
    # Detectar folio
    folio = detect_folio(image_path, detector)
    
    if folio == "unknown":
        logging.error("No se pudo detectar el folio")
        return None
    
    # Validar template type
    valid_prefixes = ['01', '02', '03', '04', '05', '06']
    if not any(folio.startswith(prefix) for prefix in valid_prefixes):
        logging.error(f"Folio inválido: {folio}")
        return None
    
    template_type = folio[:2]
    answers = None
    
    # Procesar según tipo de template
    if template_type == "01":
        # Referencia I - Acontecimientos traumáticos
        evaluation_config = config.referencia_i if hasattr(config, 'referencia_i') else config.referencia_iii
        answers = detector.detect_bubbles(image_path, evaluation_config)
        logging.info(f"Procesado Referencia I")
    elif template_type == "02":
        # Referencia III completa
        answers = get_referencia_iii_complete_answers(image_path, detector)
        logging.info(f"Procesado Referencia III completa")
    elif template_type == "03":
        # Referencia V - Datos demográficos
        answers = get_referencia_v_complete_answers(image_path, detector)
        logging.info(f"Procesado Referencia V")
    elif template_type == "04":
        # Escala Cisneros
        evaluation_config = config.escala_cisneros if hasattr(config, 'escala_cisneros') else config.referencia_iii
        answers = detector.detect_bubbles(image_path, evaluation_config)
        logging.info(f"Procesado Escala Cisneros")
    elif template_type in ["05", "06"]:
        # Likert (05 o Planta 3 06)
        answers = get_likert_complete_answers(image_path, detector, folio)
        logging.info(f"Procesado Likert (template {template_type})")
    
    if answers is None:
        logging.error("No se obtuvieron respuestas")
        return None
    
    # Guardar JSON
    result = {
        'folio': folio,
        'template_type': template_type,
        'answers': answers
    }
    
    with open(output_json_path, 'w') as json_file:
        json.dump(result, json_file, indent=4)
    
    logging.info(f"Resultado guardado en: {output_json_path}")
    
    return result

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Uso: python reprocess_single.py <image_path> <output_json_path>")
        sys.exit(1)
    
    image_path = sys.argv[1]
    output_json_path = sys.argv[2]
    
    if not os.path.exists(image_path):
        print(f"Error: No existe la imagen: {image_path}")
        sys.exit(1)
    
    # Iniciar timer
    start_time = time.time()
    
    result = process_image(image_path, output_json_path)
    
    # Calcular tiempo transcurrido
    elapsed_time = time.time() - start_time
    
    if result:
        logging.info(f"[TIMER] Folio {result['folio']} procesado en {elapsed_time:.2f} segundos")
        print(f"Procesamiento exitoso: {result['folio']} ({elapsed_time:.2f}s)")
        sys.exit(0)
    else:
        logging.error(f"[TIMER] Procesamiento fallido después de {elapsed_time:.2f} segundos")
        print(f"Error en el procesamiento ({elapsed_time:.2f}s)")
        sys.exit(1)
