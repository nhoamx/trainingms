# Sistema de Caché de Reportes de Organización

## 📋 Resumen General

La aplicación usa un **sistema de caché centralizado** para almacenar datos de reportes costosos (evaluaciones, demográficos, análisis Likert) para evitar recalcular información cada vez que un usuario carga una página.

```
┌─────────────────────────────────────────────────────────────────┐
│                    ARQUITECTURA DEL CACHÉ                       │
└─────────────────────────────────────────────────────────────────┘

    Usuario carga página
            ↓
    ┌─────────────────────────────┐
    │  ResultsController::         │
    │  listResults()              │
    └─────────────────────────────┘
            ↓
    ┌─────────────────────────────────────────┐
    │  OrganizationReportCacheService         │
    │  rememberListResults()                  │
    │  ¿Cache existe?                         │
    └─────────────────────────────────────────┘
         ↙                    ↘
    SÍ (Rápido)           NO (Lento)
         ↓                    ↓
    Retorna datos         Calcula datos
    del caché             computeListResultsData()
         ↓                    ↓
         └────→ Muestra en página
```

---

## 🔧 Componentes Principales

### 1. **OrganizationReportCacheService** (El Centro de Control)

**Ubicación:** `app/Services/OrganizationReportCacheService.php`

**Responsabilidades:**
- 🔑 Generar claves de caché uniformes
- 📦 Guardar datos en caché (`rememberForever()`)
- 🗑️ Invalidar (limpiar) cachés
- 🔍 Verificar si un caché existe
- ⏱️ Disparar precalentamiento de caché (warming)

**Métodos Principales:**

```php
// Guardar o recuperar del caché
$data = $cacheService->rememberListResults($orgId, function() {
    return $this->computeExpensiveData();
});

// Limpiar todos los cachés de una organización
$cacheService->forgetOrganizationCaches($orgId);

// Verificar si existe caché
if ($cacheService->hasLikertReportCache($orgId)) {
    // hacer algo
}
```

**Las 3 Claves de Caché que Maneja:**

| Clave | Propósito | Datos que Almacena |
|-------|-----------|-------------------|
| `org_report_list_{id}` | Lista de evaluaciones | Folios, nombres, demográficos, puntuaciones |
| `org_report_missing_folios_{id}` | Folios faltantes | Brechas entre folios secuenciales |
| `org_report_likert_{id}` | Reporte Clima Laboral | Puntuaciones, dimensiones, distribuciones |

---

### 2. **Observers** (Detectores de Cambios)

Cuando **actualizas un dato** en la BD, los Observers automáticamente **invalidan el caché**.

**Sistema de Observers:**

```
PaperEvaluation (evaluación) → PaperEvaluationObserver
    ↓ (create, update, delete, restore, forceDelete)
    └→ forgetOrganizationCaches()

DemographicData (datos demográficos) → DemographicDataObserver
    ↓
    └→ forgetOrganizationCaches()

EvaluationComment (comentarios) → EvaluationCommentObserver
    ↓
    └→ forgetOrganizationCaches()

EvaluationCustomField → EvaluationCustomFieldObserver
    ↓
    └→ forgetOrganizationCaches()
```

**Ejemplo - Cuando editas una evaluación:**

```php
// En ResultsController
$evaluation->update(['evaluee_name' => 'Nuevo Nombre']);

// ✅ Automáticamente dispara:
// PaperEvaluationObserver@updated()
//   → forgetOrganizationCaches($org_id)
//     → Borra todos los cachés
//     → Dispara WarmOrganizationReportCache job (en background)
```

---

### 3. **WarmOrganizationReportCache Job** (Precalentador)

**Ubicación:** `app/Jobs/WarmOrganizationReportCache.php`

**Qué hace:**
1. Se dispara **5 segundos después** de que se invalida el caché
2. Recalcula **todos** los datos costosos
3. Los almacena en caché para que la próxima carga sea **instant**

**Timeline:**

```
T=0s    Usuario edita evaluación
        → Observer invalida caché
        → Retorna respuesta al usuario

T=5s    WarmOrganizationReportCache job inicia
        → Precalcula Likert report
        → Precalcula list results
        → Precalcula missing folios

T=10s+  Usuario carga página
        → ✅ Caché ya está listo (warm)
        → Datos se muestran al instante
```

---

## 🔄 Flujos de Datos Completos

### Flujo 1: Carga Normal de Página (Cache HIT)

```mermaid
Usuario
  ↓
GET /organization/{id}/results/list
  ↓
ResultsController::listResults()
  ↓
rememberListResults($orgId, callback)
  ↓
Cache::has(key) ? SÍ
  ↓
Retorna datos cacheados ⚡ (instant)
  ↓
Inertia::render('Results/List', [...cached data...])
  ↓
Usuario ve página cargada
```

**Tiempo:** ~50-100ms (muy rápido)

---

### Flujo 2: Primera Carga o Cache MISS

```
Usuario
  ↓
GET /organization/{id}/results/list
  ↓
ResultsController::listResults()
  ↓
rememberListResults($orgId, callback)
  ↓
Cache::has(key) ? NO
  ↓
Ejecuta callback → computeListResultsData()
  ├─ Query: SELECT * FROM paper_evaluations
  ├─ Query: SELECT * FROM demographic_data
  ├─ Query: SELECT * FROM evaluation_comments
  ├─ Calcula puntuaciones
  ├─ Agrupa por folio
  └─ Retorna array
  ↓
Cache::rememberForever() → Guarda en DB
  ↓
Retorna datos
  ↓
Inertia::render('Results/List', [...computed data...])
  ↓
Usuario ve página cargada
```

**Tiempo:** 1-5 segundos (depende de # de evaluaciones)

---

### Flujo 3: Edición de Evaluación (Invalidación + Precalentamiento)

```
Usuario edita nombre de evaluado
  ↓
PATCH /organization/{id}/evaluation/{evalId}
  ↓
$evaluation->update(['evaluee_name' => 'Nuevo Nombre'])
  ↓
Dispara evento "updated"
  ↓
PaperEvaluationObserver@updated()
  ├─ forgetOrganizationCaches($orgId)
  │  ├─ Cache::forget('org_report_list_{id}')
  │  ├─ Cache::forget('org_report_missing_folios_{id}')
  │  ├─ Cache::forget('org_report_likert_{id}')
  │  └─ dispatch(WarmOrganizationReportCache) → DELAY 5s
  └─ Retorna OK
  ↓
Usuario ve confirmación
  ↓
[5 segundos después en background]
  ↓
WarmOrganizationReportCache::handle()
  ├─ Precalcula Likert report
  ├─ Precalcula list results
  └─ Precalcula missing folios
  ↓
Todo guardado en caché nuevamente
```

---

### Flujo 4: Carga Masiva (Bulk Import) - ANTES vs DESPUÉS DEL FIX

#### ❌ ANTES (Problema):

```
Usuario sube archivo Excel
  ↓
ProcessBulkEvaluationImport job inicia (background)
  ├─ Excel::import() actualiza 100 evaluaciones
  ├─ ❌ NO dispara Observers (bulk update bypass)
  ├─ ❌ NO invalida caché
  └─ Job completa
  ↓
Usuario recarga página
  ↓
Caché VIEJO aún en memoria
  ↓
❌ Ve datos antiguos (sin las nuevas evaluaciones)
```

#### ✅ DESPUÉS (Solucionado):

```
Usuario sube archivo Excel
  ↓
ProcessBulkEvaluationImport job inicia (background)
  ├─ Excel::import() actualiza 100 evaluaciones
  ├─ ✅ Job llama a forgetOrganizationCaches()
  │  └─ Invalida todos los cachés
  └─ Job completa
  ↓
[5 segundos después]
  ↓
WarmOrganizationReportCache job inicia
  ├─ Recalcula Likert report con datos nuevos
  ├─ Recalcula list results con datos nuevos
  └─ Precalcina missing folios con datos nuevos
  ↓
Usuario recarga página
  ↓
✅ Caché FRESCO con nuevas evaluaciones
  ↓
✅ Ve datos correctos inmediatamente
```

---

## 💾 Almacenamiento: Base de Datos (Database Driver)

**Ubicación de los datos cacheados:**

```
┌──────────────────────────────────────────┐
│  Database Table: cache                   │
├──────────────────────────────────────────┤
│ key                | value                │
├──────────────────────────────────────────┤
│org_report_list_1   | [serialized JSON]   │
│org_report_list_2   | [serialized JSON]   │
│org_report_likert_1 | [serialized JSON]   │
│org_report_likert_2 | [serialized JSON]   │
│...                 | ...                  │
└──────────────────────────────────────────┘
```

**Ventajas del Database Driver:**
- ✅ Persiste entre reinicios
- ✅ No requiere Redis
- ✅ Simple para desarrollo

**Limitaciones:**
- ⚠️ Más lento que Redis (pero suficiente para esta app)
- ⚠️ No soporta `Cache::tags()` (necesario para producción con Redis)

---

## 🚀 Optimizaciones Implementadas

### 1. **Cache::rememberForever()**
```php
// No expira nunca, solo cuando lo borramos manualmente
Cache::rememberForever($key, $callback);
```

**Ventaja:** No tienes que recomputar cada X horas.

---

### 2. **Cálculo Lazy (Solo cuando es necesario)**
```php
// Si el caché existe, usa callback
// Si NO existe, ejecuta callback
$data = $cacheService->rememberListResults($orgId, function() {
    // Este código SOLO se ejecuta si falta el caché
    return $this->computeExpensiveData();
});
```

---

### 3. **Precalentamiento Delayed (Warming)**
```php
// Espera 5 segundos para que el usuario obtenga respuesta
WarmOrganizationReportCache::dispatch($org)
    ->delay(now()->addSeconds(5));
```

**Ventaja:** Usuario no espera, caché se precalcula en background.

---

### 4. **Eager Loading en Queries**
```php
$evaluations = PaperEvaluation::where(...)
    ->with([
        'comments:id,paper_evaluation_id,factor,comment',
        'customFields:id,paper_evaluation_id,field_key,value',
        'demographicData:id,...'
    ])
    ->get();
```

**Ventaja:** Evita N+1 queries dentro del caché computado.

---

## 📊 Flujo de Datos Visualizado

```
                         ┌──────────────────────┐
                         │   Usuario             │
                         └──────────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
                Carga            Edita         Bulk Import
                página           evaluación    (Excel)
                    │               │               │
                    ↓               ↓               ↓
            ┌───────────────────────────────────────────────┐
            │         ResultsController                      │
            ├───────────────────────────────────────────────┤
            │ listResults()  │ updateEval()│ bulkUpdate()    │
            └───────────────────────────────────────────────┘
                    │               │               │
                    ↓               ↓               ↓
            ┌───────────────────────────────────────────────┐
            │    OrganizationReportCacheService             │
            ├───────────────────────────────────────────────┤
            │ rememberListResults()  │  forgetOrganizationCaches()
            └───────────────────────────────────────────────┘
                    ↓               ↓               ↓
            ┌───────────────────────────────────────────────┐
            │          Database (cache table)               │
            ├───────────────────────────────────────────────┤
            │ org_report_list_1, org_report_likert_1, ...   │
            └───────────────────────────────────────────────┘
                    ↑               │               │
                    │               ↓               ↓
                    │        PaperEvaluation    ProcessBulkXImport
                    │        Observer           Job
                    │           │               │
                    └───────────┴───────────────┘
                                │
                                ↓
                    ┌───────────────────────────────┐
                    │ WarmOrganizationReportCache   │
                    │ Job (5s delay)                │
                    └───────────────────────────────┘
```

---

## 🛠️ Cómo Trabaja en la Práctica

### Escenario 1: Usuario Normal Cargando Página

**1. Primera visita:**
```
rememberListResults() 
  → Cache NO existe
  → Ejecuta callback
  → Queries a BD (lento)
  → Almacena resultado
  → Retorna datos
```

**2. Segunda visita (minutos después):**
```
rememberListResults()
  → Cache EXISTE
  → Retorna datos instantly
```

**3. Alguien edita una evaluación:**
```
→ Observer invalida caché
→ WarmJob precalcula en background (5s)
```

**4. Tercera visita:**
```
rememberListResults()
  → Cache NUEVO (precalentado)
  → Datos actualizados + instant
```

---

### Escenario 2: Carga Masiva

**Antes (sin fix):**
1. Subes 500 evaluaciones
2. Excel::import() actualiza BD (no dispara Observers)
3. Caché viejo sigue en memoria
4. Usuarios ven datos antiguos
5. `cache:clear` manual = forzar recalcular

**Después (con fix):**
1. Subes 500 evaluaciones
2. Excel::import() actualiza BD
3. ProcessBulkEvaluationImport llama `forgetOrganizationCaches()`
4. Caché se borra
5. WarmJob precalcula en background (5s)
6. Usuarios ven datos nuevos al siguiente reload

---

## 📝 Diagrama Temporal

```
TIEMPO                  ACCIÓN                          CACHÉ
─────────────────────────────────────────────────────────────
t=0s                    Usuario carga página            
                        → rememberListResults()         
                        → Cache HIT ✅                  [org_report_list_1]
                        → Retorna datos cacheados
                        
t=2m                    Usuario edita evaluación
                        → Observer invalida
                        → Cache borrado                 [empty]
                        → WarmJob dispatchado (delay 5s)

t=2m05s                 WarmOrganizationReportCache job inicia
                        → Precalcula Likert report
                        → Precalcula list results        [org_report_list_1 - NUEVO]
                        → Guarda en caché

t=2m06s                 Usuario recarga página
                        → rememberListResults()
                        → Cache HIT ✅ (FRESCO)         [org_report_list_1 - NUEVO]
                        → Retorna datos actualizados

t=2m07s                 Usuario ve página con cambios
```

---

## ✅ Resumen de la Arquitectura

| Aspecto | Descripción |
|---------|-------------|
| **Sistema** | Caché en base de datos (Database Driver) |
| **Servicio Central** | `OrganizationReportCacheService` |
| **Invalidación** | Observers (automático) + Jobs (bulk import) |
| **Precalentamiento** | `WarmOrganizationReportCache` con delay de 5s |
| **Duración** | `rememberForever()` - nunca expira |
| **Almacenamiento** | Tabla `cache` en BD |
| **Organización** | Por organización (3 cachés por org) |
| **Performance** | HIT: ~100ms, MISS: 1-5s, After warming: ~100ms |

---

## 🔍 Debugging: Cómo Verificar el Caché

```bash
# Ver qué está en el caché
php artisan tinker
> DB::table('cache')->where('key', 'like', 'org_report_%')->pluck('key')

# Limpiar caché específico
> Cache::forget('org_report_list_1')

# Limpiar TODOS los cachés
> php artisan cache:clear

# Ver cachés de una organización
> $org = Organization::find(1);
> $cacheService = app(OrganizationReportCacheService::class);
> $cacheService->forgetOrganizationCaches($org->id)
```

---

## 🚀 Futuras Mejoras

```php
// TODO: En producción, migrar a Redis
if ($this->cache instanceof RedisStore) {
    // Usar Cache::tags() para invalidación más eficiente
    Cache::tags(['org_' . $orgId])->flush();
}
```

Esto permitiría limpiar todos los cachés de una org con una sola operación.
