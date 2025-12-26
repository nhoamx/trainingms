---
agent: agent
model: Claude Opus 4.5 (copilot)
tools: ['execute/getTerminalOutput', 'execute/runTests', 'read/readFile', 'edit', 'search', 'web/fetch', 'laravel-boost/*']
---

### 🎯 OBJETIVO DEL MVP

Implementar el flujo completo para NOM-002 con los siguientes pasos:

1. Habilitar la NOM-002 para una organización
2. Dar de alta **extintores (assets)**
3. Listar extintores para el administrador
4. Generar y descargar **QR por extintor**
5. (Opcional posterior) Preparar el sistema para futuras inspecciones

---

## 🧱 MODELO DE DATOS A USAR

### Tabla existente: `organizations`

* Se usará para habilitar la NOM-002 a la empresa
* Debemos crear la tabla `instruments`
 - id (UUID)
 - name = `string` (ej. `nom_002`) 
 - description = `string` (ej. `NOM-002 Extintores`)

* Debemos crear la nueva tabla pivote `organization_instruments` para relacionar organizacion con instrumentos
 - organization_id
 - instrument_id

* Esto habilitara permisos, menus, flujos, etc. (opciones futuras para organiaciones)

### Nueva tabla: `assets`

Cada extintor es un asset físico.

Campos mínimos:

* `id` (UUID)
* `organization_id`
* `asset_type` = `string` (ej. `extintor`)
* `serial_number` = `string` (ej. `EXT-12345`)
* `location` = `string` (ej. `Oficina Principal - Pasillo 2`)
* `capacity` = `string` (ej. `10 lbs`, `20 lbs`)
* `fire_class` = `string` (ej. `Clase A`, `Clase B`, `Clase C`)
* `created_at`
* `updated_at`

---

### Tabla existente: `evaluations`

**Aún NO se crean evaluaciones en este MVP**,
pero el sistema debe quedar listo para crear evaluaciones futuras con:

* `instrument_code = nom_002_extintores`
* `source = online`
* `subject = asset`

---

## 🔑 QR CODES

* Cada **extintor debe tener su propio QR**
* El QR **identifica únicamente al asset**
* El QR **NO contiene respuestas**
* El QR puede contener:

  * el `asset_id`
  * o una URL del tipo `/assets/{asset_id}/inspect`

El QR será:

* visible
* descargable
* imprimible
* reutilizable

---

## 🧭 FLUJO FUNCIONAL (PASO A PASO)

### 1️⃣ Asignar NOM-002 a la organización

* El sistema debe permitir marcar que una organización tiene habilitada la NOM-002
* Esto solo habilita el menú y funcionalidades relacionadas

---

### 2️⃣ Alta de extintores (assets)

* El administrador puede:
  * crear extintores
  * editar datos
  * eliminarlos si es necesario
* Cada extintor queda ligado a una organización

---

### 3️⃣ Listado de extintores

El administrador debe ver:

* lista de extintores
* ubicación
* datos principales
* botón **“Ver / Descargar QR”**

---

### 4️⃣ Generación de QR

* El sistema genera un QR por extintor
* El QR puede descargarse como:
  * PNG
* Se pueden descargar todos los QRs en un zip donde el nombre de cada archivo es el location.
* El QR se puede imprimir y pegar físicamente al extintor

---

## 🧠 REGLAS IMPORTANTES

* ❌ NO usar OMR
* ❌ NO usar escáner
* ❌ NO usar fiducial marks
* ❌ NO guardar respuestas en PDF

---

## 🏁 RESULTADO ESPERADO

Al finalizar esta implementación:

* Una organización puede usar NOM-002
* Puede registrar extintores
* Puede descargar QR por extintor
* El sistema queda listo para inspecciones futuras
* NO se rompe ninguna lógica existente
* El modelo es consistente con otras normas

---

## 🧠 NOTA FINAL

El concepto de **evaluation** representa

> *la ejecución de un instrumento normativo sobre un sujeto*,
> y el sujeto puede ser una persona o un activo físico.

---

