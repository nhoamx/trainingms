<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationComment extends Model
{
    /** @use HasFactory<\Database\Factories\EvaluationCommentFactory> */
    use HasFactory;

    protected $fillable = [
        'paper_evaluation_id',
        'factor',
        'comment',
    ];

    /**
     * Get the paper evaluation that owns the comment
     */
    public function paperEvaluation(): BelongsTo
    {
        return $this->belongsTo(PaperEvaluation::class);
    }
}
