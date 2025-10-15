# Cómo Usar la Calibración Web para Generar TODAS las Configuraciones OMR

## Respuesta a tu Pregunta

**SÍ**, la herramienta de calibración web puede generar automáticamente los configs de:
1. ✅ **Folio** (9 columnas × 10 dígitos = 90 burbujas)
2. ✅ **Referencia I** (24 preguntas × 2 opciones = 48 burbujas)
3. ✅ **Referencia III** (46 preguntas × 5 opciones = 230 burbujas)
4. ✅ **Referencia III - CITSATS** (6 preguntas × 2 opciones = 12 burbujas)
5. ✅ **Referencia V** (datos demográficos, ~150 burbujas)

**Y SÍ**, la imagen que usas para calibrar ES la misma imagen de referencia que el sistema usa para alinear todas las demás imágenes. Por eso las coordenadas que captures serán 100% precisas.

## ¿Cómo Funciona el Sistema Completo?

### El Flujo de Trabajo

```
1. GENERAR PDF DE PRUEBA
   ├─> php artisan omr:generate-test-pdfs
   └─> Crea PDFs con folios pre-llenados y burbujas de muestra

2. CONVERTIR A IMAGEN DE REFERENCIA
   ├─> docker exec ... python /app/generate_reference.py <pdf> <output.png>
   └─> Esta imagen ES la referencia que se usa para alinear

3. CALIBRAR CON LA MISMA IMAGEN
   ├─> docker exec ... python /app/calibrate_bubbles.py <referencia.png> 5001
   └─> Abres http://localhost:5001 y calibras las burbujas

4. GENERAR CÓDIGO PYTHON AUTOMÁTICAMENTE
   ├─> Seleccionas el tipo de sección (Folio, Ref I, Ref III, etc.)
   └─> Click en "🐍 Copiar como Python" → Código listo para pegar

5. PEGAR EN CONFIG_LEGACY.PY
   └─> Las coordenadas ya están en el formato correcto

6. EL SISTEMA USA ESAS COORDENADAS
   ├─> Cuando procesas un PDF real con main.py
   ├─> Alinea la imagen con la misma imagen de referencia
   └─> Lee las burbujas usando las coordenadas calibradas
```

## La Clave: Imagen de Referencia = Imagen de Calibración

### ¿Por Qué Esto Funciona Perfectamente?

El sistema de alineación funciona así:

```python
# En main.py
def get_reference_image_for_template(template_type):
    """
    Obtiene la imagen de referencia correcta según el template type.
    """
    ref_path = REFERENCE_IMAGES.get(template_type, '/app/reference-referencia-i.png')
    ref_img = cv2.imread(ref_path)  # ← Esta es la MISMA imagen que usas para calibrar
    return ref_img
```

Cuando procesas un PDF:
1. El sistema detecta los marcadores en la imagen de referencia
2. Detecta los marcadores en tu imagen escaneada
3. **Alinea tu imagen para que coincida EXACTAMENTE con la referencia**
4. Lee las burbujas usando las coordenadas que calibraste

**Por eso**: Si calibras las coordenadas en la imagen de referencia, esas coordenadas funcionarán para TODAS las imágenes procesadas (porque todas se alinean con esa referencia).

## Mejoras Implementadas en la Herramienta

### Nueva Funcionalidad: Generación Automática de Código Python

La herramienta ahora tiene un selector de sección que **genera automáticamente el código Python correcto**:

#### 1. Selector de Tipo de Sección

```
📝 Tipo de Sección: [Dropdown]
  - Personalizado
  - Folio (F1-F9, dígitos 0-9)
  - Referencia I (Preguntas 1-24, SI/NO)
  - Referencia III (Preguntas 1-46, A/B/C/D/E)
  - Referencia III - CITSATS (1-6, SI/NO)
  - Referencia V (Demográficos)
```

#### 2. Indicador de Progreso en Tiempo Real

Mientras calibras, ves exactamente en qué estás:

**Ejemplo para Folio:**
```
Pregunta/Elemento Actual: F1, Dígito 3
```

**Ejemplo para Referencia I:**
```
Pregunta/Elemento Actual: Pregunta 5, SI
```

**Ejemplo para Referencia III:**
```
Pregunta/Elemento Actual: Pregunta 12, C (Algunas veces)
```

#### 3. Dos Opciones de Copia

**📋 Copiar Simple** (como antes):
```
'0': (655, 785, 35, 35),
'1': (655, 845, 35, 35),
'2': (655, 905, 35, 35),
```

**🐍 Copiar como Python** (NUEVO - código listo para pegar):
```python
folio_configuration = {
    'F1': {
        '0': (655, 785, 35, 35),
        '1': (655, 845, 35, 35),
        '2': (655, 905, 35, 35),
        '3': (655, 975, 35, 35),
        '4': (655, 1035, 35, 35),
        '5': (655, 1100, 35, 35),
        '6': (655, 1160, 35, 35),
        '7': (655, 1220, 35, 35),
        '8': (655, 1285, 35, 35),
        '9': (655, 1350, 35, 35),
    },
    'F2': {
        '0': (715, 785, 35, 35),
        # ... etc
    },
}
```

## Flujo de Trabajo Completo: Del PDF al Config

### Ejemplo Práctico: Calibrar Referencia III

#### Paso 1: Generar PDF de Prueba
```bash
php artisan omr:generate-test-pdfs
```

Esto crea: `storage/app/omr-test-pdfs/referencia-iii.pdf` (5 páginas)

#### Paso 2: Convertir a Imagen de Referencia
```bash
docker cp ./storage/app/omr-test-pdfs/referencia-iii.pdf training-and-ms:/app/input/referencia-iii.pdf
docker exec training-and-ms python /app/generate_reference.py /app/input/referencia-iii.pdf /app/reference-referencia-iii.png
```

Esto crea: `/app/reference-referencia-iii.png` (esta ES la imagen de referencia oficial)

#### Paso 3: Procesar el PDF para Obtener Imágenes Alineadas
```bash
docker cp ./storage/app/omr-test-pdfs/referencia-iii.pdf training-and-ms:/app/input/evaluation.pdf
docker exec training-and-ms python /app/main.py
```

Esto crea imágenes alineadas en: `/app/outputs_aligned/page_1_aligned.png`, etc.

**IMPORTANTE**: Estas imágenes alineadas **YA ESTÁN** alineadas con la imagen de referencia, por eso puedes calibrar directamente sobre ellas.

#### Paso 4: Calibrar Página 1 (Preguntas 1-16 aproximadamente)

```bash
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5001
```

Abre: http://localhost:5001

1. Selecciona: **"Referencia III (Preguntas 1-46, A/B/C/D/E)"**
2. Pregunta inicial: **1**
3. Empieza a calibrar:
   - Click 1: Esquina superior-izquierda de la burbuja "A" de pregunta 1
   - Click 2: Esquina inferior-derecha de la burbuja "A" de pregunta 1
   - Click 3: Esquina superior-izquierda de la burbuja "B" de pregunta 1
   - Click 4: Esquina inferior-derecha de la burbuja "B" de pregunta 1
   - ... y así hasta completar todas las preguntas de la página

4. Click en **"🐍 Copiar como Python"**
5. Pega en `config_legacy.py`

#### Paso 5: Calibrar Página 2 (Preguntas 17-32 aproximadamente)

```bash
# Detener el servidor anterior (Ctrl+C en la terminal)
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_2_aligned.png 5001
```

1. Selecciona: **"Referencia III (Preguntas 1-46, A/B/C/D/E)"**
2. Pregunta inicial: **17** ← IMPORTANTE: Ajustar según dónde empiezas
3. Calibra las preguntas de esta página
4. Copia el código Python y agrégalo a `config_legacy.py`

#### Paso 6: Repetir para las Demás Páginas

Continúa hasta completar todas las 46 preguntas.

#### Paso 7: Calibrar Sección CITSATS (Página 3 o 4 según layout)

```bash
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_3_aligned.png 5001
```

1. Selecciona: **"Referencia III - CITSATS (1-6, SI/NO)"**
2. Pregunta inicial: **1**
3. Calibra las 6 preguntas CITSATS
4. Copia el código y agrégalo a `config_legacy.py`

### Código Final en config_legacy.py

```python
# ============================================================
# REFERENCIA III - FACTORES DE RIESGO PSICOSOCIAL
# ============================================================

referencia_iii = {
    '1': {
        'A': (x1, y1, w1, h1),  # Coordenadas de página 1
        'B': (x2, y2, w2, h2),
        'C': (x3, y3, w3, h3),
        'D': (x4, y4, w4, h4),
        'E': (x5, y5, w5, h5),
    },
    '2': {
        'A': (...),
        'B': (...),
        'C': (...),
        'D': (...),
        'E': (...),
    },
    # ... preguntas 3-16 de página 1
    
    '17': {  # De página 2
        'A': (...),
        # ...
    },
    # ... hasta pregunta 46
}

# Sección CITSATS-s1
citsats_s1 = {
    '1': {
        'SI': (...),
        'NO': (...),
    },
    # ... preguntas 2-6
}
```

## Calibrar TODAS las Configuraciones

### 1. Folio (Una Sola Vez para Todos los Templates)

El folio es **idéntico en todos los templates**, así que solo calibras una vez:

```bash
# Usar cualquier imagen alineada
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5001
```

1. Selecciona: **"Folio (F1-F9, dígitos 0-9)"**
2. Calibra las 90 burbujas (9 columnas × 10 dígitos)
3. Copia el código Python completo
4. Pega en `config_legacy.py` → sección `folio_configuration`

**Resultado**: 90 burbujas calibradas en ~10-15 minutos

### 2. Referencia I

```bash
# Generar imagen de referencia I
docker cp ./storage/app/omr-test-pdfs/referencia-i.pdf training-and-ms:/app/input/evaluation.pdf
docker exec training-and-ms python /app/main.py

# Calibrar
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5001
```

1. Selecciona: **"Referencia I (Preguntas 1-24, SI/NO)"**
2. Calibra 48 burbujas (24 × 2)
3. Copia y pega en `config_legacy.py` → sección `reference_i`

**Resultado**: 48 burbujas calibradas en ~5-7 minutos

### 3. Referencia III

Como vimos arriba, calibras por páginas.

**Resultado**: ~242 burbujas (230 preguntas + 12 CITSATS) en ~30-40 minutos

### 4. Referencia V

```bash
# Generar imagen de referencia V
docker cp ./storage/app/omr-test-pdfs/referencia-v.pdf training-and-ms:/app/input/evaluation.pdf
docker exec training-and-ms python /app/main.py

# Calibrar
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5001
```

1. Selecciona: **"Referencia V (Demográficos)"**
2. Calibra las secciones demográficas
3. Copia y pega en `config_legacy.py` → sección `reference_v`

**Resultado**: ~150 burbujas en ~20-25 minutos

## Tiempo Total Estimado

| Sección | Burbujas | Tiempo Estimado |
|---------|----------|-----------------|
| Folio | 90 | 10-15 min |
| Referencia I | 48 | 5-7 min |
| Referencia III | 242 | 30-40 min |
| Referencia V | 150 | 20-25 min |
| **TOTAL** | **~530** | **~1-1.5 horas** |

Con la herramienta web, puedes calibrar todo el sistema OMR en aproximadamente 1-1.5 horas.

## Verificación: ¿Cómo Saber que Funcionó?

### Después de Calibrar Todo

1. **Guardar `config_legacy.py`** con todas las coordenadas
2. **Copiar al contenedor**:
   ```bash
   docker cp ./docker/config_legacy.py training-and-ms:/app/config_legacy.py
   ```

3. **Probar con un PDF de prueba**:
   ```bash
   docker cp ./storage/app/omr-test-pdfs/referencia-iii.pdf training-and-ms:/app/input/evaluation.pdf
   docker exec training-and-ms python /app/main.py
   ```

4. **Revisar los logs**:
   ```
   ✅ ÉXITO: Folio válido detectado: 020000001
   ✅ ÉXITO: Folio 020000001 → Referencia III (Evaluación Principal)
   ✅ ÉXITO: Resultados guardados en: /app/output/020000001.json
   ```

5. **Verificar el JSON generado**:
   ```bash
   docker exec training-and-ms cat /app/output/020000001.json
   ```

   Deberías ver algo como:
   ```json
   {
       "1": "A",
       "2": "B",
       "3": "C",
       "4": "D",
       "5": "A",
       ...
   }
   ```

Si ves folios correctos (empiezan con 01, 02, 03, 04) y las respuestas se leen bien, **¡la calibración fue exitosa!** 🎉

## Ventajas de Este Enfoque

### ✅ Una Sola Fuente de Verdad

- La imagen de referencia ES la imagen de calibración
- No hay discrepancias entre lo que calibras y lo que el sistema usa
- 100% de precisión garantizada

### ✅ Código Python Automático

- No necesitas escribir manualmente el código
- El formato es correcto desde el inicio
- Solo copiar y pegar

### ✅ Calibración Progresiva

- Puedes calibrar por secciones
- Guardar progreso entre sesiones
- Validar cada sección antes de continuar

### ✅ Visual y Preciso

- Ves exactamente qué estás calibrando en tiempo real
- Feedback visual de qué burbuja estás marcando
- Zoom del navegador para precisión máxima

## Próximos Pasos

1. **Calibrar el Folio primero** (porque es común a todos los templates)
2. **Probar con un PDF simple** para verificar que el folio se detecta
3. **Calibrar Referencia III** (la más compleja)
4. **Calibrar Referencia I y V**
5. **Validar con PDFs de prueba completos**

¿Listo para comenzar? El servidor está corriendo en http://localhost:5001 🚀
