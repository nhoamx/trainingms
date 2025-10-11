<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'logo',
        'folio_organization',
        'razon_social',
        'rfc',
        'registro_patronal',
        'calle_numero',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado',
        'contacto_nombre',
        'contacto_puesto',
        'contacto_email',
        'contacto_movil',
        'responsable_nombre',
        'responsable_puesto',
        'responsable_email',
        'responsable_movil',
        'actividad_principal',
        'total_trabajadores',
        'total_hombres',
        'total_mujeres',
        'muestra_aplicada',
        'muestra_hombres',
        'muestra_mujeres',
        'comite_integrantes',
        'comite_hombres',
        'comite_mujeres',
        'fecha_aplicacion',
        'justificacion_muestra',
    ];

    protected function casts(): array
    {
        return [
            'fecha_aplicacion' => 'date',
            'total_trabajadores' => 'integer',
            'total_hombres' => 'integer',
            'total_mujeres' => 'integer',
            'muestra_aplicada' => 'integer',
            'muestra_hombres' => 'integer',
            'muestra_mujeres' => 'integer',
            'comite_integrantes' => 'integer',
            'comite_hombres' => 'integer',
            'comite_mujeres' => 'integer',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function occupationPositions()
    {
        return $this->hasMany(OccupationPosition::class);
    }

    public function departmentAreas()
    {
        return $this->hasMany(DepartmentArea::class);
    }

    public function folioBatches()
    {
        return $this->hasMany(FolioBatch::class);
    }

    public function folios()
    {
        return $this->hasManyThrough(Folio::class, FolioBatch::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }
}
