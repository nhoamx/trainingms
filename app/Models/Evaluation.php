<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasUuids;
    protected $fillable = [
        'document_id',
        'folio',
        'organization_id',
        'data',
        'reference_guide', // Agregar reference_guide a fillable
    ];

    protected $casts = [
        'data' => 'json',
    ];

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
