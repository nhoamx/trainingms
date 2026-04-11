<?php

namespace App\Models;

use App\Enums\EvaluationInstrument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationAnswer extends Model
{
    protected $fillable = [
        'paper_evaluation_id',
        'instrument',
        'question_key',
        'answer_value',
        'answer_meta',
    ];

    protected function casts(): array
    {
        return [
            'instrument' => EvaluationInstrument::class,
            'answer_meta' => 'json',
        ];
    }

    /**
     * Get the evaluation this answer belongs to.
     */
    public function paperEvaluation(): BelongsTo
    {
        return $this->belongsTo(PaperEvaluation::class);
    }

    /**
     * Scope to filter answers by instrument.
     */
    public function scopeForInstrument($query, EvaluationInstrument $instrument)
    {
        return $query->where('instrument', $instrument->value);
    }

    /**
     * Scope to filter answers where the question was answered (non-null value).
     */
    public function scopeAnswered($query)
    {
        return $query->whereNotNull('answer_value');
    }

    /**
     * Scope to filter questions that were skipped/unanswered.
     */
    public function scopeUnanswered($query)
    {
        return $query->whereNull('answer_value');
    }
}
