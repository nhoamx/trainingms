<?php

namespace App\Enums;

enum WorkCenterType: string
{
    case Headquarters = 'headquarters';
    case Plant = 'plant';
    case Branch = 'branch';
    case Warehouse = 'warehouse';
    case Office = 'office';
    case Other = 'other';

    /**
     * Get human-readable label for the type
     */
    public function label(): string
    {
        return match ($this) {
            self::Headquarters => 'Headquarters',
            self::Plant => 'Plant',
            self::Branch => 'Branch',
            self::Warehouse => 'Warehouse',
            self::Office => 'Office',
            self::Other => 'Other',
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
