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

    /**
     * Get the organization that owns the online answer.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the quiz that owns the online answer.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}