<?php

namespace App\Http\Controllers;

use App\Exports\OccupationPositionsExport;
use App\Imports\OccupationPositionsImport;
use App\Models\OccupationPosition;
use App\Models\Organization;
use App\Services\OccupationPositionService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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
            'position' => $position, // Devolver el puesto creado para actualización reactiva
        ]);
    }

    /**
     * Elimina un puesto específico.
     *
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
            ],
        ]);
    }

    /**
     * Descargar plantilla Excel con los puestos actuales
     */
    public function downloadTemplate(Organization $organization)
    {
        $filename = 'puestos_'.$organization->folio_organization.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new OccupationPositionsExport($organization), $filename);
    }

    /**
     * Importar puestos desde archivo Excel
     */
    public function import(Request $request, Organization $organization)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $import = new OccupationPositionsImport($organization, $this->occupationService);

            Excel::import($import, $request->file('file'));

            $summary = $import->getSummary();

            $message = sprintf(
                'Importación completada: %d creados, %d actualizados, %d omitidos.',
                $summary['created'],
                $summary['updated'],
                $summary['skipped']
            );

            if (! empty($summary['errors'])) {
                $message .= ' Errores: '.implode(', ', array_slice($summary['errors'], 0, 3));
                if (count($summary['errors']) > 3) {
                    $message .= '...';
                }
            }

            return back()->with([
                'flash' => [
                    'type' => empty($summary['errors']) ? 'success' : 'warning',
                    'title' => 'Importación de Puestos',
                    'message' => $message,
                ],
            ]);
        } catch (\Exception $e) {
            return back()->with([
                'flash' => [
                    'type' => 'error',
                    'title' => 'Error en la importación',
                    'message' => 'No se pudo procesar el archivo: '.$e->getMessage(),
                ],
            ]);
        }
    }
}
