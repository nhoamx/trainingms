# Arquitectura de Configuración OMR - Sistema Actual

## 📁 Estructura de Archivos de Configuración

Actualmente el sistema tiene **DOS** fuentes de configuración que se están usando simultáneamente:

### 1. **Directorio `docker/config/`** (Modular - NUEVO)
```
docker/config/
├── __init__.py              ← Punto de entrada principal
├── folio.py                 ← Configuración del folio (9 columnas × 10 dígitos)
├── referencia_i.py          ← Referencia I (24 preguntas × SI/NO)
├── referencia_iii.py        ← Referencia III (46 preguntas × A/B/C/D/E + CITSATS)
└── referencia_v.py          ← Referencia V (datos demográficos)
```

### 2. **Archivo `docker/config_legacy.py`** (Monolítico - ANTIGUO)
```
docker/config_legacy.py      ← Todas las configuraciones en un solo archivo
```

---

## 🔄 Cómo Funciona la Importación Actual

### En `main.py` (Línea 5):
```python
import config  # Esto importa el DIRECTORIO config/ (por el __init__.py)
```

### En `config/__init__.py`:
```python
# 1. Primero importa desde archivos modulares:
from .folio import folio_configuration
from .referencia_i import reference_i
from .referencia_iii import referencia_iii
from .referencia_v import referencia_v

# 2. Luego intenta importar config_legacy.py:
import config_legacy

# 3. Y SOBRESCRIBE las configuraciones modulares con las de config_legacy si existen:
if hasattr(config_legacy, 'folio_configuration'):
    folio_configuration = config_legacy.folio_configuration  # ← SOBRESCRIBE el de folio.py
    
if hasattr(config_legacy, 'reference_i'):
    reference_i = config_legacy.reference_i  # ← SOBRESCRIBE el de referencia_i.py
```

**⚠️ IMPORTANTE**: Esto significa que actualmente **`config_legacy.py` tiene prioridad** sobre los archivos modulares.

---

## 🎯 ¿Qué Usa `main.py`?

### Cuando ejecutas `main.py`, se usan estas configuraciones:

| Template Type | Variable en main.py | Fuente Actual | Archivo |
|--------------|-------------------|---------------|---------|
| **Folio** (todos) | `config.folio_configuration` | `config_legacy.py` ✅ | Sobrescribe `config/folio.py` |
| **01** (Ref. I) | `config.reference_i` | `config_legacy.py` ✅ | Sobrescribe `config/referencia_i.py` |
| **02** (Evaluación) | `config.evaluation_01` | `config_legacy.py` ✅ | Solo en legacy |
| **03** (Ref. V) | `config.reference_v` | `config_legacy.py` ✅ | Sobrescribe `config/referencia_v.py` |
| **04** (Cisneros) | `config.escala_cisneros` | `config_legacy.py` ✅ | Solo en legacy |

### Código en `main.py` (líneas 221-238):
```python
def detect_template_type_from_folio(folio):
    """Detecta el tipo de template basándose en el folio."""
    if folio.startswith("01"):
        evaluation_config = config.reference_i        # De config_legacy.py
    elif folio.startswith("02"):
        evaluation_config = config.evaluation_01      # De config_legacy.py
    elif folio.startswith("03"):
        evaluation_config = config.reference_v        # De config_legacy.py
    elif folio.startswith("04"):
        evaluation_config = config.escala_cisneros    # De config_legacy.py
    else:
        evaluation_config = config.evaluation_01      # De config_legacy.py
    
    return evaluation_config
```

---

## 🤔 ¿Por Qué Esta Estructura?

La estructura actual existe por **compatibilidad durante la migración**:

1. **Antes**: Todo estaba en `config.py` (un solo archivo gigante)
2. **Problema**: Conflicto de nombres entre `config.py` (archivo) y `config/` (directorio)
3. **Solución Temporal**: 
   - Renombrar `config.py` → `config_legacy.py`
   - Crear directorio `config/` con archivos modulares
   - `__init__.py` importa ambos (legacy tiene prioridad)

---

## ✅ ¿Dónde Actualizar las Coordenadas que Acabas de Calibrar?

### Opción 1: **Actualizar `config_legacy.py`** (Recomendado por ahora)

**✅ VENTAJAS**:
- Es lo que actualmente está usando `main.py`
- Funcionará inmediatamente sin cambios
- Es el archivo que tiene la prioridad

**📝 PASOS**:
1. Abre `docker/config_legacy.py`
2. Busca la sección `folio_configuration = {`
3. Reemplaza todo el diccionario con el código que copiaste de la herramienta web
4. Guarda y copia al contenedor:
   ```bash
   docker cp ./docker/config_legacy.py training-and-ms:/app/config_legacy.py
   ```

**📍 UBICACIÓN EN config_legacy.py**:
```python
# Busca esta sección (aproximadamente línea 1-100):
folio_configuration = {
    'F1': {
        '0': (337, 551, 29, 33),   # ← Reemplaza estas coordenadas
        '1': (338, 613, 25, 32),
        # ... etc
    },
    # ... F2 hasta F9
}
```

### Opción 2: **Actualizar `config/folio.py`** (Para el futuro)

**⚠️ PROBLEMA ACTUAL**:
- Aunque lo actualices, `config_legacy.py` lo sobrescribirá
- No funcionará hasta que eliminemos la prioridad de config_legacy

**🔧 PARA QUE FUNCIONE**:
1. Actualiza `config/folio.py` con las nuevas coordenadas
2. Modifica `config/__init__.py` para que NO sobrescriba desde config_legacy:
   ```python
   # Comentar estas líneas en config/__init__.py:
   # if hasattr(config_legacy, 'folio_configuration'):
   #     folio_configuration = config_legacy.folio_configuration
   ```

---

## 🚀 Plan de Migración Completa (Futuro)

### Flujo de Trabajo Recomendado:

1. **Calibrar con la herramienta web** ✅ (Ya lo hiciste para folios)
2. **Actualizar `config_legacy.py` inmediatamente** para que funcione
3. **También actualizar `config/folio.py`** para ir migrando
4. **Cuando tengas todas las secciones calibradas**:
   - Eliminar las sobrescrituras en `config/__init__.py`
   - Eliminar `config_legacy.py`
   - Usar solo archivos modulares en `config/`

### Archivos por Calibrar:

| Archivo | Estado | Configuración |
|---------|--------|---------------|
| `config/folio.py` | ✅ **CALIBRADO** | 90 burbujas (F1-F9 × 0-9) |
| `config/referencia_i.py` | ⚠️ Pendiente | 48 burbujas (24 preguntas × SI/NO) |
| `config/referencia_iii.py` | ⚠️ Pendiente | 242 burbujas (46×5 + 6×2) |
| `config/referencia_v.py` | ⚠️ Pendiente | ~150 burbujas (demográficos) |
| `config_legacy.py` | ✅ **USAR POR AHORA** | Todas las secciones (tiene prioridad) |

---

## 📝 Resumen Ejecutivo

### ¿Qué Archivo Actualizar AHORA?

**👉 `docker/config_legacy.py`** - Sección `folio_configuration`

### ¿Por Qué?

Porque `config/__init__.py` da prioridad a `config_legacy.py` sobre los archivos modulares.

### ¿Cuál es el Plan?

1. ✅ Actualizar `config_legacy.py` inmediatamente (para que funcione)
2. 📋 Actualizar `config/folio.py` también (para migrar gradualmente)
3. 🔄 Repetir con cada sección que calibres
4. 🗑️ Eventualmente eliminar `config_legacy.py` cuando todo esté migrado

### Comando para Probar:

```bash
# Después de actualizar config_legacy.py:
docker cp ./docker/config_legacy.py training-and-ms:/app/config_legacy.py
docker exec training-and-ms python /app/main.py

# Revisar el output/XXXXXXXXX.json para ver si los folios se detectan correctamente
```

---

## 🎯 Siguiente Paso Inmediato

**Actualiza `docker/config_legacy.py`** pegando el código Python que generó la herramienta web en la sección `folio_configuration`.

Luego prueba procesando un PDF de prueba para verificar que los folios se detecten correctamente (deben empezar con 01, 02, 03, o 04).
