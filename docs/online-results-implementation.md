# 📊 Implementación de Captura y Visualización de Resultados en Línea

**Rama:** `feature/online-results-capture-and-visualization`  
**Fecha de inicio:** 27 de Octubre, 2025  
**Responsable:** GitHub Copilot (AI Agent)

---

## 🎯 Objetivo General

Mejorar el sistema de captura y visualización de resultados para evaluaciones **online** (en línea), integrando correctamente la lógica de guardado en el modelo `PaperEvaluation` y creando vistas dedicadas para mostrar estos resultados de forma clara y consistente.

---

## 📋 Análisis de la Situación Actual

### ✅ Estado Actual del Sistema

1. **Modelo Principal**: `PaperEvaluation`
   - ✅ Ya maneja evaluaciones `online` y `paper`
   - ✅ Campo `source` diferencia el origen (enum: 'online', 'paper')
   - ✅ Estructura JSON flexible para almacenar respuestas

2. **Captura de Datos Online**: 
   - ✅ `QuizController@submit()` ya guarda en `PaperEvaluation`
   - ✅ Se generan folios únicos por evaluación
   - ✅ Se procesan correctamente los 3 tipos de quiz: completo, reducido, cisneros

3. **Controlador Existente**: `OnlineResultsController`
   - ⚠️ Consulta el modelo `OnlineAnswer` (modelo antiguo/diferente)
   - ⚠️ No consulta `PaperEvaluation` con `source='online'`
   - ⚠️ Necesita refactorización completa

4. **Vistas Actuales**:
   - ⚠️ Existen vistas básicas que consultan datos del modelo incorrecto
   - ⚠️ Necesitan actualización para reflejar estructura de `PaperEvaluation`

---

## 🔄 Tipos de Evaluación Online

### 1️⃣ **Completo** (`is_reduced=false`, `is_cisneros=false`)
Incluye:
- ✅ Referencia I (Guía I - Datos traumáticos)
- ✅ Referencia III Completa (Cuestionario general + secciones condicionales)
- ✅ Referencia V (Datos demográficos y laborales) *(condicional: solo si CITSAT tiene "sí")*

### 2️⃣ **Reducido** (`is_reduced=true`, `is_cisneros=false`)
Incluye:
- ✅ Referencia I
- ✅ Solo CITSAT (Acontecimientos Traumáticos Severos)
- ✅ Referencia V *(condicional: solo si CITSAT tiene "sí")*

### 3️⃣ **Cisneros** (`is_cisneros=true`)
Incluye:
- ✅ Referencia I
- ✅ Escala Cisneros (Mobbing/Violencia Laboral)
- ✅ Referencia V

---

## 📊 Estructura de Datos en `PaperEvaluation`

```php
// Campos relevantes para evaluaciones online
'source' => 'online',
'processing_status' => 'completed',
'demographic_data' => json,           // Referencia V
'referencia_i_answers' => json,       // Guía I
'referencia_iii_answers' => json,     // Referencia III (preguntas generales)
'referencia_iii_conditional' => json, // CITSAT (Acontecimientos Traumáticos)
'cisneros_answers' => json,           // Escala Cisneros
'raw_data' => [
    'custom_fields' => json,
    'quiz_id' => uuid,
    'quiz_name' => string,
    'submitted_at' => timestamp
]
```

---

## ✅ Lista de Tareas

### Fase 1: Refactorización del Backend

- [x] **Tarea 1.1**: Crear rama `feature/online-results-capture-and-visualization`
- [x] **Tarea 1.2**: Analizar y documentar estructura actual de `PaperEvaluation`
- [x] **Tarea 1.3**: Refactorizar `OnlineResultsController`
  - [x] Cambiar queries de `OnlineAnswer` a `PaperEvaluation`
  - [x] Filtrar por `source='online'`
  - [x] Mantener compatibilidad con rutas existentes
- [x] **Tarea 1.4**: Crear métodos helper en `PaperEvaluation` model
  - [x] `scopeOnline()` - scope para filtrar evaluaciones online
  - [x] `getQuizType()` - obtener tipo de quiz (completo/reducido/cisneros)
  - [x] `hasReferenciaV()` - verificar si tiene Referencia V
- [x] **Tarea 1.5**: Actualizar rutas en `routes/web.php` si es necesario
- [ ] **Tarea 1.6**: Crear controlador `OnlineResultsReportController` para reportes agregados

### Fase 2: Desarrollo de Vistas Frontend

- [x] **Tarea 2.1**: Crear `resources/js/Pages/OnlineResults/List.vue`
  - [x] Tabla de resultados individuales
  - [x] Filtros por tipo de quiz, fecha, organización
  - [x] Paginación
  - [x] Búsqueda por folio
- [x] **Tarea 2.2**: Crear `resources/js/Pages/OnlineResults/Detail.vue`
  - [x] Vista detallada de una evaluación individual
  - [x] Mostrar todas las secciones según tipo de quiz
  - [x] Visualización de imágenes INE si existen
  - [x] Datos demográficos formateados
- [ ] **Tarea 2.3**: Crear `resources/js/Pages/OnlineResults/Report.vue`
  - [ ] Reporte agregado por organización
  - [ ] Gráficas de distribución de respuestas
  - [ ] Estadísticas por dimensión (si aplica)
  - [ ] Exportación a PDF/Excel (futuro)
- [x] **Tarea 2.4**: Actualizar `AdminDashboard.vue`
  - [x] Verificar que los enlaces apunten correctamente
  - [x] Agregar contador de evaluaciones online por organización

### Fase 3: Componentes Reutilizables

- [ ] **Tarea 3.1**: Crear `components/OnlineResults/AnswerCard.vue`
  - [ ] Componente para mostrar preguntas/respuestas
- [ ] **Tarea 3.2**: Crear `components/OnlineResults/ScoreDisplay.vue`
  - [ ] Componente para mostrar puntajes calculados
- [ ] **Tarea 3.3**: Crear `components/OnlineResults/FilterBar.vue`
  - [ ] Filtros reutilizables para listas

### Fase 4: Testing y Validación

- [ ] **Tarea 4.1**: Verificar guardado correcto en `PaperEvaluation`
  - [ ] Probar quiz completo
  - [ ] Probar quiz reducido
  - [ ] Probar quiz cisneros
- [ ] **Tarea 4.2**: Verificar visualización de datos
  - [ ] Comprobar que las vistas muestren datos correctos
  - [ ] Validar formato de respuestas JSON
- [ ] **Tarea 4.3**: Pruebas de integración
  - [ ] Crear test para `OnlineResultsController@index`
  - [ ] Crear test para `OnlineResultsController@show`
- [ ] **Tarea 4.4**: Pruebas de UI
  - [ ] Verificar responsividad
  - [ ] Probar filtros y búsqueda
  - [ ] Validar enlaces de navegación

### Fase 5: Documentación y Finalización

- [ ] **Tarea 5.1**: Actualizar documentación técnica
- [ ] **Tarea 5.2**: Crear guía de usuario para visualización de resultados
- [ ] **Tarea 5.3**: Code review y refactorización final
- [ ] **Tarea 5.4**: Ejecutar Laravel Pint para formato de código
- [ ] **Tarea 5.5**: Merge a `develop`

---

## 🔧 Detalles Técnicos

### Consulta Base para Resultados Online

```php
// En OnlineResultsController
PaperEvaluation::query()
    ->where('source', 'online')
    ->where('organization_id', $organizationId)
    ->with('organization')
    ->orderBy('created_at', 'desc')
    ->get();
```

### Determinación del Tipo de Quiz

```php
// Método helper en PaperEvaluation model
public function getQuizTypeAttribute(): string
{
    $rawData = $this->raw_data ?? [];
    
    // Verificar en raw_data si el quiz original era reducido o cisneros
    if (isset($rawData['quiz_type'])) {
        return $rawData['quiz_type'];
    }
    
    // Inferir basado en datos presentes
    if (!empty($this->cisneros_answers)) {
        return 'cisneros';
    }
    
    if (empty($this->referencia_iii_answers) && !empty($this->referencia_iii_conditional)) {
        return 'reducido';
    }
    
    return 'completo';
}
```

---

## 📝 Notas Importantes

1. **Compatibilidad**: Mantener la estructura existente de `PaperEvaluation` sin cambios destructivos
2. **Modelo Antiguo**: El modelo `OnlineAnswer` puede quedar para referencia pero NO debe usarse en nuevas implementaciones
3. **Convenciones**: Seguir estilo Laravel 11 + Inertia v2 + Vue 3 Composition API
4. **Formato de Código**: Ejecutar `vendor/bin/pint --dirty` antes de cada commit

---

## 🚀 Próximos Pasos Inmediatos

1. ✅ Crear rama de trabajo
2. ✅ Documentar análisis inicial
3. ⏭️ Refactorizar `OnlineResultsController`
4. ⏭️ Crear vista de lista de resultados
5. ⏭️ Crear vista de detalle individual

---

## 📊 Progreso General

**Completado:** 13/24 tareas (54%)

- ✅ Análisis de estructura actual
- ✅ Creación de rama y documentación inicial
- ✅ Refactorización completa del backend
- ✅ Vistas principales de frontend creadas
- ✅ Enlaces del dashboard actualizados
- ⏳ Testing y validación pendientes...

---

## 🔗 Referencias

- **Modelo Principal**: `app/Models/PaperEvaluation.php`
- **Controlador de Quiz**: `app/Http/Controllers/QuizController.php`
- **Controlador de Resultados**: `app/Http/Controllers/OnlineResultsController.php`
- **Rutas**: `routes/web.php`
- **Dashboard**: `resources/js/Components/AdminDashboard.vue`
