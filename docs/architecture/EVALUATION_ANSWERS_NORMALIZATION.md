# Plan: Normalización de Respuestas de Evaluación

## Contexto y Diagnóstico

### El problema

`PaperEvaluation` almacena respuestas en 6 columnas JSON (`referencia_i_answers`,
`referencia_iii_answers`, `referencia_iii_conditional`, `cisneros_answers`,
`likert_answers`, `citsats_s1`). Esto causa:

- **Incompletud no detectable a nivel SQL**: saber si el usuario 42 no respondió la
  pregunta 15 requiere cargar el modelo en PHP y hacer loops.
- **Queries analíticos imposibles**: no puedes GROUP BY ni WHERE por valor de pregunta.
- **Duplicación de estrategia**: `online_answers` ya es una tabla normalizada, pero
  sin FK a `paper_evaluations`. Son dos mundos separados para el mismo dato.
- **Código defensivo acumulado**: cada servicio tiene `?? []` y fallbacks a `raw_data`
  porque la escritura no garantiza estructura completa.

### Datos actuales (producción)

| Fuente   | Registros |
|----------|-----------|
| Paper    | 8,548     |
| Online   | 2,433     |
| Hybrid   | 3         |
| **Total**| **10,984**|

| Instrumento      | Registros con datos | Preguntas | Filas estimadas |
|------------------|---------------------|-----------|-----------------|
| Likert           | 7,125               | 23        | ~163,875        |
| Referencia III   | 1,488               | 72        | ~107,136        |
| Referencia I     | 371                 | 14        | ~5,194          |
| Cisneros         | 45                  | 44        | ~1,980          |
| **Total**        |                     |           | **~278,000**    |

---

## Arquitectura Objetivo

### Tabla `evaluation_answers`

```
evaluation_answers
  id                   bigint unsigned  PK AUTO_INCREMENT
  paper_evaluation_id  char(36)         FK → paper_evaluations.id  NOT NULL
  instrument           enum             (referencia_i, referencia_iii, cisneros, likert)
  question_key         varchar(10)      "1", "pregunta_1", "65"  NOT NULL
  answer_value         varchar(255)     nullable
  answer_meta          json             nullable  ← cisneros {persona, frecuencia}, condicionales
  created_at           timestamp
  updated_at           timestamp

  UNIQUE (paper_evaluation_id, instrument, question_key)
  INDEX  (paper_evaluation_id, instrument)
  INDEX  (question_key, answer_value)          ← queries analíticos
```

### Por qué no reutilizar `online_answers`

`online_answers` tiene `folio` + `quiz_id` como identificadores, no
`paper_evaluation_id`. Refactorizarla rompe código existente. La estrategia es:

1. Crear `evaluation_answers` con FK explícita a `paper_evaluations`.
2. En `ProcessQuizSubmission`, escribir en **ambas** tablas (backward compat).
3. Una vez validado, deprecar `online_answers`.

---

## Plan de Ejecución por Fases

### Fase 1 — Tabla + Backfill (HOY)

**Objetivo**: tener `evaluation_answers` poblada con datos existentes, sin tocar
nada en producción.

#### 1.1 Crear migración

```bash
php artisan make:migration create_evaluation_answers_table --no-interaction
```

Schema:
- `id` bigint PK
- `paper_evaluation_id` uuid FK → paper_evaluations (cascade delete)
- `instrument` enum(`referencia_i`, `referencia_iii`, `cisneros`, `likert`)
- `question_key` varchar(10) not null
- `answer_value` varchar(255) nullable
- `answer_meta` json nullable
- timestamps
- UNIQUE(`paper_evaluation_id`, `instrument`, `question_key`)
- INDEX(`paper_evaluation_id`, `instrument`)
- INDEX(`question_key`, `answer_value`)

#### 1.2 Crear modelo + relación

```bash
php artisan make:model EvaluationAnswer --no-interaction
```

Relación en `PaperEvaluation`:
```php
public function answers(): HasMany
{
    return $this->hasMany(EvaluationAnswer::class);
}
```

#### 1.3 Job de backfill

```bash
php artisan make:job BackfillEvaluationAnswers --no-interaction
```

El job itera `PaperEvaluation::query()->chunk(200)` y para cada registro llama a un
extractor que devuelve un array de filas listas para `upsert()` en `evaluation_answers`.
Errores por registro se loguean (con folio) pero no abortan el chunk.

##### Lógica de extracción completa por instrumento y fuente

```
Para cada PaperEvaluation $e:
  rows = []

  ─── REFERENCIA I ────────────────────────────────────────────────────────────
  if $e->referencia_i_answers tiene N claves (N = 14 normal, o 6–14 legacy):
      → usa $e->referencia_i_answers directamente
      → answer_value = (bool) ? "true" : "false"  (online)
                     = "SI"/"NO"                   (paper)
  elseif $e->source == 'online' AND raw_data.source_metadata.quiz_type == 'reducido':
      → las 6 respuestas ATS están solo en raw_data.referencia_i.acontecimientos_traumaticos
      → answer_value = "true"/"false"  (booleans del JSON)
      → nota: usuario respondió NO a todo, sin cuestionario PTSD completo
  end

  ─── REFERENCIA III (core 1–64) ──────────────────────────────────────────────
  if $e->referencia_iii_answers:
      → itera claves "1"–"64"
      → answer_value = "A"/"B"/"C"/"D"/"E" o null (pregunta sin respuesta)

  ─── REFERENCIA III (condicionales 65–72) ───────────────────────────────────
  if $e->referencia_iii_conditional:
      paper format: {"customer_service": {"condition": "SI"/"NO", "questions": {"65":…}},
                     "management":       {"condition": "SI"/"NO", "questions": {"69":…}}}
      online format: ya está aplanado en referencia_iii_answers claves 65–72
                     + condition_customer_service / condition_management booleans
      → para cada sección, guardar el condition como question_key="condition_cs"/"condition_mgmt"
      → guardar preguntas 65–68 y 69–72 con answer_meta = {section: "customer_service"/"management"}

  ─── CISNEROS ────────────────────────────────────────────────────────────────
  if $e->cisneros_answers:
      → itera claves "1"–"43"
      → answer_value = (string) item["frecuencia"]   (0–6)
      → answer_meta  = {persona: "A"/"B"/"C", frecuencia: 0–6}
      → clave "44" si existe = pregunta resumen yes/no

  ─── LIKERT ──────────────────────────────────────────────────────────────────
  if $e->likert_answers:
      → itera likert_answers["questions"] claves "1"–"23"
      → answer_value = "A"/"B"/"C"/"D"/"E"

  upsert(rows, uniqueBy: [paper_evaluation_id, instrument, question_key])
```

##### Tabla resumen de extracción

| Instrumento | Fuente column | question_key | answer_value | answer_meta |
|-------------|---------------|--------------|--------------|-------------|
| `referencia_i` | `referencia_i_answers["N"]` | `"1"`–`"14"` | `"true"`/`"false"` (online) · `"SI"`/`"NO"` (paper) | null |
| `referencia_i` *(reducido NO-todo)* | `raw_data.referencia_i.acontecimientos_traumaticos["N"]` | `"1"`–`"6"` | `"true"`/`"false"` | null |
| `referencia_iii` | `referencia_iii_answers["N"]` | `"1"`–`"64"` | `"A"`–`"E"` · null | null |
| `referencia_iii` *(cond.)* | `referencia_iii_conditional` | `"65"`–`"72"` | `"A"`–`"E"` · null | `{section, condition}` |
| `referencia_iii` *(cond. condition)* | `referencia_iii_conditional` | `"condition_cs"` / `"condition_mgmt"` | `"SI"`/`"NO"` · bool | null |
| `cisneros` | `cisneros_answers["N"]` | `"1"`–`"44"` | frecuencia `"0"`–`"6"` | `{persona, frecuencia}` |
| `likert` | `likert_answers.questions["N"]` | `"1"`–`"23"` | `"A"`–`"E"` | null |

#### 1.4 Tests

- Unit test para el job de backfill.
- Verificar que el count de filas en `evaluation_answers` coincide con los
  totales esperados (~278k).

---

### Fase 2 — Escribir en dual mode (DÍA 2-3)

**Objetivo**: toda nueva evaluación escribe en JSON Y en `evaluation_answers`
simultáneamente. Sin romper servicios existentes.

#### 2.1 Modificar `ProcessPaperEvaluation` job

Después de hacer el `updateOrCreate` de `PaperEvaluation`, llamar un método
`syncAnswers()` que hace el upsert en `evaluation_answers`.

#### 2.2 Modificar `ProcessQuizSubmission` job

Después de crear las filas en `online_answers`, también escribir en
`evaluation_answers` con el `paper_evaluation_id` correspondiente.

#### 2.3 Añadir Observer a `PaperEvaluation`

Si `referencia_iii_answers` / `likert_answers` cambia → re-sync en
`evaluation_answers`.

---

### Fase 3 — Queries de completitud (DÍA 3-4)

**Objetivo**: poder responder "¿quién no respondió?" con SQL, sin PHP loops.

#### Queries clave que se habilitan

```sql
-- Evaluaciones que no respondieron la pregunta 15 de Referencia III
SELECT pe.personal_folio, pe.evaluee_name, pe.organization_id
FROM paper_evaluations pe
LEFT JOIN evaluation_answers ea
    ON ea.paper_evaluation_id = pe.id
    AND ea.instrument = 'referencia_iii'
    AND ea.question_key = '15'
WHERE ea.id IS NULL
  AND pe.organization_id = ?;

-- Conteo de respuestas por WorkCenter (completitud)
SELECT pe.work_center_id,
       COUNT(DISTINCT pe.id) as evaluaciones,
       COUNT(ea.id) as total_respuestas,
       ROUND(COUNT(ea.id) / COUNT(DISTINCT pe.id), 1) as promedio_por_evaluacion
FROM paper_evaluations pe
LEFT JOIN evaluation_answers ea ON ea.paper_evaluation_id = pe.id
WHERE pe.organization_id = ?
GROUP BY pe.work_center_id;

-- Distribución de respuestas en pregunta específica (estadísticas)
SELECT ea.answer_value, COUNT(*) as frecuencia
FROM evaluation_answers ea
JOIN paper_evaluations pe ON pe.id = ea.paper_evaluation_id
WHERE pe.work_center_id = ?
  AND ea.instrument = 'referencia_iii'
  AND ea.question_key = '5'
GROUP BY ea.answer_value;
```

#### 3.1 Servicio `EvaluationCompletenessService`

Nuevo servicio que expone:
- `getMissingAnswers(PaperEvaluation $e): array` → preguntas sin respuesta
- `getCompletenessForOrganization(Organization $o): Collection` → por evaluación
- `getCompletenessForWorkCenter(WorkCenter $wc): array` → estadísticas agregadas
- `getUnansweredByInstrument(Organization $o, string $instrument): Collection`

---

### Fase 4 — Migrar servicios de scoring (SEMANA 2)

**Objetivo**: los servicios de scoring leen de `evaluation_answers`, no de JSON.

Servicios a migrar (en orden de impacto):

1. `LikertScoreService` — 7,125 registros, mayor impacto
2. `Nom035DomainCalculationService` — referencia_iii, central para reportes
3. `PaperEvaluationScoreService` — scoring individual
4. `ExecutiveReportService` — cisneros, menor volumen

**Estrategia por servicio:**
- Añadir método nuevo que acepta `Collection` de `EvaluationAnswer`.
- Mantener método viejo con `@deprecated`.
- Migrar llamadas una por una, validando que resultados coinciden.
- Eliminar método viejo cuando todas las llamadas estén migradas.

---

### Fase 5 — Deprecar JSON columns (SEMANA 3+)

Una vez que todos los servicios leen de `evaluation_answers`:

1. Hacer `raw_data` el único JSON que se conserva (como audit log).
2. Migrar columnas JSON a nullable con default null.
3. Dejar de escribir en columnas JSON en nuevas evaluaciones.
4. En iteración futura, drop de columnas (con backup previo).

> ⚠️ No hacer drop sin al menos 2 semanas de monitoreo en producción.

---

## Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Backfill incompleto (datos malformados en JSON) | Media | Alto | Log de errores por folio, no abortar job completo |
| Diferencia de resultados entre JSON y tabla normalizada | Media | Alto | Tests de paridad: calcular score por ambas vías, comparar |
| Performance en queries analíticos | Baja | Medio | Índice compuesto ya contemplado en schema |
| `online_answers` queda desincronizada | Baja | Bajo | Mantener escritura dual hasta deprecación formal |
| Observer en PaperEvaluation genera re-sync innecesarios | Baja | Bajo | Comparar hash antes de re-sync |

---

## Métricas de Éxito

- [ ] `evaluation_answers` tiene ≥ 278k filas después del backfill
- [ ] Queries de completitud retornan en < 100ms para organizations con 1k evaluaciones
- [ ] `EvaluationCompletenessService` pasa tests de todos los instrumentos
- [ ] Los scores calculados desde tabla normalizada coinciden 100% con los calculados desde JSON
- [ ] Zero errores de backfill en producción (folio exceptions logueadas y conocidas)

---

## Dependencias Entre Cambios

```
Fase 1 (tabla + backfill)
    └── prerequisito para → Fase 2 (dual write)
    └── prerequisito para → Fase 3 (completeness service)
                                └── prerequisito para → Nuevas pantallas/reportes
Fase 2 + Fase 3
    └── prerequisito para → Fase 4 (migrar servicios)
                                └── prerequisito para → Fase 5 (deprecar JSON)
```

Fase 1 y Fase 3 se pueden iniciar en paralelo una vez la migración y el modelo
existan.

---

---

## Análisis de Formatos por Fuente y Organización

> Auditado el 2026-04-10 sobre registros de MAS BODEGA Y LOGISTICA, 7-SERVICIOS DE
> LOGISTICA y MAS BAKERIES.

### Fuente: `online`

El `raw_data` online es el snapshot completo del quiz enviado por el usuario,
normalizado por `ProcessQuizSubmission`. Estructura consistente en todas las organizaciones:

```json
{
  "source": "online",
  "file_uploads": { "ine_frente": null, "ine_reverso": null },
  "referencia_i": {
    "acontecimientos_traumaticos": {
      "1": false, "2": false, ..., "N": true
    }
  },
  "referencia_v": {
    "edad": "24", "sexo": "Masculino", "estado_civil": "Soltero",
    "nivel_estudios": "Licenciatura - Terminada",
    "datos_laborales": {
      "tipo_puesto": "Operativo", "tipo_jornada": "6_20",
      "tipo_personal": "Sindicalizado", "rotacion_turnos": "No",
      "ocupacion_puesto": "VERIFICADOR",
      "tipo_contratacion": "Tiempo indeterminado",
      "departamento_seccion_area": "DISTRIBUCIÓN",
      "experiencia": {
        "tiempo_puesto_actual": "Entre 6 meses y 1 año",
        "tiempo_experiencia_laboral": "Entre 1 a 4 años"
      }
    }
  },
  "custom_fields": [],
  "referencia_iii": {
    "1": "B", "2": "C", ..., "64": "E",
    "65": "D", "66": "D", "67": "D", "68": "E",
    "69": null, "70": null, "71": null, "72": null,
    "condition_customer_service": true,
    "condition_management": false
  },
  "source_metadata": {
    "quiz_id": 43,
    "quiz_name": "PIEDRAS NEGRAS",
    "quiz_type": "normal",
    "instrument": "referencia_iii",
    "user_agent": "...",
    "submitted_at": "2026-02-25T11:00:02-06:00",
    "submission_ip": "201.98.35.137",
    "organization_info": { "ciudad": "", "estado": "", "nombre_comercial": "", "division_sucursal": null }
  }
}
```

#### Variaciones online confirmadas

| Campo | Normal | Reducido |
|-------|--------|---------|
| `referencia_iii` | Presente (64-72 preguntas + condition flags) | **Ausente** |
| `referencia_i.acontecimientos_traumaticos` | Claves `"1"`–`"14"` | Claves `"1"`–`"6"` (screening) |
| `source_metadata.quiz_type` | `"normal"` | `"reducido"` |
| `raw_data` top keys | source, file_uploads, referencia_i, referencia_v, custom_fields, referencia_iii, source_metadata | source, file_uploads, referencia_i, referencia_v, custom_fields, source_metadata |

**Tipos de valor en `referencia_i`:**
- Valores: `boolean` (`true`/`false`) — **no strings**

#### Flujo especial del quiz reducido (⚠️ bug de almacenamiento activo)

El reducido tiene **dos fases** de preguntas:

1. **Screening ATS** (6 preguntas): se muestran siempre, guardadas en
   `raw_data.referencia_i.acontecimientos_traumaticos["1"–"6"]`
2. **PTSD completo** (14 preguntas): se muestra **solo si el usuario respondió SÍ a
   alguna del screening**, guardado en `referencia_i_answers["1"–"14"]`

**El bug**: cuando el usuario responde NO a todo el screening, el job
`ProcessQuizSubmission` deja `referencia_i_answers = NULL` en lugar de guardar las
6 respuestas negativas. Esas respuestas solo existen en `raw_data`.

Conteo en producción (auditado 2026-04-10):

| Caso | Registros | `referencia_i_answers` |
|------|-----------|------------------------|
| Total reducido | 403 | — |
| Respondió SÍ a algún ATS → PTSD completo mostrado | 57 | 14 claves (`"1"`–`"14"`) ✅ |
| Respondió NO a todo → solo screening | 346 | `NULL` ❌ (solo en `raw_data`) |

**Implicación para el backfill** (ver sección de backfill más abajo): el job debe
detectar este caso leyendo desde `raw_data` como fallback.

**Tipos de valor en `referencia_iii`:**
- Preguntas 1–64 (core): `"A"` / `"B"` / `"C"` / `"D"` / `"E"` (strings)
- Preguntas 65–72 (condicionales): mismo rango de letras o `null` si no aplica
- `condition_customer_service` / `condition_management`: `boolean`
- Cantidad de claves observada: 64 (sin condicionales), 69 (con una sección), 74 (con ambas)

**Anomalías detectadas:**
- 3 registros online con solo 2, 11 o 12 preguntas en `referencia_i_answers`
  (folio `01011000007` con 2 claves — registro incompleto/abandonado)

---

### Fuente: `paper`

El `raw_data` paper es el JSON crudo del servicio OCR Docker. Estructura consistente
en todas las organizaciones (MAS BODEGA, 7-SERVICIOS, MAS BAKERIES):

```json
{
  "1": {
    "row": 0,
    "block": "answers_block_1",
    "value": "NO",
    "margin": 0.6875,
    "ambiguous": false,
    "confidence": 0.8524,
    "mapping_section": "gri_binary",
    "selected_column": "C2"
  },
  "2": { ... },
  ...
  "80": { "value": "NO", "ambiguous": false, ... }
}
```

- **Claves**: string integers `"1"` – `"80"` (siempre 80 slots, incluyendo secciones
  condicionales y sus condition-flags embebidos)
- **`value`**: respuesta detectada — `"SI"`/`"NO"` para ref_i, `"A"`–`"E"` para ref_iii
- **`ambiguous`**: flag del OCR; puede indicar bubble dudosa
- **`confidence`**: score del detector (0.0–1.0)
- El job extrae `value` de cada slot y lo copia a las columnas normalizadas

**Distribución de slots 65–80 (ref_iii paper):**

| Slot(s) | Uso |
|---------|-----|
| 65      | Condition customer_service (`"SI"`/`"NO"`) |
| 66–68   | Preguntas condicionales customer_service |
| 69      | Condition management (`"SI"`/`"NO"`) |
| 70–72   | Preguntas condicionales management |
| 73–80   | Reservados / padding del bubble sheet; siempre `"NO"` |

**Para ref_i paper**, el `raw_data` también es el mismo flat dict de 13 slots (un
formulario legacy con 13 preguntas, no 14) para CORPORACION INDUSTRIAL DE CALIZA y
12 slots para Empresa DEMO. MAS BODEGA / 7-SERVICIOS / MAS BAKERIES usan 14 slots.

---

### Fuente: `paper` — Likert

El `raw_data` del Likert paper **tiene estructura diferente** al ref_iii/ref_i:

```json
{
  "areas":        1,
  "turno":        "segundo",
  "genero":       "masculino",
  "puestos":      1,
  "tipo_contrato": "directo",
  "likert": {
    "1": "B", "2": "A", "3": "C", ..., "23": "D"
  }
}
```

- Los datos demográficos van **al nivel raíz**, no dentro de un sub-objeto
- Las preguntas van bajo la clave `likert` (no `questions` como en `likert_answers`)
- `areas` y `puestos` son IDs enteros (not names)
- El job los renombra al escribirlos en `likert_answers` column:
  `{"questions": {...}, "genero": ..., "turno": ..., "areas": ..., "puestos": ..., "tipo_contrato": ...}`

---

### Resumen de diferencias clave para el backfill

| Columna destino | Fuente paper (raw_data path) | Fuente online (raw_data path) |
|-----------------|------------------------------|-------------------------------|
| `referencia_i_answers.N` | `raw_data["N"]["value"]` (string "SI"/"NO") | `raw_data["referencia_i"]["acontecimientos_traumaticos"]["N"]` (boolean) |
| `referencia_iii_answers.N` (1–64) | `raw_data["N"]["value"]` | `raw_data["referencia_iii"]["N"]` |
| `referencia_iii_conditional` (65–72) | `raw_data["65"]["value"]`…`raw_data["72"]["value"]` | `raw_data["referencia_iii"]["65"]`…`["72"]` + condition flags |
| `likert_answers.questions.N` | `raw_data["likert"]["N"]` | N/A (no online Likert) |
| `likert_answers.genero` | `raw_data["genero"]` | N/A |

> ⚠️ **Implicación directa para el job de backfill**: el job debe bifurcar la lógica
> de extracción según `$evaluation->source` antes de intentar leer cualquier clave.
> No hay un formato universal.

---

## Lo que se puede despachar HOY

En orden:

1. `php artisan migrate` con la nueva tabla `evaluation_answers`
2. `EvaluationAnswer` model + relación en `PaperEvaluation`
3. `BackfillEvaluationAnswers` job → dispatch y validar conteo
4. Tests unitarios del backfill
5. `EvaluationCompletenessService` con los queries básicos
6. Conectar en un endpoint existente para validar en UI

> Una vez que el backfill corre sin errores y los conteos son correctos,
> el sistema ya puede responder "¿quién no respondió?" con SQL real.
> Todo lo demás (dual write, migrar servicios) puede hacerse gradualmente
> sin urgencia porque los datos ya están en la nueva estructura.
