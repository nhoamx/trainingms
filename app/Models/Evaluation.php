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
        'personal_id',
        'data',
        'reference_guide', // Agregar reference_guide a fillable
    ];

    protected $casts = [
        'data' => 'json',
        'personal_id' => 'string',
        'document_id' => 'integer',
        'folio' => 'string',
    ];

    // Asegurar que el personal_id siempre tenga 4 dígitos
    protected function setPersonalIdAttribute($value)
    {
        $this->attributes['personal_id'] = str_pad($value, 4, '0', STR_PAD_LEFT);
    }

    // Asegurar que el personal_id siempre se devuelva como string
    protected function getPersonalIdAttribute($value)
    {
        return (string) $value;
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
