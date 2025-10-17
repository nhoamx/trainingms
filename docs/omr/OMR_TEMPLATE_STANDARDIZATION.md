# Estandarización de Templates OMR y Generación de PDFs de Prueba

## Resumen

Este documento detalla las mejoras implementadas para estandarizar los marcadores de alineación OMR y crear herramientas para generar y probar PDFs de evaluación.

## Cambios Realizados

### 1. Estandarización de Marcadores de Alineación

**Problema:** Los marcadores de alineación estaban duplicados en cada template individual, lo que dificultaba el mantenimiento y la consistencia.

**Solución:**
- Centralizar los marcadores en `resources/views/omr/layout.blade.php`
- Los 4 marcadores están ubicados en las esquinas exactas de la página
- Especificaciones estandarizadas:
  - Tamaño: 8mm x 8mm
  - Color: Negro sólido
  - Posición: 5mm desde cada esquina
  - Forma: Cuadrada (sin bordes redondeados)

**Archivos modificados:**
- `resources/views/omr/layout.blade.php` - Agregado marcadores centralizados
- `resources/views/omr/referencia-i.blade.php` - Removido marcadores duplicados
- `resources/views/omr/referencia-iii.blade.php` - Removido marcadores duplicados
- `resources/views/omr/referencia-v.blade.php` - Removido marcadores duplicados
- `resources/views/omr/escala-cisneros.blade.php` - Removido marcadores duplicados

### 2. Comando Artisan para Generar PDFs de Prueba

**Nuevo comando:** `php artisan omr:generate-test-pdfs`

**Funcionalidad:**
- Genera 3 PDFs de prueba (uno por cada template principal)
- Incluye folios pre-llenados automáticamente:
  - `referencia-i.pdf` → Folio: 130000001 (prefijo 13)
  - `referencia-iii.pdf` → Folio: 120000001 (prefijo 12)
  - `referencia-v.pdf` → Folio: 170000001 (prefijo 17)
- Llena burbujas de respuesta de muestra para testing
- Guarda archivos en `storage/app/omr-test-pdfs/`

**Uso:**
```bash
php artisan omr:generate-test-pdfs
```

**Salida:**
```
🚀 Generando PDFs de prueba para templates OMR...
📁 Los archivos se guardarán en: C:\...\storage\app\omr-test-pdfs

📄 Generando: Guía de Referencia I
✅ Guía de Referencia I generado exitosamente

📄 Generando: Guía de Referencia III
✅ Guía de Referencia III generado exitosamente

📄 Generando: Guía de Referencia V
✅ Guía de Referencia V generado exitosamente

✨ Proceso completado!
```

**Implementación:**
- Archivo: `app/Console/Commands/GenerateOmrTestPdfs.php`
- Utiliza Browsershot para generar PDFs idénticos a los del navegador
- Inyecta CSS para llenar burbujas de forma programática
- Patrón de respuestas:
  - Referencia I: Respuestas alternadas SÍ/NO
  - Referencia III: Respuestas rotando A, B, C, D
  - Referencia V: Selecciones demográficas de muestra

### 3. Mejoras en el Pipeline de Procesamiento OMR (Docker/Python)

**Archivo:** `docker/main.py`

**Mejoras implementadas:**
1. **Detección mejorada de folios:**
   - Validación de longitud de folio (debe ser 9 dígitos)
   - Advertencias cuando el folio es incompleto
   
2. **Soporte para Escala Cisneros:**
   - Agregado prefijo "14" para Escala Cisneros
   - Folios válidos: 12, 13, 14, 17
   
3. **Logging mejorado:**
   - Mensajes informativos durante el procesamiento
   - Identificación clara del tipo de evaluación por folio
   
4. **Referencias flexibles:**
   - Intenta usar `reference-referencia-i.png` primero
   - Fallback a `reference-test-page.png` si no existe
   - Mensaje de advertencia cuando usa referencia legacy

**Código de ejemplo:**
```python
if folio.startswith("12"):
    evaluation_config = config.evaluation_01
    logging.info(f"Folio {folio} → Referencia III")
elif folio.startswith("13"):
    evaluation_config = config.reference_i
    logging.info(f"Folio {folio} → Referencia I")
elif folio.startswith("14"):
    evaluation_config = config.escala_cisneros
    logging.info(f"Folio {folio} → Escala Cisneros")
elif folio.startswith("17"):
    evaluation_config = config.reference_v
    logging.info(f"Folio {folio} → Referencia V")
```

### 4. Herramientas de Calibración

#### Script de Calibración de Burbujas
**Archivo:** `docker/calibrate_bubbles.py`

**Funcionalidad:**
- Interfaz visual para identificar coordenadas de burbujas
- Click en las esquinas de burbujas para obtener rectángulos
- Automáticamente calcula (x, y, ancho, alto) cada 4 clics
- Útil para actualizar coordenadas en `config.py`

**Uso dentro del contenedor:**
```bash
docker exec -it training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png
```

**Controles:**
- Click izquierdo: Capturar coordenada
- 'r': Reiniciar coordenadas
- 'q': Salir

#### Script de Generación de Referencia
**Archivo:** `docker/generate_reference.py`

**Funcionalidad:**
- Convierte la primera página de un PDF en imagen de referencia
- Usado para crear `reference-referencia-i.png`
- Permite actualizar la imagen de referencia cuando los templates cambian

**Uso dentro del contenedor:**
```bash
docker exec -it training-and-ms python /app/generate_reference.py /app/input/evaluation.pdf /app/reference-referencia-i.png
```

### 5. Nueva Imagen de Referencia

**Archivo:** `docker/reference-referencia-i.png`

- Generada desde el PDF de prueba de Referencia I
- Incluye los marcadores estandarizados en las 4 esquinas
- Usada como base para alinear todas las imágenes procesadas
- Mejora la precisión del proceso de alineación

## Flujo de Trabajo Actualizado

### Para generar PDFs de prueba:
```bash
# 1. Generar PDFs
php artisan omr:generate-test-pdfs

# 2. Los PDFs están en:
storage/app/omr-test-pdfs/
  ├── referencia-i.pdf      (Folio: 130000001)
  ├── referencia-iii.pdf    (Folio: 120000001)
  └── referencia-v.pdf      (Folio: 170000001)
```

### Para probar el procesamiento OMR:
```bash
# 1. Copiar PDF de prueba al contenedor
Copy-Item storage\app\omr-test-pdfs\referencia-i.pdf docker\input\evaluation.pdf

# 2. Ejecutar el procesamiento
docker exec -it training-and-ms python /app/main.py

# 3. Verificar resultados
# - Imágenes alineadas: docker/outputs_aligned/
# - Imágenes con folio: docker/output_images/
# - JSONs con respuestas: docker/output/
```

### Para actualizar la imagen de referencia:
```bash
# Si cambias los templates, regenera la referencia:
docker exec -it training-and-ms python /app/generate_reference.py /app/input/evaluation.pdf /app/reference-referencia-i.png
```

### Para calibrar coordenadas de burbujas:
```bash
# 1. Procesar un PDF para obtener imagen alineada
docker exec -it training-and-ms python /app/main.py

# 2. Abrir la herramienta de calibración
docker exec -it training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png

# 3. Hacer clic en las esquinas de las burbujas
# 4. Copiar las coordenadas mostradas a config.py
```

## Próximos Pasos

### Calibración de Coordenadas
Las coordenadas actuales en `docker/config.py` fueron creadas para un template anterior. Para que el sistema detecte correctamente los folios y respuestas con los templates estandarizados:

1. **Generar imagen alineada de referencia:**
   ```bash
   docker exec -it training-and-ms python /app/main.py
   ```

2. **Usar la herramienta de calibración:**
   ```bash
   docker exec -it training-and-ms python /app/calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png
   ```

3. **Actualizar `docker/config.py`:**
   - Sección `folio_configuration`: Coordenadas de las 9 columnas de folio
   - Sección `evaluation_01`: Coordenadas de preguntas para Referencia III
   - Sección `reference_i`: Coordenadas para Referencia I
   - Sección `reference_v`: Coordenadas para Referencia V
   - Nueva sección `escala_cisneros`: Coordenadas para Escala Cisneros

### Validación Completa
Una vez calibradas las coordenadas:

1. Regenerar PDFs de prueba
2. Procesarlos con `main.py`
3. Verificar que los folios se detecten correctamente
4. Verificar que las respuestas se lean correctamente
5. Comparar JSONs de salida con respuestas esperadas

## Beneficios de los Cambios

### Mantenibilidad
- ✅ Un solo lugar para actualizar marcadores (layout.blade.php)
- ✅ Consistencia garantizada entre todos los templates
- ✅ Menos código duplicado

### Testing
- ✅ PDFs de prueba disponibles en segundos
- ✅ Respuestas predefinidas para validación
- ✅ Folios únicos identificables

### Debugging
- ✅ Herramientas visuales para calibración
- ✅ Logging detallado en Python
- ✅ Imágenes intermedias guardadas para análisis

### Escalabilidad
- ✅ Fácil agregar nuevos tipos de evaluación
- ✅ Scripts reutilizables para futuras necesidades
- ✅ Proceso documentado y reproducible

## Archivos Importantes

### PHP/Laravel
- `app/Console/Commands/GenerateOmrTestPdfs.php` - Comando principal
- `resources/views/omr/layout.blade.php` - Layout con marcadores
- `resources/views/omr/*.blade.php` - Templates individuales

### Python/Docker
- `docker/main.py` - Pipeline de procesamiento OMR
- `docker/calibrate_bubbles.py` - Herramienta de calibración
- `docker/generate_reference.py` - Generador de referencia
- `docker/config.py` - Configuración de coordenadas
- `docker/alinear_con_marcadores.py` - Detección y alineación
- `docker/bubble_detector.py` - Detección de burbujas

### Imágenes de Referencia
- `docker/reference-referencia-i.png` - Referencia actualizada
- `docker/reference-test-page.png` - Referencia legacy (fallback)

## Conclusión

Los cambios implementados mejoran significativamente la robustez y mantenibilidad del sistema OMR:

1. **Estandarización**: Marcadores consistentes en todos los templates
2. **Automatización**: Generación de PDFs de prueba con un comando
3. **Visibilidad**: Mejor logging y herramientas de debugging
4. **Flexibilidad**: Scripts modulares y reutilizables
5. **Documentación**: Proceso claramente definido

El siguiente paso crítico es la calibración de coordenadas en `config.py` usando las herramientas provistas para asegurar 100% de precisión en la detección de folios y respuestas.
