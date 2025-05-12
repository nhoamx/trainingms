<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioBatch extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'start_number',
        'end_number',
        'quantity',
        'type',
        'active'
    ];

    /**
     * Relación con la organización a la que pertenece este lote.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Relación con los folios individuales del lote.
     */
    public function folios()
    {
        return $this->hasMany(Folio::class);
    }
}
