<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folio extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'folio_batch_id',
        'folio_number',
        'numeric_value',
        'used',
        'used_at'
    ];

    /**
     * Los atributos que deben convertirse a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'used' => 'boolean',
        'used_at' => 'datetime',
    ];

    /**
     * Relación con el lote al que pertenece este folio.
     */
    public function folioBatch()
    {
        return $this->belongsTo(FolioBatch::class);
    }
}
