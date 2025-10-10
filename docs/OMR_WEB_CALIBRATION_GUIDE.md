# Guía de Calibración Web OMR

## Resumen

La herramienta de calibración web permite identificar las coordenadas de las burbujas OMR de forma visual y sencilla desde el navegador, sin necesidad de X11, Qt o VNC.

## Características

✅ **Interfaz Web Moderna** - Interfaz responsiva y fácil de usar  
✅ **Sin Dependencias Gráficas** - Solo necesitas un navegador  
✅ **Tiempo Real** - Ve los resultados inmediatamente  
✅ **Funciones Útiles** - Deshacer, reiniciar, copiar al portapapeles  
✅ **Visual Feedback** - Muestra puntos y rectángulos mientras calibras  

## Requisitos

- Docker con el contenedor `training-and-ms` corriendo
- Puerto 5000 expuesto en `docker-compose.yml`
- Flask instalado en el contenedor

## Inicio Rápido

### 1. Generar Imágenes Alineadas

Primero necesitas procesar un PDF para obtener imágenes alineadas:

```bash
# Copiar PDF de prueba al contenedor
docker cp ./storage/app/omr-test-pdfs/referencia-iii.pdf training-and-ms:/app/input/evaluation.pdf

# Procesar el PDF (esto genera imágenes alineadas en /app/outputs_aligned/)
docker exec training-and-ms python /app/main.py
```

### 2. Iniciar el Servidor de Calibración

```bash
# Iniciar el servidor web en el puerto 5000
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5000
```

**Salida esperada:**
```
============================================================
🚀 Servidor de Calibración OMR Iniciado
============================================================
📁 Imagen: /app/outputs_aligned/page_1_aligned.png
📐 Dimensiones: 2550x3300
🌐 URL: http://localhost:5000
============================================================

📌 Abre la URL en tu navegador para comenzar la calibración
🛑 Presiona Ctrl+C para detener el servidor

 * Running on http://127.0.0.1:5000
```

### 3. Abrir en el Navegador

Abre tu navegador y ve a: **http://localhost:5000**

## Cómo Usar la Interfaz

### Instrucciones de Calibración

1. **Hacer Clic en 2 Esquinas Opuestas** de cada burbuja:
   - Primera esquina: Superior-izquierda
   - Segunda esquina: Inferior-derecha

2. **Automáticamente Calcula el Rectángulo**:
   - Después de cada 2 clics, se muestra el rectángulo (x, y, ancho, alto)
   - El rectángulo se dibuja en rojo sobre la imagen

3. **Revisar Coordenadas**:
   - Las coordenadas aparecen en la lista inferior
   - Las burbujas completas tienen fondo verde

### Controles Disponibles

| Botón | Función |
|-------|---------|
| 🔄 **Reiniciar Coordenadas** | Borra todas las coordenadas y empieza de nuevo |
| ↩️ **Deshacer Último** | Elimina el último punto capturado |
| 📋 **Copiar Coordenadas** | Copia todas las coordenadas al portapapeles |

### Visualización

- **Puntos Verdes**: Coordenadas capturadas
- **Números**: Orden de los puntos
- **Rectángulos Rojos**: Burbujas completadas
- **Posición del Mouse**: Se muestra en la esquina inferior derecha

## Ejemplo de Uso: Calibrar Sección de Folio

### Paso 1: Identificar la Sección

Para calibrar el folio (9 columnas, 10 dígitos cada una), necesitas:

1. Identificar la primera columna (F1)
2. Calibrar los 10 dígitos (0-9) de esa columna
3. Repetir para las otras 8 columnas (F2-F9)

### Paso 2: Capturar Coordenadas

Para cada burbuja:
1. Click en la esquina superior-izquierda
2. Click en la esquina inferior-derecha
3. La coordenada se muestra automáticamente

**Ejemplo de salida en la terminal:**
```
Burbuja 1: (655, 785, 35, 35)
Burbuja 2: (655, 845, 35, 35)
Burbuja 3: (655, 905, 35, 35)
...
```

### Paso 3: Copiar al Config

Click en **"📋 Copiar Coordenadas"** para copiar todas las coordenadas al portapapeles.

Luego pégalas en `docker/config_legacy.py`:

```python
folio_configuration = {
    'F1': {
        '0': (655, 785, 35, 35),
        '1': (655, 845, 35, 35),
        '2': (655, 905, 35, 35),
        # ... más dígitos
    },
    'F2': {
        '0': (715, 785, 35, 35),
        # ... etc
    },
    # ... más columnas
}
```

## Flujo de Trabajo Completo

### 1. Calibrar Folio (9 columnas × 10 dígitos = 90 burbujas)

```bash
# Iniciar calibración
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5000

# Abrir http://localhost:5000
# Calibrar las 90 burbujas del folio
# Copiar coordenadas y actualizar config_legacy.py sección 'folio_configuration'
```

### 2. Calibrar Referencia I (24 preguntas × 2 opciones SÍ/NO = 48 burbujas)

```bash
# Procesar PDF de Referencia I
docker cp ./storage/app/omr-test-pdfs/referencia-i.pdf training-and-ms:/app/input/evaluation.pdf
docker exec training-and-ms python /app/main.py

# Calibrar
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5000

# Abrir http://localhost:5000
# Calibrar las 48 burbujas
# Copiar coordenadas y actualizar config_legacy.py sección 'reference_i'
```

### 3. Calibrar Referencia III (46 preguntas × 5 opciones + 6 CITSATS × 2 = 242 burbujas)

```bash
# Procesar PDF de Referencia III
docker cp ./storage/app/omr-test-pdfs/referencia-iii.pdf training-and-ms:/app/input/evaluation.pdf
docker exec training-and-ms python /app/main.py

# Calibrar (puedes hacerlo por páginas)
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5000

# Abrir http://localhost:5000
# Calibrar las burbujas de la primera página
# Repetir para page_2_aligned.png, page_3_aligned.png, etc.
```

### 4. Calibrar Referencia V (Secciones demográficas, ~150 burbujas)

```bash
# Procesar PDF de Referencia V
docker cp ./storage/app/omr-test-pdfs/referencia-v.pdf training-and-ms:/app/input/evaluation.pdf
docker exec training-and-ms python /app/main.py

# Calibrar
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5000

# Abrir http://localhost:5000
# Calibrar las burbujas demográficas
```

## Tips y Mejores Prácticas

### Para Calibración Precisa

1. **Zoom del Navegador**: Usa Ctrl + "+" para hacer zoom y ver mejor las burbujas pequeñas
2. **Orden Sistemático**: Calibra de arriba a abajo, de izquierda a derecha
3. **Verificación Visual**: Los rectángulos rojos deben coincidir perfectamente con las burbujas
4. **Guardar Progreso**: Copia las coordenadas frecuentemente por si necesitas reiniciar

### Optimización del Trabajo

1. **Calibrar por Secciones**: No intentes calibrar todo de una vez
2. **Usar Múltiples Páginas**: Si el template tiene varias páginas, calibra cada una
3. **Validar con Debug**: Usa `debug_folio_detection.py` para verificar que las coordenadas sean correctas

### Solución de Problemas

**El servidor no inicia:**
```bash
# Verificar que Flask esté instalado
docker exec training-and-ms pip install flask

# Verificar que el puerto esté expuesto en docker-compose.yml
# Debe tener: ports: - "5000:5000"
```

**No veo la imagen en el navegador:**
```bash
# Verificar que la imagen existe
docker exec training-and-ms ls -lh /app/outputs_aligned/page_1_aligned.png

# Verificar los logs del servidor
# Busca errores en la terminal donde ejecutaste el comando
```

**Las coordenadas no son precisas:**
- Asegúrate de hacer zoom en el navegador para clicks más precisos
- Verifica que estés clickeando en las esquinas correctas (superior-izquierda e inferior-derecha)
- La imagen debe estar alineada correctamente (usa `main.py` primero)

## Estructura de Coordenadas en config_legacy.py

### Formato General

```python
{
    'identificador': {
        'opcion_1': (x, y, ancho, alto),
        'opcion_2': (x, y, ancho, alto),
        # ...
    }
}
```

### Ejemplo: Folio

```python
folio_configuration = {
    'F1': {  # Columna 1 (Template Type - primer dígito)
        '0': (655, 785, 35, 35),
        '1': (655, 845, 35, 35),
        # ... dígitos 2-9
    },
    'F2': {  # Columna 2 (Template Type - segundo dígito)
        '0': (715, 785, 35, 35),
        # ...
    },
    # F3-F5: Código de organización (3 dígitos)
    # F6-F9: Código de persona (4 dígitos)
}
```

### Ejemplo: Referencia I

```python
reference_i = {
    '1': {  # Pregunta 1
        'SI': (x, y, w, h),
        'NO': (x, y, w, h),
    },
    '2': {  # Pregunta 2
        'SI': (x, y, w, h),
        'NO': (x, y, w, h),
    },
    # ... preguntas 3-24
}
```

### Ejemplo: Referencia III

```python
referencia_iii = {
    '1': {  # Pregunta 1
        'A': (x, y, w, h),  # Siempre
        'B': (x, y, w, h),  # Casi siempre
        'C': (x, y, w, h),  # Algunas veces
        'D': (x, y, w, h),  # Casi nunca
        'E': (x, y, w, h),  # Nunca
    },
    # ... preguntas 2-46
}
```

## Siguiente Paso: Validación

Después de calibrar todas las coordenadas:

1. **Guardar `config_legacy.py`** con las nuevas coordenadas
2. **Copiar al contenedor**:
   ```bash
   docker cp ./docker/config_legacy.py training-and-ms:/app/config_legacy.py
   ```
3. **Probar el procesamiento**:
   ```bash
   docker cp ./storage/app/omr-test-pdfs/referencia-iii.pdf training-and-ms:/app/input/evaluation.pdf
   docker exec training-and-ms python /app/main.py
   ```
4. **Verificar los JSONs generados** en `docker/output/`

Si los folios y respuestas se detectan correctamente, ¡la calibración fue exitosa! 🎉

## Comandos de Referencia Rápida

```bash
# Instalar Flask (si es necesario)
docker exec training-and-ms pip install flask

# Iniciar servidor de calibración
docker exec training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png 5000

# Abrir navegador
# http://localhost:5000

# Detener servidor
# Presiona Ctrl+C en la terminal donde está corriendo
```

## Recursos Adicionales

- **Documentación OMR**: `docs/OMR_TEMPLATE_STANDARDIZATION.md`
- **Guía de Configuración**: `docs/CONFIG_MIGRATION_GUIDE.md`
- **Script de Calibración**: `docker/calibrate_bubbles.py`
- **Configuración de Coordenadas**: `docker/config_legacy.py`
