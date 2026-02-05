# Plan de Implementación: Work Centers (Centros de Trabajo)

> **Fecha de inicio**: 4 de febrero, 2026  
> **Urgencia**: FASE 1 debe completarse en 24 horas (5 feb, 2026)  
> **Razón**: Evaluaciones online necesitan mostrar Corporativo + Centro de Trabajo

---

## ⚠️ PASOS CRÍTICOS ANTES DE EMPEZAR

### 1. Backup de Base de Datos (OBLIGATORIO)
```bash
# Hacer backup completo ANTES de cualquier cambio
mysqldump -u root -p trainingms > backup_pre_work_centers_$(date +%Y%m%d_%H%M%S).sql

# Verificar que el backup se creó correctamente
ls -lh backup_pre_work_centers_*.sql
```

### 2. Datos Actuales a Migrar
- **10 organizaciones** existentes
- **~6,100 evaluaciones completadas** (JAROPAMEX PLANTA 1: 3,502, PLANTA 3: 2,339, etc.)
- **Quizzes activos** sin `work_center_id`
- **Casos especiales**: JAROPAMEX PLANTA 1 y PLANTA 3 (son organizaciones separadas)

### 3. Orden de Ejecución (NO ALTERAR)
```
1. Backup de DB ✓
2. php artisan migrate (3 migrations nuevas)
3. php artisan db:seed --class=MigrateToWorkCentersSeeder
4. Validación manual con queries SQL
5. Deploy de código frontend/backend
6. Testing en staging
7. Deploy a producción
```

---

## 🎯 ARQUITECTURA OBJETIVO

### Antes (actual):
```
Organization (1:N) → PaperEvaluation
Organization (1:N) → Quiz
```

### Después (objetivo):
```
Organization (1:N) → WorkCenter (1:N) → PaperEvaluation
Organization (1:N) → WorkCenter (1:N) → Quiz
```

### Qué se migra:
```
10 Organizations existentes
  → Cada una recibe 1 WorkCenter primario (tipo: "matriz")
  → Datos fiscales/dirección se copian al WorkCenter
  → Quizzes existentes → work_center_id = centro primario
  → PaperEvaluations existentes → work_center_id = centro primario
```

---

## 📋 FASES DE IMPLEMENTACIÓN

### **FASE 1: Arquitectura Base + Online Evaluations** ⚡ URGENTE (24 horas)

**Objetivo**: Sistema de Work Centers operativo para evaluaciones online.

#### 1.1 Base de Datos (2 horas)

**Migration: `create_work_centers_table.php`**
```sql
work_centers:
  - id (UUID, PK)
  - organization_id (UUID, FK → organizations.id)
  - code (string, 4 dígitos) → "0101" (org 01, centro 01)
  - name (string) → "Planta Monterrey"
  - type (enum: matriz, planta, sucursal, bodega, oficina, otro)
  - is_primary (boolean) → centro principal
  
  -- Datos fiscales específicos (si difieren de matriz)
  - razon_social (nullable)
  - rfc (nullable)
  - registro_patronal (nullable)
  
  -- Dirección física
  - calle_numero (nullable)
  - colonia (nullable)
  - codigo_postal (nullable)
  - municipio (nullable)
  - estado (nullable)
  
  -- Datos NOM-035 del centro
  - total_trabajadores (int, nullable)
  - total_hombres (int, nullable)
  - total_mujeres (int, nullable)
  - muestra_aplicada (int, nullable)
  - muestra_hombres (int, nullable)
  - muestra_mujeres (int, nullable)
  - comite_integrantes (int, nullable)
  - comite_hombres (int, nullable)
  - comite_mujeres (int, nullable)
  - fecha_aplicacion (date, nullable)
  
  - created_at, updated_at, deleted_at
  
  INDEXES:
  - work_centers_organization_id_index
  - work_centers_code_unique
  - work_centers_organization_id_is_primary_index
```

**Migration: `add_work_center_id_to_paper_evaluations.php`**
```sql
ALTER TABLE paper_evaluations 
  ADD COLUMN work_center_id UUID NULLABLE;
  
ALTER TABLE paper_evaluations
  ADD CONSTRAINT fk_work_center
  FOREIGN KEY (work_center_id) 
  REFERENCES work_centers(id) 
  ON DELETE SET NULL;
  
CREATE INDEX idx_paper_evaluations_work_center ON paper_evaluations(work_center_id);
```

**Migration: `add_work_center_id_to_quizzes.php`**
```sql
ALTER TABLE quizzes 
  ADD COLUMN work_center_id UUID NULLABLE;
  
ALTER TABLE quizzes
  ADD CONSTRAINT fk_quiz_work_center
  FOREIGN KEY (work_center_id) 
  REFERENCES work_centers(id) 
  ON DELETE SET NULL;
  
CREATE INDEX idx_quizzes_work_center ON quizzes(work_center_id);
```

#### 1.2 Migración de Datos Existentes (2 horas) ⚡ CRÍTICO

**Situación actual**:
- 10 organizaciones existentes
- ~6,100 evaluaciones completadas
- Quizzes activos sin `work_center_id`
- Casos especiales: "JAROPAMEX PLANTA 1" y "PLANTA 3" son organizaciones separadas pero deberían ser work centers

**Script de migración**: `database/seeders/MigrateToWorkCentersSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\WorkCenter;
use App\Models\Quiz;
use App\Models\PaperEvaluation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateToWorkCentersSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            Log::info('=== INICIO MIGRACIÓN A WORK CENTERS ===');
            
            // PASO 1: Crear work centers primarios para cada organización
            $this->createPrimaryWorkCenters();
            
            // PASO 2: Migrar quizzes existentes
            $this->migrateExistingQuizzes();
            
            // PASO 3: Migrar paper evaluations existentes
            $this->migrateExistingEvaluations();
            
            // PASO 4: Validar migración
            $this->validateMigration();
            
            DB::commit();
            Log::info('=== FIN MIGRACIÓN A WORK CENTERS (EXITOSO) ===');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en migración a work centers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    protected function createPrimaryWorkCenters(): void
    {
        Log::info('Creando work centers primarios...');
        
        $organizations = Organization::all();
        $created = 0;
        
        foreach ($organizations as $org) {
            // Verificar si ya tiene work center
            if ($org->workCenters()->exists()) {
                Log::warning("Org {$org->name} ya tiene work centers, saltando");
                continue;
            }
            
            $workCenter = WorkCenter::create([
                'organization_id' => $org->id,
                'code' => $this->generateWorkCenterCode($org),
                'name' => $this->inferWorkCenterName($org),
                'type' => 'matriz',
                'is_primary' => true,
                
                // Copiar datos fiscales
                'razon_social' => $org->razon_social,
                'rfc' => $org->rfc,
                'registro_patronal' => $org->registro_patronal,
                
                // Copiar dirección
                'calle_numero' => $org->calle_numero,
                'colonia' => $org->colonia,
                'codigo_postal' => $org->codigo_postal,
                'municipio' => $org->municipio,
                'estado' => $org->estado,
                
                // Copiar datos NOM-035
                'total_trabajadores' => $org->total_trabajadores,
                'total_hombres' => $org->total_hombres,
                'total_mujeres' => $org->total_mujeres,
                'muestra_aplicada' => $org->muestra_aplicada,
                'muestra_hombres' => $org->muestra_hombres,
                'muestra_mujeres' => $org->muestra_mujeres,
                'comite_integrantes' => $org->comite_integrantes,
                'comite_hombres' => $org->comite_hombres,
                'comite_mujeres' => $org->comite_mujeres,
                'fecha_aplicacion' => $org->fecha_aplicacion,
            ]);
            
            $created++;
            Log::info("Work center creado para {$org->name}", [
                'work_center_id' => $workCenter->id,
                'code' => $workCenter->code,
            ]);
        }
        
        Log::info("Work centers primarios creados: {$created}");
    }
    
    protected function generateWorkCenterCode(Organization $org): string
    {
        // Formato: [org_folio][center_seq]
        // Ej: org 30 → "3001" (org 30, centro 01)
        
        $orgCode = str_pad($org->folio_organization ?? '999', 2, '0', STR_PAD_LEFT);
        $centerSeq = '01'; // Primer centro
        
        return $orgCode . $centerSeq;
    }
    
    protected function inferWorkCenterName(Organization $org): string
    {
        $name = $org->name;
        
        // Si el nombre ya incluye "PLANTA", "SUCURSAL", etc., extraerlo
        if (preg_match('/(PLANTA|SUCURSAL|BODEGA|OFICINA)\s*(\d+|[A-Z]+)/i', $name, $matches)) {
            return trim($matches[0]); // "PLANTA 1", "SUCURSAL CENTRO"
        }
        
        // Si no, usar "Matriz"
        return 'Matriz';
    }
    
    protected function migrateExistingQuizzes(): void
    {
        Log::info('Migrando quizzes existentes...');
        
        $quizzes = Quiz::whereNull('work_center_id')->get();
        $migrated = 0;
        
        foreach ($quizzes as $quiz) {
            $primaryCenter = WorkCenter::where('organization_id', $quiz->organization_id)
                ->where('is_primary', true)
                ->first();
            
            if (!$primaryCenter) {
                Log::error("No se encontró work center primario para quiz", [
                    'quiz_id' => $quiz->id,
                    'organization_id' => $quiz->organization_id,
                ]);
                continue;
            }
            
            $quiz->update(['work_center_id' => $primaryCenter->id]);
            $migrated++;
        }
        
        Log::info("Quizzes migrados: {$migrated}");
    }
    
    protected function migrateExistingEvaluations(): void
    {
        Log::info('Migrando paper evaluations...');
        
        $evaluations = PaperEvaluation::whereNull('work_center_id')->get();
        $migrated = 0;
        $errors = 0;
        
        foreach ($evaluations as $eval) {
            if (!$eval->organization_id) {
                Log::warning("Evaluation sin organization_id", ['eval_id' => $eval->id]);
                $errors++;
                continue;
            }
            
            $primaryCenter = WorkCenter::where('organization_id', $eval->organization_id)
                ->where('is_primary', true)
                ->first();
            
            if (!$primaryCenter) {
                Log::error("No se encontró work center para evaluation", [
                    'eval_id' => $eval->id,
                    'organization_id' => $eval->organization_id,
                ]);
                $errors++;
                continue;
            }
            
            $eval->update(['work_center_id' => $primaryCenter->id]);
            $migrated++;
            
            // Log cada 500 evaluaciones
            if ($migrated % 500 === 0) {
                Log::info("Progreso: {$migrated} evaluaciones migradas");
            }
        }
        
        Log::info("Evaluaciones migradas: {$migrated}, errores: {$errors}");
    }
    
    protected function validateMigration(): void
    {
        Log::info('Validando migración...');
        
        // Validar que todas las organizaciones tienen work center
        $orgsWithoutWC = Organization::doesntHave('workCenters')->count();
        if ($orgsWithoutWC > 0) {
            throw new \Exception("Hay {$orgsWithoutWC} organizaciones sin work centers");
        }
        
        // Validar que todos los quizzes tienen work_center_id
        $quizzesWithoutWC = Quiz::whereNull('work_center_id')->count();
        if ($quizzesWithoutWC > 0) {
            Log::warning("Quedan {$quizzesWithoutWC} quizzes sin work_center_id");
        }
        
        // Validar que las evaluaciones completadas tienen work_center_id
        $evalsWithoutWC = PaperEvaluation::where('processing_status', 'completed')
            ->whereNull('work_center_id')
            ->count();
        if ($evalsWithoutWC > 0) {
            Log::warning("Quedan {$evalsWithoutWC} evaluaciones completadas sin work_center_id");
        }
        
        // Reporte final
        $summary = [
            'organizations_total' => Organization::count(),
            'work_centers_created' => WorkCenter::count(),
            'quizzes_migrated' => Quiz::whereNotNull('work_center_id')->count(),
            'evaluations_migrated' => PaperEvaluation::whereNotNull('work_center_id')->count(),
        ];
        
        Log::info('Resumen de migración', $summary);
        
        $this->command->info("✓ Migración completada:");
        $this->command->table(
            ['Métrica', 'Valor'],
            collect($summary)->map(fn($v, $k) => [ucfirst(str_replace('_', ' ', $k)), $v])->values()
        );
    }
}
```

**Ejecutar migración**:
```bash
# Después de correr migrations
php artisan db:seed --class=MigrateToWorkCentersSeeder
```

**Validación manual**:
```sql
-- Verificar que todas las orgs tienen work centers
SELECT o.name, COUNT(wc.id) as wc_count 
FROM organizations o 
LEFT JOIN work_centers wc ON o.id = wc.organization_id 
GROUP BY o.id, o.name 
HAVING wc_count = 0;

-- Verificar que quizzes tienen work_center_id
SELECT COUNT(*) as sin_wc FROM quizzes WHERE work_center_id IS NULL;

-- Verificar evaluaciones
SELECT COUNT(*) as sin_wc 
FROM paper_evaluations 
WHERE processing_status = 'completed' 
AND work_center_id IS NULL;
```

**Rollback plan** (si algo sale mal):
```bash
# Revertir migrations
php artisan migrate:rollback --step=3

# Restaurar desde backup
mysql -u user -p database_name < backup_pre_migration.sql
```

**Casos especiales**: JAROPAMEX PLANTA 1 y PLANTA 3

**Situación actual**:
- "JAROPAMEX PLANTA 1" (folio_organization: 30) → 3,502 evaluaciones
- "JAROPAMEX PLANTA 3" (folio_organization: 31) → 2,339 evaluaciones
- Son organizaciones separadas en DB pero conceptualmente son centros de trabajo de JAROPAMEX

**Opción A: Dejarlas como organizaciones separadas** (Recomendada para MVP)
```
✓ Sin cambios en datos existentes
✓ Sin riesgo de pérdida de datos
✓ Cada "planta" sigue siendo una organización con 1 work center primario
- Reportes consolidados requieren lógica custom
```

**Opción B: Consolidar en una sola organización** (Futuro)
```
1. Crear organización "JAROPAMEX Corporativo"
2. Convertir PLANTA 1 y PLANTA 3 en work_centers
3. Migrar evaluaciones y quizzes
⚠️ RIESGOSO: Requiere actualizar folios, reportes, cache
⚠️ TIEMPO: 4-6 horas adicionales
⚠️ TESTING: Exhaustivo antes de producción
```

**Decisión para Fase 1**: Usar **Opción A**. La consolidación se hace en Fase 3 cuando el sistema de work centers esté estable.

**Script para identificar organizaciones hermanas** (para futuro):
```php
// Detectar organizaciones que podrían ser work centers de la misma matriz
$possibleGroups = Organization::all()
    ->groupBy(function($org) {
        // Extraer nombre base: "JAROPAMEX PLANTA 1" → "JAROPAMEX"
        return preg_replace('/(PLANTA|SUCURSAL|BODEGA)\s*\d+/i', '', $org->name);
    })
    ->filter(fn($group) => $group->count() > 1);

// Mostrar grupos detectados
foreach ($possibleGroups as $baseName => $orgs) {
    echo "Grupo: {$baseName}\n";
    foreach ($orgs as $org) {
        echo "  - {$org->name} (Folio: {$org->folio_organization})\n";
    }
}
```

#### 1.3 Modelos Laravel (1 hora)

**`app/Models/WorkCenter.php`** (NUEVO)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkCenter extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'type',
        'is_primary',
        'razon_social',
        'rfc',
        'registro_patronal',
        'calle_numero',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado',
        'total_trabajadores',
        'total_hombres',
        'total_mujeres',
        'muestra_aplicada',
        'muestra_hombres',
        'muestra_mujeres',
        'comite_integrantes',
        'comite_hombres',
        'comite_mujeres',
        'fecha_aplicacion',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'fecha_aplicacion' => 'date',
            'total_trabajadores' => 'integer',
            'total_hombres' => 'integer',
            'total_mujeres' => 'integer',
            'muestra_aplicada' => 'integer',
            'muestra_hombres' => 'integer',
            'muestra_mujeres' => 'integer',
            'comite_integrantes' => 'integer',
            'comite_hombres' => 'integer',
            'comite_mujeres' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function paperEvaluations(): HasMany
    {
        return $this->hasMany(PaperEvaluation::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function occupationPositions(): HasMany
    {
        return $this->hasMany(OccupationPosition::class);
    }

    public function departmentAreas(): HasMany
    {
        return $this->hasMany(DepartmentArea::class);
    }

    /**
     * Scope para centros primarios
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Obtener nombre completo (organización + centro)
     */
    public function getFullNameAttribute(): string
    {
        return $this->organization->name . ' - ' . $this->name;
    }
}
```

**Actualizar `app/Models/Organization.php`**
```php
// Agregar relación
public function workCenters(): HasMany
{
    return $this->hasMany(WorkCenter::class);
}

public function primaryWorkCenter(): HasOne
{
    return $this->hasOne(WorkCenter::class)->where('is_primary', true);
}
```

**Actualizar `app/Models/PaperEvaluation.php`**
```php
// Agregar relación
public function workCenter(): BelongsTo
{
    return $this->belongsTo(WorkCenter::class);
}
```

**Actualizar `app/Models/Quiz.php`**
```php
// Agregar al fillable
protected $fillable = [
    // ... existentes
    'work_center_id',
];

// Agregar relación
public function workCenter(): BelongsTo
{
    return $this->belongsTo(WorkCenter::class);
}
```

#### 1.3 Controllers CRUD (2 horas)

**`app/Http/Controllers/WorkCenterController.php`** (NUEVO)
```php
<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkCenterController extends Controller
{
    public function index()
    {
        $workCenters = WorkCenter::with('organization')
            ->orderBy('organization_id')
            ->orderBy('is_primary', 'desc')
            ->orderBy('name')
            ->get();

        return Inertia::render('WorkCenters/Index', [
            'workCenters' => $workCenters,
        ]);
    }

    public function create()
    {
        return Inertia::render('WorkCenters/Create', [
            'organizations' => Organization::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'code' => 'required|string|size:4|unique:work_centers,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:matriz,planta,sucursal,bodega,oficina,otro',
            'is_primary' => 'sometimes|boolean',
            // ... resto de campos opcionales
        ]);

        // Si is_primary, desmarcar otros
        if ($validated['is_primary'] ?? false) {
            WorkCenter::where('organization_id', $validated['organization_id'])
                ->update(['is_primary' => false]);
        }

        $workCenter = WorkCenter::create($validated);

        return redirect()->route('work-centers.index')
            ->with('success', 'Centro de trabajo creado exitosamente');
    }

    // ... edit, update, destroy
}
```

#### 1.4 Actualizar QuizController para Work Centers (3 horas) ⚡ CRÍTICO

**Cambios en `app/Http/Controllers/QuizController.php`:**

1. **Método `create()`**: Cargar work centers por organización
```php
public function create()
{
    return Inertia::render('Quiz/Create', [
        'organizations' => Organization::with('workCenters')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'work_centers' => $org->workCenters->map(fn($wc) => [
                    'id' => $wc->id,
                    'name' => $wc->name,
                    'is_primary' => $wc->is_primary,
                ]),
            ]),
    ]);
}
```

2. **Método `store()`**: Validar y guardar `work_center_id`
```php
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'organization_id' => 'required|exists:organizations,id',
    'work_center_id' => 'required|exists:work_centers,id', // NUEVO
    'expires_at' => 'required|date|after:now',
    // ...
]);

$quiz = Quiz::create([
    'name' => $validated['name'],
    'organization_id' => $validated['organization_id'],
    'work_center_id' => $validated['work_center_id'], // NUEVO
    // ...
]);
```

3. **Método `showTemp($tempUrl)`**: Cargar work center info
```php
$quiz = Quiz::with(['organization', 'workCenter', 'organization.occupationPositions', ...])
    ->where('temp_url', $tempUrl)
    ->first();

// Preparar datos
$organizationData = [
    'id' => $quiz->organization->id,
    'name' => $quiz->organization->name,
    'work_center' => [ // NUEVO
        'id' => $quiz->workCenter->id,
        'name' => $quiz->workCenter->name,
        'full_name' => $quiz->workCenter->full_name, // "JAROPAMEX - Planta 1"
    ],
    // ...
];
```

4. **Método `submit()`**: Asociar evaluación con work center
```php
// Al crear PaperEvaluation
PaperEvaluation::create([
    'organization_id' => $quiz->organization_id,
    'work_center_id' => $quiz->work_center_id, // NUEVO
    // ...
]);
```

#### 1.5 Frontend Inertia (4 horas)

**NUEVO: `resources/js/Pages/WorkCenters/Index.vue`**
```vue
<template>
  <AuthenticatedLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold">Centros de Trabajo</h2>
          <Link :href="route('work-centers.create')" class="btn-primary">
            + Nuevo Centro
          </Link>
        </div>

        <!-- Agrupar por organización -->
        <div v-for="(centers, orgName) in groupedCenters" :key="orgName" class="mb-8">
          <h3 class="text-xl font-semibold mb-4">{{ orgName }}</h3>
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th>Código</th>
                  <th>Nombre</th>
                  <th>Tipo</th>
                  <th>Principal</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="center in centers" :key="center.id">
                  <td>{{ center.code }}</td>
                  <td>{{ center.name }}</td>
                  <td><span class="badge">{{ center.type }}</span></td>
                  <td>
                    <span v-if="center.is_primary" class="text-green-600">✓ Matriz</span>
                  </td>
                  <td>
                    <Link :href="route('work-centers.edit', center.id)">Editar</Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  workCenters: Array,
});

const groupedCenters = computed(() => {
  return props.workCenters.reduce((acc, center) => {
    const orgName = center.organization.name;
    if (!acc[orgName]) acc[orgName] = [];
    acc[orgName].push(center);
    return acc;
  }, {});
});
</script>
```

**ACTUALIZAR: `resources/js/Pages/Quiz/Create.vue`**
```vue
<template>
  <!-- ... -->
  
  <!-- Selector de Organización -->
  <div class="mb-4">
    <label>Organización</label>
    <select v-model="form.organization_id" @change="onOrganizationChange">
      <option value="">Seleccionar organización</option>
      <option v-for="org in organizations" :key="org.id" :value="org.id">
        {{ org.name }}
      </option>
    </select>
  </div>

  <!-- NUEVO: Selector de Centro de Trabajo -->
  <div v-if="availableWorkCenters.length > 0" class="mb-4">
    <label>Centro de Trabajo</label>
    <select v-model="form.work_center_id" required>
      <option value="">Seleccionar centro</option>
      <option v-for="wc in availableWorkCenters" :key="wc.id" :value="wc.id">
        {{ wc.name }}
        <span v-if="wc.is_primary">(Principal)</span>
      </option>
    </select>
    <p class="text-sm text-gray-500 mt-1">
      Este centro aparecerá en la URL del examen
    </p>
  </div>
  
  <!-- ... -->
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  organizations: Array,
});

const form = useForm({
  name: '',
  organization_id: '',
  work_center_id: '', // NUEVO
  expires_at: '',
  quiz_type: 'normal',
});

const availableWorkCenters = ref([]);

const onOrganizationChange = () => {
  const org = props.organizations.find(o => o.id === form.organization_id);
  availableWorkCenters.value = org?.work_centers || [];
  form.work_center_id = ''; // Reset
};
</script>
```

**ACTUALIZAR: `resources/js/Pages/Quiz/Take.vue` (y variantes)**
```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header con nombre de organización + centro -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex items-center space-x-4">
          <img v-if="quiz.organization.logo" :src="quiz.organization.logo" class="h-12" />
          <div>
            <h1 class="text-2xl font-bold text-gray-900">
              {{ quiz.organization.name }}
            </h1>
            <!-- NUEVO: Mostrar centro de trabajo -->
            <p class="text-sm text-gray-600">
              Centro: {{ quiz.organization.work_center.name }}
            </p>
          </div>
        </div>
        <h2 class="text-xl text-gray-700 mt-2">{{ quiz.name }}</h2>
      </div>
    </div>
    
    <!-- ... resto del quiz ... -->
  </div>
</template>

<script setup>
const props = defineProps({
  quiz: Object,
});
</script>
```

#### 1.6 Routes (15 minutos)

**`routes/web.php`**
```php
// Work Centers routes
Route::middleware(['auth'])->group(function () {
    Route::resource('work-centers', WorkCenterController::class);
});
```

#### 1.7 Testing (2 horas)

**`tests/Feature/WorkCenterTest.php`** (NUEVO)
```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\WorkCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_create_work_center(): void
    {
        $org = Organization::factory()->create();
        
        $workCenter = WorkCenter::create([
            'organization_id' => $org->id,
            'code' => '0101',
            'name' => 'Planta Test',
            'type' => 'planta',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('work_centers', [
            'id' => $workCenter->id,
            'code' => '0101',
        ]);
    }

    public function test_organization_has_work_centers(): void
    {
        $org = Organization::factory()->create();
        $wc = WorkCenter::factory()->create(['organization_id' => $org->id]);

        $this->assertInstanceOf(WorkCenter::class, $org->workCenters->first());
    }

    public function test_quiz_requires_work_center(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $wc = WorkCenter::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->post(route('quizzes.store'), [
            'name' => 'Test Quiz',
            'organization_id' => $org->id,
            'work_center_id' => $wc->id,
            'expires_at' => now()->addDays(7),
            'quiz_type' => 'normal',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quizzes', [
            'name' => 'Test Quiz',
            'work_center_id' => $wc->id,
        ]);
    }
}
```

#### 1.8 Factory (30 minutos)

**`database/factories/WorkCenterFactory.php`** (NUEVO)
```php
<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkCenterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code' => fake()->unique()->numerify('####'),
            'name' => fake()->randomElement(['Planta', 'Sucursal', 'Bodega']) . ' ' . fake()->city(),
            'type' => fake()->randomElement(['planta', 'sucursal', 'bodega', 'oficina']),
            'is_primary' => false,
            'calle_numero' => fake()->streetAddress(),
            'colonia' => fake()->citySuffix(),
            'codigo_postal' => fake()->postcode(),
            'municipio' => fake()->city(),
            'estado' => fake()->state(),
            'total_trabajadores' => fake()->numberBetween(10, 500),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'type' => 'matriz',
        ]);
    }
}
```

---

### **FASE 2: Formato de Folio + OCR** (1 semana)

**NOTA**: Esta fase se hace DESPUÉS de que las evaluaciones online estén funcionando.

#### 2.1 Cambio de formato de folio

**Decisión de formato:**
```
OPCIÓN A (Recomendada): 11 dígitos
[eval_type:2][org_code:2][center_code:2][personal:5]
Ejemplo: 02-01-01-00001
         ↑  ↑  ↑  ↑
         │  │  │  └─ Personal folio (00001-99999)
         │  │  └──── Centro de trabajo (01-99)
         │  └─────── Organización (01-99)
         └────────── Tipo evaluación (02 = Ref III)

Capacidad: 99 orgs × 99 centros × 99,999 personas = 97M combinaciones
```

#### 2.2 Actualizar PaperEvaluation::parseFolio()

```php
public static function parseFolio(string $folio): array
{
    if (strlen($folio) !== 11) {
        throw new \InvalidArgumentException("Folio must be 11 characters: {$folio}");
    }

    $evaluationTypeCode = substr($folio, 0, 2);
    $orgCode = substr($folio, 2, 2);
    $centerCode = substr($folio, 4, 2);
    $personalFolio = substr($folio, 6, 5);

    return [
        'folio' => $folio,
        'evaluation_type_code' => $evaluationTypeCode,
        'organization_code' => $orgCode,
        'center_code' => $centerCode,
        'personal_folio' => $personalFolio,
        'evaluation_type' => self::getEvaluationTypeFromCode($evaluationTypeCode),
    ];
}
```

#### 2.3 Actualizar OCR Config

**`docker/config.py`**: Cambiar detección de folio de 9 a 11 dígitos
**`docker/bubble_detector.py`**: Ajustar regex de validación

#### 2.4 Actualizar generación de folios

```php
private function generatePaperEvaluationFolio(Quiz $quiz, string $personalFolio): string
{
    $evaluationTypeCode = match (true) {
        $quiz->is_cisneros => '04',
        $quiz->is_reduced => '02',
        default => '03',
    };

    // Obtener códigos de org y centro
    $orgCode = str_pad($quiz->organization->folio_organization, 2, '0', STR_PAD_LEFT);
    $centerCode = str_pad($quiz->workCenter->code, 2, '0', STR_PAD_LEFT);
    $personal = str_pad($personalFolio, 5, '0', STR_PAD_LEFT);

    return $evaluationTypeCode . $orgCode . $centerCode . $personal;
}
```

---

### **FASE 3: Migrar Servicios y Reportes** (2 semanas)

#### 3.1 Servicios de Reportes

**Actualizar para soportar filtrado por work center:**
- `OrganizationReportCacheService` → `WorkCenterReportCacheService`
- `Nom035DomainCalculationService` → agregar parámetro `$workCenterId`
- `PaperEvaluationScoreService` → filtrar por work center
- Todos los exports: agregar columna de centro de trabajo

#### 3.2 Dashboard

**Actualizar `DashboardController`:**
- Mostrar lista de centros de trabajo
- Métricas por centro
- Selector de centro en filtros

#### 3.3 Observers

**Actualizar `PaperEvaluationObserver`:**
```php
// Invalidar cache del work center
Cache::forget("work_center_report_{$evaluation->work_center_id}");
```

---

### **FASE 4: UI Avanzada** (1 semana)

#### 4.1 Comparación entre centros

**Nueva página**: `WorkCenters/Compare.vue`
- Selector múltiple de centros
- Comparación lado a lado de métricas NOM-035
- Gráficos comparativos

#### 4.2 Gestión avanzada

- Bulk upload de centros (Excel/CSV)
- Clonar configuración entre centros
- Transferir evaluaciones entre centros

---

## 📊 MÉTRICAS DE ÉXITO

### Fase 1 (24 horas):
- ✅ Tabla `work_centers` creada y migrada
- ✅ CRUD de work centers funcional
- ✅ Quiz muestra "Organización - Centro" en header
- ✅ URLs online funcionan con nuevo selector
- ✅ Tests pasan (al menos work center CRUD)

### Fase 2 (1 semana):
- ✅ Folio de 11 dígitos generándose correctamente
- ✅ OCR detecta nuevo formato
- ✅ PDFs físicos generados con nuevo folio
- ✅ PaperEvaluation::parseFolio() maneja ambos formatos (legacy 9 + nuevo 11)

### Fase 3 (2 semanas):
- ✅ Reportes filtran por work center
- ✅ Cache o DB snapshots funcionan por centro
- ✅ Exports incluyen columna de centro
- ✅ Dashboard muestra métricas por centro

### Fase 4 (1 semana):
- ✅ Comparación entre centros funcional
- ✅ UI intuitiva para gestión de centros
- ✅ Documentación de usuario completa

---

## ⚠️ RIESGOS Y CONTINGENCIAS

### Riesgo 1: No completar Fase 1 en 24 horas
**Mitigación**: Priorizar solo lo esencial:
1. Migration + seeder de work centers
2. Relación Quiz → WorkCenter
3. Frontend mínimo (selector + display)

**Plan B**: Usar work center primario por defecto si no se puede elegir.

### Riesgo 2: Evaluaciones existentes sin work_center_id
**Mitigación**: Script de backfill:
```php
// Asignar al centro primario de cada organización
PaperEvaluation::whereNull('work_center_id')->chunk(100, function ($evals) {
    foreach ($evals as $eval) {
        $primaryCenter = WorkCenter::where('organization_id', $eval->organization_id)
            ->where('is_primary', true)
            ->first();
        
        if ($primaryCenter) {
            $eval->update(['work_center_id' => $primaryCenter->id]);
        }
    }
});
```

### Riesgo 3: Folio legacy (9 dígitos) vs nuevo (11 dígitos)
**Mitigación**: Soporte dual en `parseFolio()`:
```php
public static function parseFolio(string $folio): array
{
    if (strlen($folio) === 9) {
        return self::parseLegacyFolio($folio); // Formato viejo
    } elseif (strlen($folio) === 11) {
        return self::parseNewFolio($folio); // Formato nuevo
    }
    
    throw new \InvalidArgumentException("Invalid folio length: " . strlen($folio));
}
```

---

## 🚀 PLAN DE EJECUCIÓN INMEDIATA (HOY)

### Timeline Fase 1 (24 horas):

**15:00 - 17:00** (2h): Base de datos
- Crear migrations
- Correr migrations
- Seed work centers primarios

**17:00 - 18:00** (1h): Modelos
- WorkCenter model
- Relaciones en Organization, Quiz, PaperEvaluation

**18:00 - 20:00** (2h): Backend
- WorkCenterController básico
- Actualizar QuizController (store + showTemp)

**20:00 - 00:00** (4h): Frontend
- WorkCenters/Index.vue (básico)
- Actualizar Quiz/Create.vue (selector)
- Actualizar Quiz/Take.vue (display)

**00:00 - 02:00** (2h): Testing + Fixes
- Tests básicos
- Debugging
- Deploy a staging

**02:00 - 08:00** (6h): Buffer/Sleep

**08:00 - 10:00** (2h): Validación final
- Crear un work center de prueba
- Crear quiz con work center
- Verificar URL muestra correctamente
- Deploy a producción

---

## 📝 CHECKLIST PRE-DEPLOY

### Base de datos:
- [ ] **CRÍTICO: Backup de DB tomado** (`mysqldump` antes de empezar)
- [ ] Migration de work_centers ejecutada sin errores
- [ ] Migration de work_center_id en quizzes ejecutada
- [ ] Migration de work_center_id en paper_evaluations ejecutada
- [ ] Seeder MigrateToWorkCentersSeeder ejecutado exitosamente
- [ ] **Validación**: Todas las organizaciones tienen al menos 1 work center
  ```sql
  SELECT COUNT(*) FROM organizations WHERE id NOT IN 
    (SELECT DISTINCT organization_id FROM work_centers);
  -- Debe ser 0
  ```
- [ ] **Validación**: Todos los quizzes activos tienen work_center_id
  ```sql
  SELECT COUNT(*) FROM quizzes WHERE work_center_id IS NULL AND is_active = 1;
  -- Debe ser 0
  ```
- [ ] **Validación**: Evaluaciones completadas tienen work_center_id
  ```sql
  SELECT COUNT(*) FROM paper_evaluations 
  WHERE processing_status = 'completed' AND work_center_id IS NULL;
  -- Debe ser 0 (o muy bajo)
  ```
- [ ] **Validación**: No hay work_centers huérfanos
  ```sql
  SELECT COUNT(*) FROM work_centers WHERE organization_id NOT IN 
    (SELECT id FROM organizations);
  -- Debe ser 0
  ```
- [ ] **Validación**: Cada organización tiene exactamente 1 work center primario
  ```sql
  SELECT organization_id, COUNT(*) as primary_count 
  FROM work_centers WHERE is_primary = 1 
  GROUP BY organization_id HAVING primary_count != 1;
  -- Debe estar vacío
  ```

### Backend:
- [ ] WorkCenter model creado
- [ ] Relaciones actualizadas en Organization, Quiz, PaperEvaluation
- [ ] WorkCenterController con index, create, store
- [ ] QuizController actualizado (validación de work_center_id)
- [ ] Routes agregadas

### Frontend:
- [ ] WorkCenters/Index.vue funcional
- [ ] Quiz/Create.vue con selector de work center
- [ ] Quiz/Take.vue muestra nombre de centro
- [ ] Estilos consistentes

### Testing:
- [ ] WorkCenterTest básico pasa
- [ ] Crear quiz con work center funciona
- [ ] URL pública muestra organización + centro

---

## 🎯 ENTREGABLES MAÑANA (5 FEB, 2026)

1. **UI de gestión de work centers**: `/work-centers`
2. **Crear quiz requiere elegir centro**: Campo obligatorio
3. **URL pública del quiz muestra**: "JAROPAMEX - Planta Monterrey"
4. **Data migrada**: Todas las organizaciones tienen al menos 1 centro primario

**Criterio de éxito**: Usuario puede crear un quiz, elegir el centro de trabajo, y al compartir la URL, el evaluado ve claramente la organización y centro.

---

## 📞 CONTACTO DE EMERGENCIA

Si algo sale mal en el deploy de mañana:

1. **Rollback plan**: Revertir migrations con `php artisan migrate:rollback --step=3`
2. **Hotfix branch**: Crear rama `hotfix/work-centers-rollback`
3. **Data recovery**: Restaurar desde backup pre-deploy

---

**Última actualización**: 4 de febrero, 2026  
**Autor**: Architecture Partner (Claude)  
**Próxima revisión**: 5 de febrero, 2026 (post-deploy Fase 1)
