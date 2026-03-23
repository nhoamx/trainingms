# OMR Pipeline — Guía de uso manual y pruebas

Pipeline de reconocimiento de marcas ópticas (OMR) para hojas de respuestas NOM-035 y Datos Demográficos.

---

## Requisitos previos

El virtual environment del pipeline está en `docker/.venv` (Python 3.x). Todos los comandos se corren desde la raíz del proyecto (`trainingms/`).

---

## Comandos principales

### Procesar un PDF

```powershell
# Forma básica
& ".venv\Scripts\python.exe" docker/main.py --pdf docker/input/evaluation.pdf

# Con PDF de prueba incluido en el repo
& ".venv\Scripts\python.exe" docker/main.py --pdf docker/input-test/complete-test-barcoded.pdf
```

Las anotaciones (`folio-annotation-profiles.json`, `gri-annotation-manual.json`, etc.) se
resuelven automáticamente desde la carpeta del PDF o desde `docker/input-test/` si no se
pasan explícitamente.

---

## Argumentos disponibles

| Argumento | Descripción | Ejemplo |
|-----------|-------------|---------|
| `--pdf` | Ruta al PDF de entrada | `docker/input/evaluation.pdf` |
| `--expected` | JSON esperado para validación de resultados | `docker/input-test/expected/02010200053.json` |
| `--force-template` | Fuerza el tipo de plantilla (`01`–`06`) sin auto-detectar | `--force-template 02` |
| `--folio-annotation` | JSON de anotación manual del folio (bbox + celdas) | `docker/input-test/folio-annotation-profiles.json` |
| `--ref1-annotation` | JSON de anotación de Guía I (14 preguntas SI/NO) | `docker/input-test/gri-annotation-manual.json` |
| `--demographic-annotation` | JSON de anotación de datos demográficos | `docker/input-test/demographic-annotation-manual.json` |
| `--ref3-annotation` | JSON de anotación de bloques Ref III | `docker/input-test/griii-annotation-manual.json` |
| `--ref3-strict-annotation` | Usa coordenadas de Ref III exactas (sin offset refinement) | (flag, sin valor) |
| `--calibrate-ref3` | Calibración automática de offsets/umbrales Ref III (requiere `--expected`) | (flag, sin valor) |

---

## Ejemplos de uso frecuente

### PDF barcoded completo (Guía I + Ref III + Demográficos)

```powershell
& ".venv\Scripts\python.exe" docker/main.py `
    --pdf docker/input-test/complete-test-barcoded.pdf
```

### Validar contra JSON esperado (Ref III)

```powershell
& ".venv\Scripts\python.exe" docker/main.py `
    --pdf docker/input-test/nom-035-ref-iii.pdf `
    --expected docker/input-test/expected/02010200053.json `
    --force-template 02
```

### Forzar anotaciones explícitas

```powershell
& ".venv\Scripts\python.exe" docker/main.py `
    --pdf docker/input-test/complete-test-barcoded.pdf `
    --folio-annotation  docker/input-test/folio-annotation-profiles.json `
    --ref1-annotation   docker/input-test/gri-annotation-manual.json `
    --ref3-annotation   docker/input-test/griii-annotation-manual.json `
    --demographic-annotation docker/input-test/demographic-annotation-manual.json
```

### Calibración automática Ref III

```powershell
& ".venv\Scripts\python.exe" docker/main.py `
    --pdf docker/input-test/nom-035-ref-iii.pdf `
    --expected docker/input-test/expected/02010200053.json `
    --force-template 02 `
    --calibrate-ref3
```

---

## Salida del pipeline

| Ruta | Contenido |
|------|-----------|
| `docker/output/<folio>.json` | JSON con respuestas detectadas por folio |
| `docker/output_tracking/<run_id>/run_summary.json` | Resumen completo de la corrida |
| `docker/output_tracking/<run_id>/pages/page_N/` | Imágenes de debug por página |
| `docker/output_tracking/<run_id>/pages/page_N/02_alignment_meta.json` | Meta de alineación + calidad |
| `docker/output_tracking/<run_id>/pages/page_N/folio/` | Debug de detección de folio |
| `docker/output_tracking/<run_id>/pages/page_N/sections/` | Diagnósticos por sección |

### Imágenes de debug por página

```
page_N/
  01_original.png            — Imagen original antes de alinear
  02_prealign_markers.png    — Marcadores de alineación detectados
  03_aligned.png             — Imagen después del warp de alineación
  05_final_image.png         — Imagen final usada para extracción
  folio/
    roi_aligned.png          — Región del folio en el scan alineado
    roi_reference.png        — Misma región en la imagen de referencia
    roi_binary.png           — Folio binarizado (Otsu)
    roi_diff_binary.png      — Diferencia binaria scan vs referencia
    columns/F1..F11/         — Debug por columna de dígito
  sections/
    ref1_diagnostics.json    — Scores SI/NO por pregunta (Guía I)
    section_diagnostics.json — Scores por pregunta (Ref III)
    demographic_diagnostics.json — Scores demográficos
```

---

## Inspeccionar alidad de alineación

El archivo `02_alignment_meta.json` incluye el campo `alignment_quality`:

```json
{
  "marker_mode": "asymmetric",
  "alignment_quality": {
    "mean_error": 2.3,
    "ok": true,
    "warning": null
  }
}
```

- `mean_error` < 12 → alineación buena.
- `mean_error` ≥ 12 → el pipeline emite un `WARNING` en el log y el campo `warning` describe el problema.

Revisar rápidamente todas las páginas de la última corrida:

```powershell
& ".venv\Scripts\python.exe" -c "
import json; from pathlib import Path
run_dir = sorted(Path('docker/output_tracking').glob('2026*'))[-1]
for page_dir in sorted((run_dir / 'pages').iterdir()):
    meta = page_dir / '02_alignment_meta.json'
    if meta.exists():
        d = json.loads(meta.read_text())
        q = d.get('alignment_quality', {})
        print(page_dir.name, 'err=' + str(q.get('mean_error')), 'ok=' + str(q.get('ok')))
"
```

---

## Ejecutar tests unitarios

Los tests viven en `docker/omr_next/tests/`.

```powershell
# Todos los tests del pipeline
& ".venv\Scripts\python.exe" -m pytest docker/omr_next/tests/ -v

# Un test específico
& ".venv\Scripts\python.exe" -m pytest docker/omr_next/tests/test_ref3.py -v

# Alternativa con unittest
$env:PYTHONPATH = 'docker'
& ".venv\Scripts\python.exe" -m unittest omr_next.tests.test_ref3
```

---

## Estructura de archivos de anotación

Las anotaciones definen las coordenadas exactas de burbujas y folios. Se buscan
automáticamente en este orden:

1. Ruta explícita vía argumento CLI
2. Carpeta del mismo PDF
3. `docker/input-test/` (anotaciones compartidas del repo)

Nombres de archivo reconocidos automáticamente:

| Tipo | Archivos buscados |
|------|-------------------|
| Folio | `folio-annotation-profiles.json`, `folio-annotation-manual.json`, `folio-annotation-gri.json`, `folio-annotation-griii.json`, `folio-annotation-demographic.json` |
| Ref I (GRI) | `gri-annotation-manual.json`, `ref1-annotation-manual.json` |
| Demográficos | `demographic-annotation-manual.json` |
| Ref III (GRIII) | `griii-annotation-manual.json`, `ref3-annotation-manual-v2.json`, `ref3-annotation-manual.json` |
| Compartido | `general-annotation.json`, `annotation-bundle-manual.json` |

---

## Herramientas de anotación visual

Abrir en el navegador directamente (no requieren servidor):

| Herramienta | Archivo |
|-------------|---------|
| Editor de folio | `docker/input-test/folio-annotation-tool.html` |
| Editor Ref I (GRI) | `docker/input-test/ref1-annotation-tool.html` |
| Editor Ref III (GRIII) | `docker/input-test/ref3-annotation-tool.html` y `ref3-point-editor.html` |
| Editor demográficos | `docker/input-test/demographic-annotation-tool.html` |
| Generador de barcode PDF | `docker/input-test/barcode-pdf-tool.html` |

---

## Producción con Docker

```bash
# Build
docker build -t omr-pipeline docker/

# Correr con un PDF
docker run --rm \
  -v /ruta/a/input:/app/input \
  -v /ruta/a/output:/app/output \
  omr-pipeline \
  python main.py --pdf /app/input/evaluation.pdf
```

La variable de entorno `OMR_ALIGNMENT_QUALITY_THRESHOLD` controla el umbral de calidad de
alineación (default `12.0`). Valores más bajos son más estrictos.
