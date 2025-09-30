<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'temp_url',
        'expires_at',
        'is_active',
        'organization_id',
        'is_reduced',
        'is_cisneros',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_reduced' => 'boolean',
        'is_cisneros' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('expires_at', '>', now());
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Relación con las evaluaciones creadas desde este quiz
     */
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * Relación con la organización
     */
    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    /**
     * Relación con los campos personalizados
     */
    public function customFields()
    {
        return $this->hasMany(CustomField::class);
    }
}
