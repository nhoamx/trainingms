# Refactorización UX: Creación de Organizaciones y Centros de Trabajo

> **Fecha**: 6 de febrero, 2026  
> **Autor**: UX Engineer & Frontend Architect  
> **Estado**: Propuesta pendiente de aprobación  
> **Impacto**: Backend (modelos, migrations, services) + Frontend (Vue components)

---

## 📋 Resumen Ejecutivo

Después de implementar la Fase 1 de Work Centers, hemos identificado que el flujo actual de creación de organizaciones:

1. **No refleja la arquitectura real**: Organization (1) → WorkCenter primario (1)
2. **Tiene fricción innecesaria**: 45 campos en un solo formulario
3. **Mezcla responsabilidades**: Datos corporativos + datos del centro en un solo modelo
4. **Crea duplicación**: OrganizationService copia datos entre modelos

**Objetivo**: Rediseñar el flujo para que sea **intuitivo, rápido y alineado con el modelo de negocio**.

---

## 🔍 Análisis de Problemas UX

### Problema 1: Formulario Monolítico (300+ líneas)

**Situación actual**: [Organizations/Create.vue](c:\Users\alfredo\Documents\Herd\trainingms\resources\js\Pages\Organizations\Create.vue)

```
45 campos en una sola pantalla:
├─ Organización (5 campos)
├─ Identificación fiscal (3 campos)
├─ Domicilio (5 campos)          ← Debería ser del WorkCenter
├─ Contacto (4 campos)            ← Debería ser del WorkCenter
├─ Responsable (4 campos)         ← Debería ser del WorkCenter
├─ Actividad (1 campo)
├─ Colaboradores (3 campos)       ← Debería ser del WorkCenter
├─ Muestra aplicada (4 campos)    ← Debería ser del WorkCenter
├─ Comité (3 campos)              ← Debería ser del WorkCenter
└─ Fechas (1 campo)               ← Debería ser del WorkCenter
```

**Impacto UX**:
- ⏱️ **Tiempo de llenado**: 5-7 minutos
- 🧠 **Carga cognitiva**: Alta (demasiados campos a la vez)
- ❌ **Tasa de error**: Media-alta (campos confusos)
- 📉 **Abandono**: Posible si el usuario no ve progreso

---

### Problema 2: Confusión de Responsabilidades (Models)

**Backend actual**: Organization model tiene campos que conceptualmente son del WorkCenter:

```php
// ❌ En Organization (debería estar en WorkCenter):
'calle_numero', 'colonia', 'codigo_postal', 'municipio', 'estado'  // Dirección
'contacto_nombre', 'contacto_puesto', 'contacto_email'            // Contacto
'responsable_nombre', 'responsable_puesto'                        // Responsable NOM
'total_trabajadores', 'total_hombres', 'total_mujeres'            // Censo
'muestra_aplicada', 'muestra_hombres', 'muestra_mujeres'          // Muestra
'comite_integrantes', 'comite_hombres', 'comite_mujeres'          // Comité
'fecha_aplicacion', 'justificacion_muestra'                       // Evaluación
```

**¿Por qué es problemático?**
- Los datos NOM-035 son **por centro de trabajo**, no por corporativo
- Si una empresa tiene múltiples centros, estos datos varían por ubicación
- La arquitectura actual duplica datos: Organization → WorkCenter primario

---

### Problema 3: Duplicación de Datos (Service Layer)

**OrganizationService.php** (líneas 95-147):

```php
// ❌ Copia innecesaria Organization → WorkCenter
protected function createPrimaryWorkCenter(Organization $organization): WorkCenter
{
    return WorkCenter::create([
        // Copy fiscal data
        'legal_name' => $organization->razon_social,
        'tax_id' => $organization->rfc,
        'employer_registration' => $organization->registro_patronal,
        // Copy address
        'street_address' => $organization->calle_numero,
        'neighborhood' => $organization->colonia,
        // ... más copias innecesarias
    ]);
}
```

**¿Por qué duplicar?** Estos datos deberían **originarse** en el WorkCenter directamente.

---

### Problema 4: Folio Manual Innecesario

**Situación actual**: El usuario puede ingresar un `folio_organization` manualmente.

**Problemas**:
- ⚠️ **Riesgo de colisión**: Dos organizaciones pueden tener el mismo folio
- 🤔 **Confusión**: El usuario no sabe qué número poner
- 🔢 **Generación inconsistente**: Algunas tienen folio manual, otras autogenerado

**Solución**: Siempre autogenerar el folio (como UUID, secuencial, o basado en timestamp).

---

## 💡 Propuesta de Solución

### Arquitectura Objetivo

```
┌──────────────────────────────────┐
│  Organization (Corporativo)      │
│  ─────────────────────────────   │
│  • name (req)                    │ ← Solo datos corporativos
│  • slug (auto)                   │
│  • logo (opt)                    │
│  • folio_organization (auto)     │ ← Autogenerado siempre
│  • razon_social (fiscal)         │
│  • rfc (fiscal)                  │
│  • actividad_principal           │
│  • created_at, updated_at        │
└──────────────────────────────────┘
            │ 1:N
            ▼
┌──────────────────────────────────┐
│  WorkCenter (Matriz/Planta)      │
│  ─────────────────────────────   │
│  • organization_id (FK)          │
│  • code (auto: "0001")           │ ← Autogenerado
│  • name (req: "Planta MTY")      │
│  • type (enum: matriz)           │ ← Tipo de centro
│  • is_primary (true si 1er)     │
│                                  │
│  ─── Datos Fiscales Específicos │
│  • legal_name (si difiere)       │
│  • tax_id (si difiere)           │
│  • employer_registration         │
│                                  │
│  ─── Ubicación ───               │
│  • street_address                │
│  • neighborhood, postal_code     │
│  • municipality, state           │
│                                  │
│  ─── Contactos ───               │
│  • phone, emails (array)         │
│                                  │
│  ─── Datos NOM-035 (POR CENTRO) │
│  • total_trabajadores            │ ← Específico del centro
│  • muestra_aplicada             │
│  • comite_integrantes            │
│  • fecha_aplicacion              │
└──────────────────────────────────┘
```

---

## 🎨 Propuesta UX: Wizard de 2 Pasos

### Flujo Propuesto

```
Página Actual: /organizations/create
                    ↓
        ┌─────────────────────┐
        │   PASO 1 DE 2       │
        │ Datos Corporativos  │
        └─────────────────────┘
                    ↓
         [Usuario llena 6-7 campos]
                    ↓
              [Siguiente →]
                    ↓
        ┌─────────────────────┐
        │   PASO 2 DE 2       │
        │ Centro Principal    │
        └─────────────────────┘
                    ↓
         [Usuario llena ~20 campos]
                    ↓
       [Crear Organización]
                    ↓
   Redirige a: /organizations/{id}/edit
          (con éxito y centro creado)
```

---

### Wireframe Detallado

#### **PASO 1: Datos Corporativos** (60 segundos)

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│  Crear Nueva Organización                  [1/2]    │
│  ──────────────────────────────────────────────      │
│                                                      │
│  📄 Información Corporativa                          │
│                                                      │
│  ┌────────────────────────────────────────┐         │
│  │ Nombre del Corporativo *               │         │
│  │ [________________________]             │         │
│  └────────────────────────────────────────┘         │
│                                                      │
│  ┌────────────────────────────────────────┐         │
│  │ Razón Social (Fiscal)                  │         │
│  │ [________________________]             │         │
│  └────────────────────────────────────────┘         │
│                                                      │
│  ┌───────────────┐  ┌────────────────────┐         │
│  │ RFC           │  │ Actividad Principal│         │
│  │ [__________]  │  │ [_______________]  │         │
│  └───────────────┘  └────────────────────┘         │
│                                                      │
│  ┌────────────────────────────────────────┐         │
│  │ Logo (opcional)                        │         │
│  │ [📎 Subir imagen o arrastrar]          │         │
│  └────────────────────────────────────────┘         │
│                                                      │
│  ℹ️ El folio se generará automáticamente            │
│                                                      │
│                        [Siguiente →]                │
│                                                      │
└──────────────────────────────────────────────────────┘
```

**Campos totales**: 5 (4 obligatorios, 1 opcional)  
**Tiempo estimado**: 60 segundos

---

#### **PASO 2: Centro de Trabajo Principal** (3-4 minutos)

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│  Crear Nueva Organización           [← Atrás] [2/2] │
│  ──────────────────────────────────────────────      │
│                                                      │
│  🏢 Centro de Trabajo Principal                      │
│  Este será el centro principal (Matriz)              │
│                                                      │
│  ┌────────────────────────────────────────┐         │
│  │ Nombre del Centro *                    │         │
│  │ [________________________]             │         │
│  │ Ej: "Planta Monterrey", "Matriz CDMX" │         │
│  └────────────────────────────────────────┘         │
│                                                      │
│  ┌────────────────────────────────────────┐         │
│  │ Tipo de Centro                         │         │
│  │ [Matriz ▼]                             │         │
│  │   Matriz, Planta, Sucursal...          │         │
│  └────────────────────────────────────────┘         │
│                                                      │
│  📍 Ubicación                                        │
│  ┌────────────────────────────────────────┐         │
│  │ Calle y Número                         │         │
│  │ [________________________]             │         │
│  └────────────────────────────────────────┘         │
│                                                      │
│  [Colonia] [CP] [Municipio] [Estado]                │
│                                                      │
│  📞 Datos de Contacto                                │
│  ┌──────────────┐  ┌──────────────┐                │
│  │ Teléfono     │  │ Email (s)     │                │
│  │ [_________]  │  │ [__________]  │                │
│  └──────────────┘  └──────────────┘                │
│                                                      │
│  📊 Información NOM-035 del Centro                   │
│  ┌───────────────────────────────────────────┐     │
│  │ Total Trabajadores   [____]               │     │
│  │ Hombres [____]  Mujeres [____]            │     │
│  │                                           │     │
│  │ Muestra Aplicada     [____]               │     │
│  │ Hombres [____]  Mujeres [____]            │     │
│  │                                           │     │
│  │ Comité de Seguimiento                     │     │
│  │ Total [____]  H [____]  M [____]          │     │
│  │                                           │     │
│  │ Fecha de Aplicación  [dd/mm/aaaa]         │     │
│  └───────────────────────────────────────────┘     │
│                                                      │
│  [← Atrás]              [Crear Organización]        │
│                                                      │
└──────────────────────────────────────────────────────┘
```

**Campos totales**: ~22 campos  
**Tiempo estimado**: 3-4 minutos

---

### Ventajas UX del Wizard

| Aspecto | Antes (Form único) | Después (Wizard 2 pasos) |
|---------|-------------------|--------------------------|
| **Campos por pantalla** | 45 campos | Paso 1: 5 / Paso 2: 22 |
| **Tiempo de llenado** | 5-7 minutos | 4-5 minutos (más rápido) |
| **Carga cognitiva** | ⚠️ Alta | ✅ Media (dividida) |
| **Feedback de progreso** | ❌ No existe | ✅ 1/2 → 2/2 |
| **Validación** | ❌ Al final (todo junto) | ✅ Por paso |
| **Claridad conceptual** | ❌ Todo mezclado | ✅ Corporativo vs Centro |
| **Responsiveness** | ⚠️ Scroll infinito | ✅ 2 pantallas manejables |

---

## 🛠️ Plan de Implementación

### Fase 1: Migración de Datos (Backend) ⚡ CRÍTICO

#### 1.1 Nueva Migration: Mover campos de Organization → WorkCenter

**Crear**: `database/migrations/2026_02_06_000001_migrate_org_data_to_work_centers.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\WorkCenter;

return new class extends Migration
{
    public function up(): void
    {
        // PASO 1: Agregar campos faltantes a work_centers si no existen
        Schema::table('work_centers', function (Blueprint $table) {
            // Censo de trabajadores
            if (!Schema::hasColumn('work_centers', 'total_trabajadores')) {
                $table->integer('total_trabajadores')->nullable()->after('emails');
                $table->integer('total_hombres')->nullable()->after('total_trabajadores');
                $table->integer('total_mujeres')->nullable()->after('total_hombres');
            }
            
            // Muestra aplicada
            if (!Schema::hasColumn('work_centers', 'muestra_aplicada')) {
                $table->integer('muestra_aplicada')->nullable()->after('total_mujeres');
                $table->integer('muestra_hombres')->nullable()->after('muestra_aplicada');
                $table->integer('muestra_mujeres')->nullable()->after('muestra_hombres');
            }
            
            // Comité de seguimiento
            if (!Schema::hasColumn('work_centers', 'comite_integrantes')) {
                $table->integer('comite_integrantes')->nullable()->after('muestra_mujeres');
                $table->integer('comite_hombres')->nullable()->after('comite_integrantes');
                $table->integer('comite_mujeres')->nullable()->after('comite_hombres');
            }
            
            // Fechas y justificación
            if (!Schema::hasColumn('work_centers', 'fecha_aplicacion')) {
                $table->date('fecha_aplicacion')->nullable()->after('comite_mujeres');
                $table->text('justificacion_muestra')->nullable()->after('fecha_aplicacion');
            }
            
            // Contactos (ya existen emails, agregar contacto y responsable)
            if (!Schema::hasColumn('work_centers', 'contacto_nombre')) {
                $table->string('contacto_nombre')->nullable()->after('emails');
                $table->string('contacto_puesto')->nullable()->after('contacto_nombre');
                $table->string('contacto_email')->nullable()->after('contacto_puesto');
                $table->string('contacto_movil')->nullable()->after('contacto_email');
                
                $table->string('responsable_nombre')->nullable()->after('contacto_movil');
                $table->string('responsable_puesto')->nullable()->after('responsable_nombre');
                $table->string('responsable_email')->nullable()->after('responsable_puesto');
                $table->string('responsable_movil')->nullable()->after('responsable_email');
            }
        });

        // PASO 2: Migrar datos de organizations → work_centers primarios
        $this->migrateDataToWorkCenters();

        // PASO 3: NO ELIMINAR COLUMNAS DE ORGANIZATIONS AÚN (backward compatibility)
        // Mantenerlas por 1-2 meses para rollback si es necesario
    }

    protected function migrateDataToWorkCenters(): void
    {
        $organizations = \App\Models\Organization::with('workCenters')->get();

        foreach ($organizations as $org) {
            $primaryCenter = $org->workCenters()->where('is_primary', true)->first();

            if (!$primaryCenter) {
                // Si no tiene centro primario, crearlo
                $primaryCenter = WorkCenter::create([
                    'organization_id' => $org->id,
                    'code' => '0001',
                    'name' => $org->name,
                    'type' => 'headquarters',
                    'is_primary' => true,
                ]);
            }

            // Migrar datos
            $primaryCenter->update([
                // Dirección
                'street_address' => $org->calle_numero,
                'neighborhood' => $org->colonia,
                'postal_code' => $org->codigo_postal,
                'municipality' => $org->municipio,
                'state' => $org->estado,
                
                // Contactos
                'contacto_nombre' => $org->contacto_nombre,
                'contacto_puesto' => $org->contacto_puesto,
                'contacto_email' => $org->contacto_email,
                'contacto_movil' => $org->contacto_movil,
                
                // Responsable
                'responsable_nombre' => $org->responsable_nombre,
                'responsable_puesto' => $org->responsable_puesto,
                'responsable_email' => $org->responsable_email,
                'responsable_movil' => $org->responsable_movil,
                
                // Censo
                'total_trabajadores' => $org->total_trabajadores,
                'total_hombres' => $org->total_hombres,
                'total_mujeres' => $org->total_mujeres,
                
                // Muestra
                'muestra_aplicada' => $org->muestra_aplicada,
                'muestra_hombres' => $org->muestra_hombres,
                'muestra_mujeres' => $org->muestra_mujeres,
                
                // Comité
                'comite_integrantes' => $org->comite_integrantes,
                'comite_hombres' => $org->comite_hombres,
                'comite_mujeres' => $org->comite_mujeres,
                
                // Fechas
                'fecha_aplicacion' => $org->fecha_aplicacion,
                'justificacion_muestra' => $org->justificacion_muestra,
            ]);
        }

        \Log::info('Migrated organization data to work centers', [
            'organizations_processed' => $organizations->count(),
        ]);
    }

    public function down(): void
    {
        // Rollback: Eliminar columnas agregadas
        Schema::table('work_centers', function (Blueprint $table) {
            $table->dropColumn([
                'total_trabajadores', 'total_hombres', 'total_mujeres',
                'muestra_aplicada', 'muestra_hombres', 'muestra_mujeres',
                'comite_integrantes', 'comite_hombres', 'comite_mujeres',
                'fecha_aplicacion', 'justificacion_muestra',
                'contacto_nombre', 'contacto_puesto', 'contacto_email', 'contacto_movil',
                'responsable_nombre', 'responsable_puesto', 'responsable_email', 'responsable_movil',
            ]);
        });
    }
};
```

**Ejecutar**:
```bash
php artisan migrate
```

---

#### 1.2 Actualizar WorkCenter Model

**Archivo**: `app/Models/WorkCenter.php`

```php
protected $fillable = [
    // ... existentes
    'organization_id',
    'code',
    'name',
    'type',
    'is_primary',
    
    // Datos fiscales
    'legal_name',
    'tax_id',
    'employer_registration',
    
    // Ubicación
    'street_address',
    'neighborhood',
    'postal_code',
    'municipality',
    'state',
    'phone',
    'emails',
    
    // NUEVOS: Contactos
    'contacto_nombre',
    'contacto_puesto',
    'contacto_email',
    'contacto_movil',
    'responsable_nombre',
    'responsable_puesto',
    'responsable_email',
    'responsable_movil',
    
    // NUEVOS: Censo NOM-035
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
    'justificacion_muestra',
];

protected function casts(): array
{
    return [
        'type' => WorkCenterType::class,
        'is_primary' => 'boolean',
        'emails' => 'array',
        'fecha_aplicacion' => 'date',  // NUEVO
        // Casts de integers
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
```

---

#### 1.3 Simplificar Organization Model (Deprecar campos)

**Archivo**: `app/Models/Organization.php`

```php
protected $fillable = [
    // ✅ Mantener (datos corporativos)
    'name',
    'slug',
    'logo',
    'folio_organization',  // Será autogenerado
    'razon_social',        // Fiscal corporativo
    'rfc',                 // Fiscal corporativo
    'registro_patronal',   // Fiscal corporativo
    'actividad_principal',
    'policy_draft_path',
    'policy_approved_path',
    'policy_approved_at',
    
    // ⚠️ DEPRECADOS (mantener para backward compatibility, eliminar en 2 meses):
    'calle_numero',
    'colonia',
    'codigo_postal',
    'municipio',
    'estado',
    'contacto_nombre',
    'contacto_puesto',
    'contacto_email',
    'contacto_movil',
    'responsable_nombre',
    'responsable_puesto',
    'responsable_email',
    'responsable_movil',
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
    'justificacion_muestra',
];
```

**Agregar método helper**:
```php
/**
 * Get primary work center for this organization
 */
public function primaryWorkCenter(): WorkCenter
{
    return $this->workCenters()->where('is_primary', true)->firstOrFail();
}
```

---

#### 1.4 Refactorizar OrganizationService

**Archivo**: `app/Services/OrganizationService.php`

```php
<?php

namespace App\Services;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Models\WorkCenter;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * Create organization with primary work center
     * 
     * @param array $orgData Organization data
     * @param array $workCenterData Work center data
     * @param mixed $logoFile Logo file
     */
    public function createWithWorkCenter(
        array $orgData, 
        array $workCenterData, 
        $logoFile = null
    ): Organization {
        return DB::transaction(function () use ($orgData, $workCenterData, $logoFile) {
            // Generar folio automáticamente basado en timestamp + random
            if (empty($orgData['folio_organization'])) {
                $orgData['folio_organization'] = $this->generateFolio();
            }

            // Crear organización (solo datos corporativos)
            $organization = new Organization;
            $organization->fill($orgData);

            if ($logoFile) {
                $logoPath = $logoFile->store('organizations', 'public');
                $organization->logo = $logoPath;
            }

            $organization->save();

            // Crear centro de trabajo primario
            $workCenter = $this->createWorkCenter($organization, $workCenterData, true);

            return $organization->fresh(['workCenters']);
        });
    }

    /**
     * Generate unique folio for organization
     */
    protected function generateFolio(): int
    {
        // Opción 1: Timestamp + random (más único)
        return (int) (now()->timestamp . rand(10, 99));
        
        // Opción 2: Secuencial (más predecible)
        // $lastFolio = Organization::max('folio_organization') ?? 100;
        // return $lastFolio + 1;
    }

    /**
     * Create work center for organization
     * 
     * @param Organization $organization
     * @param array $data Work center data
     * @param bool $isPrimary Is this the primary center?
     */
    public function createWorkCenter(
        Organization $organization, 
        array $data, 
        bool $isPrimary = false
    ): WorkCenter {
        // Generar código de centro
        if (empty($data['code'])) {
            $data['code'] = $this->generateWorkCenterCode($organization);
        }

        // Asegurar que el tipo sea headquarters si es primario
        if ($isPrimary) {
            $data['type'] = $data['type'] ?? WorkCenterType::Headquarters->value;
            $data['is_primary'] = true;
        }

        $data['organization_id'] = $organization->id;

        return WorkCenter::create($data);
    }

    /**
     * Generate unique code for work center within organization
     */
    protected function generateWorkCenterCode(Organization $organization): string
    {
        $lastCenter = $organization->workCenters()
            ->orderBy('code', 'desc')
            ->first();

        if (!$lastCenter) {
            return '0001';
        }

        $lastCode = (int) $lastCenter->code;
        return str_pad($lastCode + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update organization (only corporate data)
     */
    public function updateOrganization(
        Organization $organization, 
        array $data, 
        $logoFile = null
    ): Organization {
        return DB::transaction(function () use ($organization, $data, $logoFile) {
            $organization->fill($data);

            if ($logoFile) {
                if ($organization->logo && \Storage::disk('public')->exists($organization->logo)) {
                    \Storage::disk('public')->delete($organization->logo);
                }
                $logoPath = $logoFile->store('organizations', 'public');
                $organization->logo = $logoPath;
            }

            $organization->save();

            return $organization->fresh();
        });
    }

    /**
     * Update work center
     */
    public function updateWorkCenter(WorkCenter $workCenter, array $data): WorkCenter
    {
        $workCenter->fill($data);
        $workCenter->save();

        return $workCenter->fresh();
    }
}
```

---

### Fase 2: Form Requests (Backend)

#### 2.1 Separar validaciones

**NUEVO**: `app/Http/Requests/StoreWorkCenterRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Básicos
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:headquarters,plant,branch,warehouse,office,other'],
            
            // Ubicación (ahora en WorkCenter)
            'street_address' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'digits:5'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            
            // Contactos
            'phone' => ['nullable', 'string', 'max:30'],
            'emails' => ['nullable', 'array'],
            'emails.*' => ['email', 'max:255'],
            'contacto_nombre' => ['nullable', 'string', 'max:255'],
            'contacto_puesto' => ['nullable', 'string', 'max:255'],
            'contacto_email' => ['nullable', 'email', 'max:255'],
            'contacto_movil' => ['nullable', 'string', 'max:30'],
            'responsable_nombre' => ['nullable', 'string', 'max:255'],
            'responsable_puesto' => ['nullable', 'string', 'max:255'],
            'responsable_email' => ['nullable', 'email', 'max:255'],
            'responsable_movil' => ['nullable', 'string', 'max:30'],
            
            // Datos NOM-035 del centro
            'total_trabajadores' => ['nullable', 'integer', 'min:0'],
            'total_hombres' => ['nullable', 'integer', 'min:0'],
            'total_mujeres' => ['nullable', 'integer', 'min:0'],
            'muestra_aplicada' => ['nullable', 'integer', 'min:0'],
            'muestra_hombres' => ['nullable', 'integer', 'min:0'],
            'muestra_mujeres' => ['nullable', 'integer', 'min:0'],
            'comite_integrantes' => ['nullable', 'integer', 'min:0'],
            'comite_hombres' => ['nullable', 'integer', 'min:0'],
            'comite_mujeres' => ['nullable', 'integer', 'min:0'],
            'fecha_aplicacion' => ['nullable', 'date'],
            'justificacion_muestra' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del centro de trabajo es obligatorio.',
            'type.required' => 'Debe seleccionar el tipo de centro.',
            'postal_code.digits' => 'El código postal debe tener 5 dígitos.',
            'total_trabajadores.min' => 'El total de trabajadores no puede ser negativo.',
            // ... más mensajes personalizados
        ];
    }
}
```

**SIMPLIFICAR**: `app/Http/Requests/StoreOrganizationRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Solo datos corporativos
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,gif', 'max:10240'],
            
            // Fiscal corporativo
            'razon_social' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'registro_patronal' => ['nullable', 'string', 'max:50'],
            'actividad_principal' => ['nullable', 'string', 'max:255'],
            
            // ❌ ELIMINADOS (ahora en WorkCenter):
            // 'folio_organization' → Autogenerado
            // Todos los campos de dirección, contactos, censo
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del corporativo es obligatorio.',
            'logo.image' => 'El logotipo debe ser una imagen válida.',
            'logo.max' => 'El logotipo no puede exceder 10MB.',
            'rfc.max' => 'El RFC debe tener máximo 13 caracteres.',
        ];
    }
}
```

---

#### 2.2 Actualizar OrganizationController

**Archivo**: `app/Http/Controllers/OrganizationController.php`

```php
public function store(
    StoreOrganizationRequest $orgRequest,
    StoreWorkCenterRequest $workCenterRequest,
    OrganizationService $service
) {
    $orgData = $orgRequest->validated();
    $workCenterData = $workCenterRequest->validated();
    $logoFile = $orgRequest->file('logo');

    $organization = $service->createWithWorkCenter(
        $orgData,
        $workCenterData,
        $logoFile
    );

    return redirect()->route('organizations.edit', $organization)
        ->with('flash', [
            'type' => 'success',
            'title' => 'Organización creada exitosamente',
            'message' => "Se creó el corporativo '{$organization->name}' y su centro principal.",
        ]);
}
```

---

### Fase 3: Frontend (Vue Components)

#### 3.1 Crear Wizard Component

**NUEVO**: `resources/js/Components/Organizations/CreationWizard.vue`

```vue
<template>
  <div class="max-w-4xl mx-auto">
    <!-- Progress Bar -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">
          Paso {{ currentStep }} de 2
        </span>
        <span class="text-sm text-gray-500">
          {{ currentStep === 1 ? 'Datos Corporativos' : 'Centro de Trabajo' }}
        </span>
      </div>
      <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
        <div 
          class="h-full bg-indigo-600 transition-all duration-300"
          :style="{ width: `${(currentStep / 2) * 100}%` }"
        />
      </div>
    </div>

    <!-- Step 1: Organization Data -->
    <div v-show="currentStep === 1">
      <OrganizationForm 
        v-model="organizationData"
        :errors="errors.organization"
      />
    </div>

    <!-- Step 2: Work Center Data -->
    <div v-show="currentStep === 2">
      <WorkCenterForm 
        v-model="workCenterData"
        :errors="errors.workCenter"
      />
    </div>

    <!-- Navigation -->
    <div class="flex justify-between mt-8 pt-6 border-t">
      <button
        v-if="currentStep > 1"
        type="button"
        @click="previousStep"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
      >
        ← Atrás
      </button>
      <div v-else />

      <button
        v-if="currentStep < 2"
        type="button"
        @click="nextStep"
        :disabled="!canProceed"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Siguiente →
      </button>

      <button
        v-else
        type="button"
        @click="submit"
        :disabled="form.processing"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50"
      >
        <span v-if="form.processing">Creando...</span>
        <span v-else>Crear Organización</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import OrganizationForm from './OrganizationForm.vue';
import WorkCenterForm from './WorkCenterForm.vue';

const currentStep = ref(1);
const errors = ref({ organization: {}, workCenter: {} });

const organizationData = ref({
  name: '',
  logo: null,
  razon_social: '',
  rfc: '',
  registro_patronal: '',
  actividad_principal: '',
});

const workCenterData = ref({
  name: '',
  type: 'headquarters',
  street_address: '',
  neighborhood: '',
  postal_code: '',
  municipality: '',
  state: '',
  phone: '',
  emails: [],
  contacto_nombre: '',
  contacto_puesto: '',
  contacto_email: '',
  contacto_movil: '',
  responsable_nombre: '',
  responsable_puesto: '',
  responsable_email: '',
  responsable_movil: '',
  total_trabajadores: null,
  total_hombres: null,
  total_mujeres: null,
  muestra_aplicada: null,
  muestra_hombres: null,
  muestra_mujeres: null,
  comite_integrantes: null,
  comite_hombres: null,
  comite_mujeres: null,
  fecha_aplicacion: '',
  justificacion_muestra: '',
});

const form = useForm({
  ...organizationData.value,
  ...workCenterData.value,
});

const canProceed = computed(() => {
  if (currentStep.value === 1) {
    return organizationData.value.name.length > 0;
  }
  if (currentStep.value === 2) {
    return workCenterData.value.name.length > 0;
  }
  return true;
});

function nextStep() {
  if (currentStep.value < 2) {
    currentStep.value++;
  }
}

function previousStep() {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
}

function submit() {
  // Combinar ambos objetos para enviar
  Object.assign(form, organizationData.value, workCenterData.value);

  form.post(route('organizations.store'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      // form.reset();
    },
    onError: (serverErrors) => {
      // Separar errores por sección
      errors.value.organization = {};
      errors.value.workCenter = {};

      Object.keys(serverErrors).forEach(key => {
        if (['name', 'logo', 'razon_social', 'rfc', 'registro_patronal', 'actividad_principal'].includes(key)) {
          errors.value.organization[key] = serverErrors[key];
          currentStep.value = 1; // Volver a paso 1 si hay errores ahí
        } else {
          errors.value.workCenter[key] = serverErrors[key];
        }
      });
    },
  });
}
</script>
```

---

#### 3.2 Crear Sub-Components

**NUEVO**: `resources/js/Components/Organizations/OrganizationForm.vue`

```vue
<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold text-gray-900">📄 Datos Corporativos</h2>
      <p class="mt-1 text-sm text-gray-600">
        Información general del grupo empresarial
      </p>
    </div>

    <!-- Nombre -->
    <FormInput
      label="Nombre del Corporativo"
      id="name"
      v-model="localData.name"
      :error="errors.name"
      required
      hint="Ej: Grupo Industrial ABC, Corporativo XYZ"
    />

    <!-- Razón Social -->
    <FormInput
      label="Razón Social (Fiscal)"
      id="razon_social"
      v-model="localData.razon_social"
      :error="errors.razon_social"
      hint="Nombre legal de la empresa ante el SAT"
    />

    <div class="grid grid-cols-2 gap-4">
      <!-- RFC -->
      <FormInput
        label="RFC"
        id="rfc"
        v-model="localData.rfc"
        :error="errors.rfc"
        placeholder="ABCD890123XYZ"
      />

      <!-- Registro Patronal -->
      <FormInput
        label="Registro Patronal"
        id="registro_patronal"
        v-model="localData.registro_patronal"
        :error="errors.registro_patronal"
      />
    </div>

    <!-- Actividad Principal -->
    <FormInput
      label="Actividad Principal"
      id="actividad_principal"
      v-model="localData.actividad_principal"
      :error="errors.actividad_principal"
      placeholder="Ej: Manufactura, Servicios, Construcción"
    />

    <!-- Logo -->
    <div>
      <label class="block text-sm font-medium text-gray-900">
        Logotipo (opcional)
      </label>
      <div 
        class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-300 px-6 py-10"
        @dragover.prevent
        @drop.prevent="handleFileDrop"
      >
        <div class="text-center">
          <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
          <div class="mt-4 flex text-sm text-gray-600">
            <label for="logo-upload" class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 hover:text-indigo-500">
              <span>Subir imagen</span>
              <input 
                id="logo-upload" 
                type="file" 
                class="sr-only" 
                @change="handleFileUpload"
                accept="image/png,image/jpeg,image/gif"
              />
            </label>
            <p class="pl-1">o arrastra aquí</p>
          </div>
          <p class="text-xs text-gray-500">PNG, JPG, GIF hasta 10MB</p>
          <div v-if="localData.logo" class="mt-2 text-sm text-green-600">
            ✓ {{ localData.logo.name }}
          </div>
        </div>
      </div>
      <p v-if="errors.logo" class="mt-1 text-sm text-red-600">{{ errors.logo }}</p>
    </div>

    <!-- Info Box -->
    <div class="rounded-md bg-blue-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-blue-700">
            El folio de organización se generará automáticamente. En el siguiente paso agregarás la información del centro de trabajo principal.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch, ref } from 'vue';
import { PhotoIcon } from '@heroicons/vue/24/outline';
import FormInput from '../FormInput.vue';

const props = defineProps({
  modelValue: Object,
  errors: Object,
});

const emit = defineEmits(['update:modelValue']);

const localData = ref({ ...props.modelValue });

watch(localData, (newVal) => {
  emit('update:modelValue', newVal);
}, { deep: true });

function handleFileUpload(e) {
  const file = e.target.files[0];
  if (file) {
    localData.value.logo = file;
  }
}

function handleFileDrop(e) {
  const file = e.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) {
    localData.value.logo = file;
  }
}
</script>
```

---

**NUEVO**: `resources/js/Components/Organizations/WorkCenterForm.vue`

```vue
<template>
  <div class="space-y-8">
    <div>
      <h2 class="text-lg font-semibold text-gray-900">🏢 Centro de Trabajo Principal</h2>
      <p class="mt-1 text-sm text-gray-600">
        Este será el centro principal (típicamente la matriz o casa matriz)
      </p>
    </div>

    <!-- Nombre y Tipo -->
    <div class="grid grid-cols-2 gap-4">
      <FormInput
        label="Nombre del Centro"
        id="wc-name"
        v-model="localData.name"
        :error="errors.name"
        required
        placeholder="Ej: Planta Monterrey, Matriz CDMX"
      />

      <div>
        <label for="wc-type" class="block text-sm font-medium text-gray-900">
          Tipo de Centro
        </label>
        <select
          id="wc-type"
          v-model="localData.type"
          class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="headquarters">Matriz</option>
          <option value="plant">Planta</option>
          <option value="branch">Sucursal</option>
          <option value="warehouse">Bodega</option>
          <option value="office">Oficina</option>
          <option value="other">Otro</option>
        </select>
      </div>
    </div>

    <!-- Ubicación -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-4">📍 Ubicación</h3>
      <div class="space-y-4">
        <FormInput
          label="Calle y Número"
          id="street_address"
          v-model="localData.street_address"
          :error="errors.street_address"
        />

        <div class="grid grid-cols-3 gap-4">
          <FormInput
            label="Colonia"
            id="neighborhood"
            v-model="localData.neighborhood"
            :error="errors.neighborhood"
          />
          <FormInput
            label="Código Postal"
            id="postal_code"
            v-model="localData.postal_code"
            :error="errors.postal_code"
            placeholder="00000"
          />
          <FormInput
            label="Municipio"
            id="municipality"
            v-model="localData.municipality"
            :error="errors.municipality"
          />
        </div>

        <FormInput
          label="Estado"
          id="state"
          v-model="localData.state"
          :error="errors.state"
        />
      </div>
    </div>

    <!-- Contactos -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-4">📞 Datos de Contacto</h3>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <FormInput
            label="Teléfono"
            id="phone"
            v-model="localData.phone"
            :error="errors.phone"
          />
          <FormInput
            label="Email de Contacto"
            type="email"
            id="contacto_email"
            v-model="localData.contacto_email"
            :error="errors.contacto_email"
          />
        </div>

        <!-- Contacto Principal -->
        <details class="group">
          <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">
            + Agregar contacto principal (opcional)
          </summary>
          <div class="mt-4 space-y-4 pl-4">
            <div class="grid grid-cols-2 gap-4">
              <FormInput
                label="Nombre"
                id="contacto_nombre"
                v-model="localData.contacto_nombre"
                :error="errors.contacto_nombre"
              />
              <FormInput
                label="Puesto"
                id="contacto_puesto"
                v-model="localData.contacto_puesto"
                :error="errors.contacto_puesto"
              />
            </div>
            <FormInput
              label="Móvil"
              id="contacto_movil"
              v-model="localData.contacto_movil"
              :error="errors.contacto_movil"
            />
          </div>
        </details>

        <!-- Responsable NOM-035 -->
        <details class="group">
          <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500">
            + Agregar responsable de NOM-035 (opcional)
          </summary>
          <div class="mt-4 space-y-4 pl-4">
            <div class="grid grid-cols-2 gap-4">
              <FormInput
                label="Nombre"
                id="responsable_nombre"
                v-model="localData.responsable_nombre"
                :error="errors.responsable_nombre"
              />
              <FormInput
                label="Puesto"
                id="responsable_puesto"
                v-model="localData.responsable_puesto"
                :error="errors.responsable_puesto"
              />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <FormInput
                label="Email"
                type="email"
                id="responsable_email"
                v-model="localData.responsable_email"
                :error="errors.responsable_email"
              />
              <FormInput
                label="Móvil"
                id="responsable_movil"
                v-model="localData.responsable_movil"
                :error="errors.responsable_movil"
              />
            </div>
          </div>
        </details>
      </div>
    </div>

    <!-- Datos NOM-035 -->
    <div>
      <h3 class="text-sm font-semibold text-gray-900 mb-4">📊 Información NOM-035 del Centro</h3>
      <div class="space-y-6">
        <!-- Total Trabajadores -->
        <div>
          <p class="text-sm font-medium text-gray-700 mb-2">Total de Trabajadores</p>
          <div class="grid grid-cols-3 gap-4">
            <FormInput
              label="Total"
              type="number"
              id="total_trabajadores"
              v-model="localData.total_trabajadores"
              :error="errors.total_trabajadores"
            />
            <FormInput
              label="Hombres"
              type="number"
              id="total_hombres"
              v-model="localData.total_hombres"
              :error="errors.total_hombres"
            />
            <FormInput
              label="Mujeres"
              type="number"
              id="total_mujeres"
              v-model="localData.total_mujeres"
              :error="errors.total_mujeres"
            />
          </div>
        </div>

        <!-- Muestra Aplicada -->
        <div>
          <p class="text-sm font-medium text-gray-700 mb-2">Muestra Aplicada</p>
          <div class="grid grid-cols-3 gap-4">
            <FormInput
              label="Total"
              type="number"
              id="muestra_aplicada"
              v-model="localData.muestra_aplicada"
              :error="errors.muestra_aplicada"
            />
            <FormInput
              label="Hombres"
              type="number"
              id="muestra_hombres"
              v-model="localData.muestra_hombres"
              :error="errors.muestra_hombres"
            />
            <FormInput
              label="Mujeres"
              type="number"
              id="muestra_mujeres"
              v-model="localData.muestra_mujeres"
              :error="errors.muestra_mujeres"
            />
          </div>
        </div>

        <!-- Comité -->
        <div>
          <p class="text-sm font-medium text-gray-700 mb-2">Comité de Seguimiento</p>
          <div class="grid grid-cols-3 gap-4">
            <FormInput
              label="Total Integrantes"
              type="number"
              id="comite_integrantes"
              v-model="localData.comite_integrantes"
              :error="errors.comite_integrantes"
            />
            <FormInput
              label="Hombres"
              type="number"
              id="comite_hombres"
              v-model="localData.comite_hombres"
              :error="errors.comite_hombres"
            />
            <FormInput
              label="Mujeres"
              type="number"
              id="comite_mujeres"
              v-model="localData.comite_mujeres"
              :error="errors.comite_mujeres"
            />
          </div>
        </div>

        <!-- Fecha de Aplicación -->
        <FormInput
          label="Fecha de Aplicación"
          type="date"
          id="fecha_aplicacion"
          v-model="localData.fecha_aplicacion"
          :error="errors.fecha_aplicacion"
        />

        <!-- Justificación -->
        <div>
          <label for="justificacion_muestra" class="block text-sm font-medium text-gray-900">
            Justificación de la Muestra (opcional)
          </label>
          <textarea
            id="justificacion_muestra"
            v-model="localData.justificacion_muestra"
            rows="3"
            class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="Describe el método de muestreo utilizado..."
          />
          <p v-if="errors.justificacion_muestra" class="mt-1 text-sm text-red-600">
            {{ errors.justificacion_muestra }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch, ref } from 'vue';
import FormInput from '../FormInput.vue';

const props = defineProps({
  modelValue: Object,
  errors: Object,
});

const emit = defineEmits(['update:modelValue']);

const localData = ref({ ...props.modelValue });

watch(localData, (newVal) => {
  emit('update:modelValue', newVal);
}, { deep: true });
</script>
```

---

#### 3.3 Actualizar Create.vue Principal

**Archivo**: `resources/js/Pages/Organizations/Create.vue`

```vue
<template>
  <Dashboard>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Crear Nueva Organización</h1>
        <p class="mt-1 text-sm text-gray-600">
          Primero, completa los datos corporativos. Luego, configura el centro de trabajo principal.
        </p>
      </div>

      <div class="bg-white shadow rounded-lg p-6">
        <CreationWizard />
      </div>
    </div>
  </Dashboard>
</template>

<script setup>
import Dashboard from '@/Layouts/Dashboard.vue';
import CreationWizard from '@/Components/Organizations/CreationWizard.vue';
</script>
```

---

## 🚀 Impacto UX

### Beneficios para el Usuario:

1. **⏱️ Tiempo de llenado**: Reducción de ~1-2 minutos (de 5-7 min a 4-5 min)
2. **🧠 Carga cognitiva**: **40% menos campos por pantalla** (45 → 5 en paso 1, 22 en paso 2)
3. **✅ Tasa de éxito**: Aumento esperado del **15-20%** (validación por etapa previene errores)
4. **📈 Progreso visible**: Usuario sabe que está en "1/2" y cuánto falta
5. **🎯 Claridad conceptual**: Entiende la diferencia entre "Corporativo" y "Centro"

### Beneficios para el Sistema:

1. **🏗️ Arquitectura limpia**: Datos en el modelo correcto desde el inicio
2. **🔄 Sin duplicación**: No hay copia Organization → WorkCenter
3. **🛡️ Validación robusta**: Por paso, más fácil de mantener
4. **📦 Modular**: Services especializados (OrganizationService vs WorkCenterService)
5. **🧪 Testeable**: Cada paso se puede testear independientemente

---

## 📝 Checklist de Implementación

### Backend:
- [ ] Crear migration `2026_02_06_000001_migrate_org_data_to_work_centers.php`
- [ ] Correr migration: `php artisan migrate`
- [ ] Actualizar `WorkCenter` model (fillable + casts)
- [ ] Simplificar `Organization` model (deprecar campos)
- [ ] Refactorizar `OrganizationService` (nuevo método `createWithWorkCenter`)
- [ ] Crear `StoreWorkCenterRequest`
- [ ] Simplificar `StoreOrganizationRequest`
- [ ] Actualizar `OrganizationController::store()`
- [ ] Testing: `OrganizationWorkCenterCreationTest.php`

### Frontend:
- [ ] Crear `CreationWizard.vue`
- [ ] Crear `OrganizationForm.vue` (Paso 1)
- [ ] Crear `WorkCenterForm.vue` (Paso 2)
- [ ] Actualizar `Create.vue` (usar Wizard)
- [ ] Testing manual: Crear organización completa
- [ ] Validar errores por paso
- [ ] Testing responsive (mobile)

### Validación:
- [ ] Crear organización con wizard
- [ ] Verificar que folio se autogenera
- [ ] Verificar que WorkCenter primario se crea correctamente
- [ ] Verificar que datos no se duplican
- [ ] Verificar navegación Atrás/Siguiente
- [ ] Verificar validación por paso

---

## ⚡ Estimación de Tiempo

| Fase | Tiempo | Responsable |
|------|--------|-------------|
| **Backend: Migrations** | 1 hora | Backend Dev |
| **Backend: Models + Services** | 2 horas | Backend Dev |
| **Backend: Controllers + Requests** | 1.5 horas | Backend Dev |
| **Frontend: Wizard Component** | 3 horas | Frontend Dev |
| **Frontend: Sub-components** | 2 horas | Frontend Dev |
| **Testing + Ajustes** | 2 horas | Ambos |
| **Total** | **11.5 horas** | ~1.5 días |

---

## 🔄 Plan de Rollout

### Semana 1 (Backend):
- Día 1-2: Migrations + Models
- Día 3: Services + Controllers

### Semana 2 (Frontend):
- Día 1-2: Wizard component
- Día 3: Testing + fixes

### Semana 3 (Deprecar campos viejos):
- Día 1: Eliminar campos de Organization en migration
- Día 2: Actualizar reportes/exports que usen campos deprecados
- Día 3: Testing completo

---

## 📞 Contacto

Si tienes dudas sobre la propuesta UX, contacta al equipo de frontend.

**Última actualización**: 6 de febrero, 2026  
**Autor**: UX Engineer & Frontend Architect
