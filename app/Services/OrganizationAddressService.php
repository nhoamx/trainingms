<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationAddress;

class OrganizationAddressService
{
    /**
     * Actualiza o crea una dirección para una organización
     *
     * @param  Organization  $organization  La organización
     * @param  string  $type  Tipo de dirección ('fiscal' o 'fisica')
     * @param  array  $data  Datos de la dirección
     * @param  bool  $isPrimary  Si debe ser la dirección principal
     * @return OrganizationAddress|null Retorna la dirección o null si todos los campos están vacíos
     */
    public function updateOrCreateAddress(
        Organization $organization,
        string $type,
        array $data,
        bool $isPrimary = false
    ): ?OrganizationAddress {
        // Verificar si todos los campos están vacíos
        if ($this->isAddressEmpty($data)) {
            return null;
        }

        // Buscar dirección existente del mismo tipo
        $address = $organization->addresses()
            ->where('type', $type)
            ->first();

        if ($address) {
            // Actualizar dirección existente
            $address->update([
                'nombre_comercial' => $data['nombre_comercial'] ?? null,
                'razon_social' => $data['razon_social'] ?? null,
                'calle_numero' => $data['calle_numero'] ?? null,
                'colonia' => $data['colonia'] ?? null,
                'codigo_postal' => $data['codigo_postal'] ?? null,
                'municipio' => $data['municipio'] ?? null,
                'estado' => $data['estado'] ?? null,
                'is_primary' => $isPrimary,
            ]);
        } else {
            // Crear nueva dirección
            $address = $organization->addresses()->create([
                'type' => $type,
                'nombre_comercial' => $data['nombre_comercial'] ?? null,
                'razon_social' => $data['razon_social'] ?? null,
                'calle_numero' => $data['calle_numero'] ?? null,
                'colonia' => $data['colonia'] ?? null,
                'codigo_postal' => $data['codigo_postal'] ?? null,
                'municipio' => $data['municipio'] ?? null,
                'estado' => $data['estado'] ?? null,
                'is_primary' => $isPrimary,
            ]);
        }

        return $address;
    }

    /**
     * Verifica si todos los campos de dirección están vacíos
     *
     * @param  array  $data  Datos de la dirección
     * @return bool True si todos los campos están vacíos
     */
    protected function isAddressEmpty(array $data): bool
    {
        $fields = ['calle_numero', 'colonia', 'codigo_postal', 'municipio', 'estado'];

        foreach ($fields as $field) {
            if (! empty($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Elimina una dirección de una organización
     *
     * @param  OrganizationAddress  $address  La dirección a eliminar
     */
    public function deleteAddress(OrganizationAddress $address): bool
    {
        return $address->delete();
    }

    /**
     * Establece una dirección como principal
     *
     * @param  OrganizationAddress  $address  La dirección a marcar como principal
     */
    public function setPrimaryAddress(OrganizationAddress $address): void
    {
        // Desmarcar todas las direcciones como principal
        $address->organization->addresses()->update(['is_primary' => false]);

        // Marcar esta como principal
        $address->update(['is_primary' => true]);
    }
}
