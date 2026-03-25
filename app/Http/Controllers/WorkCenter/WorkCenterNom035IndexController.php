<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
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
    public function show(Request $request, WorkCenter $workCenter): Response
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $workCenter->load(['organization', 'committeeMembers']);
        $selectedSource = $this->resolveSourceFilter($request);

        $instruments = $this->buildInstrumentsSummary($workCenter, $selectedSource);

        return Inertia::render('WorkCenters/Nom035DashboardIndex', [
            'title' => 'NOM-035-STPS-2018 - '.$workCenter->name,
            'dashboardData' => $this->buildDashboardData($workCenter),
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
            'selectedSource' => $selectedSource,
            'sourceSummary' => [
                'online' => $this->countTotalParticipants($workCenter, 'online'),
                'paper' => $this->countTotalParticipants($workCenter, 'paper'),
            ],
            'instruments' => $instruments,
            'totalEvaluations' => $this->countTotalParticipants($workCenter, $selectedSource),
            'evaluations' => $this->getGeneralEvaluations($workCenter, $selectedSource),
            'availableEvaluationTypes' => $this->getAvailableEvaluationTypes($workCenter, $selectedSource),
            'committeeMembers' => $workCenter->committeeMembers
                ->map(fn ($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'department_area' => $member->department_area,
                    'position' => $member->position,
                    'factor' => $member->factor,
                ])
                ->values()
                ->all(),
            'constitutiveAct' => [
                'submitted_path' => $workCenter->constitutive_act_submitted_path,
                'submitted_at' => $workCenter->constitutive_act_submitted_at,
                'admin_path' => $workCenter->constitutive_act_admin_path,
                'admin_at' => $workCenter->constitutive_act_admin_at,
            ],
            'sensitizationVideos' => $workCenter->sensitizationVideos()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get()
                ->map(fn ($video) => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'audience' => $video->audience,
                    'video_url' => asset('storage/'.$video->storage_path),
                    'original_filename' => $video->original_filename,
                    'file_size_human' => $video->file_size_human,
                    'created_at' => $video->created_at?->format('Y-m-d H:i'),
                ])
                ->values()
                ->all(),
        ]);
    }

    private function resolveSourceFilter(Request $request): string
    {
        $source = (string) $request->query('source', 'online');

        if (in_array($source, ['online', 'paper'], true)) {
            return $source;
        }

        return 'online';
    }

    /**
     * Count unique participants for NOM-035 instruments (Ref I + Ref III).
     */
    private function countTotalParticipants(WorkCenter $workCenter, ?string $source = null): int
    {
        $query = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('processing_status', 'completed')
            ->whereIn('evaluation_type', ['referencia_i', 'referencia_iii', 'cisneros', 'likert']);

        if ($source !== null) {
            $query->where('source', $source);
        }

        return $query
            ->get(['id', 'personal_folio'])
            ->map(function (PaperEvaluation $evaluation): string {
                $folio = trim((string) ($evaluation->personal_folio ?? ''));

                return $folio !== '' ? "folio:{$folio}" : "evaluation:{$evaluation->id}";
            })
            ->unique()
            ->count();
    }

    /**
     * Build common dashboard data used across NOM-035 pages.
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
                    'actividad_principal' => $organization->actividad_principal,
                    'folio_organization' => $organization->folio_organization,
                ],
                'address' => [
                    'calle_numero' => $workCenter->street_address,
                    'colonia' => $workCenter->neighborhood,
                    'codigo_postal' => $workCenter->postal_code,
                    'municipio' => $workCenter->municipality,
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
        ];
    }

    /**
     * Build the summary data for each NOM-035 instrument.
     *
     * @return array<int, array{key: string, label: string, description: string, count: int, route: string, color: string, icon: string}>
     */
    private function buildInstrumentsSummary(WorkCenter $workCenter, string $selectedSource): array
    {
        $rows = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('processing_status', 'completed')
            ->whereIn('source', ['online', 'paper'])
            ->selectRaw('evaluation_type, source, count(*) as total')
            ->groupBy('evaluation_type', 'source')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $evaluationType = (string) $row->evaluation_type;
            $source = (string) $row->source;
            $total = (int) $row->total;

            $counts[$evaluationType][$source] = $total;
        }

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
            [
                'key' => 'cisneros',
                'label' => 'Escala Cisneros',
                'subtitle' => 'Violencia Laboral',
                'description' => 'Detección de conductas de acoso psicológico y violencia laboral para identificar patrones, áreas de mayor incidencia y casos prioritarios.',
                'color' => 'orange',
                'icon' => 'shield-check',
                'route' => 'work-centers.dashboard.nom-035-cisneros',
            ],
            [
                'key' => 'likert',
                'label' => 'Clima Laboral',
                'subtitle' => 'Encuesta de Clima Organizacional',
                'description' => 'Evaluación del ambiente de trabajo, liderazgo, reconocimiento y bienestar organizacional mediante escala Likert.',
                'color' => 'teal',
                'icon' => 'sun',
                'route' => 'work-centers.dashboard.clima-laboral',
            ],
        ];

        return array_map(function (array $definition) use ($counts, $selectedSource) {
            $onlineCount = (int) ($counts[$definition['key']]['online'] ?? 0);
            $paperCount = (int) ($counts[$definition['key']]['paper'] ?? 0);

            $definition['online_count'] = $onlineCount;
            $definition['paper_count'] = $paperCount;
            $definition['count'] = $selectedSource === 'paper' ? $paperCount : $onlineCount;

            return $definition;
        }, $definitions);
    }

    /**
     * Get evaluations payload used by the general evaluation tab.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getGeneralEvaluations(WorkCenter $workCenter, ?string $source = null): array
    {
        $query = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->whereIn('evaluation_type', ['referencia_i', 'referencia_iii', 'referencia_v', 'cisneros'])
            ->where('processing_status', 'completed')
            ->with(['demographicData', 'comments']);

        if ($source !== null) {
            $query->where('source', $source);
        }

        return $query
            ->get()
            ->map(function (PaperEvaluation $evaluation) {
                return [
                    'id' => $evaluation->id,
                    'evaluation_type' => $evaluation->evaluation_type,
                    'personal_folio' => $evaluation->personal_folio,
                    'source' => $evaluation->source,
                    'demographicData' => $evaluation->demographicData ? [
                        'gender' => $evaluation->demographicData->gender,
                        'contract_type' => $evaluation->demographicData->contract_type,
                        'position' => $evaluation->demographicData->position,
                        'department' => $evaluation->demographicData->department,
                        'work_schedule' => $evaluation->demographicData->work_schedule,
                    ] : null,
                ];
            })
            ->all();
    }

    /**
     * Get available evaluation types with metadata for general tabs.
     *
     * @return array<int, array<string, string>>
     */
    private function getAvailableEvaluationTypes(WorkCenter $workCenter, ?string $source = null): array
    {
        $query = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('processing_status', 'completed');

        if ($source !== null) {
            $query->where('source', $source);
        }

        $evaluationTypes = $query
            ->distinct()
            ->pluck('evaluation_type')
            ->toArray();

        $typeConfigurations = [
            'referencia_i' => [
                'key' => 'referencia_i',
                'label' => 'Referencia I (ATS)',
                'description' => 'Identificación de trabajadores con ATS (Acontecimiento Traumáticos Severos)',
                'badge' => 'Evaluación ATS',
                'color' => 'red',
                'icon' => 'DocumentTextIcon',
            ],
            'referencia_iii' => [
                'key' => 'referencia_iii',
                'label' => 'Referencia III',
                'description' => 'Identificación de factores de riesgo psicosocial en el trabajo',
                'badge' => 'Factores de Riesgo',
                'color' => 'amber',
                'icon' => 'AdjustmentsHorizontalIcon',
            ],
            'referencia_v' => [
                'key' => 'referencia_v',
                'label' => 'Referencia V',
                'description' => 'Datos demográficos y características del entorno de trabajo',
                'badge' => 'Datos Demográficos',
                'color' => 'green',
                'icon' => 'UserGroupIcon',
            ],
            'cisneros' => [
                'key' => 'cisneros',
                'label' => 'Escala Cisneros',
                'description' => 'Evaluación de violencia laboral, acoso y mobbing',
                'badge' => 'Violencia Laboral',
                'color' => 'orange',
                'icon' => 'ShieldCheckIcon',
            ],
        ];

        $availableTypes = [];
        foreach ($evaluationTypes as $type) {
            if (isset($typeConfigurations[$type])) {
                $availableTypes[] = $typeConfigurations[$type];
            }
        }

        return $availableTypes;
    }
}
