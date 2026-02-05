# Organization Management with Work Centers - Updated Flow

## Overview

Starting from **Phase 1 (completed)**, the system now **automatically creates and syncs primary work centers** when organizations are created or updated.

This document describes the updated organization management workflow.

---

## Architecture Changes

### Before (Legacy)
```
Organization (standalone)
└── Contains all data: fiscal, address, contact, NOM-035 metrics
```

### After (Current)
```
Organization
├── Core data: name, logo, folio
├── Administrative data: contacts, responsible, activity
├── Evaluation metrics: total_trabajadores, muestra, comité, fecha
└── Has Many: WorkCenters
    └── Primary WorkCenter (code: 0001, type: headquarters)
        ├── Synced fiscal: legal_name, tax_id, employer_registration
        ├── Synced address: street_address, neighborhood, postal_code, municipality, state
        └── Synced contact: phone, email
```

---

## New Service Layer: `OrganizationService`

### Location
```
app/Services/OrganizationService.php
```

### Methods

#### `createWithWorkCenter(array $data, $logoFile = null): Organization`
Creates organization with automatic primary work center creation.

**Workflow:**
1. Validates/generates folio
2. Creates organization with provided data
3. Handles logo upload
4. **Automatically creates primary work center** (code: '0001', type: 'headquarters')
5. Copies fiscal, address, and contact data to work center
6. Returns organization with eager-loaded workCenters relation

**Transaction:** All operations wrapped in DB transaction (rollback on error)

#### `updateWithWorkCenter(Organization $org, array $data, $logoFile = null): Organization`
Updates organization and syncs primary work center data.

**Workflow:**
1. Updates organization data
2. Handles logo replacement
3. **Automatically syncs primary work center** with updated fiscal/address/contact data
4. Creates primary work center if missing (backward compatibility)
5. Returns organization with refreshed workCenters relation

**Transaction:** All operations wrapped in DB transaction

#### `createPrimaryWorkCenter(Organization $org): WorkCenter` (protected)
Internal method to create primary work center.

**Copies from Organization:**
- `name` → `name`
- `razon_social` → `legal_name`
- `rfc` → `tax_id`
- `registro_patronal` → `employer_registration`
- `calle_numero` → `street_address`
- `colonia` → `neighborhood`
- `codigo_postal` → `postal_code`
- `municipio` → `municipality`
- `estado` → `state`
- `contacto_movil` → `phone`
- `contacto_email` → `email`

**Not copied** (evaluation-specific data):
- `total_trabajadores`, `total_hombres`, `total_mujeres`
- `muestra_aplicada`, `muestra_hombres`, `muestra_mujeres`
- `comite_integrantes`, `comite_hombres`, `comite_mujeres`
- `fecha_aplicacion`, `justificacion_muestra`
- `contacto_nombre`, `contacto_puesto` (not in work center schema)
- `responsable_*` fields (organization-level only)

#### `syncPrimaryWorkCenterData(Organization $org): void` (protected)
Internal method to sync primary work center when organization updates.

---

## Updated Controller: `OrganizationController`

### Changes

#### Constructor
```php
public function __construct(
    protected OrganizationService $organizationService
) {}
```
Added dependency injection for `OrganizationService`.

#### `store(StoreOrganizationRequest $request)`
**Before:**
- Manual organization creation
- Logo handling in controller
- Folio generation in controller
- No work center creation

**After:**
```php
public function store(StoreOrganizationRequest $request)
{
    $data = $request->validated();
    $logoFile = $data['logo'] ?? null;

    // Delegates to service (creates org + primary work center)
    $organization = $this->organizationService->createWithWorkCenter($data, $logoFile);

    return redirect()->route('organizations.index')
        ->with('success', 'Organización y centro de trabajo primario creados exitosamente.');
}
```

**Benefits:**
- ✅ Automatic primary work center creation
- ✅ Transaction safety (rollback on error)
- ✅ Clean separation of concerns
- ✅ Testable service logic

#### `update(UpdateOrganizationRequest $request, Organization $organization)`
**Before:**
- Manual organization update
- Logo handling in controller
- No work center sync

**After:**
```php
public function update(UpdateOrganizationRequest $request, Organization $organization)
{
    $data = $request->validated();
    $logoFile = $data['logo'] ?? null;

    // Delegates to service (updates org + syncs work center)
    $organization = $this->organizationService->updateWithWorkCenter($organization, $data, $logoFile);

    return redirect()->route('organizations.edit', $organization)
        ->with('flash', [
            'type' => 'success',
            'title' => 'Organización actualizada exitosamente.',
            'message' => 'Los datos de la organización y su centro de trabajo primario han sido actualizados.',
        ]);
}
```

**Benefits:**
- ✅ Automatic work center sync
- ✅ Creates missing work center (backward compatibility)
- ✅ Transaction safety
- ✅ Consistent data between organization and primary work center

---

## Frontend (No Changes Required)

### Create Organization Form
**Location:** `resources/js/Pages/Organizations/Create.vue`

**Status:** ✅ No changes needed

The form continues to work exactly as before:
- Submits same fields to backend
- Backend handles work center creation automatically
- User sees success message including work center creation

### Edit Organization Form
**Location:** `resources/js/Pages/Organizations/Edit.vue`

**Status:** ✅ No changes needed (assumed similar to Create)

---

## Testing

### New Test Suite: `OrganizationServiceTest`
**Location:** `tests/Feature/OrganizationServiceTest.php`

**Coverage:** 6 tests, 36 assertions

#### Tests:
1. ✅ `test_create_organization_automatically_creates_primary_work_center`
   - Verifies organization creation
   - Verifies primary work center creation
   - Verifies data copying (fiscal, address, contact)
   - Verifies work center attributes (code: '0001', type: 'headquarters', is_primary: true)

2. ✅ `test_generates_folio_when_not_provided`
   - Verifies automatic folio generation (100-999)

3. ✅ `test_update_organization_syncs_primary_work_center`
   - Updates organization fiscal/address/contact data
   - Verifies primary work center synced with new data

4. ✅ `test_update_creates_primary_work_center_if_missing`
   - Handles organizations created before work center implementation
   - Creates missing primary work center on update

5. ✅ `test_handles_logo_upload`
   - Verifies logo storage
   - Uses Laravel Storage fake

6. ✅ `test_all_relevant_fields_copied_to_work_center`
   - Comprehensive field mapping validation
   - Verifies evaluation-specific fields NOT copied

### Running Tests
```bash
php artisan test --filter=OrganizationServiceTest --compact
# Result: 6/6 passing (36 assertions)
```

---

## Data Migration Notes

### Existing Organizations (Pre-Work Centers)
**Handled by:** `database/seeders/MigrateToWorkCentersSeeder.php` (already executed)

**Result:**
- ✅ 10 organizations migrated
- ✅ 10 primary work centers created
- ✅ 6,081 evaluations linked to work centers
- ✅ 3 quizzes linked to work centers

### Future Organization Updates
**Handled by:** `OrganizationService::syncPrimaryWorkCenterData()`

When existing organizations are edited:
1. Service checks for primary work center
2. If missing, creates it automatically
3. If exists, syncs data
4. **No manual intervention needed** ✅

---

## Backward Compatibility

### Organizations Without Work Centers
✅ **Fully supported**

When an old organization (created before work center implementation) is updated:
```php
protected function syncPrimaryWorkCenterData(Organization $organization): void
{
    $primaryCenter = $organization->workCenters()
        ->where('is_primary', true)
        ->first();

    // If no primary work center exists, create it
    if (!$primaryCenter) {
        $this->createPrimaryWorkCenter($organization);
        return;
    }
    
    // Otherwise, sync existing work center
    $primaryCenter->update([...]);
}
```

**Result:** System automatically fixes missing work centers on next edit.

---

## API/Integration Considerations

### Creating Organizations via API
If you have external integrations creating organizations:

**Before:**
```php
Organization::create($data);
```

**After:**
```php
app(OrganizationService::class)->createWithWorkCenter($data);
```

**Important:** Direct `Organization::create()` calls will NOT create work centers.

**Recommendation:** Update all organization creation code to use `OrganizationService`.

### Updating Organizations via API
Same principle applies to updates:

**Before:**
```php
$organization->update($data);
```

**After:**
```php
app(OrganizationService::class)->updateWithWorkCenter($organization, $data);
```

---

## Edge Cases Handled

### 1. Missing Fiscal Data
✅ **Handled:** Nullable fields in work center, uses `null` if organization field empty

### 2. Logo Upload Failure
✅ **Handled:** Transaction rollback, organization not created

### 3. Work Center Creation Failure
✅ **Handled:** Transaction rollback, organization not created

### 4. Concurrent Updates
✅ **Handled:** Database transactions prevent race conditions

### 5. Multiple Primary Work Centers
✅ **Prevented:** Service always creates exactly one primary (code: '0001')

---

## Future Enhancements

### Phase 2: Multiple Work Centers per Organization
**Scenario:** MAS BODEGA wants to add warehouse locations

**UI Flow:**
1. Admin navigates to Organization → Work Centers tab
2. Clicks "Add Work Center"
3. Fills form: code (e.g., '0002'), name, type (warehouse/branch/plant)
4. System validates unique code per organization
5. Work center created (is_primary: false)

**Backend:** Already implemented via `WorkCenterController` (full CRUD)

**Status:** Backend ready, UI pending

### Phase 3: Jaropamex Consolidation
**Scenario:** Merge 2 organizations into 1 with 2 work centers

**Approach:**
1. Create consolidation command/seeder
2. Convert "JAROPAMEX PLANTA 1" → Work Center code '0001'
3. Convert "JAROPAMEX PLANTA 3" → Work Center code '0002'
4. Update all evaluations/quizzes to unified organization
5. Soft delete duplicate organization

**Status:** Planned, not started

---

## Documentation Files

### Related Documentation
1. [WORK_CENTERS_PHASE_1_COMPLETE.md](./WORK_CENTERS_PHASE_1_COMPLETE.md)
   - Phase 1 implementation summary
   - Migration results
   - Database architecture
   - Testing results

2. [WORK_CENTERS_IMPLEMENTATION_PLAN.md](./WORK_CENTERS_IMPLEMENTATION_PLAN.md)
   - Original architecture planning
   - Decision log

3. This file: **ORGANIZATION_WORK_CENTER_FLOW.md**
   - Updated organization management workflow
   - Service layer documentation
   - Integration guide

---

## Quick Reference

### Key Files Modified
```
✅ app/Services/OrganizationService.php (new)
✅ app/Http/Controllers/OrganizationController.php (updated)
✅ tests/Feature/OrganizationServiceTest.php (new)
```

### Key Concepts
- **Primary Work Center:** code='0001', type='headquarters', is_primary=true
- **Auto-sync:** Fiscal/address/contact data copied from organization
- **Transaction Safety:** Create/update operations wrapped in DB transactions
- **Backward Compatibility:** Automatically fixes missing work centers

### Testing Commands
```bash
# Run all OrganizationService tests
php artisan test --filter=OrganizationServiceTest --compact

# Run all WorkCenter tests
php artisan test --filter=WorkCenterTest --compact

# Run both
php artisan test --filter="OrganizationServiceTest|WorkCenterTest" --compact
```

---

**Last Updated:** 2025-02-05  
**Status:** Production Ready ✅  
**Version:** Phase 1 Complete + Organization Flow Updated
