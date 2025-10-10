<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaperEvaluation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'folio',
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
        'cisneros_answers',
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
            'cisneros_answers' => 'json',
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
     * Derive evaluation type from folio code
     */
    public static function getEvaluationTypeFromCode(string $code): string
    {
        return match ($code) {
            '01' => 'referencia_i',
            '02' => 'referencia_iii',
            '03' => 'referencia_v',
            '04' => 'cisneros',
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
}
