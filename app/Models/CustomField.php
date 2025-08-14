<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'quiz_id'
    ];

    protected $casts = [
        'type' => 'string'
    ];

    /**
     * Relación con el quiz
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Validar tipos de campo disponibles
     */
    public static function getAvailableTypes()
    {
        return ['text', 'number', 'textarea'];
    }
}
