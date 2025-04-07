<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasUuids;

    protected $fillable = [
        'evaluation_id',
        'personal_id',
        'question',
        'answer',
        'domain_id',
        'dimension_id',
        'category_id',
        'value',
        'reference_guide',
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

    /**
     * Relación con el dominio.
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Relación con la categoría.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
