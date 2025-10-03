# Guía de Calibración OMR - Coordenadas de Burbujas

## 📋 Resumen

Este documento explica cómo calibrar las coordenadas de las burbujas en los templates OMR para que el sistema pueda detectar correctamente:
- Folios (9 dígitos)
- Respuestas de evaluaciones
- Datos demográficos
- Sección CITSATS-s1

## 🎯 Estructura del archivo `config.py`

El archivo `docker/config.py` contiene las configuraciones para cada template:

### 1. **folio_configuration**
Define las 9 columnas del folio (F1-F9), cada una con 10 burbujas (0-9)

```python
folio_configuration = {
    'F1': {  # Primera columna del folio (Template Type - dígito 1)
        '0': (x, y, width, height),  # Coordenadas de la burbuja para el dígito 0
        '1': (x, y, width, height),  # Coordenadas de la burbuja para el dígito 1
        # ... hasta '9'
    },
    'F2': { ... },  # Segunda columna (Template Type - dígito 2)
    # ... hasta F9
}
```

### 2. **evaluation_01** (Referencia III)
Define las preguntas principales con 5 opciones cada una (A, B, C, D, E)

```python
evaluation_01 = {
    '1': {  # Pregunta 1
        'A': (x, y, width, height),  # Siempre
        'B': (x, y, width, height),  # Casi siempre
        'C': (x, y, width, height),  # Algunas veces
        'D': (x, y, width, height),  # Casi nunca
        'E': (x, y, width, height),  # Nunca
    },
    # ... hasta la última pregunta
}
```

### 3. **reference_i** (Referencia I - Acontecimientos Traumáticos)
Define 24 preguntas con 2 opciones (SÍ/NO)

```python
reference_i = {
    '1': {
        'SI': (x, y, width, height),
        'NO': (x, y, width, height),
    },
    # ... hasta '24'
}
```

### 4. **reference_v** (Referencia V - Datos Demográficos)
Define secciones demográficas variadas (edad, género, estado civil, etc.)

```python
reference_v = {
    'sexo': {
        'H': (x, y, width, height),  # Hombre
        'M': (x, y, width, height),  # Mujer
    },
    'edad': {
        '15-19': (x, y, width, height),
        '20-24': (x, y, width, height),
        # ... otros rangos
    },
    # ... otras secciones
}
```

## 🛠️ Proceso de Calibración

**IMPORTANTE:** Docker NO puede abrir ventanas gráficas. Las herramientas de calibración funcionan de dos formas:

### **Opción A: Detección Automática (RECOMENDADA para desarrollo)**

Usa el script `auto_detect_bubbles.py` que analiza la imagen y genera las coordenadas automáticamente:

```bash
# 1. Procesa el PDF para generar imagen alineada
docker exec -it training-and-ms python /app/main.py

# 2. Ejecuta detección automática
docker exec -it training-and-ms python /app/auto_detect_bubbles.py /app/outputs_aligned/page_1_aligned.png

# 3. Revisa el archivo generado
cat docker/bubble_coordinates.json

# 4. El script imprime código Python listo para copiar a config.py
```

**Ventajas:**
- ✅ No requiere interfaz gráfica
- ✅ Funciona directamente en Docker
- ✅ Detecta burbujas automáticamente
- ✅ Genera código Python listo para usar

**Desventajas:**
- ⚠️ Puede requerir ajustes manuales si la detección no es perfecta

### **Opción B: Manual con Visualizador de Imágenes (Más precisa)**

Para calibración manual cuando la detección automática no funciona bien:

```bash
# 1. Procesa el PDF
docker exec -it training-and-ms python /app/main.py

# 2. Copia imagen alineada a tu máquina local
Copy-Item "docker\outputs_aligned\page_1_aligned.png" "calibration_temp.png"

# 3. Abre la imagen en un programa con coordenadas:
#    - Paint (muestra X,Y en la esquina inferior)
#    - Photoshop/GIMP (coordenadas en barra de herramientas)
#    - Navegador + DevTools

# 4. Anota las coordenadas manualmente para cada burbuja

# 5. Usa config_generator.py LOCALMENTE (no en Docker)
python docker/config_generator.py
```

**Ventajas:**
- ✅ Máxima precisión
- ✅ Control total sobre cada coordenada

**Desventajas:**
- ⚠️ Más trabajo manual
- ⚠️ Requiere copiar archivos del contenedor

### Paso 1: Generar Imagen Alineada

```bash
# 1. Genera PDFs de prueba
php artisan omr:generate-test-pdfs --pages=5

# 2. Copia el PDF al contenedor Docker
Copy-Item "storage\app\omr-test-pdfs\referencia-iii.pdf" "docker\input\evaluation.pdf"

# 3. Procesa el PDF para generar imagen alineada
docker exec -it training-and-ms python /app/main.py
```

Esto generará imágenes alineadas en `docker/outputs_aligned/`

### Paso 2: Detectar Burbujas Automáticamente

```bash
# Ejecuta el detector automático
docker exec -it training-and-ms python /app/auto_detect_bubbles.py /app/outputs_aligned/page_1_aligned.png
```

El script:
1. Detecta todas las burbujas automáticamente
2. Las agrupa por filas y columnas
3. Genera un archivo `bubble_coordinates.json` con todas las coordenadas
4. Imprime código Python listo para copiar a `config.py`

**Salida esperada:**
```
Analizando imagen: /app/outputs_aligned/page_1_aligned.png
Dimensiones de imagen: 2481x3510
Contornos detectados: 523
Burbujas detectadas: 234

=== ANÁLISIS DE ESTRUCTURA ===
Filas detectadas: 23
  Fila 1: 10 burbujas, Y≈785, X: [655, 715, 775, 830, 885]...
  ...

✅ Coordenadas guardadas en: bubble_coordinates.json

=== GENERANDO CONFIGURACIÓN DE FOLIO ===

# Pega esto en docker/config.py:

folio_configuration = {
    'F1': {
        '0': (655, 785, 35, 35),
        '1': (655, 845, 35, 35),
        ...
```

### Paso 3: Revisar y Ajustar (Si es necesario)

```bash
# Ver el archivo JSON completo
cat docker/bubble_coordinates.json

# Si necesitas ajustar coordenadas manualmente, copia la imagen
Copy-Item "docker\outputs_aligned\page_1_aligned.png" "temp_calibration.png"
# Ábrela en Paint/Photoshop y verifica las coordenadas
```

### Paso 2 (Alternativo): Abrir Herramienta de Calibración

**NOTA:** Esta opción NO funciona en Docker sin interfaz gráfica. Solo úsala si ejecutas Python localmente en tu máquina con entorno gráfico.

```bash
# SOLO SI TIENES INTERFAZ GRÁFICA (no en Docker)
python docker/calibrate_bubbles.py imagen_alineada.png
```

### Paso 3 (Alternativo): Capturar Coordenadas Manualmente

La herramienta `calibrate_bubbles.py` abre una ventana interactiva:

1. **Haz clic** en las **4 esquinas** de cada burbuja (en orden: arriba-izq, arriba-der, abajo-izq, abajo-der)
2. Cada 4 clics, el sistema calcula automáticamente: `(x, y, width, height)`
3. Las coordenadas aparecen en la terminal
4. Copia estas coordenadas a `config.py`

**Ejemplo de salida:**
```
Coordenada capturada: (655, 785)
Coordenada capturada: (690, 785)
Coordenada capturada: (655, 820)
Coordenada capturada: (690, 820)
  → Rectángulo: (655, 785, 35, 35)
```

### Paso 4: Actualizar config.py

Abre `docker/config.py` y actualiza las coordenadas con los valores capturados.

**Tips importantes:**
- **Mantén el orden**: Calibra siempre de arriba hacia abajo, izquierda a derecha
- **Consistencia**: Usa el mismo tamaño de burbuja cuando sea posible (generalmente 35x35)
- **Verifica**: Después de actualizar, ejecuta el procesamiento completo para verificar

## 📊 Calibración por Template

### Referencia I - Acontecimientos Traumáticos
**Elementos a calibrar:**
- ✅ Folio (9 columnas x 10 dígitos) - **YA CALIBRADO**
- ⚠️ 24 preguntas x 2 opciones (SÍ/NO) - **VERIFICAR**

### Referencia III - Evaluación Principal
**Elementos a calibrar:**
- ✅ Folio (9 columnas x 10 dígitos) - **YA CALIBRADO**
- ⚠️ ~46 preguntas x 5 opciones (A,B,C,D,E) - **VERIFICAR**
- ❌ CITSATS-s1: 6 preguntas x 2 opciones (SÍ/NO) - **FALTA AÑADIR**

### Referencia V - Datos Demográficos
**Elementos a calibrar:**
- ✅ Folio (9 columnas x 10 dígitos) - **YA CALIBRADO**
- ⚠️ Secciones demográficas (sexo, edad, estado civil, etc.) - **VERIFICAR**

## 🔧 Añadir Nueva Configuración (Ejemplo: CITSATS-s1)

Para añadir la sección CITSATS-s1 a la Referencia III:

```python
# Añadir al final de evaluation_01 en config.py
evaluation_01 = {
    # ... preguntas existentes ...
    
    # Sección CITSATS-s1
    'CITSATS_1': {
        'SI': (x, y, 35, 35),
        'NO': (x, y, 35, 35),
    },
    'CITSATS_2': {
        'SI': (x, y, 35, 35),
        'NO': (x, y, 35, 35),
    },
    'CITSATS_3': {
        'SI': (x, y, 35, 35),
        'NO': (x, y, 35, 35),
    },
    'CITSATS_4': {
        'SI': (x, y, 35, 35),
        'NO': (x, y, 35, 35),
    },
    'CITSATS_5': {
        'SI': (x, y, 35, 35),
        'NO': (x, y, 35, 35),
    },
    'CITSATS_6': {
        'SI': (x, y, 35, 35),
        'NO': (x, y, 35, 35),
    },
}
```

## 🧪 Pruebas de Validación

Después de calibrar:

1. **Genera un PDF de prueba** con folios conocidos
2. **Imprímelo y marca burbujas manualmente**
3. **Escanea o fotografía** la hoja
4. **Procesa con Docker**: `docker exec -it training-and-ms python /app/main.py`
5. **Verifica el JSON** en `docker/output/` que coincida con las burbujas marcadas

## 📝 Notas Importantes

- **Las coordenadas son en píxeles** de la imagen alineada (generalmente 2481x3510)
- **Todas las burbujas del mismo tipo** deben tener el mismo tamaño (width, height)
- **El folio ya está calibrado** y funciona bien, no es necesario recalibrarlo
- **Cada template** puede tener coordenadas diferentes debido a sus layouts únicos
- **Guarda backups** de `config.py` antes de hacer cambios grandes

## 🎯 Comandos Rápidos

```bash
# === FLUJO COMPLETO DE CALIBRACIÓN AUTOMÁTICA ===

# 1. Generar PDFs de prueba
php artisan omr:generate-test-pdfs --pages=5

# 2. Copiar PDF al contenedor
Copy-Item "storage\app\omr-test-pdfs\referencia-iii.pdf" "docker\input\evaluation.pdf"

# 3. Procesar para generar imagen alineada
docker exec -it training-and-ms python /app/main.py

# 4. Detectar burbujas automáticamente
docker exec -it training-and-ms python /app/auto_detect_bubbles.py /app/outputs_aligned/page_1_aligned.png

# 5. Revisar resultados
cat docker/bubble_coordinates.json

# 6. Copiar código generado a config.py (impreso en la terminal)

# === CALIBRACIÓN MANUAL (si auto-detección falla) ===

# Copiar imagen a local para análisis manual
Copy-Item "docker\outputs_aligned\page_1_aligned.png" "calibration_temp.png"

# Abrir en Paint/Photoshop y anotar coordenadas manualmente

# Usar generador interactivo LOCALMENTE (no en Docker)
python docker/config_generator.py
```

## ✅ Checklist de Calibración

- [ ] Referencia I - Folio (ya calibrado)
- [ ] Referencia I - 24 preguntas SÍ/NO
- [ ] Referencia III - Folio (ya calibrado)
- [ ] Referencia III - ~46 preguntas A/B/C/D/E
- [ ] Referencia III - CITSATS-s1 (6 preguntas SÍ/NO)
- [ ] Referencia V - Folio (ya calibrado)
- [ ] Referencia V - Datos demográficos
- [ ] Escala Cisneros - Folio y preguntas

---

**¿Necesitas ayuda?** Revisa los logs en `docker/log.log` o ejecuta con logging detallado para ver qué coordenadas están causando problemas.
