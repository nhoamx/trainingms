<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page controller for NOM-035 dashboard.
 *
 * Shows an overview of available instruments (Ref I, Ref III, etc.)
 * with summary metrics so the user can choose which dashboard to enter.
 */
class WorkCenterNom035IndexController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show the NOM-035 instruments index for a work center.
     */
    public function show(WorkCenter $workCenter): Response
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $workCenter->load('organization');

        $instruments = $this->buildInstrumentsSummary($workCenter);

        return Inertia::render('WorkCenters/Nom035DashboardIndex', [
            'title' => 'NOM-035-STPS-2018 - '.$workCenter->name,
            'workCenter' => [
                'id' => $workCenter->id,
                'name' => $workCenter->name,
                'code' => $workCenter->code,
            ],
            'organization' => [
                'id' => $workCenter->organization->id,
                'name' => $workCenter->organization->name,
                'logo' => $workCenter->organization->logo
                    ? asset('storage/'.$workCenter->organization->logo)
                    : null,
            ],
            'instruments' => $instruments,
            'totalEvaluations' => array_sum(array_column($instruments, 'count')),
        ]);
    }

    /**
     * Build the summary data for each NOM-035 instrument.
     *
     * @return array<int, array{key: string, label: string, description: string, count: int, route: string, color: string, icon: string}>
     */
    private function buildInstrumentsSummary(WorkCenter $workCenter): array
    {
        $counts = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('processing_status', 'completed')
            ->selectRaw('evaluation_type, count(*) as total')
            ->groupBy('evaluation_type')
            ->pluck('total', 'evaluation_type')
            ->toArray();

        $definitions = [
            [
                'key' => 'referencia_iii',
                'label' => 'Guía de Referencia III',
                'subtitle' => 'Factores de Riesgo Psicosocial',
                'description' => 'Identificación y análisis de los factores de riesgo psicosocial en el entorno de trabajo, incluyendo dominios, categorías y dimensiones.',
                'color' => 'blue',
                'icon' => 'chart-bar',
                'route' => 'work-centers.dashboard.nom-035',
            ],
            [
                'key' => 'referencia_i',
                'label' => 'Guía de Referencia I',
                'subtitle' => 'Acontecimientos Traumáticos Severos',
                'description' => 'Identificación de trabajadores expuestos a acontecimientos traumáticos severos (ATS) mediante 14 preguntas de evaluación.',
                'color' => 'red',
                'icon' => 'document-text',
                'route' => 'work-centers.dashboard.nom-035-ref-i',
            ],
        ];

        return array_map(function (array $definition) use ($counts) {
            $definition['count'] = $counts[$definition['key']] ?? 0;

            return $definition;
        }, $definitions);
    }
}
