# Estandarización de Estructura de Datos para Evaluaciones Online

## Fecha de Implementación
8 de febrero de 2026

## Resumen
Se estandarizó completamente la estructura de datos de las evaluaciones online para que coincida con el formato de las evaluaciones en papel (OCR), facilitando el procesamiento unificado en el backend.

---

## Estructura Estandarizada

### Formato de Envío al Backend

```javascript
{
    demographic_data: {
        organization_info: {
            nombre_comercial: '',
            estado: '',
            ciudad: ''
        },
        personal_data: {
            sexo: '',
            edad: '',
            estado_civil: '',
            nivel_estudios: '',
            ...
        },
        labor_data: {
            ocupacion_puesto: '',
            tipo_puesto: '',
            tipo_contratacion: '',
            ...
        }
    },
    reference_iii: {
        // Preguntas generales (1-64)
        1: 'A',
        2: 'B',
        ...
        64: 'D',
        
        // Sección de atención a clientes (65-68)
        customer_service: {
            condition: true,  // o false
            65: 'D',          // Solo si condition === true
            66: 'E',
            67: 'D',
            68: 'A'
        },
        
        // Sección de supervisión (69-72)
        management: {
            condition: false, // o true
            // Vacío si condition === false
        },
        
        // Acontecimientos traumáticos CITSATS (1-6)
        ats_s1: {
            1: true,
            2: false,
            3: false,
            4: true,
            5: false,
            6: false
        }
    },
    reference_i: {
        // Preguntas de seguimiento (1-13)
        1: true,
        2: false,
        ...
        13: false
    }
}
```

---

## Cambios Realizados

### 1. **Frontend - Componentes Vue**

#### TraumaticEventsSection.vue
**Antes:**
- Índices: 0-5
- Mostraba: "0. Pregunta"

**Después:**
- Índices: **1-6**
- Muestra: "1. Pregunta", "2. Pregunta", etc.
- Los datos se guardan como `{1: true, 2: false, ..., 6: false}`

#### FollowUpQuestionsSection.vue
**Antes:**
- Índices: `category_0`, `category_1`, ..., `category_12`
- Mostraba texto sin numeración

**Después:**
- Índices: **1-13** (numeración global consecutiva)
- Muestra: "1. Pregunta", "2. Pregunta", etc.
- Los datos se guardan como `{1: true, 2: false, ..., 13: false}`
- Mapeo interno: categoría + índice local → índice global

#### ConditionalQuestionsSection.vue
**Sin cambios de numeración**, pero ahora la estructura de datos enviada es:
- `customer_service: { condition: true/false, 65-68: valores }`
- `management: { condition: true/false, 69-72: valores }`

### 2. **Frontend - Take.vue**

#### Nueva Función: `transformToStandardizedStructure()`
Transforma los datos del formato interno de Vue al formato estandarizado antes de enviarlos:

```javascript
const transformToStandardizedStructure = () => {
    // Extrae preguntas generales (1-64)
    // Extrae customer_service con condition + 65-68
    // Extrae management con condition + 69-72
    // Extrae ats_s1 (1-6)
    // Extrae referencia_i (1-13)
    
    return {
        referencia_iii: {...},
        referencia_i: {...}
    };
};
```

**Lógica de Condicionales:**
- Si `condition === false`: Solo envía `{ condition: false }`, sin preguntas
- Si `condition === true`: Envía `{ condition: true, 65: 'A', 66: 'B', ... }`

### 3. **Backend - ProcessOnlineEvaluation.php**

#### Métodos Actualizados:

##### `extractReferenciaI()`
**Antes:** Esperaba `referencia_i.acontecimientos_traumaticos`  
**Después:** Extrae índices **1-13** directamente de `referencia_i`

##### `extractReferenciaIII()`
**Antes:** Esperaba `referencia_iii.general` (objeto anidado)  
**Después:** Extrae índices **1-64** directamente de `referencia_iii`

##### `extractConditionals()`
**Antes:** Esperaba `atencion_clientes.condition` y `supervision.condition`  
**Después:** Extrae `customer_service` y `management` con su estructura completa:
- `condition`: boolean
- Índices 65-68 o 69-72 (solo si condition es true)

##### `extractCitsatsS1()` (NUEVO)
Extrae los acontecimientos traumáticos (CITSATS) con índices **1-6** desde `referencia_iii.ats_s1`

#### Almacenamiento en Base de Datos:
Los datos se guardan en `PaperEvaluation`:
- `referencia_i_answers`: Índices 1-13
- `referencia_iii_answers`: Índices 1-64
- `referencia_iii_conditional`: customer_service + management con su estructura
- `citsats_s1`: Índices 1-6
- `demographic_data`: Datos demográficos completos

---

## Validaciones de Completitud

### Take.vue - Validaciones Actualizadas:

```javascript
const isAcontecimientosComplete = computed(() => {
    const traumaticAnswers = answers.value.referencia_iii.acontecimientos_traumaticos || {};
    // Valida que todas las preguntas 1-6 tengan respuesta
    return Object.keys(traumaticAnswers).length >= 6;
});

const isReferenciaIComplete = computed(() => {
    const referenciaIAnswers = answers.value.referencia_i || {};
    // Valida que todas las preguntas 1-13 tengan respuesta
    return Object.keys(referenciaIAnswers).length >= 13;
});

const isConditionalQuestionsComplete = computed(() => {
    // Valida que las condiciones estén respondidas
    // Si condition === true, valida que las preguntas estén completas
    // Si condition === false, se considera completo
});
```

---

## Compatibilidad con Evaluaciones en Papel (OCR)

La estructura estandarizada ahora coincide con la estructura de datos provenientes de OCR:

### Evaluación en Papel (raw_data):
```php
[
    'citsats_s1' => [1 => 'NO', 2 => 'NO', ..., 6 => 'SI'],
    'referencia_iii' => [1 => 'A', 2 => 'D', ..., 64 => 'C'],
    'customer_service_questions' => [65 => 'D', 66 => 'E', 67 => 'D', 68 => 'E'],
    'customer_service_conditional' => ['condition' => 'SI'],
    'management_questions' => [69 => '', 70 => '', ...],
    'conditional_management' => ['condition' => 'NO']
]
```

### Evaluación Online (después de estandarización):
```php
[
    'reference_iii' => [
        1 => 'A',
        ...
        64 => 'C',
        'customer_service' => ['condition' => true, 65 => 'D', 66 => 'E', ...],
        'management' => ['condition' => false],
        'ats_s1' => [1 => true, 2 => false, ..., 6 => true]
    ],
    'reference_i' => [1 => true, 2 => false, ..., 13 => false]
]
```

Ambas estructuras son procesadas de manera uniforme por los servicios de scoring y reportes.

---

## Pruebas Recomendadas

### 1. Prueba de Flujo Completo
- [ ] Completar evaluación online con todas las secciones
- [ ] Verificar que los datos se almacenen con numeración 1-6 (CITSATS)
- [ ] Verificar que los datos se almacenen con numeración 1-13 (Referencia I)
- [ ] Verificar que las condicionales se guarden correctamente

### 2. Prueba de Condicionales
- [ ] Responder "Sí" a atención a clientes → verificar que se guarden 65-68
- [ ] Responder "No" a supervisión → verificar que solo se guarde condition: false
- [ ] Cambiar respuesta de "Sí" a "No" → verificar que se limpien las preguntas

### 3. Prueba de Validación
- [ ] Intentar avanzar sin completar CITSATS → debe bloquear
- [ ] Intentar avanzar sin completar Referencia I → debe bloquear
- [ ] Intentar enviar con datos incompletos → debe mostrar error

### 4. Prueba de Compatibilidad
- [ ] Comparar estructura de datos online vs papel
- [ ] Verificar que los reportes funcionen igual para ambos orígenes
- [ ] Verificar scoring en ambos casos

---

## Archivos Modificados

### Frontend
- `resources/js/Components/Quiz/TraumaticEventsSection.vue`
- `resources/js/Components/Quiz/FollowUpQuestionsSection.vue`
- `resources/js/Pages/Quiz/Take.vue`

### Backend
- `app/Jobs/ProcessOnlineEvaluation.php`

---

## Notas Importantes

1. **Retrocompatibilidad**: Las evaluaciones existentes en la base de datos NO se ven afectadas. Solo aplica a nuevas evaluaciones.

2. **Numeración Consecutiva**: Las preguntas de Referencia I ahora tienen numeración consecutiva (1-13) en lugar de estar agrupadas por categorías con índices locales.

3. **CITSATS Independiente**: Los acontecimientos traumáticos (CITSATS) mantienen numeración independiente (1-6) dentro de `ats_s1`, no continúan después del 72.

4. **Condicionales Explícitas**: Las secciones condicionales ahora tienen un campo `condition` explícito, facilitando la validación y el procesamiento.

5. **Frontend Compilation**: Después de estos cambios, ejecutar `npm run build` es obligatorio para reflejar los cambios en producción.

---

## Próximos Pasos

1. ✅ Implementar estandarización en frontend (Completado)
2. ✅ Actualizar backend para procesar nueva estructura (Completado)
3. ⏳ Actualizar servicios de scoring si es necesario
4. ⏳ Actualizar reportes para usar nueva estructura
5. ⏳ Crear tests unitarios y de integración
6. ⏳ Documentar en API documentation
7. ⏳ Actualizar guías de usuario si es necesario

---

## Contacto
Para dudas o problemas con esta estandarización, contactar al equipo de desarrollo.
