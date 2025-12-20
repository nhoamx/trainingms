---
agent: agent
---

Perfecto, te dejo **un resumen claro, compacto y accionable** de **todo lo que vamos a cambiar** (DB + arquitectura + flujo), pensando en **30k evaluaciones**, **OMR en Python**, **Laravel**, **NOM-035** y **tiempo realista (fin de semana)**.

---

# 🧱 1. Cambios clave en BASE DE DATOS

## ❌ Lo que dejamos atrás

* `paper_evaluations` como tabla principal ❌
* Guardar respuestas completas como JSON “crudo” para todo ❌
* Folio rígido que se rompe con 30k personas ❌

---

## ✅ Nuevo esquema base (simple y escalable)

### 🟢 `evaluations` (tabla principal)

**1 fila = 1 evaluación aplicada a 1 persona**

Contiene:

* Identidad
* Contexto
* Estado del procesamiento

Campos clave:

* `id (uuid)`
* `organization_id`
* `instrument_code` (`ref_i`, `ref_iii`, `cisneros`)
* `source` (`paper`, `online`)
* `folio_visible` (el que ve la persona)
* `processing_status`
* `processed_at`
* timestamps

📌 **Esta tabla reemplaza `paper_evaluations`**

---

### 🟢 `answers` (la más grande)

**Aquí vive el 80% de los datos**

**1 fila = 1 respuesta**

Campos:

* `evaluation_id`
* `question_number`
* `answer_raw` (`A`, `B`, `SI`, `NO`)
* `answer_numeric` (0–4, según NOM)
* `domain_code` (opcional)
* `created_at`

📌 Una evaluación de 74 preguntas → **74 filas aquí**

---

### 🟢 `demographic_data`

**Datos que se usan para segmentar y reportar**

Campos:

* `evaluation_id`
* `sex`
* `age`
* `department`
* `position`
* `seniority`
* `work_shift`
* etc.

✔️ Normalizados
✔️ Indexables
✔️ Consultables rápido

---

### 🟡 `evaluation_parts` (solo si aplica)

Solo si un instrumento tiene **secciones condicionales**
(ej. Referencia III → servicio al cliente / mando)

Campos:

* `evaluation_id`
* `part_code`
* `condition_result` (`SI / NO`)
* `applies`

📌 **No es obligatoria** si quieres salir rápido
📌 Se puede agregar después

---

### 🟡 `evaluation_configs`

Configuración por tipo de evaluación (NOM-035, futuras normas)

Guarda:

* Escalas de puntaje
* Dominios
* Límites de riesgo
* Mapeo respuesta → valor

📌 Esto evita hardcode
📌 Te prepara para futuras normas

---

# 🔢 2. Folios (PUNTO CRÍTICO)

## ❌ Antes

```
02 010 0001
(tipo)(empresa)(persona)
```

🚨 Se rompe con 30k personas

---

## ✅ Ahora (mejor práctica)

### 🔹 Folio visible (para papel)

Ejemplo:

```
010-7E2K-00421
```

* Legible
* Único
* Cabe en QR / barcode
* NO es PK

### 🔹 Identificador real

* `UUID` interno
* Nunca lo ve el usuario

📌 El folio **solo identifica**, no calcula nada

---

# 📄 3. Evaluaciones presenciales (PAPEL)

* La hoja tiene:

  * **QR o barcode**
  * Contiene: `evaluation_id` o `folio_visible`

✔️ Python OMR **sí puede leer QR/barcode desde PDF**
✔️ Es común y estable
✔️ Usa `pyzbar`, `opencv`, etc.

---

# 🧠 4. OMR en PYTHON

## Decisión final

👉 **Python como servicio API (FastAPI)**
❌ No ejecutar Python desde Laravel

### Flujo:

```
Laravel Job
   ↓
API Python
   ↓
JSON respuestas
   ↓
Laravel guarda:
   - answers
   - demographic_data
```

✔️ Escala
✔️ Aislado
✔️ Seguro
✔️ Rápido

---

# 📊 5. Reportes (WEB + WORD)

## Web (directivos)

* Gráficas por:

  * Dominio
  * Nivel de riesgo
  * Sexo / área / puesto
* Datos agregados (NO respuestas individuales)

## Word

* Plantilla DOCX
* Tablas y gráficas NOM-035
* Datos ya calculados (no en crudo)

📌 Todo sale de:

* `answers`
* `demographic_data`
* `evaluations`

---

# 🏗️ 6. Qué NO haremos ahora (por tiempo)

❌ Microservicios complejos
❌ Event sourcing
❌ Data warehouse
❌ Postgres (MySQL está bien)

---

# ⏱️ 7. Qué SÍ se puede dejar listo este fin

✔️ Nuevo schema base
✔️ API OMR funcional
✔️ Inserción de respuestas normalizadas
✔️ Pantalla demo para directivos
✔️ Flujo papel + online unificado

---

# 🎯 RESUMEN FINAL (1 línea)

👉 **Normalizamos respuestas, separamos demografía, usamos UUID + folio visible, Python por API y dejamos la DB lista para 30k+ evaluaciones y futuras normas.**

---

Si quieres, el siguiente paso puede ser:

* 📐 **Diagrama ER**
* 🧬 **Migraciones Laravel**
* 🔌 **Contrato JSON Python ↔ Laravel**
* 📄 **Estructura del Word NOM-035**

Dime cuál seguimos y le damos.
