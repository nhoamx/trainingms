# 📸 Guía de Sistema de Almacenamiento de Imágenes

## Descripción General

El sistema `ImageStorage` organiza automáticamente todas las imágenes de debug y procesadas en una estructura de directorios lógica y fácil de navegar.

### Estructura de Carpetas

```
output_tracking/
├── documento-slug_YYYYMMDD_HHMMSS/
│   ├── page_001/
│   │   ├── debug/
│   │   │   ├── markers_detected.jpg
│   │   │   ├── folio_roi.jpg
│   │   │   └── ...
│   │   ├── normalized/
│   │   │   ├── normalized.jpg
│   │   │   └── ...
│   │   ├── warped/
│   │   │   ├── warped.jpg
│   │   │   └── ...
│   │   └── other_stages/
│   │       ├── threshold.jpg
│   │       ├── contours.jpg
│   │       └── ...
│   ├── page_002/
│   │   └── ...
│   └── page_003/
│       └── ...
```

## Uso en el Código

### 1. Inicializar el Sistema al Inicio de `main.py`

```python
from helpers import image_storage

# Al procesar un nuevo documento
document_name = "formulario_nom35_lote1"  # Se convierte a slug automáticamente
image_storage.initialize_document(document_name)
```

### 2. Guardar Imágenes Normalizadas

```python
# Guardar imagen normalizada
normalized_img = normalize_size(image, width=1000, height=1400, 
                               page_number=1, save=True)
```

### 3. Guardar Imágenes con Markers Dibujados

```python
# Guardar imagen con markers detectados
debug_img = draw_markers(image, markers, page_number=1, save=True)
```

### 4. Guardar Imágenes Warpificadas

```python
# Guardar imagen después de perspectiva
warped_img = warp_from_markers(image, TL, TR, BL, 
                               page_number=1, save=True)
```

### 5. Guardar Región del Folio

```python
# Guardar ROI del folio para debugging
debug_folio_roi(image, annotation, page_number=1)
```

### 6. Guardar Etapas de Procesamiento Personalizadas

```python
# Opción 1: Guardar una sola imagen
threshold_img = cv2.threshold(gray, 127, 255, cv2.THRESH_BINARY)[1]
save_processing_stage(threshold_img, page_number=1, 
                     stage_name="threshold", filename="threshold_debug")

# Opción 2: Guardar imagen original y procesada para comparación
original_path, processed_path = save_comparison_images(
    original_image, threshold_img, 
    page_number=1, 
    stage_name="threshold_comparison",
    prefix="step1"
)
```

### 7. Obtener la Ruta del Documento Actual

```python
from helpers import get_current_document_path

doc_path = get_current_document_path()
print(f"Documento guardado en: {doc_path}")
```

### 8. Ver la Estructura de Directorios

```python
from helpers import print_document_structure

# Imprime la estructura completa del documento
print_document_structure()
```

## API Completa

### Clase `ImageStorage`

#### Métodos Principales

| Método | Descripción | Parámetros |
|--------|-------------|-----------|
| `initialize_document(name)` | Inicializa el almacenamiento para un documento | `name`: Nombre del documento |
| `save_image()` | Guarda una imagen genérica | `image`, `page_number`, `output_type`, `filename` |
| `save_debug_image()` | Atajo para guardar en carpeta 'debug' | `image`, `page_number`, `filename` |
| `save_normalized_image()` | Atajo para guardar en carpeta 'normalized' | `image`, `page_number`, `filename` |
| `save_warped_image()` | Atajo para guardar en carpeta 'warped' | `image`, `page_number`, `filename` |
| `get_page_path()` | Obtiene la ruta de una página | `page_number` |
| `get_output_path()` | Obtiene la ruta de un tipo de output | `page_number`, `output_type` |
| `get_document_structure()` | Retorna string con la estructura de directorios | Sin parámetros |

## Ejemplo Completo

```python
from helpers import image_storage, normalize_size, draw_markers, \
                   warp_from_markers, save_processing_stage

# 1. Inicializar para un documento
image_storage.initialize_document("formulario_NOM035_2024_01_15")

# 2. Procesar múltiples páginas
for page_num, image in enumerate(pages, 1):
    # Convertir a escala de grises
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    save_processing_stage(gray, page_num, "grayscale")
    
    # Detectar markers
    thresh = cv2.threshold(gray, 127, 255, cv2.THRESH_BINARY)[1]
    save_processing_stage(thresh, page_num, "threshold")
    
    top_markers, bottom_markers = detect_markers(thresh)
    debug_with_markers = draw_markers(image, list(top_markers) + list(bottom_markers), 
                                     page_num, save=True)
    
    # Warpificar
    TL, TR, BL = classify_markers(top_markers, bottom_markers)
    warped = warp_from_markers(image, TL, TR, BL, page_num, save=True)
    
    # Normalizar
    if warped is not None:
        normalized = normalize_size(warped, page_number=page_num, save=True)

# 3. Ver estructura final
image_storage.print_document_structure()
```

## Dependencias Requeridas

```bash
pip install python-slugify
```

La librería `python-slugify` se usa para convertir nombres de documentos a slugs válidos para directorios.

## Tips y Mejores Prácticas

1. **Nombres de documentos**: Usa nombres descriptivos que se convertirán a slugs:
   - ✅ "Formulario NOM-035 Lote 1" → "formulario-nom-035-lote-1_20240315_143022"
   - ❌ Evita caracteres especiales excepto espacios

2. **Números de página**: Siempre comienza desde 1 y secuencial:
   ```python
   for page_number in range(1, len(images) + 1):
       process_page(images[page_number - 1], page_number)
   ```

3. **Tipos de output consistentes**: Usa nombres cortos y en minúsculas:
   - `debug`, `normalized`, `warped`
   - `threshold`, `grayscale`, `contours`
   - `original`, `processed`

4. **Timestamps automáticos**: Si no proporcionas un nombre, se genera automáticamente:
   ```python
   # Usa timestamp automático
   image_storage.save_image(img, 1, "debug")  # image_HHMMSS_mmm.jpg
   
   # O proporciona uno personalizad
   image_storage.save_image(img, 1, "debug", "mi_imagen")  # mi_imagen.jpg
   ```

5. **Limpiar entre ejecuciones**: Cada documento crea carpeta nueva con timestamp
   - No hay conflictos entre ejecuciones diferentes
   - Facilita auditar cambios entre versiones

## Troubleshooting

### Error: "Document no inicializado"
```python
# ❌ Error
image_storage.save_image(img, 1, "debug")

# ✅ Solución
image_storage.initialize_document("mi_documento")
image_storage.save_image(img, 1, "debug")
```

### Error: "No se pudo crear el directorio"
- Verifica permisos de escritura en `output_tracking/`
- Asegúrate que el path es válido en tu sistema operativo

### Las imágenes no se guardan
- Activa el parámetro `save=True` en funciones que soportan guardado
- Verifica que `page_number` es un entero válido (≥ 1)
