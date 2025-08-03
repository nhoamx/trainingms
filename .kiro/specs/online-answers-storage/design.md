# Design Document

## Overview

This design outlines the creation of a new `online_answers` table to store quiz responses separately from in-person evaluations, along with the necessary changes to the QuizController's submit logic. The design maintains backward compatibility with existing folio generation while providing better data separation and granular storage of question-answer pairs.

## Architecture

### Database Schema Changes

#### New Table: `online_answers`
```sql
CREATE TABLE online_answers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(255) NOT NULL,
    personal_id VARCHAR(255) NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    quiz_id BIGINT UNSIGNED NOT NULL,
    question_key VARCHAR(255) NOT NULL,
    answer_value TEXT,
    reference_guide ENUM('I', 'III', 'V', 'Cisneros') NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_folio (folio),
    INDEX idx_personal_id (personal_id),
    INDEX idx_organization_quiz (organization_id, quiz_id),
    INDEX idx_reference_guide (reference_guide),
    
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);
```

### Data Flow Changes

#### Current Flow
1. Validate request data
2. Generate folio and personal_id
3. Merge all answers into single array
4. Create single Evaluation record
5. Call saveOnlineAnswers method
6. Create virtual folio

#### New Flow
1. Validate request data
2. Generate folio and personal_id
3. Process each answer section individually
4. Create multiple OnlineAnswer records (one per question)
5. Create virtual folio
6. Return completion response

## Components and Interfaces

### Model: OnlineAnswer

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineAnswer extends Model
{
    protected $fillable = [
        'folio',
        'personal_id', 
        'organization_id',
        'quiz_id',
        'question_key',
        'answer_value',
        'reference_guide'
    ];

    protected $casts = [
        'answer_value' => 'string'
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
```

### Controller Changes: QuizController

#### New Method: `storeOnlineAnswers`
```php
private function storeOnlineAnswers($folio, $personalId, $organizationId, $quizId, $answers)
{
    $records = [];
    
    // Process referencia_iii answers
    if (isset($answers['referencia_iii'])) {
        foreach ($answers['referencia_iii'] as $key => $value) {
            $records[] = [
                'folio' => $folio,
                'personal_id' => $personalId,
                'organization_id' => $organizationId,
                'quiz_id' => $quizId,
                'question_key' => $key,
                'answer_value' => $this->formatAnswerValue($value),
                'reference_guide' => 'III',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
    }
    
    // Process referencia_i answers
    if (isset($answers['referencia_i'])) {
        foreach ($answers['referencia_i'] as $key => $value) {
            $records[] = [
                'folio' => $folio,
                'personal_id' => $personalId,
                'organization_id' => $organizationId,
                'quiz_id' => $quizId,
                'question_key' => $key,
                'answer_value' => $this->formatAnswerValue($value),
                'reference_guide' => 'I',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
    }
    
    // Process referencia_v answers
    if (isset($answers['referencia_v'])) {
        foreach ($answers['referencia_v'] as $key => $value) {
            $records[] = [
                'folio' => $folio,
                'personal_id' => $personalId,
                'organization_id' => $organizationId,
                'quiz_id' => $quizId,
                'question_key' => $key,
                'answer_value' => $this->formatAnswerValue($value),
                'reference_guide' => 'V',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
    }
    
    // Process escala_cisneros answers (for Cisneros quizzes)
    if (isset($answers['escala_cisneros'])) {
        foreach ($answers['escala_cisneros'] as $key => $value) {
            $records[] = [
                'folio' => $folio,
                'personal_id' => $personalId,
                'organization_id' => $organizationId,
                'quiz_id' => $quizId,
                'question_key' => $key,
                'answer_value' => $this->formatAnswerValue($value),
                'reference_guide' => 'Cisneros',
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
    }
    
    // Batch insert for performance
    OnlineAnswer::insert($records);
}
```

#### Helper Method: `formatAnswerValue`
```php
private function formatAnswerValue($value)
{
    if (is_array($value)) {
        return json_encode($value);
    }
    
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    
    return (string) $value;
}
```

#### Updated Submit Method
The submit method will be refactored to:
1. Remove Evaluation model creation
2. Remove saveOnlineAnswers call
3. Add call to new storeOnlineAnswers method
4. Maintain existing folio generation logic
5. Keep virtual folio creation

## Data Models

### Answer Storage Structure
Each question-answer pair will be stored as a separate record with the following structure:

- **folio**: Links to the quiz session (e.g., "0001")
- **personal_id**: Identifies the participant (e.g., "0001") 
- **question_key**: The specific question identifier (e.g., "sexo", "edad", "trauma_0")
- **answer_value**: The actual answer (string representation)
- **reference_guide**: The guide type (I, III, V, or Cisneros)

### Example Records
```
folio: "0001", personal_id: "0001", question_key: "sexo", answer_value: "M", reference_guide: "V"
folio: "0001", personal_id: "0001", question_key: "edad", answer_value: "25", reference_guide: "V"  
folio: "0001", personal_id: "0001", question_key: "trauma_0", answer_value: "1", reference_guide: "III"
```

## Error Handling

### Database Transaction
All answer insertions will be wrapped in a database transaction to ensure data consistency:

```php
DB::transaction(function () use ($folio, $personalId, $organizationId, $quizId, $validated) {
    $this->storeOnlineAnswers($folio, $personalId, $organizationId, $quizId, $validated);
    $this->createVirtualFolio($organizationId, $folio, null);
});
```

### Error Recovery
- If answer storage fails, the transaction will rollback
- Virtual folio creation will be skipped if answer storage fails
- User will receive appropriate error message
- Logging will capture detailed error information

## Testing Strategy

### Unit Tests
1. Test OnlineAnswer model creation and relationships
2. Test formatAnswerValue helper method with different data types
3. Test storeOnlineAnswers method with various answer structures
4. Test folio and personal_id generation logic

### Integration Tests  
1. Test complete quiz submission flow for each quiz type
2. Test error handling during database failures
3. Test virtual folio creation integration
4. Test data integrity across multiple submissions

### Performance Tests
1. Test batch insertion performance with large answer sets
2. Test database query performance with indexes
3. Test concurrent quiz submissions

## Migration Strategy

### Database Migration
1. Create migration for online_answers table
2. Add appropriate indexes for performance
3. Set up foreign key constraints

### Deployment Steps
1. Run database migration
2. Deploy updated QuizController code
3. Monitor for any errors during initial submissions
4. Verify data is being stored correctly in new table

## Backward Compatibility

### Existing Data
- Existing evaluation records remain untouched
- No data migration required from evaluation to online_answers
- Both systems can coexist during transition period

### API Compatibility
- Quiz submission endpoints remain unchanged
- Response format stays the same
- Error handling maintains same structure