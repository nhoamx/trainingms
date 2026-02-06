<?php

namespace App\Enums;

enum RoleType: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Organization = 'organization';
    case WorkCenterUser = 'work_center_user';

    /**
     * Get human-readable label for the role
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Administrador',
            self::Organization => 'Organización',
            self::WorkCenterUser => 'Usuario de Centro de Trabajo',
        };
    }

    /**
     * Get badge color for the role (used in UI)
     */
    public function color(): string
    {
        return match ($this) {
            self::SuperAdmin => 'purple',
            self::Admin => 'blue',
            self::Organization => 'green',
            self::WorkCenterUser => 'gray',
        };
    }

    /**
     * Get all available values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Try to get enum from string value
     */
    public static function tryFromValue(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }
}
