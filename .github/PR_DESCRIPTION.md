# Extended 9-Digit Folio Format for OMR Templates

## 🎯 Objetivos

Implementar un formato extendido de folio de 9 dígitos para las plantillas OMR, permitiendo identificar el tipo de documento, la organización y la persona durante el proceso de escaneo.

## 📋 Cambios Implementados

### 1. **Formato Extendido de Folio (9 dígitos)**

El nuevo formato de folio se compone de:
- **Tipo de Template (2 dígitos)**: 
  - `01` = Referencia I
  - `02` = Referencia III
  - `03` = Referencia V
  - `04` = Escala Cisneros
- **Organización (3 dígitos)**: Del campo `folio_organization` (ejemplo: `001`, `002`, `123`)
- **Persona (4 dígitos)**: Folio interno de la persona (ejemplo: `0001`, `0002`, `0003`)

**Ejemplo**: Para Referencia I (01) + Organización 123 + Persona 0001 = `010120001`

### 2. **Visualización de Dígitos en Templates**

- Actualización de las 4 plantillas OMR para **mostrar los dígitos del folio** en los recuadros superiores
- Los dígitos ahora se muestran encima de las burbujas correspondientes para facilitar el llenado manual
- Plantillas actualizadas:
  - `resources/views/omr/referencia-i.blade.php`
  - `resources/views/omr/referencia-iii.blade.php`
  - `resources/views/omr/referencia-v.blade.php`
  - `resources/views/omr/escala-cisneros.blade.php`

### 3. **Refactorización de Generación de PDF**

**Problema anterior**: Se usaban query strings largas que podían fallar con muchos folios.

**Solución implementada**:
- Nuevo endpoint POST `/omr/generate-pdf` (requiere autenticación)
- Validación mediante `StoreOMRPdfRequest` con reglas claras
- Parámetros enviados via POST body en lugar de query string
- Soporte para generar todos los folios de un lote o folios específicos

### 4. **Actualización del Frontend (Folios.vue)**

- Refactorización del método `generatePdf()` para usar Inertia form POST
- Eliminación de construcción de query strings largas
- Mejor manejo de errores con callbacks `onSuccess` y `onError`

### 5. **Testing Completo**

Nueva suite de tests `OMRPdfGenerationTest.php` con 9 tests:
- ✅ Validación de autenticación
- ✅ Validación de campos requeridos
- ✅ Validación de tipo de guía
- ✅ Validación de existencia de organización
- ✅ Generación con folios específicos
- ✅ Generación con todos los folios del lote
- ✅ Generación para cada tipo de plantilla (I, III, V, Cisneros)
- ✅ Verificación de formato extendido de folio

### 6. **Factory para Testing**

- Creado `FolioBatchFactory` para facilitar tests
- Métodos de estado: `presencial()` y `online()`
- Generación automática de rangos de folios coherentes

## 🔧 Cambios Técnicos

### Backend
- **Nuevo Request**: `app/Http/Requests/StoreOMRPdfRequest.php`
- **Controlador**: Métodos refactorizados en `OMRController.php`
  - `generatePdf()` - Nuevo método principal para generación POST
  - `generateExtendedFolio()` - Helper para generar formato de 9 dígitos
  - `getGuideData()` - Obtiene configuración de cada tipo de guía
  - `flattenQuestions()` - Aplana arrays de preguntas
- **Rutas**: Nueva ruta POST en `routes/web.php`

### Frontend
- **Componente**: `resources/js/Pages/Organizations/components/Folios.vue`
  - Cambio de window.open() con query string a Inertia form POST

### Testing
- **Factory**: `database/factories/FolioBatchFactory.php`
- **Tests**: `tests/Feature/OMRPdfGenerationTest.php` (9 tests, 28 assertions)

## 📊 Resultados de Tests

```bash
Tests:    9 passed (28 assertions)
Duration: 18.78s
```

## 🔄 Breaking Changes

⚠️ **BREAKING CHANGE**: La generación de PDF ahora requiere:
- Request POST en lugar de GET
- Parámetros `organization_id` y `folio_batch_id` en el body
- Autenticación obligatoria

Las vistas individuales de OMR siguen funcionando con GET para previsualización.

## 📝 Convenciones Seguidas

- ✅ Conventional Commits
- ✅ Database Transactions en tests
- ✅ Laravel 11 conventions (método `casts()`, constructor promotion)
- ✅ Vue 3 Composition API
- ✅ Laravel Pint formatting aplicado

## 🚀 Próximos Pasos

1. Revisar y aprobar este PR
2. Merge a develop
3. Actualizar el sistema de procesamiento OCR para reconocer el formato de 9 dígitos
4. Documentar el nuevo formato en la guía de usuario

## 📸 Ejemplo de Formato

**Antes**: `0001` (4 dígitos - solo persona)
**Después**: `010120001` (9 dígitos - tipo + org + persona)

Esto permite al sistema OCR identificar automáticamente:
- Qué tipo de evaluación es (I, III, V, o Cisneros)
- A qué organización pertenece
- Qué persona específica es

## 🎓 Referencias

- NOM-035-STPS-2018: Regulación mexicana de factores de riesgo psicosocial
- Prompt original: `.github/prompts/omr-folio-fill.prompt.md`
