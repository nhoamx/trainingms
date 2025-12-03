<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaperEvaluation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'folio',
        'evaluee_name',
        'evaluation_type_code',
        'organization_code',
        'personal_folio',
        'organization_id',
        'evaluation_type',
        'source',
        'processing_status',
        'pdf_file_path',
        'processed_at',
        'demographic_data',
        'referencia_i_answers',
        'referencia_iii_answers',
        'referencia_iii_conditional',
        'citsats_s1',
        'cisneros_answers',
        'likert_answers',
        'raw_data',
        'processing_error',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'demographic_data' => 'json',
            'referencia_i_answers' => 'json',
            'referencia_iii_answers' => 'json',
            'referencia_iii_conditional' => 'json',
            'citsats_s1' => 'json',
            'cisneros_answers' => 'json',
            'likert_answers' => 'json',
            'raw_data' => 'json',
            'processed_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    /**
     * Get the organization that owns the evaluation
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the quiz associated with this evaluation (if from online source)
     */
    public function quiz(): ?BelongsTo
    {
        if ($this->source !== 'online' || ! isset($this->raw_data['quiz_id'])) {
            return null;
        }

        return $this->belongsTo(Quiz::class, 'raw_data->quiz_id');
    }

    /**
     * Get the demographic data for this evaluation
     */
    public function demographicData(): HasOne
    {
        return $this->hasOne(DemographicData::class);
    }

    /**
     * Get custom fields for this evaluation
     */
    public function customFields(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EvaluationCustomField::class);
    }

    /**
     * Get a specific custom field value by key
     */
    public function getCustomField(string $key): ?string
    {
        return $this->customFields()->where('key', $key)->value('value');
    }

    /**
     * Set or update a custom field
     */
    public function setCustomField(string $key, string $keyLabel, ?string $value): EvaluationCustomField
    {
        return $this->customFields()->updateOrCreate(
            ['key' => $key],
            ['key_label' => $keyLabel, 'value' => $value]
        );
    }

    /**
     * Derive evaluation type from folio code
     */
    public static function getEvaluationTypeFromCode(string $code): string
    {
        return match ($code) {
            '01' => 'referencia_i',
            '02' => 'referencia_iii',
            '03' => 'referencia_v',
            '04' => 'cisneros',
            '05' => 'likert',
            '06' => 'likert',  // Likert Planta 3 - se guarda como 'likert' en DB
            default => throw new \InvalidArgumentException("Invalid evaluation type code: {$code}"),
        };
    }

    /**
     * Parse folio into its components
     */
    public static function parseFolio(string $folio): array
    {
        if (strlen($folio) !== 9) {
            throw new \InvalidArgumentException("Folio must be exactly 9 characters: {$folio}");
        }

        $evaluationTypeCode = substr($folio, 0, 2);
        $organizationCode = substr($folio, 2, 3);
        $personalFolio = substr($folio, 5, 4);

        return [
            'folio' => $folio,
            'evaluation_type_code' => $evaluationTypeCode,
            'organization_code' => $organizationCode,
            'personal_folio' => $personalFolio,
            'evaluation_type' => self::getEvaluationTypeFromCode($evaluationTypeCode),
        ];
    }

    /**
     * Mark evaluation as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'processing_status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark evaluation as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'processing_status' => 'failed',
            'processing_error' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Scope to filter by evaluation type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('evaluation_type', $type);
    }

    /**
     * Scope to filter by source
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope to filter by processing status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('processing_status', $status);
    }

    /**
     * Scope to get completed evaluations
     */
    public function scopeCompleted($query)
    {
        return $query->where('processing_status', 'completed');
    }

    /**
     * Scope to get failed evaluations
     */
    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }

    /**
     * Scope to filter online evaluations only
     */
    public function scopeOnline($query)
    {
        return $query->where('source', 'online');
    }

    /**
     * Scope to filter paper evaluations only
     */
    public function scopePaper($query)
    {
        return $query->where('source', 'paper');
    }

    /**
     * Get the quiz type as a human-readable string
     */
    public function getQuizTypeAttribute(): string
    {
        $rawData = $this->raw_data ?? [];

        // Check if quiz_type is stored in raw_data
        if (isset($rawData['quiz_type'])) {
            return $rawData['quiz_type'];
        }

        // Infer based on present data
        if (! empty($this->cisneros_answers)) {
            return 'cisneros';
        }

        if (empty($this->referencia_iii_answers) && ! empty($this->referencia_iii_conditional)) {
            return 'reducido';
        }

        return 'completo';
    }

    /**
     * Check if evaluation has Referencia V data
     */
    public function hasReferenciaV(): bool
    {
        return ! empty($this->demographic_data);
    }

    /**
     * Check if evaluation has Referencia I data
     */
    public function hasReferenciaI(): bool
    {
        return ! empty($this->referencia_i_answers);
    }

    /**
     * Check if evaluation has Referencia III data
     */
    public function hasReferenciaIII(): bool
    {
        return ! empty($this->referencia_iii_answers) || ! empty($this->referencia_iii_conditional);
    }

    /**
     * Check if evaluation has Cisneros data
     */
    public function hasCisneros(): bool
    {
        return ! empty($this->cisneros_answers);
    }

    /**
     * Get the quiz ID from raw_data if it's an online evaluation
     */
    public function getQuizIdAttribute(): ?string
    {
        if ($this->source !== 'online') {
            return null;
        }

        return $this->raw_data['quiz_id'] ?? null;
    }

    /**
     * Get the quiz name from raw_data if it's an online evaluation
     */
    public function getQuizNameAttribute(): ?string
    {
        if ($this->source !== 'online') {
            return null;
        }

        return $this->raw_data['quiz_name'] ?? null;
    }

    /**
     * Get custom fields data from raw_data (for quiz custom fields)
     */
    public function getQuizCustomFieldsAttribute(): ?array
    {
        return $this->raw_data['custom_fields'] ?? null;
    }

    /**
     * Update evaluee name for ALL related evaluations
     * This updates all evaluations (01, 02, 03 types) for the same person in the same organization
     */
    public function updateName(string $name): bool
    {
        // Get current organization_id and personal_folio to find all related evaluations
        $currentOrganizationId = $this->organization_id;
        $currentPersonalFolio = $this->personal_folio;

        // Find all evaluations with the same organization_id and personal_folio
        $relatedEvaluations = self::where('organization_id', $currentOrganizationId)
            ->where('personal_folio', $currentPersonalFolio)
            ->get();

        // Update ALL related evaluations
        $updated = 0;
        foreach ($relatedEvaluations as $evaluation) {
            $evaluation->update(['evaluee_name' => $name]);
            $updated++;
        }

        // Refresh current model
        $this->refresh();

        return $updated > 0;
    }

    /**
     * Update personal folio and recalculate complete folio for ALL related evaluations
     * This updates all evaluations (01, 02, 03 types) for the same person in the same organization
     */
    public function updatePersonalFolio(string $personalFolio): bool
    {
        // Validate format (exactly 4 digits)
        if (! preg_match('/^\d{4}$/', $personalFolio)) {
            throw new \InvalidArgumentException('Personal folio must be exactly 4 digits');
        }

        // Get current organization_id and personal_folio to find all related evaluations
        $currentOrganizationId = $this->organization_id;
        $currentPersonalFolio = $this->personal_folio;

        // Find all evaluations with the same organization_id and personal_folio
        // These are all the guides (I, III, V) for the same person
        $relatedEvaluations = self::where('organization_id', $currentOrganizationId)
            ->where('personal_folio', $currentPersonalFolio)
            ->get();

        // Check if any of the new folios would conflict with existing records
        foreach ($relatedEvaluations as $evaluation) {
            $newFolio = $evaluation->evaluation_type_code.$evaluation->organization_code.$personalFolio;

            // Check if folio exists for records outside this group
            $conflict = self::where('folio', $newFolio)
                ->where('organization_id', '!=', $currentOrganizationId)
                ->orWhere(function ($query) use ($newFolio, $currentPersonalFolio) {
                    $query->where('folio', $newFolio)
                        ->where('personal_folio', '!=', $currentPersonalFolio);
                })
                ->exists();

            if ($conflict) {
                throw new \InvalidArgumentException("Folio {$newFolio} already exists for another person or organization");
            }
        }

        // Update ALL related evaluations
        $updated = 0;
        foreach ($relatedEvaluations as $evaluation) {
            $newFolio = $evaluation->evaluation_type_code.$evaluation->organization_code.$personalFolio;

            $evaluation->update([
                'personal_folio' => $personalFolio,
                'folio' => $newFolio,
            ]);

            $updated++;
        }

        // Refresh current model
        $this->refresh();

        return $updated > 0;
    }

    /**
     * Check if a folio is available (not used by other records)
     */
    public static function isFolioAvailable(string $folio, ?string $excludeId = null): bool
    {
        $query = self::where('folio', $folio);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Generate complete folio from components
     */
    public static function generateFolio(string $evaluationTypeCode, string $organizationCode, string $personalFolio): string
    {
        return $evaluationTypeCode.$organizationCode.$personalFolio;
    }
}
