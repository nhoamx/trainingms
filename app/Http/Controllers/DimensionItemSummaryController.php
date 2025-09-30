<?php

namespace App\Http\Controllers;

use App\Services\DimensionItemSummaryService;
use Illuminate\Http\Request;

class DimensionItemSummaryController extends Controller
{
    protected $service;

    public function __construct(DimensionItemSummaryService $service)
    {
        $this->service = $service;
    }

    /**
     * Endpoint unificado para obtener todos los datos del reporte para una organización.
     * Incluye tanto los datos crudos como los agrupados por nivel de riesgo.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function byOrganization(Request $request)
    {
        $organizationId = $request->input('organization_id');

        // Si no se proporciona un ID de organización y el usuario tiene una organización asignada
        if (! $organizationId && auth()->check() && auth()->user()->organization) {
            $organizationId = auth()->user()->organization->id;
        }

        if (! $organizationId) {
            return response()->json(['error' => 'organization_id es requerido o no se encontró una organización asociada al usuario'], 422);
        }

        try {
            $data = $this->service->getReportSummaryByOrganization($organizationId);

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los datos del reporte: '.$e->getMessage()], 500);
        }
    }
}
