# Demographic Data Normalization

## Overview

The demographic data normalization system extracts and standardizes demographic information from paper-based evaluations (processed via OCR) and stores it in a normalized relational structure. The system supports both legacy OCR data formats and new evaluation structures automatically, ensuring backward compatibility while standardizing data representation.

## Problem Statement

Previously, demographic data from paper-based evaluations (referencia_v type) was stored as JSON in the `demographic_data` column of the `paper_evaluations` table. This approach had several limitations:

- Difficult to perform complex SQL queries on demographic attributes
- Hard to aggregate and analyze demographic patterns
- No type safety or validation at the database level
- Inconsistent field naming and value formats across evaluations
- Multiple JSON structure formats from different data sources (OCR vs manual entry)
- Difficult to maintain data integrity

## Solution: Automatic Structure Detection & Normalization

### Data Flow

```
PaperEvaluation.demographic_data (JSON)
            ↓
    [Structure Detection]
            ↓
    ┌───────┴──────────┐
    ↓                  ↓
New Format         Old OCR Format
(dados_laborales)  (decenas/unidades)
    ↓                  ↓
    └───────┬──────────┘
            ↓
Value Normalization & Mapping
            ↓
DemographicData Table (15 columns + extra_fields JSON)
```

### Database Table: `demographic_data`

Normalized table with all demographic attributes:

```sql
CREATE TABLE demographic_data (
    id CHAR(36) PRIMARY KEY,
    paper_evaluation_id CHAR(36) FOREIGN KEY ON DELETE CASCADE,
    gender VARCHAR(50),
    age VARCHAR(20),
    marital_status VARCHAR(100),
    education_level VARCHAR(100),
    position VARCHAR(255),
    department VARCHAR(255),
    position_type VARCHAR(100),
    contract_type VARCHAR(100),
    personnel_type VARCHAR(100),
    work_schedule VARCHAR(255),
    shift_rotation VARCHAR(100),
    time_in_current_position INT,
    work_experience VARCHAR(100),
    extra_fields JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Supported Data Structures

### Structure 1: New Nested Format

Newer evaluation records contain demographic data in a nested `datos_laborales` object:

```json
{
  "datos_laborales": {
    "ocupacion_puesto": "Software Developer",
    "area_departamento": "IT",
    "tipo_posicion": "profesional_o_tecnico",
    "tipo_contrato": "tiempo_indeterminado",
    "tipo_personal": "confianza",
    "horario_laboral": "fijo_diurno_(entre_las_6:00_y_20:00_hrs)",
    "rotacion_turnos": "no_aplica",
    "antiguedad_puesto": "5",
    "experiencia": {
      "anos": 10,
      "tipo": "entre_5_a_9_anos"
    }
  }
}
```

**Detection**: System checks for presence of `datos_laborales` key.

### Structure 2: Old OCR Format (Legacy)

Legacy OCR-processed records contain demographic data with object-type fields:

```json
{
  "edad": {
    "decenas": 2,
    "unidades": 7
  },
  "genero": {
    "fila1": "femenino"
  },
  "estado_civil": {
    "fila1": "casado"
  },
  "escolaridad": {
    "fila1": "primaria",
    "completado": true
  },
  "ocupacion_puesto": {
    "fila1": "Engineer",
    "fila2": ""
  },
  "tipo_posicion": {
    "fila1": "operativo"
  }
}
```

**Detection**: System checks for absence of `datos_laborales` key.

**Migration Status**: Successfully migrated 60 evaluations (58 old format, 2 new format).

### Structure 3: Likert Scale Format

Likert workplace climate evaluations provide demographic data with the following structure:

```json
{
  "areas": 1,
  "turno": "nocturno",
  "genero": "masculino",
  "puestos": 1,
  "tipo_contrato": "confianza",
  "questions": {
    "1": "A",
    "2": "A",
    "3": "A",
    "4": "A",
    "5": "A",
    // ... additional questions up to 23
  }
}
```

**Detection**: System checks for presence of `questions` key (Likert-specific).

**Mapped Fields**:
- `genero` → `gender` (normalized: Masculino / Femenino)
- `turno` → `work_schedule` (normalized: Fijo diurno / Nocturno / Rotativo)
- `tipo_contrato` → `contract_type` (normalized: Tiempo indeterminado, Confianza, etc)
- `areas` → `department` (numeric area code)
- `puestos` → `position` (numeric position code)
- `questions` → `extra_fields['questions']` (stored as JSON)

## Implementation Details

### Models

#### DemographicData Model (`app/Models/DemographicData.php`)

- Uses `HasUuids` trait for UUID primary key generation
- Configured with 15 fillable attributes in English
- Casts `extra_fields` as JSON for automatic serialization
- `BelongsTo` relationship to `PaperEvaluation` model

**Attributes**:
- `gender`, `age`, `marital_status`, `education_level`, `position`, `department`
- `position_type`, `contract_type`, `personnel_type`, `work_schedule`, `shift_rotation`
- `time_in_current_position`, `work_experience`, `extra_fields`

**Usage:**
```php
$demographic = \App\Models\DemographicData::find($id);
$evaluation = $demographic->paperEvaluation;
$extras = $demographic->extra_fields;
```

#### PaperEvaluation Model Updates

- Added `HasOne` relationship to `DemographicData` model
- Maintains backward compatibility with existing `demographic_data` JSON field
- Allows eager loading: `with('demographicData')`

**Usage:**
```php
$evaluation = \App\Models\PaperEvaluation::with('demographicData')->first();
$demo = $evaluation->demographicData; // Access normalized data
```

### Jobs

#### ProcessPaperEvaluation Job (`app/Jobs/ProcessPaperEvaluation.php`)

When processing paper-based evaluations:

1. **Automatic Structure Detection** (`extractDemographicInfo()`):
   - Checks for `questions` key → Likert format
   - Checks for `datos_laborales` key → New nested format
   - Falls back to old OCR format
   - Routes to appropriate extraction method

2. **Likert Format Extraction** (`extractFromLikert()`):
   - Extracts gender, work schedule, contract type, areas, and puestos
   - Normalizes enum values (gender, work schedule, contract type)
   - Stores all questions in `extra_fields['questions']` JSON

3. **New Format Extraction** (`extractFromNewStructure()`):
   - Directly maps nested labor data to English columns
   - Extracts education from `experiencia` sub-object
   - Converts enum values to proper format

4. **Old OCR Format Extraction** (`extractFromOldStructure()`):
   - Extracts values from `fila1`/`fila2` fields
   - Converts numeric age (`decenas` + `unidades`) to range format
   - Handles education with `completado` flag
   - Normalizes all enum values

5. **Normalization Helpers**:
   - `convertAgeToRange()` - Converts age number to range string
   - `normalizePosicionType()` - Maps position types
   - `normalizeContractType()` - Maps contract types
   - `normalizePersonnelType()` - Maps personnel types
   - `normalizeWorkSchedule()` - Maps work schedules
   - `normalizeYesNo()` - Maps yes/no values
   - `normalizeExperience()` - Maps experience ranges
   - `extractEducationLevel()` - Extracts education with completado status
   - `normalizeValue()` - General value normalization

5. **Storage**:
   - Deletes existing demographic record (prevents duplicates)
   - Creates new DemographicData entry
   - Stores unmapped values in `extra_fields` JSON

### Artisan Command

#### MigrateDemographicData Command (`app/Console/Commands/MigrateDemographicData.php`)

One-time command for existing demographic data migration.

**Usage:**
```bash
# Run with confirmation
php artisan demographic-data:migrate

# Skip confirmation
php artisan demographic-data:migrate --force
```

**Features:**
- Automatic structure detection (same logic as ProcessPaperEvaluation)
- Queries only referencia_v evaluations
- Skips already-migrated records
- Progress bar with detailed output
- Supports both old OCR and new nested formats
- Handles extra fields gracefully

**Migration Results**:
```
✓ Migration completed successfully!
  - Total evaluated: 60
  - Migrated: 58 (old OCR format)
  - Migrated: 2 (new nested format)
  - Skipped: 0 (all processed)
```

### Integration with Likert Scale Evaluations

When processing Likert workplace climate evaluations (evaluation type 05):

1. **Data Capture**: Likert data includes demographic information along with 23 climate questions
2. **Storage**: Data stored in `likert_answers` JSON field with structure:
   ```json
   {
     "questions": {...},
     "genero": "...",
     "turno": "...",
     "tipo_contrato": "...",
     "areas": "...",
     "puestos": "..."
   }
   ```
3. **Extraction**: `ProcessPaperEvaluation` job detects Likert structure by presence of `questions` key
4. **Normalization**: `extractFromLikert()` method normalizes enum values and maps fields to DemographicData table
5. **Result**: Demographics from Likert evaluations are stored alongside Referencia V data in the same normalized table

**Example Likert Demographic Extraction**:
```php
// Likert input
{
    "areas": 1,
    "turno": "nocturno",
    "genero": "masculino",
    "puestos": 1,
    "tipo_contrato": "confianza",
    "questions": {...}
}

// Normalized output in DemographicData
{
    "gender": "Masculino",
    "work_schedule": "Nocturno",
    "contract_type": "Confianza",
    "department": 1,
    "position": 1,
    "extra_fields": {
        "questions": {...}
    }
}
```



## Data Mapping Reference

### Field Mappings (Spanish to English)

| Spanish Field | English Column | Data Type | Notes |
|---|---|---|---|
| genero | gender | varchar | Masculino / Femenino |
| edad | age | varchar | Age range (e.g., "25 - 29") |
| estado_civil | marital_status | varchar | Marital status |
| escolaridad / nivel_estudios | education_level | varchar | Educational level |
| ocupacion_puesto | position | varchar | Job position/occupation |
| departamento_seccion_area | department | varchar | Department/Section |
| tipo_posicion | position_type | varchar | Operativo / Profesional o técnico / Directivo |
| tipo_contrato | contract_type | varchar | Tiempo indeterminado / Tiempo determinado |
| tipo_personal | personnel_type | varchar | Confianza / Sindicalizado |
| horario_laboral | work_schedule | varchar | Fijo diurno / Fijo nocturno / Rotativo |
| rotacion_turnos | shift_rotation | varchar | Sí / No / No aplica |
| antiguedad_puesto | time_in_current_position | int | Years in position |
| experiencia | work_experience | varchar | Experience range or description |

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
$permanent = DemographicData::where('contract_type', 'Tiempo indeterminado')->get();
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
$byDepartment = DemographicData::selectRaw('department, time_in_current_position, COUNT(*) as count')
    ->groupBy('department', 'time_in_current_position')
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
    $query->where('position_type', 'Directivo');
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
    $table->index('position_type');
    $table->index('department');
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

## Normalization Rules Reference

### Age Conversion
Converts numeric age (stored as `decenas` + `unidades` in old OCR format) to age range:

| Input | Output | Input | Output |
|-------|--------|-------|--------|
| 15-19 | 15 - 19 | 50-54 | 50 - 54 |
| 20-24 | 20 - 24 | 55-59 | 55 - 59 |
| 25-29 | 25 - 29 | 60+ | 60+ |
| 30-34 | 30 - 34 | | |
| 35-39 | 35 - 39 | | |
| 40-44 | 40 - 44 | | |
| 45-49 | 45 - 49 | | |

### Enum Value Mappings

#### Gender
```
masculino → Masculino
femenino → Femenino
```

#### Marital Status
```
soltero → Soltero
casado → Casado
union_libre → Unión libre
divorciado → Divorciado
viudo → Viudo
```

#### Position Type
```
operativo → Operativo
profesional_o_tecnico → Profesional o técnico
directivo → Directivo
```

#### Contract Type
```
tiempo_indeterminado → Tiempo indeterminado
tiempo_determinado → Tiempo determinado
contrato_temporal → Contrato temporal
```

#### Personnel Type
```
confianza → Confianza
sindicalizado → Sindicalizado
```

#### Work Schedule
```
fijo_diurno_(entre_las_6:00_y_20:00_hrs) → Fijo diurno (entre las 6:00 y 20:00 hrs)
fijo_nocturno_(entre_las_20:00_y_6:00_hrs) → Fijo nocturno (entre las 20:00 y 6:00 hrs)
rotativo → Rotativo
```

#### Experience Ranges
```
entre_1_a_4_anos → Entre 1 a 4 años
entre_5_a_9_anos → Entre 5 a 9 años
entre_10_a_19_anos → Entre 10 a 19 años
mas_de_20_anos → Más de 20 años
```

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
