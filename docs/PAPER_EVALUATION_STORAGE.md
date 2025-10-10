# Paper Evaluation Storage System

## Overview

This document describes the improved evaluation storage system for NOM-035-STPS-2018 compliance. The new system provides a structured, maintainable approach to storing evaluation data from both paper-based (OCR-processed) and online assessments.

## Background

Previously, the system stored evaluation data in a generic JSON format within the `evaluations` table. While functional, this approach had several limitations:
- Difficult to query specific evaluation types
- Hard to generate reports and analytics
- Mixed online and paper evaluation data
- Unclear data structure for different evaluation types
- Limited error handling and retry mechanisms

## New Architecture

### Database Structure

The new `paper_evaluations` table provides a structured approach to storing evaluation data:

#### Table: `paper_evaluations`

| Column | Type | Description |
|--------|------|-------------|
| `id` | UUID | Primary key |
| `folio` | String(9) | Unique folio identifier (indexed) |
| `evaluation_type_code` | String(2) | 01, 02, 03, or 04 (indexed) |
| `organization_code` | String(3) | Organization identifier from folio |
| `personal_folio` | String(4) | Personal identifier from folio |
| `organization_id` | UUID (FK) | Reference to organizations table |
| `evaluation_type` | Enum | referencia_i, referencia_iii, referencia_v, cisneros |
| `source` | Enum | online, paper (indexed) |
| `processing_status` | Enum | pending, processing, completed, failed (indexed) |
| `pdf_file_path` | String | Path to original PDF file |
| `processed_at` | Timestamp | When processing completed |
| `demographic_data` | JSON | Referencia V demographic information |
| `referencia_i_answers` | JSON | Guide I PTSD assessment answers |
| `referencia_iii_answers` | JSON | Reference III workplace questions |
| `referencia_iii_conditional` | JSON | Conditional questions (customer service, management) |
| `cisneros_answers` | JSON | Cisneros mobbing scale answers |
| `raw_data` | JSON | Original OCR output for auditing |
| `processing_error` | Text | Error message if processing failed |
| `retry_count` | Integer | Number of processing retries |
| `created_at` | Timestamp | Record creation time |
| `updated_at` | Timestamp | Last update time |
| `deleted_at` | Timestamp | Soft delete timestamp |

#### Composite Indexes

1. `idx_org_type_date`: (organization_id, evaluation_type, created_at) - For organizational reports
2. `idx_type_source_status`: (evaluation_type, source, processing_status) - For filtering and dashboards

### Folio Structure

Folios are 9-digit identifiers structured as follows:

```
XX YYY ZZZZ
│  │   └── Personal folio (4 digits)
│  └────── Organization code (3 digits)
└───────── Evaluation type code (2 digits)
```

**Evaluation Type Codes:**
- `01` - Referencia I (PTSD Assessment)
- `02` - Referencia III (Workplace Factors)
- `03` - Referencia V (Demographics)
- `04` - Cisneros (Mobbing Scale)

**Example:** `019530001`
- Type: `01` (Referencia I)
- Organization: `953`
- Personal: `0001`

## Model: PaperEvaluation

### Key Methods

#### `parseFolio(string $folio): array`
Parses a 9-digit folio into its components.

```php
$parsed = PaperEvaluation::parseFolio('019530001');
// Returns:
[
    'folio' => '019530001',
    'evaluation_type_code' => '01',
    'organization_code' => '953',
    'personal_folio' => '0001',
    'evaluation_type' => 'referencia_i'
]
```

#### `getEvaluationTypeFromCode(string $code): string`
Converts evaluation type code to type name.

```php
PaperEvaluation::getEvaluationTypeFromCode('01'); // 'referencia_i'
```

#### Status Methods

```php
$evaluation->markAsCompleted(); // Sets status to completed
$evaluation->markAsFailed('Error message'); // Sets status to failed with error
```

### Query Scopes

```php
// Filter by evaluation type
PaperEvaluation::ofType('referencia_i')->get();

// Filter by source
PaperEvaluation::fromSource('paper')->get();

// Filter by status
PaperEvaluation::withStatus('completed')->get();
PaperEvaluation::completed()->get();
PaperEvaluation::failed()->get();
```

## Job: ProcessPaperEvaluation

The new job handles paper evaluation processing with improved error handling and structure.

### Workflow

1. **Upload**: PDF file uploaded via web interface
2. **Copy**: File copied to Docker container
3. **OCR**: Python script processes PDF and generates JSON files
4. **Parse**: JSON files parsed and structured
5. **Store**: Data saved to `paper_evaluations` table
6. **Cleanup**: Temporary files removed

### Key Features

- Real-time status updates via Laravel Reverb
- Structured data extraction based on evaluation type
- Automatic organization creation/lookup
- Comprehensive error handling with retry tracking
- Audit trail via `raw_data` field

### Data Extraction

The job intelligently extracts data based on evaluation type:

#### Referencia I (PTSD)
```json
{
  "referencia_i_answers": {
    "1": "SI",
    "2": "NO",
    "3": "SI"
  }
}
```

#### Referencia III (Workplace)
```json
{
  "referencia_iii_answers": {
    "referencia_iii": {
      "1": "A",
      "2": "B"
    }
  },
  "referencia_iii_conditional": {
    "customer_service": {
      "condition": "SI",
      "questions": {"65": "A", "66": "B"}
    },
    "management": {
      "condition": "NO",
      "questions": {}
    }
  },
  "cisneros_answers": {
    "citsats_s1": {
      "1": "SI",
      "2": "NO"
    }
  }
}
```

#### Referencia V (Demographics)
```json
{
  "demographic_data": {
    "sexo": "masculino",
    "edad": {"decenas": "3", "unidades": "4"},
    "estado_civil": "casado",
    "nivel_estudios": {...}
  }
}
```

## UI/UX Improvements

### Real-time Status Updates

The upload interface now provides real-time feedback using Laravel Reverb:

1. **Upload Progress**: Shows file upload percentage
2. **Processing Status**: Updates as OCR processes the document
3. **Completion**: Shows success/error state with appropriate messaging

### Status Indicators

- 🔵 **Running**: File is being processed (spinning icon)
- ✅ **Finished**: Processing completed successfully
- ❌ **Error**: Processing failed with error message
- ⏳ **Queued**: Waiting for processing to begin

## Legacy Support

The original `ProcessEvaluation` job has been renamed to `ProcessEvaluationLegacy` and remains available for compatibility. The legacy `evaluations` table is unchanged and continues to work for historical data.

## Migration Guide

### For Developers

1. **Use New Job**: Dispatch `ProcessPaperEvaluation` instead of `ProcessEvaluation`
2. **Query New Table**: Use `PaperEvaluation` model for new evaluations
3. **Test Coverage**: All functionality is covered by PHPUnit tests

### For Administrators

1. The system automatically uses the new storage format for new uploads
2. Historical evaluations remain accessible in the original format
3. No data migration required - both systems coexist

## Testing

### Factory

The `PaperEvaluationFactory` provides convenient test data creation:

```php
// Create basic evaluation
PaperEvaluation::factory()->create();

// Create specific type
PaperEvaluation::factory()->referenciaI()->create();
PaperEvaluation::factory()->referenciaIII()->create();
PaperEvaluation::factory()->referenciaV()->create();
PaperEvaluation::factory()->cisneros()->create();

// Create with specific status
PaperEvaluation::factory()->pending()->create();
PaperEvaluation::factory()->failed()->create();

// Create online evaluation
PaperEvaluation::factory()->online()->create();
```

### Running Tests

```bash
# Run all paper evaluation tests
php artisan test tests/Feature/ProcessPaperEvaluationTest.php

# Run specific test
php artisan test --filter=test_can_parse_folio_correctly
```

## Reporting & Analytics

The structured format enables powerful queries:

```php
// Get all completed Referencia I evaluations for an organization
$evaluations = PaperEvaluation::where('organization_id', $orgId)
    ->ofType('referencia_i')
    ->completed()
    ->get();

// Get failed evaluations for retry
$failed = PaperEvaluation::failed()
    ->where('retry_count', '<', 3)
    ->get();

// Get evaluations by source and date range
$paperEvals = PaperEvaluation::fromSource('paper')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get();
```

## Future Enhancements

Potential improvements for future iterations:

1. **Automated Reporting**: Generate NOM-035 compliance reports from structured data
2. **Data Validation**: Validate OCR results against expected ranges
3. **Batch Processing**: Process multiple PDFs in a single job
4. **Result Review**: Admin interface to review and correct OCR errors
5. **Export Functionality**: Export evaluations in various formats (PDF, Excel, CSV)
6. **Analytics Dashboard**: Visual analytics for evaluation trends

## Best Practices

1. **Always use factories in tests** to ensure consistent test data
2. **Check processing_status** before generating reports
3. **Review raw_data** when debugging OCR issues
4. **Monitor retry_count** for evaluations that consistently fail
5. **Use composite indexes** when querying by organization and type

## Support

For questions or issues related to the paper evaluation storage system:

1. Check test cases in `tests/Feature/ProcessPaperEvaluationTest.php`
2. Review job implementation in `app/Jobs/ProcessPaperEvaluation.php`
3. Examine model in `app/Models/PaperEvaluation.php`
4. Consult migration in `database/migrations/*_create_paper_evaluations_table.php`
