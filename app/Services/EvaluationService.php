<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PaperEvaluation;

class EvaluationService
{
    public function getOrganizationEvaluations(Organization $organization)
    {
        return $organization->evaluations()
            ->with(['answers'])
            ->select('id', 'folio', 'created_at', 'reference_guide', 'organization_id', 'personal_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'folio' => $evaluation->folio,
                    'created_at' => $evaluation->created_at->format('Y-m-d H:i:s'),
                    'reference_guide' => $evaluation->reference_guide,
                    'organization_id' => $evaluation->organization_id,
                    'personal_id' => $evaluation->personal_id,
                    'total_score' => $evaluation->answers->sum('score'),
                ];
            });
    }

    public function getAllEvaluationsByOrganization()
    {
        // Traer organizaciones con conteo de quizzes
        $orgs = Organization::withCount('quizzes')->get();
        $paperCounts = PaperEvaluation::groupBy('organization_id')
            ->where('processing_status', 'completed')
            ->selectRaw('organization_id, COUNT(*) as count')
            ->pluck('count', 'organization_id');

        return $orgs->map(function ($organization) use ($paperCounts) {
            return [
                'id' => $organization->id,
                'name' => $organization->name,
                'logo' => $organization->logo,
                'evaluations_count' => $paperCounts[$organization->id] ?? 0,
                'online_quizzes_count' => $organization->quizzes_count ?? 0,
            ];
        });
    }

    public function getDemographicData(Organization $organization)
    {
        // Obtener todas las evaluaciones de la guía V para esta organización
        $guideVEvaluations = $organization->evaluations()
            ->where('reference_guide', 'V')
            ->get()
            ->groupBy('personal_id')
            ->map(function ($evaluations) {
                // Tomar la evaluación más reciente para cada personal_id
                return $evaluations->sortByDesc('created_at')->first();
            })
            ->values();

        // Obtener todas las evaluaciones de la guía III para esta organización
        $guideIIIEvaluations = $organization->evaluations()
            ->where('reference_guide', 'III')
            ->get()
            ->groupBy('personal_id')
            ->map(function ($evaluations) {
                // Tomar la evaluación más reciente para cada personal_id
                return $evaluations->sortByDesc('created_at')->first();
            })
            ->values();

        // Combinar los datos demográficos de la guía V con los resultados de la guía III
        $combinedData = $guideVEvaluations->map(function ($guideVEvaluation) use ($guideIIIEvaluations) {
            $guideIIIEvaluation = $guideIIIEvaluations->firstWhere('personal_id', $guideVEvaluation->personal_id);

            if ($guideIIIEvaluation) {
                return [
                    'demographic_data' => $guideVEvaluation->data,
                    'evaluation_data' => $guideIIIEvaluation->data,
                    'personal_id' => $guideVEvaluation->personal_id,
                    'guide_v_id' => $guideVEvaluation->id,
                    'guide_iii_id' => $guideIIIEvaluation->id,
                ];
            }

            return null;
        })->filter();

        return $combinedData;
    }
}
