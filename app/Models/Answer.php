<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasUuids;

    protected $fillable = [
        'evaluation_id',
        'dimension_id',
        'question',
        'answer',
        'score',
    ];

    /**
     * Relación con la evaluación.
     */
    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    /**
     * Relación con la dimensión.
     */
    public function dimension()
    {
        return $this->belongsTo(Dimension::class);
    }
}
