# Demographic Data Normalization Implementation

## Overview

This document describes the demographic data normalization feature that moves demographic information from unstructured JSON storage into a normalized relational table. This enables complex queries, aggregations, and report generation for psychosocial risk assessment data.

## Problem Statement

Previously, demographic data from paper-based evaluations (referencia_v type) was stored as JSON in the `demographic_data` column of the `paper_evaluations` table. This approach had several limitations:

- Difficult to perform complex SQL queries on demographic attributes
- Hard to aggregate and analyze demographic patterns
- No type safety or validation at the database level
- Inconsistent field naming across evaluations
- Difficult to maintain data integrity

## Solution Architecture

### New Database Table: `demographic_data`

A new normalized table stores demographic information with the following structure:

```sql
CREATE TABLE demographic_data (
    id CHAR(36) PRIMARY KEY,
    paper_evaluation_id CHAR(36) FOREIGN KEY,
    gender VARCHAR(255),
    age VARCHAR(255),
    estado_civil VARCHAR(255),
    nivel_estudios VARCHAR(255),
    puesto VARCHAR(255),
    area VARCHAR(255),
    tipo_puesto VARCHAR(255),
    tipo_contratacion VARCHAR(255),
    tipo_personal VARCHAR(255),
    tipo_jornada VARCHAR(255),
    rotacion_turnos VARCHAR(255),
    tiempo_puesto_actual VARCHAR(255),
    tiempo_experiencia_laboral VARCHAR(255),
    extra_fields JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Database Constraints

- **Primary Key**: UUID (`id`)
- **Foreign Key**: `paper_evaluation_id` references `paper_evaluations.id` with `ON DELETE CASCADE`
- **Extra Fields**: JSON column stores any unmapped demographic data for flexibility

## Implementation Details

### Models

#### DemographicData Model (`app/Models/DemographicData.php`)

- Uses `HasUuids` trait for UUID primary key generation
- Configured with 15 fillable attributes corresponding to demographic columns
- Casts `extra_fields` as JSON for automatic serialization
- `BelongsTo` relationship to `PaperEvaluation` model

**Usage:**
```php
$demographic = \App\Models\DemographicData::find($id);
$evaluation = $demographic->paperEvaluation; // Access parent evaluation
$extras = $demographic->extra_fields; // Access any extra fields
```

#### PaperEvaluation Model Updates

- Added `HasOne` relationship to `DemographicData` model
- Maintains backward compatibility with existing `demographic_data` JSON field
- Allows eager loading of normalized demographic data

**Usage:**
```php
$evaluation = \App\Models\PaperEvaluation::with('demographicData')->first();
$demo = $evaluation->demographicData; // Access normalized demographic data
```

### Jobs

#### ProcessPaperEvaluation Job Updates (`app/Jobs/ProcessPaperEvaluation.php`)

When processing paper-based referencia_v evaluations:

1. Extracts demographic data from OCR raw output
2. Maps Spanish field names to English database columns:
   - `genero` → `gender`
   - `edad` → `age`
   - `estado_civil` → `estado_civil`
   - `nivel_estudios` → `nivel_estudios`
   - `ocupacion_puesto` → `puesto`
   - `departamento_seccion_area` → `area`
   - `tipo_puesto` → `tipo_puesto`
   - `tipo_contratacion` → `tipo_contratacion`
   - `tipo_personal` → `tipo_personal`
   - `tipo_jornada` → `tipo_jornada`
   - `rotacion_turnos` → `rotacion_turnos`
   - `tiempo_puesto_actual` → `tiempo_puesto_actual`
   - `tiempo_experiencia_laboral` → `tiempo_experiencia_laboral`

3. Stores unmapped fields in `extra_fields` JSON column
4. Deletes existing demographic records before creating new ones (prevents duplicates)
5. Maintains backward compatibility by also storing raw JSON in `demographic_data` field

### Artisan Command

#### MigrateDemographicData Command (`app/Console/Commands/MigrateDemographicData.php`)

One-time command to migrate existing demographic data from the JSON field to the normalized table.

**Usage:**
```bash
# Run with confirmation
php artisan demographic-data:migrate

# Skip confirmation and run immediately
php artisan demographic-data:migrate --force
```

**Features:**
- Queries only referencia_v type evaluations with demographic data
- Skips evaluations that already have normalized data
- Shows progress bar during migration
- Logs detailed information about the process
- Handles extra fields gracefully
- Returns summary statistics

**Output Example:**
```
Starting demographic data migration...
Found 250 evaluations with demographic data
[==========================================] 250/250
✓ Migration completed successfully!
  - Migrated: 245
  - Skipped: 5
```

## Data Mapping Reference

### Field Mappings (Spanish to English)

| Spanish Field | English Column | Data Type | Notes |
|---|---|---|---|
| genero | gender | varchar | Masculino / Femenino |
| edad | age | varchar | Age range (e.g., "25 - 29") |
| estado_civil | estado_civil | varchar | Marital status |
| nivel_estudios | nivel_estudios | varchar | Educational level |
| ocupacion_puesto | puesto | varchar | Job position/occupation |
| departamento_seccion_area | area | varchar | Department/Section |
| tipo_puesto | tipo_puesto | varchar | Operational/Professional/Supervisor/Manager |
| tipo_contratacion | tipo_contratacion | varchar | Contract type |
| tipo_personal | tipo_personal | varchar | Syndical/Confidence/None |
| tipo_jornada | tipo_jornada | varchar | Work schedule type |
| rotacion_turnos | rotacion_turnos | varchar | Shift rotation |
| tiempo_puesto_actual | tiempo_puesto_actual | varchar | Time in current position |
| tiempo_experiencia_laboral | tiempo_experiencia_laboral | varchar | Years of work experience |

### Extra Fields Storage

Any demographic data that doesn't match the standard columns is automatically stored in the `extra_fields` JSON column:

```php
// If raw data contains unmapped fields
$demographicData = [
    'gender' => 'Masculino',
    'age' => '30 - 34',
    'unknown_field' => 'value'
];

// Result in database
[
    'gender' => 'Masculino',
    'age' => '30 - 34',
    'extra_fields' => {
        'unknown_field': 'value'
    }
]
```

## Migration Path

### Step 1: Run Database Migration
```bash
php artisan migrate
```

This creates the new `demographic_data` table with all required columns and constraints.

### Step 2: Migrate Existing Data (if applicable)
```bash
php artisan demographic-data:migrate --force
```

This one-time command migrates any existing demographic data from the JSON field to the normalized table. Evaluations without demographic data are skipped.

### Step 3: Verify Migration
```bash
# Check record count
SELECT COUNT(*) FROM demographic_data;

# Verify foreign key relationships
SELECT d.*, p.folio 
FROM demographic_data d
JOIN paper_evaluations p ON d.paper_evaluation_id = p.id
LIMIT 5;
```

## Backward Compatibility

The implementation maintains full backward compatibility:

- The original `demographic_data` JSON field in `paper_evaluations` table remains unchanged
- The `ProcessPaperEvaluation` job continues to populate the JSON field
- New records also populate the `DemographicData` table
- Applications can use either the JSON field or the normalized table
- Gradual migration is possible without breaking existing functionality

## Querying Examples

### Simple Queries

```php
// Get all female employees
$females = DemographicData::where('gender', 'Femenino')->get();

// Get employees in specific age range
$ageGroup = DemographicData::where('age', '30 - 34')->get();

// Get employees with specific contract type
$permanent = DemographicData::where('tipo_contratacion', 'Tiempo indeterminado')->get();
```

### Aggregation Queries

```php
// Count employees by gender
$genderDistribution = DemographicData::selectRaw('gender, COUNT(*) as count')
    ->groupBy('gender')
    ->get();

// Count employees by age group
$ageDistribution = DemographicData::selectRaw('age, COUNT(*) as count')
    ->groupBy('age')
    ->get();

// Get average time in current position by department
$byDepartment = DemographicData::selectRaw('area, tiempo_puesto_actual, COUNT(*) as count')
    ->groupBy('area', 'tiempo_puesto_actual')
    ->get();
```

### Relationship Queries

```php
// Get demographic data with evaluation details
$demographics = DemographicData::with('paperEvaluation')
    ->where('gender', 'Masculino')
    ->get();

// Get all referencia_v evaluations with demographic data
$evaluations = PaperEvaluation::ofType('referencia_v')
    ->with('demographicData')
    ->get();

// Filter by demographic criteria and get evaluation answers
$evaluations = PaperEvaluation::whereHas('demographicData', function($query) {
    $query->where('tipo_puesto', 'Gerente');
})->with('demographicData')->get();
```

## Performance Considerations

### Indexes

The migration creates the following indexes:
- **Primary Key**: UUID `id`
- **Foreign Key**: `paper_evaluation_id` (automatically indexed for constraint)

Consider adding additional indexes if frequently querying by demographic attributes:

```php
// In a future migration if needed
Schema::table('demographic_data', function (Blueprint $table) {
    $table->index('gender');
    $table->index('age');
    $table->index('tipo_puesto');
    $table->index('area');
    // Add more indexes based on query patterns
});
```

### Eager Loading

Always use eager loading to prevent N+1 queries:

```php
// ✓ Good - Eager load
$evaluations = PaperEvaluation::with('demographicData')->get();

// ✗ Bad - N+1 queries
$evaluations = PaperEvaluation::all();
foreach ($evaluations as $eval) {
    $demo = $eval->demographicData; // Query on each iteration
}
```

## Testing

The implementation includes:
- Model relationship tests
- Migration file creation and structure
- Factory for creating test data
- Command functionality tests

## Future Enhancements

Potential improvements for consideration:

1. **Data Validation**: Add model validation rules for demographic fields
2. **Audit Trail**: Track changes to demographic data with soft deletes
3. **Demographics Versioning**: Keep historical demographic snapshots
4. **Additional Indexes**: Add performance indexes based on query patterns
5. **Demographic Reports**: Create specialized reporting views
6. **Data Anonymization**: Implement PII masking for GDPR compliance

## Troubleshooting

### Foreign Key Constraint Issues

If experiencing foreign key constraint errors during migration:

```bash
# Check for orphaned demographic records
SELECT d.* FROM demographic_data d
LEFT JOIN paper_evaluations p ON d.paper_evaluation_id = p.id
WHERE p.id IS NULL;
```

### UUID Conflicts

Ensure the `paper_evaluations` table uses UUID primary keys:

```bash
php artisan migrate --path=/database/migrations/2025_10_09_235223_create_paper_evaluations_table.php
```

### Migration Command Issues

```bash
# Run with verbose output
php artisan demographic-data:migrate --verbose

# Check for exceptions in logs
tail -f storage/logs/laravel.log
```

## Related Files

- Model: `app/Models/DemographicData.php`
- Related Model: `app/Models/PaperEvaluation.php`
- Migration: `database/migrations/2025_11_17_000111_create_demographic_data_table.php`
- Factory: `database/factories/DemographicDataFactory.php`
- Job: `app/Jobs/ProcessPaperEvaluation.php`
- Command: `app/Console/Commands/MigrateDemographicData.php`
- Configuration: `config/referencia_v.php`

## References

- Laravel Migrations: https://laravel.com/docs/11.x/migrations
- Eloquent Relationships: https://laravel.com/docs/11.x/eloquent-relationships
- Model Factories: https://laravel.com/docs/11.x/database-testing#model-factories
- Artisan Commands: https://laravel.com/docs/11.x/artisan
