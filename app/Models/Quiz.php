<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'name',
        'temp_url',
        'expires_at',
        'is_active'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean'
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
}
