<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemographicData extends Model
{
    /** @use HasFactory<\Database\Factories\DemographicDataFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'paper_evaluation_id',
        'gender',
        'age',
        'estado_civil',
        'nivel_estudios',
        'puesto',
        'area',
        'tipo_puesto',
        'tipo_contratacion',
        'tipo_personal',
        'tipo_jornada',
        'rotacion_turnos',
        'tiempo_puesto_actual',
        'tiempo_experiencia_laboral',
        'extra_fields',
    ];

    protected function casts(): array
    {
        return [
            'extra_fields' => 'json',
        ];
    }

    /**
     * Get the paper evaluation that owns this demographic data
     */
    public function paperEvaluation(): BelongsTo
    {
        return $this->belongsTo(PaperEvaluation::class);
    }
}
