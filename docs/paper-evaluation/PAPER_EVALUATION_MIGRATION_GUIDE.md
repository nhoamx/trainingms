# Migration Guide: From Legacy to New Paper Evaluation Storage

## Quick Start

This guide helps you transition from the legacy evaluation storage system to the new structured `paper_evaluations` table.

## What Changed?

### Before (Legacy)
```php
// Single evaluations table with JSON data
Evaluation::create([
    'document_id' => $documentId,
    'folio' => $folio,
    'personal_id' => $personalId,
    'organization_id' => $organization->id,
    'data' => $data, // All data in single JSON field
    'reference_guide' => $referenceGuide,
]);
```

### After (New System)
```php
// Structured paper_evaluations table
PaperEvaluation::create([
    'folio' => $folio,
    'evaluation_type_code' => '01',
    'organization_code' => '953',
    'personal_folio' => '0001',
    'organization_id' => $organization->id,
    'evaluation_type' => 'referencia_i',
    'source' => 'paper',
    'processing_status' => 'completed',
    'referencia_i_answers' => [...], // Structured by type
    'demographic_data' => [...],
    'raw_data' => [...], // Original for audit
]);
```

## Key Benefits

✅ **Type Safety**: Enum-based evaluation types and statuses  
✅ **Better Queries**: Indexed fields for fast filtering  
✅ **Error Tracking**: Built-in retry and error logging  
✅ **Audit Trail**: Raw data preserved alongside structured data  
✅ **Source Tracking**: Distinguish online vs paper evaluations  
✅ **Status Management**: Track processing lifecycle  

## What Stays the Same?

- Original `evaluations` table remains untouched
- Legacy data is still accessible
- No data migration required
- Python OCR processing unchanged
- Folio format remains identical (9 digits)

## Job Changes

### Old Job (Now ProcessEvaluationLegacy)
```php
use App\Jobs\ProcessEvaluation; // OLD

ProcessEvaluation::dispatch($fullPath, $containerName);
```

### New Job (ProcessPaperEvaluation)
```php
use App\Jobs\ProcessPaperEvaluation; // NEW

ProcessPaperEvaluation::dispatch($fullPath, $containerName);
```

The controller has been updated to use the new job automatically.

## Code Examples

### Creating Evaluations

```php
// Parse folio first
$folioData = PaperEvaluation::parseFolio('019530001');

// Create evaluation
$evaluation = PaperEvaluation::create([
    ...$folioData,
    'organization_id' => $organization->id,
    'source' => 'paper',
    'processing_status' => 'pending',
]);

// Mark as completed when done
$evaluation->markAsCompleted();
```

### Querying Evaluations

```php
// Get all Referencia I evaluations
$referenciaI = PaperEvaluation::ofType('referencia_i')->get();

// Get completed paper evaluations for an organization
$completed = PaperEvaluation::where('organization_id', $orgId)
    ->fromSource('paper')
    ->completed()
    ->get();

// Get failed evaluations for retry
$failed = PaperEvaluation::failed()
    ->where('retry_count', '<', 3)
    ->get();
```

### Accessing Data

```php
$evaluation = PaperEvaluation::find($id);

// Access type-specific data
if ($evaluation->evaluation_type === 'referencia_i') {
    $ptsdAnswers = $evaluation->referencia_i_answers;
}

if ($evaluation->evaluation_type === 'referencia_v') {
    $demographics = $evaluation->demographic_data;
}

// Access original OCR data
$originalData = $evaluation->raw_data;
```

## Testing

### Factory Usage

```php
// In your tests
use App\Models\PaperEvaluation;

// Create test evaluation
$evaluation = PaperEvaluation::factory()->create();

// Create specific type
$referenciaI = PaperEvaluation::factory()->referenciaI()->create();

// Create failed evaluation
$failed = PaperEvaluation::factory()->failed()->create();
```

### Test Example

```php
public function test_can_process_evaluation(): void
{
    $organization = Organization::factory()->create([
        'folio_organization' => '953',
    ]);

    $evaluation = PaperEvaluation::factory()->pending()->create([
        'organization_code' => '953',
    ]);

    $evaluation->markAsCompleted();

    $this->assertEquals('completed', $evaluation->fresh()->processing_status);
    $this->assertNotNull($evaluation->fresh()->processed_at);
}
```

## UI/UX Changes

### LoadEvaluation Component

The upload interface now includes:

1. **Real-time progress bar** during file upload
2. **Live status updates** via Laravel Reverb WebSocket
3. **Visual status indicators** (icons with colors)
4. **Better error messaging** with retry information

### Status Flow

```
📤 Uploading → 📋 Queued → ⚙️ Processing → ✅ Completed
                                          ↘ ❌ Failed
```

## Database Schema

Run the migration:

```bash
php artisan migrate
```

This creates the `paper_evaluations` table with:
- UUID primary key
- Structured folio components
- Type-specific JSON fields
- Processing status tracking
- Error handling fields
- Composite indexes for performance

## Rollback Plan

If you need to rollback:

1. **Controller**: Change back to `ProcessEvaluationLegacy`
2. **Migration**: Run `php artisan migrate:rollback`
3. **Legacy job**: The old system still works unchanged

## Performance Notes

### Indexes

The new table includes optimized indexes:

```sql
-- Individual indexes
INDEX idx_folio (folio)
INDEX idx_evaluation_type_code (evaluation_type_code)
INDEX idx_organization_code (organization_code)
INDEX idx_evaluation_type (evaluation_type)
INDEX idx_source (source)
INDEX idx_processing_status (processing_status)

-- Composite indexes
INDEX idx_org_type_date (organization_id, evaluation_type, created_at)
INDEX idx_type_source_status (evaluation_type, source, processing_status)
```

### Query Optimization

✅ **Good**: Use scopes and indexes
```php
PaperEvaluation::ofType('referencia_i')->completed()->get();
```

❌ **Avoid**: Direct JSON queries on large datasets
```php
// This works but is slower on large datasets
PaperEvaluation::whereJsonContains('raw_data->some_key', 'value')->get();
```

## Common Tasks

### 1. Find evaluation by folio
```php
$evaluation = PaperEvaluation::where('folio', '019530001')->first();
```

### 2. Get evaluations by organization
```php
$evaluations = PaperEvaluation::where('organization_id', $orgId)
    ->completed()
    ->orderBy('created_at', 'desc')
    ->get();
```

### 3. Retry failed evaluation
```php
$failed = PaperEvaluation::failed()->where('retry_count', '<', 3)->get();

foreach ($failed as $evaluation) {
    // Re-process the evaluation
    ProcessPaperEvaluation::dispatch(
        $evaluation->pdf_file_path,
        'training-and-ms'
    );
}
```

### 4. Generate report
```php
$report = PaperEvaluation::where('organization_id', $orgId)
    ->ofType('referencia_iii')
    ->completed()
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get()
    ->groupBy('evaluation_type');
```

## Troubleshooting

### Issue: Evaluation not processing

**Check:**
1. Processing status: `$evaluation->processing_status`
2. Error message: `$evaluation->processing_error`
3. Retry count: `$evaluation->retry_count`
4. Raw data: `$evaluation->raw_data`

### Issue: Organization not found

The system auto-creates organizations if not found:
```php
// Check if organization was created
$org = Organization::where('folio_organization', '953')->first();
```

### Issue: Invalid folio format

Ensure folio is exactly 9 digits:
```php
try {
    $parsed = PaperEvaluation::parseFolio($folio);
} catch (\InvalidArgumentException $e) {
    // Handle invalid folio
    Log::error("Invalid folio: {$folio}");
}
```

## Support & Documentation

- Full documentation: `docs/PAPER_EVALUATION_STORAGE.md`
- Test examples: `tests/Feature/ProcessPaperEvaluationTest.php`
- Job implementation: `app/Jobs/ProcessPaperEvaluation.php`
- Model: `app/Models/PaperEvaluation.php`

## Questions?

Common questions answered in main documentation:
- How is data structured by evaluation type?
- What are the composite indexes for?
- How does error handling work?
- What's the difference between online and paper source?
