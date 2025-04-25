<?php

namespace App\Http\Controllers;

use App\Models\OccupationPosition;
use App\Models\Organization;
use App\Services\OccupationPositionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OccupationPositionController extends Controller
{
    protected $occupationService;

    public function __construct(OccupationPositionService $occupationService)
    {
        $this->occupationService = $occupationService;
    }

    /**
     * Almacena un nuevo puesto en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validar la solicitud
        $validated = $request->validate([
            'organization_id' => 'required|uuid|exists:organizations,id',
            'name' => 'required|string|max:255',
        ]);

        // Buscar la organización
        $organization = Organization::findOrFail($validated['organization_id']);

        // Crear el puesto usando nuestro servicio
        $position = $this->occupationService->createPosition(
            $organization,
            $validated['name']
        );

        // Redireccionar con un mensaje flash de éxito y devolver el puesto creado
        return back()->with([
            'flash' => [
                'type' => 'success',
                'title' => 'Puesto creado',
                'message' => 'El puesto ha sido creado exitosamente.',
            ],
            'position' => $position // Devolver el puesto creado para actualización reactiva
        ]);
    }

    /**
     * Elimina un puesto específico.
     *
     * @param  \App\Models\OccupationPosition  $occupationPosition
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(OccupationPosition $occupationPosition)
    {
        // Verificar si existe y eliminarlo
        $occupationPosition->delete();

        // Redireccionar con un mensaje flash de éxito
        return back()->with([
            'flash' => [
                'type' => 'success',
                'title' => 'Puesto eliminado',
                'message' => 'El puesto ha sido eliminado exitosamente.',
            ]
        ]);
    }
}
