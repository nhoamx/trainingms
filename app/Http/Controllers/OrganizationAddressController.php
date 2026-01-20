<?php

namespace App\Http\Controllers;

use App\Models\OrganizationAddress;
use App\Services\OrganizationAddressService;
use Illuminate\Http\Request;

class OrganizationAddressController extends Controller
{
    public function __construct(protected OrganizationAddressService $addressService) {}

    /**
     * Store a newly created address
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'type' => ['required', 'in:fiscal,fisica'],
            'calle_numero' => ['nullable', 'string', 'max:255'],
            'colonia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $organization = \App\Models\Organization::findOrFail($validated['organization_id']);

        // Asegurar que is_primary esté definido
        $validated['is_primary'] = $validated['is_primary'] ?? false;

        // Si is_primary es true, desmarcar todas las demás
        if ($validated['is_primary']) {
            $organization->addresses()->update(['is_primary' => false]);
        }

        $address = $organization->addresses()->create($validated);

        return back()->with([
            'flash' => [
                'type' => 'success',
                'title' => 'Dirección agregada',
                'message' => 'La dirección ha sido agregada exitosamente.',
            ],
        ]);
    }

    /**
     * Remove the specified address
     */
    public function destroy(OrganizationAddress $address)
    {
        $this->addressService->deleteAddress($address);

        return back()->with([
            'flash' => [
                'type' => 'success',
                'title' => 'Dirección eliminada',
                'message' => 'La dirección ha sido eliminada exitosamente.',
            ],
        ]);
    }

    /**
     * Set address as primary
     */
    public function setPrimary(OrganizationAddress $address)
    {
        $this->addressService->setPrimaryAddress($address);

        return back()->with([
            'flash' => [
                'type' => 'success',
                'title' => 'Dirección principal actualizada',
                'message' => 'La dirección ha sido marcada como principal (Matriz).',
            ],
        ]);
    }
}
