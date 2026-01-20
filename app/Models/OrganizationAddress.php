<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationAddress extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'type',
        'nombre_comercial',
        'razon_social',
        'calle_numero',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
