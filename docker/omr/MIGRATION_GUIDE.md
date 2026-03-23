# 🔄 Guía de Migración - De Guardado Manual a ImageStorage

## 📋 Antes vs Después

### Escenario 1: Guardar imagen de debug simple

**ANTES:**
```python
import cv2

# ...

debug_img = draw_markers(image, markers)
cv2.imwrite("debug_markers.jpg", debug_img)  # Se guarda en raíz sin organización
```

**DESPUÉS:**
```python
from helpers import image_storage, draw_markers

# Al inicio del script
image_storage.initialize_document("mi_documento")

# En el procesamiento
debug_img = draw_markers(image, markers, page_number=1, save=True)
# ✅ Se guarda automáticamente en: 
# output_tracking/mi-documento_timestamp/page_001/debug/markers_detected.jpg
```

---

### Escenario 2: Normalizar y guardar imagen

**ANTES:**
```python
import cv2

normalized = normalize_size(image)
cv2.imwrite("normalized.jpg", normalized)
```

**DESPUÉS:**
```python
from helpers import normalize_size

normalized = normalize_size(image, page_number=1, save=True)
# ✅ Se guarda en: output_tracking/mi-documento_timestamp/page_001/normalized/normalized.jpg
```

---

### Escenario 3: Trabajar con warp/perspectiva

**ANTES:**
```python
warped = warp_from_markers(image, TL, TR, BL)
cv2.imwrite("warped_output.jpg", warped)  # Sin contexto de página
```

**DESPUÉS:**
```python
from helpers import warp_from_markers

warped = warp_from_markers(image, TL, TR, BL, page_number=1, save=True)
# ✅ Se guarda en: output_tracking/mi-documento_timestamp/page_001/warped/warped.jpg
```

---

### Escenario 4: Múltiples imágenes de una región

**ANTES:**
```python
import cv2

roi = crop_folio_region(image, annotation)
cv2.imwrite("folio_roi.jpg", roi)
cv2.imwrite("folio_threshold.jpg", thresh)
# Ambas en la raíz, sin saber cuál es cuál
```

**DESPUÉS:**
```python
from helpers import image_storage, crop_folio_region, save_processing_stage

# Modo 1: Usar métodos de conveniencia
image_storage.save_debug_image(roi, page_number=1, "folio_roi")
image_storage.save_image(thresh, page_number=1, "threshold", "folio_threshold")

# Modo 2: Usar función helper
save_processing_stage(roi, 1, "debug", "folio_roi")
save_processing_stage(thresh, 1, "threshold", "folio_threshold")

# ✅ Resultado:
# output_tracking/mi-documento_timestamp/page_001/debug/folio_roi.jpg
# output_tracking/mi-documento_timestamp/page_001/threshold/folio_threshold.jpg
```

---

### Escenario 5: Debugging con comparación antes/después

**ANTES:**
```python
import cv2

gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
cv2.imwrite("original.jpg", image)
cv2.imwrite("gray.jpg", gray)
# Archivos en raíz sin indicar relación

thresh = cv2.threshold(gray, 127, 255, cv2.THRESH_BINARY)[1]
cv2.imwrite("gray.jpg", gray)  # Sobrescribe lo anterior
cv2.imwrite("threshold.jpg", thresh)
```

**DESPUÉS:**
```python
from helpers import image_storage, save_comparison_images

# Al inicio
image_storage.initialize_document("mi_documento")

page_number = 1
gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

# Guardar comparación: orignal vs resultado
save_comparison_images(image, gray, page_number, 
                      stage_name="rgb_to_gray", 
                      prefix="step1")

thresh = cv2.threshold(gray, 127, 255, cv2.THRESH_BINARY)[1]
save_comparison_images(gray, thresh, page_number,
                      stage_name="thresholding",
                      prefix="step2")

# ✅ Resultado ordenado:
# page_001/rgb_to_gray/step1_original.jpg
# page_001/rgb_to_gray/step1_processed.jpg
# page_001/thresholding/step2_original.jpg
# page_001/thresholding/step2_processed.jpg
```

---

### Escenario 6: Procesar múltiples páginas

**ANTES:**
```python
import cv2
from helpers import pdf_to_image

pages = pdf_to_image("documento.pdf")

for i, image in enumerate(pages):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    cv2.imwrite(f"page_{i}_gray.jpg", gray)  # Archivos en raíz
    
    # ❌ Problema: Todos en el mismo nivel, sin organización coherente
```

**DESPUÉS:**
```python
from helpers import image_storage, pdf_to_image, normalize_size

# Inicializar una sola vez
image_storage.initialize_document("documento_completo")

pages = pdf_to_image("documento.pdf")

for page_number, image in enumerate(pages, 1):  # Comenzar desde 1
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    
    # Guardar etapa de conversión
    save_processing_stage(gray, page_number, "grayscale")
    
    # Normalizar y guardar
    normalized = normalize_size(image, page_number=page_number, save=True)
    
    # ✅ Resultado automáticamente organizado:
    # output_tracking/documento-completo_timestamp/
    # ├── page_001/grayscale/image_*.jpg
    # ├── page_001/normalized/normalized.jpg
    # ├── page_002/grayscale/image_*.jpg
    # ├── page_002/normalized/normalized.jpg
    # └── ...
```

---

## 🛠️ Plan de Migración para `main.py`

### Paso 1: Agregar importaciones

```python
# Al inicio de main.py
from helpers import image_storage  # ← Agregar esta línea

# Tus otros imports...
from helpers import (
    pdf_to_image,
    normalize_size,
    draw_markers,
    warp_from_markers,
    # ... otros ...
)
```

### Paso 2: Inicializar en función principal

```python
def main():
    # ... código existente ...
    
    # AGREGAR ESTAS LÍNEAS:
    document_name = get_document_name(pdf_path)  # o generar nombre
    image_storage.initialize_document(document_name)
    
    # ... resto del código ...
```

### Paso 3: Reemplazar `cv2.imwrite()` calls

**Buscar en el código:**
```bash
# En terminal/editor
grep -r "cv2.imwrite" docker/
```

**Para cada `cv2.imwrite()` encontrado, reemplazar:**

```python
# ❌ REMOVER
cv2.imwrite("algo.jpg", image)

# ✅ REEMPLAZAR CON
image_storage.save_image(image, page_number, output_type)
# O usar métodos específicos:
image_storage.save_debug_image(image, page_number)
image_storage.save_normalized_image(image, page_number)
```

### Paso 4: Actualizar parámetros de funciones

```python
# ❌ Antiguo
normalized = normalize_size(image)

# ✅ Nuevo
normalized = normalize_size(image, page_number=1, save=True)
```

### Paso 5: Verificar estructura al final

```python
# En main(), al final:
print("\n" + "="*60)
print("📂 Estructura de almacenamiento:")
print("="*60)
image_storage.print_document_structure()
```

---

## ✅ Checklist de Migración

- [ ] Agregar `image_storage` a importaciones
- [ ] Instalar `python-slugify` en requirements
- [ ] Inicializar documento en función principal
- [ ] Reemplazar `cv2.imwrite()` con métodos de `image_storage`
- [ ] Actualizar llamadas a `normalize_size()`, `draw_markers()`, `warp_from_markers()`
- [ ] Agregar `page_number` a todas las llamadas relevantes
- [ ] Probar con un documento pequeño
- [ ] Verificar estructura en `output_tracking/`
- [ ] Documentar nombres de stages personalizados
- [ ] Ejecutar suite de pruebas, si existe

---

## 🚀 Ejecución de Prueba

```python
# test_image_storage.py
from helpers import image_storage, normalize_size
from pathlib import Path
import cv2
import numpy as np

# Crear imagen de prueba
test_image = np.zeros((600, 800, 3), dtype=np.uint8)
cv2.putText(test_image, "Test Image", (200, 300), 
           cv2.FONT_HERSHEY_SIMPLEX, 2, (255, 255, 255), 3)

# Inicializar
image_storage.initialize_document("test_document")

# Guardar
normalized = normalize_size(test_image, page_number=1, save=True)

# Verificar
output_dir = Path("output_tracking")
if output_dir.exists():
    print("✅ Estructura creada exitosamente")
    image_storage.print_document_structure()
else:
    print("❌ Error: directorio no fue creado")
```

**Ejecutar:**
```bash
cd docker/omr
python test_image_storage.py
```

---

## 📞 Soporte en Migración

Si encuentras errores:

1. **"Document no inicializado"**
   - Solución: Llamar `image_storage.initialize_document()` antes de usar

2. **"AttributeError: module 'cv2' has no attribute 'imwrite'"**
   - Verificar que OpenCV esté importado correctamente

3. **Directorios no se crean**
   - Verificar permisos de escritura
   - Verificar que Python se ejecuta en directorio correcto

4. **ImportError con slugify**
   - Ejecutar: `pip install python-slugify`

---

**¡Completa tu migración hoy y disfruta de un code más organizado! 🎉**
