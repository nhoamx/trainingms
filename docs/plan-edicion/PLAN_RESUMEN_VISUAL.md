# 🎯 Plan de Edición de Evaluaciones - Resumen Visual

## 📊 Situación Actual vs. Deseada

### ❌ ANTES (Situación Actual)
```
Evaluación en papel procesada:
├── Folio: 020100001 ✓ (existe)
├── Tipo: referencia_iii ✓ (existe)
├── Respuestas: {...} ✓ (existen)
└── Nombre: ❌ (NO EXISTE)

Limitaciones:
❌ No puedo agregar el nombre del evaluado
❌ No puedo corregir errores en el folio personal
❌ No puedo corregir respuestas mal procesadas por OCR
```

### ✅ DESPUÉS (Situación Deseada)
```
Evaluación en papel procesada:
├── Folio: 020100025 ✓ (editable - solo últimos 4 dígitos)
├── Tipo: referencia_iii ✓ (fijo)
├── Respuestas: {...} ✓ (editables individualmente)
└── Nombre: "Juan Pérez" ✓ (agregable y editable)

Capacidades nuevas:
✅ Puedo agregar nombre después de procesar
✅ Puedo corregir folio personal si hubo error
✅ Puedo corregir respuestas mal detectadas
```

---

## 🔢 Funcionalidad 1: Agregar/Editar Nombre

### Flujo de Usuario

```
1. Administrador ve lista de evaluaciones
   ┌─────────────────────────────────────────┐
   │ Folio       │ Tipo    │ Nombre  │ Acción│
   ├─────────────────────────────────────────┤
   │ 020100001   │ Ref III │ -       │[+📝]  │
   │ 020100002   │ Ref III │ -       │[+📝]  │
   └─────────────────────────────────────────┘

2. Click en [+📝] → Abre modal
   ┌──────────────────────────────┐
   │ 📝 Agregar Nombre            │
   ├──────────────────────────────┤
   │ Folio: 020100001             │
   │                              │
   │ Nombre completo:             │
   │ [Juan Pérez González_____]   │
   │                              │
   │    [Cancelar]  [💾 Guardar]  │
   └──────────────────────────────┘

3. Después de guardar
   ┌─────────────────────────────────────────┐
   │ Folio       │ Tipo    │ Nombre        │  │
   ├─────────────────────────────────────────┤
   │ 020100001   │ Ref III │ Juan Pérez G. │✏️│
   │ 020100002   │ Ref III │ -             │+📝│
   └─────────────────────────────────────────┘
   
   Click en ✏️ → Permite editar el nombre
```

### Base de Datos
```
Tabla: paper_evaluations

Agregar columna:
+ evaluee_name (VARCHAR 255, nullable)

Ejemplo de registro:
{
  id: "uuid...",
  folio: "020100001",
  evaluee_name: "Juan Pérez González",  ← NUEVO
  evaluation_type: "referencia_iii",
  ...
}
```

---

## 🔢 Funcionalidad 2: Editar Folio Personal

### Estructura del Folio
```
Folio Completo: 0 2 0 1 0 0 0 0 1
                └┬┘ └──┬──┘ └──┬──┘
                 │     │       │
            Tipo │  Org│    Personal
         (FIJO)  │(FIJO)│  (EDITABLE)
                 │     │       │
        Ref III  │  010 │    0001
```

### Ejemplo de Edición

```
CASO: Corregir folio personal de 0001 a 0025

┌─────────────────────────────────────┐
│ ✏️ Editar Folio Personal             │
├─────────────────────────────────────┤
│ Folio Actual: 020100001             │
│                                     │
│ Tipo Evaluación: 02                 │
│ Organización:    010                │
│                  ────────           │
│ Folio Personal:  [0025]  ← EDITO    │
│                  ────────           │
│                                     │
│ Nuevo Folio:     020100025          │
│                  ══════════         │
│                                     │
│ ✅ Folio disponible                 │
│                                     │
│    [Cancelar]  [💾 Actualizar]      │
└─────────────────────────────────────┘
```

### Validaciones Automáticas

```
Usuario escribe en campo "Folio Personal":

Entrada: "1"      → ❌ Debe tener 4 dígitos
Entrada: "01"     → ❌ Debe tener 4 dígitos
Entrada: "001"    → ❌ Debe tener 4 dígitos
Entrada: "0001"   → ✅ Formato válido
                   → 🔍 Verificar si existe...
                   → ❌ Ya existe folio 020100001
                   
Entrada: "0025"   → ✅ Formato válido
                   → 🔍 Verificar si existe...
                   → ✅ Folio 020100025 disponible
                   → [Botón Guardar HABILITADO]
```

### Lógica del Sistema

```
PASO 1: Usuario cambia folio personal
        "0001" → "0025"

PASO 2: Sistema recalcula folio completo
        Tipo: 02 (no cambia)
        +
        Org: 010 (no cambia)
        +
        Personal: 0025 (nuevo)
        =
        Folio: 020100025

PASO 3: Sistema verifica unicidad
        SELECT COUNT(*) FROM paper_evaluations 
        WHERE folio = '020100025'
        
        Si COUNT = 0 → ✅ Permitir guardar
        Si COUNT > 0 → ❌ Mostrar error

PASO 4: Actualizar registro
        UPDATE paper_evaluations
        SET folio = '020100025',
            personal_folio = '0025'
        WHERE id = '...'
```

---

## 📝 Funcionalidad 3: Editar Respuestas

### Tipos de Respuestas por Evaluación

```
┌─────────────────┬────────────────┬──────────────────┐
│ Tipo Evaluación │ Preguntas      │ Opciones         │
├─────────────────┼────────────────┼──────────────────┤
│ Referencia I    │ 13 preguntas   │ SI / NO          │
│ Referencia III  │ 64 preguntas   │ A / B / C / D / E│
│ Referencia V    │ Datos demog.   │ Múltiples tipos  │
│ Cisneros        │ Variable       │ Escala Likert    │
└─────────────────┴────────────────┴──────────────────┘
```

### Ejemplo: Editar Referencia III

```
┌─────────────────────────────────────────────┐
│ 📝 Editar Respuestas - Referencia III       │
├─────────────────────────────────────────────┤
│ Folio: 020100001                            │
│ Nombre: Juan Pérez González                 │
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ Pregunta 1                              │ │
│ │ Respuesta actual: D                     │ │
│ │ Nueva respuesta: [D ▼] A B C D E        │ │
│ ├─────────────────────────────────────────┤ │
│ │ Pregunta 2                              │ │
│ │ Respuesta actual: A                     │ │
│ │ Nueva respuesta: [A ▼] A B C D E        │ │
│ ├─────────────────────────────────────────┤ │
│ │ Pregunta 3                              │ │
│ │ Respuesta actual: D                     │ │
│ │ Nueva respuesta: [E ▼] A B C D E ← EDITÓ│ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ ... (61 preguntas más)                      │
│                                             │
│ Preguntas modificadas: 1                    │
│                                             │
│    [Cancelar]  [💾 Guardar Cambios]         │
└─────────────────────────────────────────────┘
```

### Ejemplo: Editar Referencia I

```
┌─────────────────────────────────────────────┐
│ 📝 Editar Respuestas - Referencia I         │
├─────────────────────────────────────────────┤
│ Folio: 010100016                            │
│ Tipo: Evaluación de trauma severo           │
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ 1. ¿Ha presenciado o sufrido algún      │ │
│ │    acontecimiento traumático severo?    │ │
│ │    Actual: SI  Nueva: [SI ▼] SI / NO    │ │
│ ├─────────────────────────────────────────┤ │
│ │ 2. ¿Recuerda el acontecimiento?         │ │
│ │    Actual: NO  Nueva: [NO ▼] SI / NO    │ │
│ ├─────────────────────────────────────────┤ │
│ │ 3. ¿Le afecta emocionalmente?           │ │
│ │    Actual: NO  Nueva: [SI ▼] SI / NO ←  │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ ... (10 preguntas más)                      │
│                                             │
│ Preguntas modificadas: 1                    │
│                                             │
│    [Cancelar]  [💾 Guardar Cambios]         │
└─────────────────────────────────────────────┘
```

### Estructura JSON de Respuestas

```javascript
// ANTES DE EDITAR
{
  "1": "D",
  "2": "A",
  "3": "D",  ← Queremos cambiar esta
  "4": "D",
  ...
}

// DESPUÉS DE EDITAR
{
  "1": "D",
  "2": "A",
  "3": "E",  ← Cambiada de D a E
  "4": "D",
  ...
}

// El sistema:
// 1. Lee el JSON actual
// 2. Modifica solo el valor de la pregunta 3
// 3. Valida que "E" sea una opción válida
// 4. Guarda el JSON completo actualizado
```

---

## 🔒 Reglas de Validación

### Para Nombre
```
✅ Puede estar vacío inicialmente
✅ Puede contener letras, espacios, acentos
✅ Máximo 255 caracteres
❌ No puede contener caracteres especiales raros
```

### Para Folio Personal
```
✅ Debe tener EXACTAMENTE 4 dígitos
✅ Puede ser 0000 a 9999
✅ El folio completo resultante debe ser ÚNICO
❌ No puede tener letras
❌ No puede tener menos de 4 dígitos
❌ No puede duplicar folio existente
```

### Para Respuestas
```
✅ Solo opciones válidas según tipo de pregunta
✅ Mantener estructura JSON correcta
✅ No eliminar preguntas obligatorias
❌ No permitir valores fuera del rango
❌ No permitir texto libre en preguntas cerradas
```

---

## 🎯 Flujo Completo de Usuario

```
ADMINISTRADOR ACCEDE AL SISTEMA
        ↓
Ve lista de evaluaciones procesadas
        ↓
    ┌───┴───┐
    │       │       │
Agregar  Editar  Editar
Nombre   Folio   Respuestas
    │       │       │
    ↓       ↓       ↓
  Modal   Modal   Modal
  Simple  con     con
          validación  formulario
          tiempo real  adaptativo
    │       │       │
    └───┬───┘       │
        ↓           ↓
    Validar      Validar
    y Guardar    y Guardar
        │           │
        └─────┬─────┘
              ↓
    Actualización exitosa
              ↓
    Mensaje de confirmación
              ↓
    Lista actualizada
```

---

## 📱 Interfaz Responsive

```
VISTA DE ESCRITORIO
┌────────────────────────────────────────────────────┐
│ Evaluaciones en Papel                              │
├──────┬──────────┬───────────┬──────────────┬───────┤
│Folio │ Tipo     │ Nombre    │ Estado       │Acción │
├──────┼──────────┼───────────┼──────────────┼───────┤
│020..1│ Ref III  │Juan Pérez │✅ Completada │[📝✏️🗑️]│
│020..2│ Ref III  │-          │✅ Completada │[📝✏️🗑️]│
└──────┴──────────┴───────────┴──────────────┴───────┘

VISTA MÓVIL
┌──────────────────────┐
│ 📄 020100001        │
│ Tipo: Ref III       │
│ Nombre: Juan Pérez  │
│ Estado: ✅ Completa  │
│ ┌──────────────────┐│
│ │ Agregar Nombre   ││
│ │ Editar Folio     ││
│ │ Editar Respuestas││
│ │ Eliminar         ││
│ └──────────────────┘│
└──────────────────────┘
```

---

## ⚡ Resumen de Implementación

| Funcionalidad | Complejidad | Tiempo Est. | Prioridad |
|--------------|-------------|-------------|-----------|
| Agregar Nombre | 🟢 Baja | 2-3 hrs | Alta |
| Editar Folio | 🟡 Media | 4-6 hrs | Alta |
| Editar Respuestas | 🔴 Alta | 8-12 hrs | Media |

### Orden Sugerido
1. **Primero**: Agregar Nombre (más simple, da valor inmediato)
2. **Segundo**: Editar Folio (importante para correcciones)
3. **Tercero**: Editar Respuestas (más complejo, menos urgente)

---

## ✅ Checklist de Desarrollo

### Fase 1: Nombre
- [ ] Migración de base de datos
- [ ] Actualizar modelo
- [ ] Crear componente Vue para modal
- [ ] Implementar endpoint de guardado
- [ ] Pruebas unitarias
- [ ] Pruebas de integración

### Fase 2: Folio
- [ ] Crear validación de unicidad
- [ ] Implementar recalculo automático
- [ ] Crear componente Vue con validación en tiempo real
- [ ] Implementar endpoint de actualización
- [ ] Pruebas de validación
- [ ] Pruebas de casos edge

### Fase 3: Respuestas
- [ ] Analizar estructura de cada tipo
- [ ] Crear componente por tipo de evaluación
- [ ] Implementar validaciones específicas
- [ ] Crear endpoint de actualización
- [ ] Pruebas por tipo de evaluación
- [ ] Pruebas de integridad JSON

---

¿Todo claro? 🎯
