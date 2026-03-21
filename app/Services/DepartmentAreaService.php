<?php

namespace App\Services;

use App\Models\DepartmentArea;
use App\Models\Organization;
use App\Support\OmrIdentifierSequence;
use InvalidArgumentException;
use RuntimeException;

class DepartmentAreaService
{
    /**
     * Crea una nueva área de departamento con un identificador generado automáticamente
     *
     * @param  Organization  $organization  La organización a la que pertenece el área
     * @param  string  $name  Nombre descriptivo del área
     * @param  string|null  $customIdentifier  Identificador personalizado (opcional)
     */
    public function createArea(Organization $organization, string $name, ?string $customIdentifier = null): DepartmentArea
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

        return DepartmentArea::create([
            'organization_id' => $organization->id,
            'name' => $name,
            'identifier' => $identifier,
        ]);
    }

    /**
     * Genera el siguiente identificador disponible para un área de departamento
     *
     * @return string Identificador en formato "n_a" (1_a, 1_b, etc.)
     */
    protected function generateNextIdentifier(Organization $organization): string
    {
        $usedIdentifiers = $organization->departmentAreas()
            ->pluck('identifier')
            ->all();

        return OmrIdentifierSequence::nextAvailable($usedIdentifiers);
    }

    /**
     * Importa un área desde un archivo JSON
     *
     * @param  string  $jsonIdentifier  El identificador del área en el JSON (ej: "1_b")
     * @param  string|null  $name  Nombre descriptivo (opcional)
     */
    public function importFromJson(Organization $organization, string $jsonIdentifier, ?string $name = null): DepartmentArea
    {
        $normalizedIdentifier = OmrIdentifierSequence::normalize($jsonIdentifier);

        // Verificar si ya existe este identificador
        $existingArea = $organization->departmentAreas()
            ->where('identifier', $normalizedIdentifier)
            ->first();

        if ($existingArea) {
            return $existingArea;
        }

        // Si no existe nombre, generamos uno basado en el identificador
        if (! $name) {
            $name = 'Área '.strtoupper($normalizedIdentifier);
        }

        // Creamos la nueva área con el identificador extraído del JSON
        return $this->createArea($organization, $name, $normalizedIdentifier);
    }

    private function ensureIdentifierIsAvailable(Organization $organization, string $identifier): void
    {
        $identifierExists = $organization->departmentAreas()
            ->where('identifier', $identifier)
            ->exists();

        if ($identifierExists) {
            throw new InvalidArgumentException('El identificador del departamento ya existe en esta organización.');
        }
    }
}
