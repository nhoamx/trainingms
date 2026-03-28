<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
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
        'policy_draft_path',
        'policy_approved_path',
        'policy_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_aplicacion' => 'date',
            'policy_approved_at' => 'datetime',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($organization) {
            if (empty($organization->slug)) {
                $organization->slug = static::generateUniqueSlug($organization->name);
            }
        });
    }

    /**
     * Generate a unique slug for the organization
     */
    private static function generateUniqueSlug(string $name, ?string $id = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->when($id, function ($query) use ($id) {
            return $query->where('id', '!=', $id);
        })->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
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

    public function committeeMembers()
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function analysisBlocks()
    {
        return $this->hasMany(OrganizationAnalysisBlock::class);
    }

    public function instruments()
    {
        return $this->belongsToMany(Instrument::class, 'organization_instrument')->withTimestamps();
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function addresses()
    {
        return $this->hasMany(OrganizationAddress::class);
    }

    public function primaryAddress()
    {
        return $this->hasOne(OrganizationAddress::class)->where('is_primary', true);
    }

    public function workCenters()
    {
        return $this->hasMany(WorkCenter::class);
    }

    public function primaryWorkCenter()
    {
        return $this->hasOne(WorkCenter::class)->where('is_primary', true);
    }

    public function climaSections()
    {
        return $this->hasMany(OrganizationClimaSection::class);
    }

    public function conclusionsFiles()
    {
        return $this->hasMany(OrganizationConclusionsFile::class)->orderBy('slot');
    }

    public function hasInstrument(string $instrumentName): bool
    {
        return $this->instruments()->where('name', $instrumentName)->exists();
    }
}
