# Plan de Edición de Evaluaciones en Papel
## Resumen Ejecutivo para Personas No Técnicas

Este documento explica cómo se implementarán las funcionalidades de edición para las evaluaciones en papel que ya han sido procesadas en el sistema.

---

## 📊 Estado Actual del Sistema

### ¿Cómo se Guarda la Información?

Actualmente, cada evaluación en papel se almacena con la siguiente estructura:

```
FOLIO (9 dígitos): 020100001
├── Código de Tipo (2 dígitos): 02 = Referencia III
├── Código de Organización (3 dígitos): 010
└── Folio Personal (4 dígitos): 0001
```

### Datos Almacenados por Evaluación

1. **Datos de Identificación**
   - Folio completo (único e irrepetible)
   - Tipo de evaluación
   - Organización
   - Folio personal
   - **❌ NO hay campo para NOMBRE** (necesitamos agregarlo)

2. **Respuestas del Examen** (guardadas en formato JSON)
   - **Referencia I**: Preguntas sobre trauma (SI/NO)
     - Ejemplo: `{"1": "SI", "2": "NO", "3": "NO", ...}`
   
   - **Referencia III**: Preguntas sobre factores laborales (A, B, C, D, E)
     - Ejemplo: `{"1": "D", "2": "A", "3": "D", ...}` (64 preguntas)
   
   - **Referencia V**: Datos demográficos (estructura compleja)
     - Ejemplo: `{"edad": {"decenas": "2", "unidades": "7"}, "sexo": "femenino", ...}`
   
   - **Escala Cisneros**: Preguntas sobre mobbing (actualmente sin datos)

---

## 🎯 Funcionalidades a Implementar

### 1. Agregar/Editar Nombre del Evaluado

**¿Qué necesitamos?**
- Un campo nuevo en la base de datos llamado `evaluee_name` (nombre del evaluado)
- Este nombre será agregado DESPUÉS de que se suben las evaluaciones
- Solo el administrador del sistema podrá agregarlo o editarlo

**¿Por qué es importante?**
- Actualmente solo tenemos el folio personal (0001, 0002, etc.)
- No podemos identificar a la persona por su nombre
- Necesario para reportes y consultas personalizadas

**Implementación:**
```
Base de Datos:
  + Agregar columna: evaluee_name (texto, opcional)

Interfaz:
  + Botón "Agregar Nombre" en cada evaluación
  + Modal con formulario simple:
    - Campo de texto para el nombre
    - Botón "Guardar"
    - Botón "Cancelar"
  
  + Mostrar el nombre en la lista de evaluaciones
  + Permitir editar el nombre posteriormente
```

---

### 2. Editar Folio Personal

**¿Qué pasa actualmente?**
- El folio completo es: `[TipoEvaluación][Organización][FolioPersonal]`
- Ejemplo: `020100001` = Referencia III (02) + Organización 010 + Personal 0001

**¿Qué necesitamos cambiar?**
- Permitir editar SOLO el folio personal (últimos 4 dígitos)
- Al cambiar el folio personal, el folio completo se recalcula automáticamente

**⚠️ IMPORTANTE - Reglas de Negocio:**

1. **Unicidad del Folio Completo**
   - No pueden existir dos evaluaciones con el mismo folio completo
   - El sistema debe validar que el nuevo folio no esté ya en uso
   
2. **Validación del Formato**
   - El folio personal debe ser EXACTAMENTE 4 dígitos
   - Ejemplos válidos: 0001, 0002, 9999
   - Ejemplos inválidos: 1, 01, 001 (menos de 4 dígitos)

3. **Recalculo Automático**
   ```
   Si cambio el folio personal de 0001 a 0025:
   
   Folio Anterior: 020100001
   ├── Tipo: 02 (se mantiene)
   ├── Org: 010 (se mantiene)
   └── Personal: 0001 → 0025 (cambia)
   
   Folio Nuevo: 020100025
   ```

**Implementación:**
```
Interfaz:
  + Botón "Editar Folio" en cada evaluación
  + Modal con:
    - Mostrar folio actual completo
    - Campo editable SOLO para los 4 dígitos del folio personal
    - Validación en tiempo real:
      ✓ Debe tener exactamente 4 dígitos
      ✓ Solo números permitidos
      ✗ No puede estar duplicado
    - Vista previa del nuevo folio completo
    - Botón "Guardar" (habilitado solo si válido)
    - Botón "Cancelar"

Lógica del Sistema:
  1. Usuario edita folio personal
  2. Sistema valida formato (4 dígitos)
  3. Sistema recalcula folio completo
  4. Sistema verifica que no exista ese folio
  5. Si todo OK → Actualiza ambos campos (personal y completo)
  6. Si hay error → Muestra mensaje y no permite guardar
```

---

### 3. Editar Respuestas del Examen

**¿Cómo están guardadas las respuestas?**

Las respuestas están en formato JSON, que es como una lista de preguntas y respuestas:

**Referencia I (Trauma):**
```json
{
  "1": "SI",
  "2": "NO",
  "3": "SI",
  ...
  "13": "NO"
}
```

**Referencia III (Factores Laborales):**
```json
{
  "1": "D",
  "2": "A",
  "3": "E",
  ...
  "64": "E"
}
```

**Referencia V (Demográficos):**
```json
{
  "edad": {
    "decenas": "2",
    "unidades": "7"
  },
  "sexo": "femenino",
  "estado_civil": "casado",
  "nivel_estudios": {
    "licenciatura": {
      "seleccionado": true,
      "completado": "completo"
    }
  },
  ...
}
```

**¿Qué queremos permitir editar?**

**Opción Recomendada (Más Segura):**
- Permitir editar SOLO respuestas individuales
- NO permitir cambiar la estructura completa
- Validar que las respuestas sean válidas según el tipo de pregunta

**Implementación:**
```
Interfaz:
  + Botón "Editar Respuestas" en cada evaluación
  + Modal que muestra:
    - Tipo de evaluación
    - Lista de preguntas con sus respuestas actuales
    - Campos editables según el tipo:
      
      Para Referencia I:
        Pregunta 1: [Dropdown: SI / NO]
        Pregunta 2: [Dropdown: SI / NO]
        ...
      
      Para Referencia III:
        Pregunta 1: [Dropdown: A / B / C / D / E]
        Pregunta 2: [Dropdown: A / B / C / D / E]
        ...
      
      Para Referencia V:
        Edad: [Campo numérico de 2 dígitos]
        Sexo: [Dropdown: masculino / femenino]
        Estado Civil: [Dropdown: opciones válidas]
        ...

Validaciones:
  ✓ Solo permitir opciones válidas para cada pregunta
  ✓ Mantener la estructura JSON correcta
  ✓ No permitir borrar preguntas obligatorias
  ✓ Verificar que las opciones existan en la configuración

Lógica del Sistema:
  1. Usuario abre modal de edición
  2. Sistema carga las respuestas actuales
  3. Sistema muestra formulario según tipo de evaluación
  4. Usuario modifica respuestas deseadas
  5. Sistema valida cada cambio
  6. Al guardar:
     - Actualiza solo las respuestas modificadas
     - Mantiene la estructura JSON
     - Registra fecha de última modificación
```

---

## 🔒 Consideraciones de Seguridad

1. **Control de Acceso**
   - Solo administradores pueden editar
   - Registrar quién editó y cuándo
   - Historial de cambios (opcional para fase 2)

2. **Validaciones Críticas**
   - Folio único (no duplicados)
   - Formato correcto de folio (4 dígitos)
   - Respuestas válidas según configuración
   - No permitir cambios que rompan la estructura

3. **Integridad de Datos**
   - Validar ANTES de guardar
   - No permitir datos inconsistentes
   - Mantener respaldo de datos originales (soft deletes ya existe)

---

## 📋 Orden de Implementación Sugerido

### Fase 1: Campo de Nombre (Más Simple)
1. Agregar columna `evaluee_name` a la base de datos
2. Actualizar modelo para incluir el campo
3. Crear interfaz de edición de nombre
4. Implementar guardado y validación

### Fase 2: Edición de Folio Personal (Complejidad Media)
1. Crear validaciones de unicidad
2. Implementar recalculo de folio completo
3. Crear interfaz de edición con validaciones en tiempo real
4. Implementar guardado con verificación

### Fase 3: Edición de Respuestas (Más Complejo)
1. Analizar estructura de respuestas de cada tipo
2. Crear componentes de formulario para cada tipo
3. Implementar validaciones específicas por tipo
4. Crear interfaz adaptativa según tipo de evaluación
5. Implementar guardado con validación de estructura JSON

---

## ⏱️ Estimación de Tiempo

- **Fase 1 (Nombre)**: 2-3 horas
- **Fase 2 (Folio)**: 4-6 horas
- **Fase 3 (Respuestas)**: 8-12 horas por tipo de evaluación

**Total estimado**: 20-30 horas de desarrollo

---

## 🎨 Ejemplos Visuales de la Interfaz

### Editar Nombre
```
┌─────────────────────────────────┐
│ Agregar Nombre al Evaluado      │
├─────────────────────────────────┤
│ Folio: 020100001                │
│                                 │
│ Nombre: [________________]      │
│                                 │
│ [Cancelar]  [Guardar]          │
└─────────────────────────────────┘
```

### Editar Folio Personal
```
┌─────────────────────────────────┐
│ Editar Folio Personal           │
├─────────────────────────────────┤
│ Folio Actual: 020100001         │
│                                 │
│ Tipo Evaluación: 02 (no cambia) │
│ Organización: 010 (no cambia)   │
│ Folio Personal: [0001]          │
│                                 │
│ Nuevo Folio: 020100001          │
│                                 │
│ ⚠️ El folio debe tener 4 dígitos│
│ ⚠️ No puede estar duplicado     │
│                                 │
│ [Cancelar]  [Guardar]          │
└─────────────────────────────────┘
```

### Editar Respuestas (Referencia III)
```
┌─────────────────────────────────┐
│ Editar Respuestas - Ref III     │
├─────────────────────────────────┤
│ Folio: 020100001                │
│ Nombre: Juan Pérez              │
│                                 │
│ Pregunta 1: [D ▼]              │
│ Pregunta 2: [A ▼]              │
│ Pregunta 3: [D ▼]              │
│ ...                             │
│ (Mostrar 10 a la vez con paginación)
│                                 │
│ [Cancelar]  [Guardar Cambios]  │
└─────────────────────────────────┘
```

---

## ✅ Criterios de Éxito

Al finalizar la implementación, el sistema debe permitir:

1. ✅ Agregar nombre a evaluación sin nombre
2. ✅ Editar nombre de evaluación existente
3. ✅ Cambiar folio personal con validación de duplicados
4. ✅ Ver folio completo actualizado automáticamente
5. ✅ Editar respuestas individuales según tipo de evaluación
6. ✅ Validar que las respuestas sean válidas
7. ✅ Mantener la integridad de los datos JSON
8. ✅ Mostrar mensajes de error claros y comprensibles

---

## 📞 Soporte y Documentación

Este plan será acompañado de:
- Guías de usuario para administradores
- Documentación técnica para desarrolladores
- Videos tutoriales de uso
- Manual de troubleshooting
