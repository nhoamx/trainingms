<?php

namespace App\Services;

use App\Models\OccupationPosition;
use App\Models\Organization;
use App\Support\OmrIdentifierSequence;
use InvalidArgumentException;
use RuntimeException;

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
        $identifier = $customIdentifier;

        if ($customIdentifier) {
            try {
                $identifier = OmrIdentifierSequence::ensureValid($customIdentifier);
            } catch (RuntimeException $exception) {
                throw new InvalidArgumentException($exception->getMessage());
            }
        }

        if (! $identifier) {
            $identifier = $this->generateNextIdentifier($organization);
        }

        $this->ensureIdentifierIsAvailable($organization, $identifier);

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
        $usedIdentifiers = $organization->occupationPositions()
            ->pluck('identifier')
            ->all();

        return OmrIdentifierSequence::nextAvailable($usedIdentifiers);
    }

    /**
     * Importa un puesto desde un archivo JSON
     *
     * @param  string  $jsonIdentifier  El identificador del puesto en el JSON (ej: "1_a")
     * @param  string|null  $name  Nombre descriptivo (opcional)
     */
    public function importFromJson(Organization $organization, string $jsonIdentifier, ?string $name = null): OccupationPosition
    {
        $normalizedIdentifier = OmrIdentifierSequence::normalize($jsonIdentifier);

        // Verificar si ya existe este identificador
        $existingPosition = $organization->occupationPositions()
            ->where('identifier', $normalizedIdentifier)
            ->first();

        if ($existingPosition) {
            return $existingPosition;
        }

        // Si no existe nombre, generamos uno basado en el identificador
        if (! $name) {
            $name = 'Puesto '.strtoupper($normalizedIdentifier);
        }

        // Creamos el nuevo puesto con el identificador extraído del JSON
        return $this->createPosition($organization, $name, $normalizedIdentifier);
    }

    private function ensureIdentifierIsAvailable(Organization $organization, string $identifier): void
    {
        $identifierExists = $organization->occupationPositions()
            ->where('identifier', $identifier)
            ->exists();

        if ($identifierExists) {
            throw new InvalidArgumentException('El identificador del puesto ya existe en esta organización.');
        }
    }
}
