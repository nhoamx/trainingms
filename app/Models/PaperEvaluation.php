<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'work_center_code',
        'personal_folio',
        'organization_id',
        'work_center_id',
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
     * Get the work center that owns the evaluation
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
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
     * Get comments for this evaluation
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EvaluationComment::class);
    }

    /**
     * Get a specific custom field value by key
     */
    public function getCustomField(string $fieldKey): ?string
    {
        return $this->customFields()->where('field_key', $fieldKey)->value('value');
    }

    /**
     * Set or update a custom field
     */
    public function setCustomField(string $fieldKey, string $keyLabel, ?string $value): EvaluationCustomField
    {
        return $this->customFields()->updateOrCreate(
            ['field_key' => $fieldKey],
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
     *
     * Supports two formats:
     * - New 11-digit format: [TT][OO][CC][PPPPP]
     *   - TT = Evaluation type (2 digits): 01, 02, 03, 04
     *   - OO = Organization (2 digits): 01-99
     *   - CC = Work center (2 digits): 01-99
     *   - PPPPP = Personal (5 digits): 00001-99999
     *
     * - Legacy 9-digit format: [TT][OOO][PPPP]
     *   - TT = Evaluation type (2 digits): 01, 02, 03, 04
     *   - OOO = Organization (3 digits): 001-999
     *   - PPPP = Personal (4 digits): 0001-9999
     *
     * @return array{folio: string, evaluation_type_code: string, organization_code: string, work_center_code: string|null, personal_folio: string, evaluation_type: string}
     */
    public static function parseFolio(string $folio): array
    {
        $folioLength = strlen($folio);

        if ($folioLength === 11) {
            // New format: [TT][OO][CC][PPPPP]
            $evaluationTypeCode = substr($folio, 0, 2);
            $organizationCode = substr($folio, 2, 2);
            $workCenterCode = substr($folio, 4, 2);
            $personalFolio = substr($folio, 6, 5);

            return [
                'folio' => $folio,
                'evaluation_type_code' => $evaluationTypeCode,
                'organization_code' => $organizationCode,
                'work_center_code' => $workCenterCode,
                'personal_folio' => $personalFolio,
                'evaluation_type' => self::getEvaluationTypeFromCode($evaluationTypeCode),
            ];
        } elseif ($folioLength === 9) {
            // Legacy format: [TT][OOO][PPPP]
            $evaluationTypeCode = substr($folio, 0, 2);
            $organizationCode = substr($folio, 2, 3);
            $personalFolio = substr($folio, 5, 4);

            return [
                'folio' => $folio,
                'evaluation_type_code' => $evaluationTypeCode,
                'organization_code' => $organizationCode,
                'work_center_code' => null,
                'personal_folio' => $personalFolio,
                'evaluation_type' => self::getEvaluationTypeFromCode($evaluationTypeCode),
            ];
        }

        throw new \InvalidArgumentException("Folio must be either 9 (legacy) or 11 (new) characters: {$folio}");
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
        $relatedEvaluations = $this->relatedEvaluationsQuery()->get();

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
        $expectedLength = $this->isElevenDigitFolio() ? 5 : 4;

        if (! preg_match('/^\d{'.$expectedLength.'}$/', $personalFolio)) {
            throw new \InvalidArgumentException("Personal folio must be exactly {$expectedLength} digits");
        }

        $relatedEvaluations = $this->relatedEvaluationsQuery()->get();

        // Check if any of the new folios would conflict with existing records
        foreach ($relatedEvaluations as $evaluation) {
            $newFolio = self::generateFolio(
                $evaluation->evaluation_type_code,
                $evaluation->organization_code,
                $personalFolio,
                $evaluation->isElevenDigitFolio() ? $evaluation->work_center_code : null
            );

            $conflict = self::where('folio', $newFolio)
                ->where('source', $evaluation->source)
                ->where('id', '!=', $evaluation->id)
                ->exists();

            if ($conflict) {
                throw new \InvalidArgumentException("Folio {$newFolio} already exists");
            }
        }

        // Update ALL related evaluations
        $updated = 0;
        foreach ($relatedEvaluations as $evaluation) {
            $newFolio = self::generateFolio(
                $evaluation->evaluation_type_code,
                $evaluation->organization_code,
                $personalFolio,
                $evaluation->isElevenDigitFolio() ? $evaluation->work_center_code : null
            );

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
    public static function isFolioAvailable(string $folio, string $source = 'paper', ?string $excludeId = null): bool
    {
        $query = self::query()
            ->where('folio', $folio)
            ->where('source', $source);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Generate complete folio from components
     */
    public static function generateFolio(
        string $evaluationTypeCode,
        string $organizationCode,
        string $personalFolio,
        ?string $workCenterCode = null
    ): string {
        if ($workCenterCode !== null) {
            return $evaluationTypeCode
                .str_pad(substr($organizationCode, -2), 2, '0', STR_PAD_LEFT)
                .str_pad(substr($workCenterCode, -2), 2, '0', STR_PAD_LEFT)
                .str_pad($personalFolio, 5, '0', STR_PAD_LEFT);
        }

        return $evaluationTypeCode.$organizationCode.$personalFolio;
    }

    protected function relatedEvaluationsQuery(): Builder
    {
        $query = self::query()
            ->where('organization_id', $this->organization_id)
            ->where('personal_folio', $this->personal_folio);

        if ($this->work_center_id) {
            return $query->where('work_center_id', $this->work_center_id);
        }

        return $query->whereNull('work_center_id');
    }

    protected function isElevenDigitFolio(): bool
    {
        return strlen((string) $this->folio) === 11 || ! empty($this->work_center_code);
    }

    /**
     * Check if evaluation is complete (has all required data)
     * For hybrid evaluations, requires both demographic data and evaluation answers
     * For paper evaluations, requires demographic data OR evaluation answers
     * For online evaluations, requires evaluation answers
     */
    public function isComplete(): bool
    {
        if ($this->source === 'hybrid') {
            // Hybrid requires demographic data AND at least one evaluation section
            return ! empty($this->demographic_data) &&
                   (! empty($this->referencia_iii_answers) || ! empty($this->referencia_i_answers));
        }

        if ($this->source === 'paper') {
            // Paper evaluations are complete if they have any data
            return ! empty($this->demographic_data) ||
                   ! empty($this->referencia_iii_answers) ||
                   ! empty($this->referencia_i_answers) ||
                   ! empty($this->cisneros_answers) ||
                   ! empty($this->likert_answers);
        }

        if ($this->source === 'online') {
            // Online evaluations should have evaluation answers
            return ! empty($this->referencia_iii_answers) ||
                   ! empty($this->referencia_i_answers) ||
                   ! empty($this->cisneros_answers);
        }

        return false;
    }

    /**
     * Check if evaluation is partially complete (hybrid evaluations only)
     * Returns true if either part is completed but not both
     */
    public function isPartiallyComplete(): bool
    {
        if ($this->source !== 'hybrid') {
            return false;
        }

        $hasDemographic = ! empty($this->demographic_data);
        $hasEvaluation = ! empty($this->referencia_iii_answers) || ! empty($this->referencia_i_answers);

        // Partially complete if one part exists but not both
        return $hasDemographic xor $hasEvaluation;
    }

    /**
     * Scope to filter hybrid evaluations only
     */
    public function scopeHybrid($query)
    {
        return $query->where('source', 'hybrid');
    }
}
