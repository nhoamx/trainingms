<?php

namespace App\Enums;

enum WorkCenterType: string
{
    case Headquarters = 'headquarters';
    case Plant = 'planta';
    case Branch = 'sucursal';
    case Warehouse = 'almacen';
    case Office = 'oficina';
    case Other = 'otro';
    case Square = 'plaza';

    /**
     * Get human-readable label for the type
     */
    public function label(): string
    {
        return match ($this) {
            self::Headquarters => 'Matriz',
            self::Plant => 'Planta',
            self::Branch => 'Sucursal',
            self::Warehouse => 'Almacén',
            self::Office => 'Oficina',
            self::Other => 'Otro',
        };
    }

    /**
     * Get all available values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
