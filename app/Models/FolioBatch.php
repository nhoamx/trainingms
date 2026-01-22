<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioBatch extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * Tipos de lote de folios
     */
    public const TYPE_PRESENCIAL = 'presencial';

    public const TYPE_EN_LINEA = 'en_linea';

    public const TYPE_HIBRIDO = 'hibrido';

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
        'active',
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

    /**
     * Obtener todos los tipos de lote disponibles
     *
     * @return array<string>
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_PRESENCIAL,
            self::TYPE_EN_LINEA,
            self::TYPE_HIBRIDO,
        ];
    }

    /**
     * Verificar si el lote es de tipo híbrido
     */
    public function isHibrido(): bool
    {
        return $this->type === self::TYPE_HIBRIDO;
    }

    /**
     * Verificar si el lote es de tipo presencial
     */
    public function isPresencial(): bool
    {
        return $this->type === self::TYPE_PRESENCIAL;
    }

    /**
     * Verificar si el lote es de tipo en línea
     */
    public function isEnLinea(): bool
    {
        return $this->type === self::TYPE_EN_LINEA;
    }
}
