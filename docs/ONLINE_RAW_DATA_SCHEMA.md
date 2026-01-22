# Online Quiz Submission - Raw Data Schema

## Overview

This document defines the standardized JSON schema for the `raw_data` field in `PaperEvaluation` records created from online quiz submissions.

## Schema Structure

```json
{
  "source": "online",
  "source_metadata": {
    "quiz_id": 123,
    "quiz_name": "Evaluación NOM-035 Enero 2026",
    "quiz_type": "normal|reducido|cisneros",
    "submitted_at": "2026-01-22T10:30:45Z",
    "submission_ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "organization_info": {
      "nombre_comercial": "Planta Norte",
      "division_sucursal": "Producción",
      "estado": "Nuevo León",
      "ciudad": "Monterrey"
    }
  },
  "custom_fields": {
    "field_1": "Valor del campo personalizado 1",
    "field_2": "Valor del campo personalizado 2"
  },
  "file_uploads": {
    "ine_frente": "quiz_submissions/org-uuid/020010001/ine_frente_xyz.jpg",
    "ine_reverso": "quiz_submissions/org-uuid/020010001/ine_reverso_xyz.jpg"
  }
}
```

## Field Definitions

### `source` (string, required)
Always set to `"online"` for quiz submissions.

### `source_metadata` (object, required)

#### `quiz_id` (integer, required)
The ID of the Quiz model that generated this submission.

#### `quiz_name` (string, required)
The name/title of the quiz at the time of submission.

#### `quiz_type` (string, required)
Type of quiz evaluation:
- `"normal"` - Full NOM-035 evaluation (Referencia III + I + V)
- `"reducido"` - Reduced evaluation (Acontecimientos traumáticos + I + V)
- `"cisneros"` - Cisneros mobbing scale evaluation

#### `submitted_at` (string, required)
ISO 8601 timestamp when the user clicked submit (before job processing).

#### `submission_ip` (string, optional)
IP address of the user who submitted the quiz.

#### `user_agent` (string, optional)
User agent string from the browser.

#### `organization_info` (object, optional)
Organization-specific information provided during submission:
- `nombre_comercial`: Commercial name or location
- `division_sucursal`: Division or branch
- `estado`: State
- `ciudad`: City

### `custom_fields` (object, optional)
Key-value pairs of custom field responses defined in the quiz configuration.
- Keys are field IDs (e.g., "field_1", "field_2")
- Values are the user's responses

### `file_uploads` (object, optional)
Paths to uploaded files (relative to storage disk):
- `ine_frente`: Path to INE front image
- `ine_reverso`: Path to INE back image

## Usage Examples

### Full Quiz Submission
```json
{
  "source": "online",
  "source_metadata": {
    "quiz_id": 45,
    "quiz_name": "Evaluación Psicosocial Q1 2026",
    "quiz_type": "normal",
    "submitted_at": "2026-01-22T14:30:00Z",
    "submission_ip": "201.134.56.78",
    "organization_info": {
      "nombre_comercial": "Oficina Central",
      "division_sucursal": "Recursos Humanos",
      "estado": "Ciudad de México",
      "ciudad": "CDMX"
    }
  },
  "custom_fields": {
    "numero_empleado": "EMP-2024-1234",
    "area_funcional": "Ventas"
  },
  "file_uploads": {
    "ine_frente": "quiz_submissions/uuid-org/020010055/ine_frente_hash.jpg",
    "ine_reverso": "quiz_submissions/uuid-org/020010055/ine_reverso_hash.jpg"
  }
}
```

### Reduced Quiz Submission (No Custom Fields)
```json
{
  "source": "online",
  "source_metadata": {
    "quiz_id": 67,
    "quiz_name": "Evaluación Reducida - Eventos Traumáticos",
    "quiz_type": "reducido",
    "submitted_at": "2026-01-22T09:15:30Z",
    "submission_ip": "10.0.1.45",
    "organization_info": {
      "nombre_comercial": "Sucursal Guadalajara",
      "division_sucursal": "Operaciones",
      "estado": "Jalisco",
      "ciudad": "Guadalajara"
    }
  },
  "custom_fields": {},
  "file_uploads": {}
}
```

## Related Models

- **PaperEvaluation**: Stores this JSON in `raw_data` field
- **SubmissionStatus**: Temporarily stores data in `data_snapshot` before job processing
- **Quiz**: Source of submission, referenced in `source_metadata.quiz_id`

## Processing Flow

1. User submits quiz → `QuizController::submit()`
2. Data validated and files uploaded
3. `SubmissionStatus` created with `data_snapshot`
4. `ProcessOnlineEvaluation` job dispatched
5. Job builds `raw_data` using this schema
6. `PaperEvaluation` created with standardized `raw_data`
