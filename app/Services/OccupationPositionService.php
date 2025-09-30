<?php

namespace App\Services;

use App\Models\OccupationPosition;
use App\Models\Organization;

class OccupationPositionService
{
    /**
     * Crea un nuevo puesto de ocupación con un identificador generado automáticamente
     *
     * @param  Organization  $organization  La organización a la que pertenece el puesto
     * @param  string  $name  Nombre descriptivo del puesto
     * @param  string|null  $customIdentifier  Identificador personalizado (opcional)
     */
    public function createPosition(Organization $organization, string $name, ?string $customIdentifier = null): OccupationPosition
    {
        // Si se proporciona un identificador personalizado, lo usamos
        if ($customIdentifier) {
            return OccupationPosition::create([
                'organization_id' => $organization->id,
                'name' => $name,
                'identifier' => $customIdentifier,
            ]);
        }

        // Generamos un identificador automático
        $identifier = $this->generateNextIdentifier($organization);

        return OccupationPosition::create([
            'organization_id' => $organization->id,
            'name' => $name,
            'identifier' => $identifier,
        ]);
    }

    /**
     * Genera el siguiente identificador disponible para un puesto de ocupación
     *
     * @return string Identificador en formato "n_a" (1_a, 1_b, etc.)
     */
    protected function generateNextIdentifier(Organization $organization): string
    {
        // Obtenemos el último puesto creado para esta organización
        $lastPosition = $organization->occupationPositions()
            ->orderByRaw('CAST(SUBSTRING_INDEX(identifier, "_", 1) AS UNSIGNED) DESC')
            ->orderByRaw('SUBSTRING(identifier, LOCATE("_", identifier) + 1) DESC')
            ->first();

        if (! $lastPosition) {
            // Si no hay puestos previos, empezamos en 1_a
            return '1_a';
        }

        // Parseamos el último identificador
        [$number, $letter] = explode('_', $lastPosition->identifier);

        // Si la letra es 'z', incrementamos el número y volvemos a 'a'
        if ($letter === 'z') {
            return (intval($number) + 1).'_a';
        }

        // Si no, incrementamos la letra
        return $number.'_'.chr(ord($letter) + 1);
    }

    /**
     * Importa un puesto desde un archivo JSON
     *
     * @param  string  $jsonIdentifier  El identificador del puesto en el JSON (ej: "1_a")
     * @param  string|null  $name  Nombre descriptivo (opcional)
     */
    public function importFromJson(Organization $organization, string $jsonIdentifier, ?string $name = null): OccupationPosition
    {
        // Verificar si ya existe este identificador
        $existingPosition = $organization->occupationPositions()
            ->where('identifier', $jsonIdentifier)
            ->first();

        if ($existingPosition) {
            return $existingPosition;
        }

        // Si no existe nombre, generamos uno basado en el identificador
        if (! $name) {
            $name = 'Puesto '.strtoupper($jsonIdentifier);
        }

        // Creamos el nuevo puesto con el identificador extraído del JSON
        return $this->createPosition($organization, $name, $jsonIdentifier);
    }
}
