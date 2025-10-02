#!/usr/bin/env python3
"""
Script helper para generar configuraciones de coordenadas de burbujas.
Este script te guía paso a paso en la calibración de un template OMR.
"""

import sys

def generate_folio_config():
    """Genera código Python para configuración de folio."""
    print("\n=== CALIBRACIÓN DE FOLIO (9 columnas x 10 dígitos) ===\n")
    print("Instrucciones:")
    print("1. Abre la imagen alineada con calibrate_bubbles.py")
    print("2. Para cada columna (F1-F9), captura las coordenadas del dígito 0")
    print("3. Ingresa los valores cuando se te pidan\n")
    
    folio_config = {}
    
    for col_num in range(1, 10):
        col_name = f"F{col_num}"
        print(f"\n--- Columna {col_name} ---")
        print(f"Haz clic en las 4 esquinas de la burbuja '0' en columna {col_num}")
        
        x = int(input(f"  X inicial (esquina superior izquierda): "))
        y_start = int(input(f"  Y inicial (burbuja 0): "))
        width = int(input(f"  Ancho de la burbuja (generalmente 35): ") or "35")
        height = int(input(f"  Alto de la burbuja (generalmente 35): ") or "35")
        spacing = int(input(f"  Espaciado vertical entre burbujas (generalmente 60-65): ") or "60")
        
        folio_config[col_name] = {}
        for digit in range(10):
            y = y_start + (digit * spacing)
            folio_config[col_name][str(digit)] = (x, y, width, height)
    
    # Generar código Python
    print("\n\n=== CÓDIGO PYTHON GENERADO ===\n")
    print("folio_configuration = {")
    for col_name, digits in folio_config.items():
        print(f"    '{col_name}': {{")
        for digit, coords in digits.items():
            print(f"        '{digit}': {coords},")
        print("    },")
    print("}")
    
    return folio_config

def generate_evaluation_config():
    """Genera código Python para configuración de evaluación (5 opciones)."""
    print("\n=== CALIBRACIÓN DE EVALUACIÓN (preguntas con 5 opciones A-E) ===\n")
    
    num_questions = int(input("¿Cuántas preguntas tiene esta evaluación? "))
    num_columns = int(input("¿En cuántas columnas están distribuidas? (generalmente 3): ") or "3")
    questions_per_column = (num_questions + num_columns - 1) // num_columns
    
    print(f"\nConfiguración: {num_questions} preguntas en {num_columns} columnas")
    print(f"Aproximadamente {questions_per_column} preguntas por columna\n")
    
    eval_config = {}
    
    for col in range(1, num_columns + 1):
        print(f"\n--- Columna {col} ---")
        print("Calibra la PRIMERA pregunta de esta columna:")
        
        question_num = int(input(f"  Número de la primera pregunta en columna {col}: "))
        x_start = int(input(f"  X inicial (opción A): "))
        y_start = int(input(f"  Y inicial (primera pregunta): "))
        width = int(input(f"  Ancho de burbuja: ") or "35")
        height = int(input(f"  Alto de burbuja: ") or "35")
        x_spacing = int(input(f"  Espaciado horizontal entre opciones: ") or "50")
        y_spacing = int(input(f"  Espaciado vertical entre preguntas: ") or "40")
        
        questions_in_col = min(questions_per_column, num_questions - (question_num - 1))
        
        for i in range(questions_in_col):
            q_num = question_num + i
            if q_num > num_questions:
                break
            
            y = y_start + (i * y_spacing)
            eval_config[str(q_num)] = {}
            
            for idx, option in enumerate(['A', 'B', 'C', 'D', 'E']):
                x = x_start + (idx * x_spacing)
                eval_config[str(q_num)][option] = (x, y, width, height)
    
    # Generar código Python
    print("\n\n=== CÓDIGO PYTHON GENERADO ===\n")
    print("evaluation_config = {")
    for q_num in sorted(eval_config.keys(), key=int):
        print(f"    '{q_num}': {{")
        for option in ['A', 'B', 'C', 'D', 'E']:
            if option in eval_config[q_num]:
                print(f"        '{option}': {eval_config[q_num][option]},")
        print("    },")
    print("}")
    
    return eval_config

def generate_yesno_config():
    """Genera código Python para configuración SÍ/NO."""
    print("\n=== CALIBRACIÓN DE PREGUNTAS SÍ/NO ===\n")
    
    num_questions = int(input("¿Cuántas preguntas SÍ/NO tiene? "))
    
    print("\nCalibra la PRIMERA pregunta:")
    x_si = int(input("  X de la burbuja SÍ: "))
    x_no = int(input("  X de la burbuja NO: "))
    y_start = int(input("  Y de la primera pregunta: "))
    width = int(input("  Ancho de burbuja: ") or "35")
    height = int(input("  Alto de burbuja: ") or "35")
    y_spacing = int(input("  Espaciado vertical entre preguntas: ") or "40")
    
    config = {}
    
    for i in range(num_questions):
        q_num = i + 1
        y = y_start + (i * y_spacing)
        config[str(q_num)] = {
            'SI': (x_si, y, width, height),
            'NO': (x_no, y, width, height),
        }
    
    # Generar código Python
    print("\n\n=== CÓDIGO PYTHON GENERADO ===\n")
    print("config = {")
    for q_num, options in config.items():
        print(f"    '{q_num}': {{")
        print(f"        'SI': {options['SI']},")
        print(f"        'NO': {options['NO']},")
        print("    },")
    print("}")
    
    return config

def main():
    print("="*60)
    print("  GENERADOR DE CONFIGURACIÓN OMR")
    print("="*60)
    print("\nSelecciona qué tipo de configuración quieres generar:\n")
    print("1. Folio (9 columnas x 10 dígitos)")
    print("2. Evaluación con 5 opciones (A, B, C, D, E)")
    print("3. Preguntas SÍ/NO")
    print("4. Salir")
    
    choice = input("\nOpción: ").strip()
    
    if choice == "1":
        generate_folio_config()
    elif choice == "2":
        generate_evaluation_config()
    elif choice == "3":
        generate_yesno_config()
    elif choice == "4":
        print("Saliendo...")
        sys.exit(0)
    else:
        print("Opción inválida")
        return
    
    print("\n\n✅ Copia el código generado y pégalo en docker/config.py")
    print("\n¿Quieres generar otra configuración? (s/n): ", end="")
    if input().strip().lower() == 's':
        main()

if __name__ == "__main__":
    main()
