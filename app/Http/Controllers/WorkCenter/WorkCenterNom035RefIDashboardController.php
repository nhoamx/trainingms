<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Models\OrganizationAnalysisBlock;
use App\Models\WorkCenter;
use App\Services\OrganizationReportCacheService;
use App\Services\WorkCenter\WorkCenterNom035RefIStatisticsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class WorkCenterNom035RefIDashboardController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected WorkCenterNom035RefIStatisticsService $statisticsService,
        protected OrganizationReportCacheService $cacheService
    ) {}

    /**
     * Muestra el dashboard NOM-035 Referencia I (ATS) para un centro de trabajo
     */
    public function show(WorkCenter $workCenter): Response
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $workCenter->load('organization');

        $dashboardData = $this->buildDashboardData($workCenter);

        // Obtener estadísticas agregadas con cache
        $aggregatedStats = Cache::rememberForever(
            $this->cacheService->getWcNom035RefIStatsCacheKey($workCenter->id),
            fn () => $this->statisticsService->getAggregatedStats($workCenter)
        );

        // Obtener lista de participantes (no cached, necesita ser fresca)
        $participants = $this->statisticsService->getParticipantsList($workCenter);

        // Obtener resumen ejecutivo
        $executiveSummary = $this->statisticsService->getExecutiveSummary($workCenter);

        $analysisData = $this->statisticsService->getStagesAnalysisData($workCenter);
        $questionStatistics = $this->statisticsService->getQuestionStatistics($workCenter);
        $blockStatistics = $this->statisticsService->getBlockStatistics($workCenter);
        $atsPanoramaStatistics = $this->statisticsService->getAtsPanoramaStatistics($workCenter);
        $acontecimientoParticipants = $this->statisticsService->getAcontecimientoParticipants($workCenter);

        $analysisBlocks = OrganizationAnalysisBlock::query()
            ->where('organization_id', $workCenter->organization_id)
            ->orderBy('instrument_type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('instrument_type')
            ->map(fn ($blocks) => $blocks->map(fn (OrganizationAnalysisBlock $block) => [
                'id' => $block->id,
                'instrument_type' => $block->instrument_type,
                'title' => $block->title,
                'content_html' => $block->content_html,
                'sort_order' => $block->sort_order,
            ])->values())
            ->all();

        return Inertia::render('WorkCenters/Nom035RefIDashboard', [
            'title' => 'NOM-035 Referencia I (ATS) - '.$workCenter->name,
            'dashboardData' => $dashboardData,
            'aggregatedStats' => $aggregatedStats,
            'participants' => $participants->values()->all(),
            'executiveSummary' => $executiveSummary,
            'analysisData' => $analysisData,
            'questionStatistics' => $questionStatistics,
            'blockStatistics' => $blockStatistics,
            'atsPanoramaStatistics' => $atsPanoramaStatistics,
            'acontecimientoParticipants' => $acontecimientoParticipants,
            'analysisBlocks' => [
                'referencia_i' => $analysisBlocks['referencia_i'] ?? [],
                'referencia_iii' => $analysisBlocks['referencia_iii'] ?? [],
            ],
            'canManageAnalysisBlocks' => request()->user()?->hasRole(['admin', 'super-admin']) ?? false,
            'preventionActions' => $workCenter->preventionActions()
                ->where('instrument_type', 'referencia_i')
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get()
                ->map(fn ($action) => [
                    'id' => $action->id,
                    'instrument_type' => $action->instrument_type,
                    'title' => $action->title,
                    'description' => $action->description,
                    'responsible' => $action->responsible,
                    'status' => $action->status,
                    'due_date' => $action->due_date?->format('Y-m-d'),
                    'sort_order' => $action->sort_order,
                    'updated_at' => $action->updated_at?->format('Y-m-d H:i'),
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Construye los datos del dashboard a partir del WorkCenter
     */
    private function buildDashboardData(WorkCenter $workCenter): array
    {
        $organization = $workCenter->organization;

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'logo' => $organization->logo ? asset('storage/'.$organization->logo) : null,
            ],
            'work_center' => [
                'id' => $workCenter->id,
                'name' => $workCenter->name,
                'code' => $workCenter->code,
            ],
            'company_data' => [
                'general' => [
                    'name' => $workCenter->name,
                    'razon_social' => $workCenter->legal_name,
                    'rfc' => $workCenter->tax_id,
                    'registro_patronal' => $workCenter->employer_registration,
                    'actividad_principal' => $workCenter->main_activity,
                    'folio_organization' => null,
                ],
                'address' => [
                    'calle_numero' => $workCenter->street_address,
                    'colonia' => $workCenter->neighborhood,
                    'codigo_postal' => $workCenter->postal_code,
                    'municipio' => $workCenter->city,
                    'estado' => $workCenter->state,
                ],
                'contact' => [
                    'nombre' => $workCenter->contact_name,
                    'puesto' => $workCenter->contact_position,
                    'email' => $workCenter->contact_email,
                    'movil' => $workCenter->contact_phone,
                ],
                'responsible' => [
                    'nombre' => $workCenter->responsible_name,
                    'puesto' => $workCenter->responsible_position,
                    'email' => $workCenter->responsible_email,
                    'movil' => $workCenter->responsible_phone,
                ],
                'workforce' => [
                    'total_trabajadores' => $workCenter->total_workers,
                    'total_hombres' => $workCenter->total_men,
                    'total_mujeres' => $workCenter->total_women,
                ],
                'sample' => [
                    'muestra_aplicada' => $workCenter->sample_applied,
                    'muestra_hombres' => $workCenter->sample_men,
                    'muestra_mujeres' => $workCenter->sample_women,
                    'justificacion_muestra' => $workCenter->sample_justification,
                ],
                'evaluation_date' => $workCenter->application_date,
                'committee' => [
                    'comite_integrantes' => $workCenter->committee_members,
                    'comite_hombres' => $workCenter->committee_men,
                    'comite_mujeres' => $workCenter->committee_women,
                ],
            ],
            'demographic_summary' => [],
            'demographic_details' => [
                'genders' => [],
                'contract_types' => [],
                'positions' => [],
                'departments' => [],
                'work_schedules' => [],
                'total_evaluations' => 0,
            ],
        ];
    }
}
