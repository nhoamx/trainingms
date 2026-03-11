<?php

namespace App\Http\Controllers;

use App\Enums\WorkCenterType;
use App\Exports\WorkCentersExport;
use App\Imports\WorkCentersImport;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class WorkCenterController extends Controller
{
    /**
     * Display a listing of work centers for an organization
     */
    public function index(Organization $organization): Response
    {
        $workCenterMetrics = $this->buildWorkCenterMetrics($organization);

        $workCenters = $organization->workCenters()
            ->withCount(['quizzes', 'paperEvaluations' => function ($query) {
                $query->where('processing_status', 'completed');
            }])
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get()
            ->each(function (WorkCenter $workCenter) use ($workCenterMetrics): void {
                $metrics = $workCenterMetrics[$workCenter->id] ?? [
                    'evaluated_people_count' => 0,
                    'men_count' => 0,
                    'women_count' => 0,
                    'requires_clinical_attention_count' => 0,
                ];

                $workCenter->setAttribute('evaluated_people_count', $metrics['evaluated_people_count']);
                $workCenter->setAttribute('men_count', $metrics['men_count']);
                $workCenter->setAttribute('women_count', $metrics['women_count']);
                $workCenter->setAttribute('requires_clinical_attention_count', $metrics['requires_clinical_attention_count']);
            });

        return Inertia::render('WorkCenters/Index', [
            'title' => 'Centros de Trabajo - '.$organization->name,
            'organization' => $organization,
            'workCenters' => $workCenters,
        ]);
    }

    /**
     * Build participant and clinical-attention counters per work center.
     *
     * @return array<string, array{evaluated_people_count: int, men_count: int, women_count: int, requires_clinical_attention_count: int}>
     */
    private function buildWorkCenterMetrics(Organization $organization): array
    {
        $evaluations = PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->where('processing_status', 'completed')
            ->whereNotNull('work_center_id')
            ->with(['demographicData:id,paper_evaluation_id,gender'])
            ->select(['id', 'work_center_id', 'personal_folio', 'folio', 'evaluation_type', 'referencia_i_answers', 'demographic_data'])
            ->get();

        /** @var array<string, array{participants: array<string, true>, participant_gender: array<string, string>, clinical: array<string, true>}> $raw */
        $raw = [];

        foreach ($evaluations as $evaluation) {
            if (! $evaluation->work_center_id) {
                continue;
            }

            $workCenterId = $evaluation->work_center_id;
            $participantKey = $this->resolveParticipantKey($evaluation->personal_folio, $evaluation->folio);
            $gender = $this->resolveNormalizedGender($evaluation->demographicData?->gender, $evaluation->demographic_data);

            if (! isset($raw[$workCenterId])) {
                $raw[$workCenterId] = ['participants' => [], 'participant_gender' => [], 'clinical' => []];
            }

            $raw[$workCenterId]['participants'][$participantKey] = true;

            if ($gender !== null && ! isset($raw[$workCenterId]['participant_gender'][$participantKey])) {
                $raw[$workCenterId]['participant_gender'][$participantKey] = $gender;
            }

            $answers = is_array($evaluation->referencia_i_answers) ? $evaluation->referencia_i_answers : [];
            if ($evaluation->evaluation_type === 'referencia_i' && $this->requiresClinicalAttention($answers)) {
                $raw[$workCenterId]['clinical'][$participantKey] = true;
            }
        }

        /** @var array<string, array{evaluated_people_count: int, men_count: int, women_count: int, requires_clinical_attention_count: int}> $metrics */
        $metrics = [];

        foreach ($raw as $workCenterId => $values) {
            $menCount = 0;
            $womenCount = 0;

            foreach (array_keys($values['participants']) as $participantKey) {
                $gender = $values['participant_gender'][$participantKey] ?? null;

                if ($gender === 'male') {
                    $menCount++;
                }

                if ($gender === 'female') {
                    $womenCount++;
                }
            }

            $metrics[$workCenterId] = [
                'evaluated_people_count' => count($values['participants']),
                'men_count' => $menCount,
                'women_count' => $womenCount,
                'requires_clinical_attention_count' => count($values['clinical']),
            ];
        }

        return $metrics;
    }

    private function resolveParticipantKey(?string $personalFolio, ?string $folio): string
    {
        $normalizedPersonalFolio = trim((string) $personalFolio);
        if ($normalizedPersonalFolio !== '') {
            return 'personal:'.$normalizedPersonalFolio;
        }

        return 'folio:'.trim((string) $folio);
    }

    private function resolveNormalizedGender(?string $modelGender, mixed $demographicData): ?string
    {
        if (is_string($modelGender) && trim($modelGender) !== '') {
            $normalizedFromModel = $this->mapGenderToCanonical($modelGender);

            if ($normalizedFromModel !== null) {
                return $normalizedFromModel;
            }
        }

        if (! is_array($demographicData)) {
            return null;
        }

        $rawGender = $demographicData['sexo']
            ?? $demographicData['genero']
            ?? $demographicData['gender']
            ?? null;

        if (! is_string($rawGender)) {
            return null;
        }

        return $this->mapGenderToCanonical($rawGender);
    }

    private function mapGenderToCanonical(string $rawGender): ?string
    {
        $normalized = mb_strtolower(trim($rawGender));

        if (in_array($normalized, ['masculino', 'hombre', 'male', 'm'], true)) {
            return 'male';
        }

        if (in_array($normalized, ['femenino', 'mujer', 'female', 'f'], true)) {
            return 'female';
        }

        return null;
    }

    /**
     * Clinical attention criteria for Ref I.
     *
     * Section II (1-2): >=1 yes
     * Section III (3-9): >=3 yes
     * Section IV (10-14): >=2 yes
     */
    private function requiresClinicalAttention(array $answers): bool
    {
        $normalizedAnswers = $this->normalizeRefIAnswers($answers);

        $sectionIICount = $this->countYesByQuestionRange($normalizedAnswers, 1, 2);
        $sectionIIICount = $this->countYesByQuestionRange($normalizedAnswers, 3, 9);
        $sectionIVCount = $this->countYesByQuestionRange($normalizedAnswers, 10, 14);

        return $sectionIICount >= 1 || $sectionIIICount >= 3 || $sectionIVCount >= 2;
    }

    /**
     * @param  array<string|int, mixed>  $answers
     * @return array<string, mixed>
     */
    private function normalizeRefIAnswers(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $rawKey => $value) {
            $key = (string) $rawKey;

            if (preg_match('/^pregunta_(\d+)$/', $key, $matches) === 1) {
                $normalized['pregunta_'.((int) $matches[1])] = $value;

                continue;
            }

            if (preg_match('/^(\d+)$/', $key, $matches) === 1) {
                $normalized['pregunta_'.((int) $matches[1])] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private function countYesByQuestionRange(array $answers, int $fromQuestion, int $toQuestion): int
    {
        $count = 0;

        for ($question = $fromQuestion; $question <= $toQuestion; $question++) {
            $value = $answers['pregunta_'.$question] ?? null;

            if ($this->isAffirmativeAnswer($value)) {
                $count++;
            }
        }

        return $count;
    }

    private function isAffirmativeAnswer(mixed $answer): bool
    {
        if (is_string($answer)) {
            $normalizedAnswer = mb_strtolower(trim($answer));

            return in_array($normalizedAnswer, ['sí', 'si', 'true', '1'], true);
        }

        return in_array($answer, [true, 1], true);
    }

    /**
     * Show the form for creating a new work center
     */
    public function create(Organization $organization): Response
    {
        return Inertia::render('WorkCenters/Create', [
            'title' => 'Nuevo Centro de Trabajo',
            'organization' => $organization->only(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created work center
     */
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:4',
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(WorkCenterType::values())],
            'is_primary' => 'boolean',
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:13',
            'employer_registration' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'municipality' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        // Verify code uniqueness within organization
        if ($organization->workCenters()->where('code', $validated['code'])->exists()) {
            return back()->withErrors(['code' => 'El código ya existe para esta organización.']);
        }

        $workCenter = $organization->workCenters()->create($validated);

        return redirect()
            ->route('organizations.work-centers.index', $organization)
            ->with('flash', [
                'type' => 'success',
                'title' => 'Centro de trabajo creado',
                'message' => "Centro '{$workCenter->name}' creado exitosamente.",
            ]);
    }

    /**
     * Show the form for editing a work center
     */
    public function edit(Organization $organization, WorkCenter $workCenter): Response
    {
        // Ensure work center belongs to organization
        if ($workCenter->organization_id !== $organization->id) {
            abort(403, 'No autorizado.');
        }

        $workCenter->loadCount(['quizzes', 'paperEvaluations' => function ($query) {
            $query->where('processing_status', 'completed');
        }]);

        return Inertia::render('WorkCenters/Edit', [
            'title' => 'Editar Centro de Trabajo',
            'organization' => $organization->only(['id', 'name']),
            'workCenter' => $workCenter,
        ]);
    }

    /**
     * Update the specified work center
     */
    public function update(Request $request, Organization $organization, WorkCenter $workCenter): RedirectResponse
    {
        // Ensure work center belongs to organization
        if ($workCenter->organization_id !== $organization->id) {
            abort(403, 'No autorizado.');
        }

        $validated = $request->validate([
            'code' => 'required|string|size:4',
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(WorkCenterType::values())],
            'is_primary' => 'boolean',
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:13',
            'employer_registration' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'municipality' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        // Verify code uniqueness (excluding current work center)
        if ($organization->workCenters()
            ->where('code', $validated['code'])
            ->where('id', '!=', $workCenter->id)
            ->exists()) {
            return back()->withErrors(['code' => 'El código ya existe para esta organización.']);
        }

        $workCenter->update($validated);

        return redirect()
            ->route('organizations.work-centers.index', $organization)
            ->with('flash', [
                'type' => 'success',
                'title' => 'Centro actualizado',
                'message' => "Centro '{$workCenter->name}' actualizado exitosamente.",
            ]);
    }

    /**
     * Remove (soft delete) the specified work center
     */
    public function destroy(Organization $organization, WorkCenter $workCenter): RedirectResponse
    {
        // Ensure work center belongs to organization
        if ($workCenter->organization_id !== $organization->id) {
            abort(403, 'No autorizado.');
        }

        // Prevent deletion of primary work center
        if ($workCenter->is_primary) {
            return back()->with('flash', [
                'type' => 'error',
                'title' => 'No se puede eliminar',
                'message' => 'No se puede eliminar el centro de trabajo primario.',
            ]);
        }

        $workCenter->delete();

        return redirect()
            ->route('organizations.work-centers.index', $organization)
            ->with('flash', [
                'type' => 'info',
                'title' => 'Centro eliminado',
                'message' => "Centro '{$workCenter->name}' eliminado exitosamente.",
            ]);
    }

    /**
     * Descargar plantilla Excel con los centros de trabajo actuales
     */
    public function downloadTemplate(Organization $organization)
    {
        $filename = 'centros_trabajo_'.$organization->folio_organization.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new WorkCentersExport($organization), $filename);
    }

    /**
     * Importar centros de trabajo desde archivo Excel
     */
    public function import(Request $request, Organization $organization)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $import = new WorkCentersImport($organization);

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
                    'title' => 'Importación de Centros de Trabajo',
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

    /**
     * Display work centers assigned to the authenticated user
     */
    public function myWorkCenters(): Response
    {
        $user = auth()->user();

        $workCenters = $user->workCenters()
            ->with('organization:id,name')
            ->withCount(['paperEvaluations' => function ($query) {
                $query->where('processing_status', 'completed');
            }])
            ->get()
            ->map(function ($workCenter) {
                return [
                    'id' => $workCenter->id,
                    'code' => $workCenter->code,
                    'name' => $workCenter->name,
                    'work_center_type' => $workCenter->work_center_type,
                    'is_primary' => $workCenter->is_primary,
                    'organization_id' => $workCenter->organization_id,
                    'organization_name' => $workCenter->organization->name ?? 'N/A',
                    'paper_evaluations_count' => $workCenter->paper_evaluations_count,
                    'has_evaluations' => $workCenter->paper_evaluations_count > 0,
                ];
            });

        return Inertia::render('WorkCenters/MyWorkCenters', [
            'workCenters' => $workCenters,
        ]);
    }
}
