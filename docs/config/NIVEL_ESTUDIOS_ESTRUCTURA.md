# 📚 Nivel de Estudios - Estructura Consolidada

**Fecha:** 9 de octubre, 2025  
**Cambio:** Nueva estructura consolidada `nivel_estudios` en Referencia V

---

## 🎯 Objetivo

Consolidar todos los niveles educativos en una sola estructura JSON que indique:
1. Qué nivel fue seleccionado
2. Si está completo o incompleto
3. Validar cuando ningún círculo está marcado

---

## 📋 Estructura en `config_legacy.py`

```python
referencia_v = {
    'sexo': sexo,
    'edad': edad,
    'estado_civil': estado_civil,
    'tipo_personal': tipo_personal,
    'nivel_estudios': {
        'sin_formacion': sin_formacion,
        'primaria': primaria,
        'secundaria': secundaria,
        'preparatoria': preparatoria,
        'tecnico_superior': tecnico_superior,
        'licenciatura': licenciatura,
        'maestria': maestria,
        'doctorado': doctorado,
    },
    'tipo_puesto': tipo_puesto,
    # ... resto de subsecciones
}
```

---

## 📊 Formato JSON Esperado

### Caso 1: Preparatoria Completa

**Burbujas marcadas:** Preparatoria → Terminada

```json
{
    "nivel_estudios": {
        "sin_formacion": false,
        "primaria": false,
        "secundaria": false,
        "preparatoria": {
            "seleccionado": true,
            "completado": "completo"
        },
        "tecnico_superior": false,
        "licenciatura": false,
        "maestria": false,
        "doctorado": false
    }
}
```

### Caso 2: Maestría Incompleta

**Burbujas marcadas:** Maestría → Incompleta

```json
{
    "nivel_estudios": {
        "sin_formacion": false,
        "primaria": false,
        "secundaria": false,
        "preparatoria": false,
        "tecnico_superior": false,
        "licenciatura": false,
        "maestria": {
            "seleccionado": true,
            "completado": "incompleto"
        },
        "doctorado": false
    }
}
```

### Caso 3: Sin Formación

**Burbujas marcadas:** Sin formación (única opción)

```json
{
    "nivel_estudios": {
        "sin_formacion": true,
        "primaria": false,
        "secundaria": false,
        "preparatoria": false,
        "tecnico_superior": false,
        "licenciatura": false,
        "maestria": false,
        "doctorado": false
    }
}
```

### Caso 4: Ningún Círculo Marcado (Validación)

**Burbujas marcadas:** Ninguna

```json
{
    "nivel_estudios": {
        "sin_formacion": false,
        "primaria": false,
        "secundaria": false,
        "preparatoria": false,
        "tecnico_superior": false,
        "licenciatura": false,
        "maestria": false,
        "doctorado": false
    }
}
```

---

## 🔧 Lógica en `main.py`

### Procesamiento de `nivel_estudios`

```python
# Estructura inicial con todos en false
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
for nivel in niveles:
    if nivel == 'sin_formacion':
        # sin_formacion solo tiene una opción
        answer = detector.detect_bubbles(image_file, {nivel: nivel_config})
        detected_option = answer.get(nivel)
        if detected_option == 'sin_formacion':
            nivel_estudios_result['sin_formacion'] = True
    else:
        # Otros niveles tienen 'terminada' e 'incompleta'
        answer = detector.detect_bubbles(image_file, {nivel: nivel_config})
        detected_option = answer.get(nivel)
        
        if detected_option == 'terminada':
            nivel_estudios_result[nivel] = {
                'seleccionado': True, 
                'completado': 'completo'
            }
        elif detected_option == 'incompleta':
            nivel_estudios_result[nivel] = {
                'seleccionado': True, 
                'completado': 'incompleto'
            }
        # Si detected_option es None, permanece como False
```

---

## ✅ Validación de Círculos Vacíos

### Cómo Detectar si No Hay Selección

El `BubbleDetector` retorna `None` cuando ningún círculo tiene suficientes píxeles marcados:

```python
detected_option = answer.get(nivel)  # Retorna None si no hay selección

if detected_option is None:
    # Ningún círculo marcado para este nivel
    nivel_estudios_result[nivel] = False
```

### Validación a Nivel de Subsección Completa

Después de procesar todos los niveles, puedes validar:

```python
# Verificar si al menos un nivel fue seleccionado
niveles_seleccionados = [
    k for k, v in nivel_estudios_result.items() 
    if v is not False
]

if not niveles_seleccionados:
    logging.warning("⚠️ Ningún nivel de estudios fue seleccionado")
    # Opcionalmente: marcar como dato faltante
```

---

## 🧪 Casos de Prueba

### Test 1: Licenciatura Completa

**Entrada:**
- Burbuja marcada: Licenciatura → Terminada

**Salida esperada:**
```json
{
    "licenciatura": {
        "seleccionado": true,
        "completado": "completo"
    }
}
```

### Test 2: Secundaria Incompleta

**Entrada:**
- Burbuja marcada: Secundaria → Incompleta

**Salida esperada:**
```json
{
    "secundaria": {
        "seleccionado": true,
        "completado": "incompleto"
    }
}
```

### Test 3: Sin Formación

**Entrada:**
- Burbuja marcada: Sin formación

**Salida esperada:**
```json
{
    "sin_formacion": true
}
```

### Test 4: Error de Doble Selección

**Entrada:**
- Burbujas marcadas: Licenciatura → Terminada Y Maestría → Incompleta

**Comportamiento:**
El `BubbleDetector` selecciona la que tenga más píxeles marcados. Si es ambiguo, puede elegir cualquiera.

**Recomendación:** Validar en backend que solo uno esté seleccionado.

---

## 📈 Flujo Completo de Detección

```
┌─────────────────────────────────┐
│  Imagen Alineada de Ref V       │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│  Procesar cada nivel educativo  │
│  - sin_formacion                │
│  - primaria                     │
│  - secundaria                   │
│  - preparatoria                 │
│  - tecnico_superior             │
│  - licenciatura                 │
│  - maestria                     │
│  - doctorado                    │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│  BubbleDetector.detect_bubbles()│
│  Para cada nivel:               │
│  - Detecta 'terminada'          │
│  - Detecta 'incompleta'         │
│  - O retorna None               │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│  Consolidar en estructura JSON  │
│  - false: no seleccionado       │
│  - true: sin_formacion marcado  │
│  - {seleccionado, completado}:  │
│    nivel marcado con estado     │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│  Guardar JSON: {folio}.json     │
└─────────────────────────────────┘
```

---

## 🔍 Debugging

### Ver qué detectó el BubbleDetector

Agrega logs temporales en `main.py`:

```python
for nivel in niveles:
    answer = detector.detect_bubbles(image_file, {nivel: nivel_config})
    detected_option = answer.get(nivel)
    logging.info(f"  {nivel}: {detected_option}")  # Ver qué detectó
```

### Verificar coordenadas

Si un nivel no se detecta correctamente:

1. Verifica las coordenadas en `config_legacy.py`
2. Usa `calibrate_bubbles.py` para recalibrar
3. Revisa la imagen alineada en `outputs_aligned/`

---

## 📝 Notas Importantes

1. **Exclusividad:** Solo un nivel educativo debería estar seleccionado por formulario
2. **Validación Backend:** Implementar validación para detectar dobles selecciones
3. **Null vs False:** 
   - `False` = No seleccionado (intencional)
   - `None` = Error de detección
4. **Sin Formación:** Caso especial con una sola opción (no tiene terminada/incompleta)

---

## 🎯 Ejemplo Completo de JSON Referencia V

```json
{
    "sexo": "masculino",
    "edad": {
        "decenas": "3",
        "unidades": "5"
    },
    "estado_civil": "casado",
    "tipo_personal": "confianza",
    "nivel_estudios": {
        "sin_formacion": false,
        "primaria": false,
        "secundaria": false,
        "preparatoria": false,
        "tecnico_superior": false,
        "licenciatura": {
            "seleccionado": true,
            "completado": "completo"
        },
        "maestria": false,
        "doctorado": false
    },
    "tipo_puesto": "gerente",
    "tipo_contratacion": "tiempo_indeterminado",
    "tipo_jornada": "fijo_diurno",
    "rotacion_turnos": "no",
    "tiempo_puesto_actual": "entre_5_a_9_anos",
    "experiencia_laboral": "entre_10_a_14_anos",
    "ocupacion": {
        "fila1": "C",
        "fila2": null
    },
    "departamento": {
        "fila1": "A",
        "fila2": null
    }
}
```

---

**Autor:** GitHub Copilot  
**Fecha:** 9 de octubre, 2025
