# Organization & Work Centers - Sistema Actualizado

## 🎯 Estado Actual: Phase 1 Complete + Organization Flow Updated

---

## 📊 Flujo Actualizado: Crear Organización

### Antes (Legacy)
```
Usuario completa formulario → OrganizationController → Organization::create()
```
**Resultado:** Solo organización creada

### Ahora (Actualizado)
```
Usuario completa formulario
    ↓
OrganizationController::store()
    ↓
OrganizationService::createWithWorkCenter()
    ├─ DB::transaction START
    ├─ 1. Crear Organization con datos del formulario
    ├─ 2. Manejar logo upload
    ├─ 3. Crear WorkCenter primario automáticamente
    │   ├─ code: '0001'
    │   ├─ type: 'headquarters' (enum)
    │   ├─ is_primary: true
    │   └─ Copiar: fiscal + address + contact
    ├─ DB::transaction COMMIT
    └─ Return organization con workCenters eager-loaded
```
**Resultado:** Organización + Work Center primario creados automáticamente ✅

---

## 🔄 Flujo Actualizado: Editar Organización

### Antes (Legacy)
```
Usuario edita datos → OrganizationController → Organization::update()
```
**Resultado:** Solo organización actualizada

### Ahora (Actualizado)
```
Usuario edita datos
    ↓
OrganizationController::update()
    ↓
OrganizationService::updateWithWorkCenter()
    ├─ DB::transaction START
    ├─ 1. Actualizar Organization con nuevos datos
    ├─ 2. Manejar logo si cambió
    ├─ 3. Sincronizar WorkCenter primario automáticamente
    │   ├─ Si existe → actualizar con nuevos datos
    │   └─ Si no existe → crear (backward compatibility)
    ├─ DB::transaction COMMIT
    └─ Return organization con workCenters refreshed
```
**Resultado:** Organización + Work Center primario sincronizados ✅

---

## 🗂️ Mapeo de Datos: Organization → WorkCenter

### ✅ Campos que SE COPIAN (sincronizados)
| Organization | WorkCenter | Propósito |
|-------------|-----------|-----------|
| `name` | `name` | Nombre del centro |
| `razon_social` | `legal_name` | Identificación fiscal |
| `rfc` | `tax_id` | RFC |
| `registro_patronal` | `employer_registration` | Registro patronal |
| `calle_numero` | `street_address` | Dirección completa |
| `colonia` | `neighborhood` | Colonia/barrio |
| `codigo_postal` | `postal_code` | CP |
| `municipio` | `municipality` | Municipio |
| `estado` | `state` | Estado |
| `contacto_movil` | `phone` | Teléfono |
| `contacto_email` | `email` | Email |

### ❌ Campos que NO SE COPIAN (específicos de evaluación)
| Campo Organization | ¿Por qué NO se copia? |
|-------------------|---------------------|
| `total_trabajadores` | Varía por periodo de evaluación |
| `total_hombres` | Varía por periodo de evaluación |
| `total_mujeres` | Varía por periodo de evaluación |
| `muestra_aplicada` | Específico de cada aplicación NOM-035 |
| `muestra_hombres` | Específico de cada aplicación NOM-035 |
| `muestra_mujeres` | Específico de cada aplicación NOM-035 |
| `comite_integrantes` | Varía con el comité de la evaluación |
| `comite_hombres` | Varía con el comité de la evaluación |
| `comite_mujeres` | Varía con el comité de la evaluación |
| `fecha_aplicacion` | Específico de cada ciclo de evaluación |
| `justificacion_muestra` | Documento por periodo |
| `contacto_nombre` | No existe en work_centers (solo en org) |
| `contacto_puesto` | No existe en work_centers (solo en org) |
| `responsable_*` | Datos administrativos de la org |
| `actividad_principal` | Descriptivo de la organización |
| `logo` | Solo a nivel organización |
| `folio_organization` | Identificador único de la org |

---

## 🏗️ Arquitectura de 3 Capas

```
┌────────────────────────────────────────────────────────────┐
│  FRONTEND (Vue/Inertia)                                    │
│  resources/js/Pages/Organizations/Create.vue               │
│  resources/js/Pages/Organizations/Edit.vue                 │
│  ✅ Sin cambios necesarios                                 │
└────────────────────────────────────────────────────────────┘
                         │
                         │ POST /organizations
                         │ PUT /organizations/{id}
                         ▼
┌────────────────────────────────────────────────────────────┐
│  CONTROLLER LAYER                                          │
│  app/Http/Controllers/OrganizationController.php           │
│                                                             │
│  store(Request) {                                          │
│    service->createWithWorkCenter($data, $logo)             │
│  }                                                          │
│                                                             │
│  update(Request, Organization) {                           │
│    service->updateWithWorkCenter($org, $data, $logo)       │
│  }                                                          │
└────────────────────────────────────────────────────────────┘
                         │
                         │ Delegate business logic
                         ▼
┌────────────────────────────────────────────────────────────┐
│  SERVICE LAYER (New!)                                      │
│  app/Services/OrganizationService.php                      │
│                                                             │
│  createWithWorkCenter():                                   │
│    - Generate folio if missing                             │
│    - Create organization                                   │
│    - Handle logo upload                                    │
│    - Create primary work center                            │
│    - Transaction safety                                    │
│                                                             │
│  updateWithWorkCenter():                                   │
│    - Update organization                                   │
│    - Handle logo replacement                               │
│    - Sync primary work center                              │
│    - Create if missing (backward compat.)                  │
│    - Transaction safety                                    │
└────────────────────────────────────────────────────────────┘
                         │
                         │ Eloquent ORM
                         ▼
┌────────────────────────────────────────────────────────────┐
│  DATABASE                                                   │
│                                                             │
│  organizations (1)  ←─┐                                    │
│  - id                  │ 1:N                               │
│  - name                └─→ work_centers (N)                │
│  - razon_social            - organization_id (FK)          │
│  - rfc                     - code (unique per org)         │
│  - registro_patronal       - name                          │
│  - calle_numero            - type (enum)                   │
│  - colonia                 - is_primary                    │
│  - codigo_postal           - legal_name (synced)           │
│  - municipio               - tax_id (synced)               │
│  - estado                  - street_address (synced)       │
│  - contacto_movil          - phone (synced)                │
│  - contacto_email          - email (synced)                │
│  - ... (evaluation data)   - ... (location data)           │
└────────────────────────────────────────────────────────────┘
```

---

## ✅ Beneficios del Nuevo Flujo

### 1. Automatización Total
- ✅ Usuario no necesita crear work centers manualmente
- ✅ Sistema garantiza que toda organización tiene work center primario
- ✅ Backward compatibility: crea work centers faltantes en updates

### 2. Consistencia de Datos
- ✅ Organización y work center siempre sincronizados
- ✅ Cambios en datos fiscales/dirección se reflejan automáticamente
- ✅ No hay discrepancias entre org y work center

### 3. Seguridad Transaccional
- ✅ Si falla creación de work center → rollback de organización
- ✅ Si falla sync de work center → rollback de cambios
- ✅ Base de datos siempre en estado consistente

### 4. Separación de Responsabilidades
- ✅ Controller: solo coordinación HTTP
- ✅ Service: lógica de negocio
- ✅ Model: persistencia ORM
- ✅ Código más fácil de mantener y testear

---

## 🧪 Testing Completo

### OrganizationServiceTest (6 tests, 36 assertions)
```bash
php artisan test --filter=OrganizationServiceTest --compact
```

| Test | Verifica |
|------|----------|
| `test_create_organization_automatically_creates_primary_work_center` | Creación automática de work center |
| `test_generates_folio_when_not_provided` | Generación de folio |
| `test_update_organization_syncs_primary_work_center` | Sincronización en updates |
| `test_update_creates_primary_work_center_if_missing` | Backward compatibility |
| `test_handles_logo_upload` | Manejo de logos |
| `test_all_relevant_fields_copied_to_work_center` | Mapeo completo de campos |

**Resultado:** ✅ 6/6 pasando

### WorkCenterTest (12 tests, 26 assertions)
```bash
php artisan test --filter=WorkCenterTest --compact
```
**Resultado:** ✅ 12/12 pasando

---

## 📈 Datos Migrados

### Resultados de MigrateToWorkCentersSeeder
```
✅ 10 organizaciones → 10 work centers primarios
✅ 6,081 evaluaciones → migradas a work centers
✅ 3 quizzes → migrados a work centers
❌ 22 evaluaciones huérfanas (sin organization_id) → esperado
```

### Distribución de Datos
| Organización | Evaluaciones | Quizzes | Work Centers |
|-------------|--------------|---------|--------------|
| JAROPAMEX PLANTA 1 | 3,502 | 0 | 1 (primario) |
| JAROPAMEX PLANTA 3 | 2,339 | 0 | 1 (primario) |
| CORPORACION INDUSTRIAL | 122 | 0 | 1 (primario) |
| Empresa DEMO | 100 | 0 | 1 (primario) |
| SEVEN | 13 | 3 | 1 (primario) |
| Otros (5 orgs) | 5 | 0 | 5 (primarios) |

---

## 🔮 Próximos Pasos

### Phase 2: Work Center Selector en Quizzes
**Objetivo:** Permitir seleccionar work center al crear quiz

**Cambios necesarios:**
1. ✅ Backend: WorkCenterController ya tiene CRUD completo
2. ❌ Frontend: Agregar selector en formulario de crear quiz
3. ❌ Validación: Hacer work_center_id requerido en CreateQuizRequest

**Estimado:** 2-4 horas

### Phase 3: Múltiples Work Centers (MAS BODEGA)
**Objetivo:** Permitir agregar bodegas/sucursales

**Cambios necesarios:**
1. ✅ Backend: CRUD ya implementado
2. ❌ Frontend: UI para gestionar work centers de una organización
3. ❌ Rutas: Work centers tab en página de organización

**Estimado:** 1-2 días

### Phase 4: Consolidación Jaropamex
**Objetivo:** 2 organizaciones → 1 organización con 2 work centers

**Cambios necesarios:**
1. ❌ Seeder/Command: Consolidar datos
2. ❌ Migrar evaluaciones a organización unificada
3. ❌ Soft delete organización duplicada

**Estimado:** 4-6 horas

---

## 📚 Documentación

### Archivos Generados
1. ✅ [ORGANIZATION_WORK_CENTER_FLOW.md](./ORGANIZATION_WORK_CENTER_FLOW.md)
   - Flujo completo actualizado
   - Guía de integración
   - Mapeo de campos

2. ✅ [WORK_CENTERS_PHASE_1_COMPLETE.md](./WORK_CENTERS_PHASE_1_COMPLETE.md)
   - Resumen de Phase 1
   - Resultados de migración
   - Roadmap

3. ✅ [WORK_CENTERS_IMPLEMENTATION_PLAN.md](./WORK_CENTERS_IMPLEMENTATION_PLAN.md)
   - Plan original
   - Decision log

---

## 🚀 Estado del Branch

```bash
feature/work-centers (36fc991)
```

### Commits
1. `b331d98` - feat: Implement WorkCenter architecture with migration and enum
2. `36fc991` - feat: Update Organization workflow with automatic Work Center management

### Listo para merge a main
- ✅ Todos los tests pasando (18/18)
- ✅ Código formateado con Pint
- ✅ Documentación completa
- ✅ Backward compatibility garantizada
- ✅ Sin breaking changes en frontend

---

**Generado:** 2025-02-05  
**Branch:** feature/work-centers  
**Commit:** 36fc991  
**Status:** Ready for Production ✅
