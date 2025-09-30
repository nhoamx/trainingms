<?php

namespace App\Services;

use App\Models\DepartmentArea;
use App\Models\Organization;

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
        // Si se proporciona un identificador personalizado, lo usamos
        if ($customIdentifier) {
            return DepartmentArea::create([
                'organization_id' => $organization->id,
                'name' => $name,
                'identifier' => $customIdentifier,
            ]);
        }

        // Generamos un identificador automático
        $identifier = $this->generateNextIdentifier($organization);

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
        // Obtenemos la última área creada para esta organización
        $lastArea = $organization->departmentAreas()
            ->orderByRaw('CAST(SUBSTRING_INDEX(identifier, "_", 1) AS UNSIGNED) DESC')
            ->orderByRaw('SUBSTRING(identifier, LOCATE("_", identifier) + 1) DESC')
            ->first();

        if (! $lastArea) {
            // Si no hay áreas previas, empezamos en 1_a
            return '1_a';
        }

        // Parseamos el último identificador
        [$number, $letter] = explode('_', $lastArea->identifier);

        // Si la letra es 'z', incrementamos el número y volvemos a 'a'
        if ($letter === 'z') {
            return (intval($number) + 1).'_a';
        }

        // Si no, incrementamos la letra
        return $number.'_'.chr(ord($letter) + 1);
    }

    /**
     * Importa un área desde un archivo JSON
     *
     * @param  string  $jsonIdentifier  El identificador del área en el JSON (ej: "1_b")
     * @param  string|null  $name  Nombre descriptivo (opcional)
     */
    public function importFromJson(Organization $organization, string $jsonIdentifier, ?string $name = null): DepartmentArea
    {
        // Verificar si ya existe este identificador
        $existingArea = $organization->departmentAreas()
            ->where('identifier', $jsonIdentifier)
            ->first();

        if ($existingArea) {
            return $existingArea;
        }

        // Si no existe nombre, generamos uno basado en el identificador
        if (! $name) {
            $name = 'Área '.strtoupper($jsonIdentifier);
        }

        // Creamos la nueva área con el identificador extraído del JSON
        return $this->createArea($organization, $name, $jsonIdentifier);
    }
}
