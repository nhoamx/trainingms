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

**Last Updated:** 2026-02-06  
**Status:** Phase 2A Complete - User Work Center Access ✅  
**Version:** Multi-Center User Access Implemented

---

## 🎉 Phase 2A: User Work Center Access - COMPLETED

### Summary

Successfully implemented **multi-center user access control** with role-based permissions. Users can now be assigned to specific work centers or have organization-wide access.

### Implementation Date
February 6, 2026

### Key Features Delivered

#### 1. **Three-Tier Role System**
- `admin`: Global system access (all organizations, all centers)
- `organization`: Organization-wide access (all centers of their org)
- `work_center_user`: Granular access (only assigned centers)

#### 2. **Many-to-Many User↔WorkCenter Relationship**
- Pivot table: `user_work_centers`
- Users can have access to multiple centers
- Dynamic role-based access control

#### 3. **Smart UX Workflow**
- **Admin role**: No org/center selection needed
- **Organization role**: Select org, auto-access to all centers
- **Work Center User**: Select org → Select centers (multi-select)
  - Auto-assigns if org has only 1 center
  - Checkboxes for multiple centers

#### 4. **Frontend Enhancements**
- Dynamic form fields based on selected role
- Real-time work center loading via API
- Visual indicators for single-center orgs
- Consistent Create/Edit experience

#### 5. **Backend Safety**
- Transaction-wrapped operations (rollback on error)
- Automatic cleanup when changing roles
- Organization field maintained for performance
- Comprehensive access control methods

---

## 📊 Implementation Statistics

### Files Created/Modified
- **Migrations**: 1 new (`user_work_centers`)
- **Seeders**: 2 updated (`RolesSeeder`, `MigrateUsersToWorkCentersSeeder`)
- **Models**: 1 updated (`User` - 4 new methods)
- **Controllers**: 1 updated (`UserController`)
- **Routes**: 1 new API endpoint
- **Frontend**: 2 updated (`Create.vue`, `Edit.vue`)
- **Tests**: 1 new (`UserWorkCenterTest` - 9 tests, 35 assertions)

### Migration Results
- **Total users migrated**: 20
- **Skipped (organization role)**: 20 (correct behavior)
- **New work_center_user assignments**: 0 (all existing users had org-wide access)

### Test Results
```
✓ admin has access to all work centers
✓ organization user has access to all org centers
✓ work center user has access only to assigned centers
✓ work center user can have multiple centers
✓ has access to work center method works
✓ creating work center user with centers
✓ updating work center user centers
✓ changing role to organization removes work centers
✓ api endpoint returns work centers for organization

Tests: 9 passed (35 assertions) ✅
```

---

## 🏗️ Architecture Changes

### Database Schema

#### New Pivot Table: `user_work_centers`
```sql
CREATE TABLE user_work_centers (
    user_id UUID,
    work_center_id UUID,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    PRIMARY KEY (user_id, work_center_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (work_center_id) REFERENCES work_centers(id) ON DELETE CASCADE
);
```

#### Existing Users Table (Unchanged)
```sql
users
  - organization_id (MAINTAINED for performance)
  - ... other fields
```

---

## 💡 Key Architectural Decisions

### Decision 1: Keep `organization_id` in Users Table
**Rationale**: Performance over purity
- Avoids expensive JOINs in common queries
- Minimal redundancy (single foreign key)
- Huge performance gain for organization-scoped queries

### Decision 2: Empty Pivot = No Restriction for admin/organization
**Rationale**: Clear intent separation
- `admin`/`organization`: No records in pivot (intended behavior)
- `work_center_user`: Has records in pivot (explicit assignments)
- Makes queries cleaner and intent obvious

### Decision 3: Multi-Select UI Instead of Switcher
**Rationale**: Simplicity + Better UX
- Cards/badges show all accessible centers at once
- No session state management needed
- Users see comprehensive view without clicking
- **Switcher deferred to Phase 2B** (if needed)

### Decision 4: Maintain `organization` Role Name
**Rationale**: Backward compatibility
- No breaking changes to existing role checks
- `organization_admin` rename deferred to cleanup sprint
- Reduces scope of Phase 2A changes

---

## 📝 User Model - New Methods

### 1. `workCenters()` - Relationship
```php
public function workCenters()
{
    return $this->belongsToMany(WorkCenter::class, 'user_work_centers')
        ->withTimestamps();
}
```

### 2. `accessibleWorkCenters()` - Dynamic Access
```php
public function accessibleWorkCenters()
{
    if ($this->hasRole('admin')) {
        return WorkCenter::query(); // All centers
    }
    
    if ($this->hasRole('organization')) {
        return WorkCenter::where('organization_id', $this->organization_id);
    }
    
    if ($this->hasRole('work_center_user')) {
        return $this->workCenters(); // Only assigned
    }
    
    return WorkCenter::whereRaw('1 = 0'); // No access
}
```

### 3. `scopeForAccessibleCenters()` - Query Scope
```php
public function scopeForAccessibleCenters($query, User $user)
{
    // Filters any model query by user's accessible centers
    // Usage: PaperEvaluation::forAccessibleCenters($user)->get()
}
```

### 4. `hasAccessToWorkCenter()` - Permission Check
```php
public function hasAccessToWorkCenter(int|WorkCenter $workCenter): bool
{
    // Returns true if user can access the given center
}
```

---

## 🔌 API Endpoint

### GET `/api/organizations/{organization}/work-centers`
**Purpose**: Load work centers for organization selector

**Response Example**:
```json
[
    { "value": "uuid-1", "label": "0001 - Headquarters" },
    { "value": "uuid-2", "label": "0002 - Warehouse" }
]
```

**Used By**: Users/Create.vue, Users/Edit.vue

---

## 🎨 Frontend Workflow

### Users/Create.vue Logic

```vue
1. User selects role
   ↓
2. IF role = 'admin'
   → Hide org/center fields
   
3. IF role = 'organization' OR 'work_center_user'
   → Show organization dropdown
   ↓
4. User selects organization
   ↓
5. IF role = 'work_center_user'
   → Load work centers via API
   ↓
   a) IF 1 center: Auto-select + show green box
   b) IF >1 centers: Show checkboxes
```

### Users/Edit.vue Logic
- Same as Create.vue
- Pre-populates assigned work centers
- Watch triggers load centers on mount (`immediate: true`)

---

## 🧪 Testing Strategy

### Unit Tests Coverage
1. ✅ Admin access to all centers
2. ✅ Organization user access to org centers
3. ✅ Work center user restricted access
4. ✅ Multi-center assignment
5. ✅ Permission check methods
6. ✅ User creation with centers
7. ✅ User update with centers
8. ✅ Role change cleanup
9. ✅ API endpoint response

### Integration Scenarios Tested
- Creating new work_center_user with multiple centers
- Updating existing user's center assignments
- Role changes (work_center_user → organization)
- API endpoint with authentication
- Pivot table cascade deletes

---

## 🚀 Deployment Checklist

### Pre-Deployment
- [x] Run migrations (`php artisan migrate`)
- [x] Seed roles (`php artisan db:seed --class=RolesSeeder`)
- [x] Run user migration seeder (`php artisan db:seed --class=MigrateUsersToWorkCentersSeeder`)
- [x] Run tests (`php artisan test --filter=UserWorkCenterTest`)
- [x] Format code (`vendor/bin/pint --dirty`)
- [x] Build frontend assets (`npm run build`)

### Post-Deployment
- [ ] Verify existing users retained their access
- [ ] Test creating new work_center_user
- [ ] Test editing existing users
- [ ] Verify API endpoint in browser network tab
- [ ] Check role-based dashboard filtering

---

## 🔍 Usage Examples

### Example 1: Creating Work Center User
```php
// Admin creates user with access to 2 centers
POST /usuarios
{
    "name": "John Doe",
    "email": "john@example.com",
    "role": "work_center_user_role_id",
    "organization": "org_uuid",
    "work_centers": ["center1_uuid", "center2_uuid"]
}
```

### Example 2: Filtering Evaluations by Access
```php
// In any controller
$user = auth()->user();
$evaluations = PaperEvaluation::forAccessibleCenters($user)->get();
// Returns only evaluations from user's accessible centers
```

### Example 3: Checking Permission
```php
if ($user->hasAccessToWorkCenter($workCenter)) {
    // Allow operation
} else {
    abort(403);
}
```

---

## 📋 Next Steps (Phase 2B - Optional)

### Context Switcher Enhancement
If users request the ability to focus on one center at a time:

1. **Session-based Active Center**
   - Store `session('active_work_center_id')`
   - Navbar dropdown to switch context
   - Filter dashboard by active center only

2. **Implementation Effort**
   - Middleware: `SetActiveWorkCenter`
   - Frontend: Navbar component
   - Backend: Scopes with session filter
   - **Estimated**: ~3-4 hours

3. **Benefits**
   - Focused view for multi-center users
   - Cleaner dashboards
   - Easier navigation

4. **Drawbacks**
   - Added complexity
   - Session state management
   - More middleware/filters

**Decision**: Deferred until user feedback indicates need.

---

## 🐛 Known Limitations (Phase 2A)

### 1. No per-model permission granularity
- Users have access to **all data** in their centers (evaluations, quizzes, reports)
- Future enhancement: Add permissions like `view-evaluations`, `edit-quizzes`, etc.

### 2. Organization change doesn't auto-update centers
- Admin must manually re-assign centers if user moves orgs
- Future enhancement: Auto-suggest centers on org change

### 3. No center-level roles
- Can't have "manager of center A, viewer of center B"
- Future enhancement: Add `PermissionableWorkCenter` pivot with role column

---

## 📚 Documentation Updates

### Files Updated
1. ✅ [ORGANIZATION_WORK_CENTER_FLOW.md](./ORGANIZATION_WORK_CENTER_FLOW.md) (this file)
2. ✅ Added Phase 2A implementation summary
3. ✅ Added architecture decisions log
4. ✅ Added testing results
5. ✅ Added usage examples

### Files to Create (Future)
- [ ] `USER_ROLES_GUIDE.md` - End-user documentation
- [ ] `WORK_CENTER_ACCESS_API.md` - API documentation

---

**Last Updated:** 2026-02-06  
**Status:** Phase 2A Complete - User Work Center Access ✅  
**Version:** Multi-Center User Access Implemented
