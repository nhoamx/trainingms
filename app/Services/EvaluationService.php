<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Evaluation;

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
                    'total_score' => $evaluation->answers->sum('score')
                ];
            });
    }

    public function getAllEvaluationsByOrganization()
    {
        // Solo traemos las organizaciones y un conteo de evaluaciones
        return Organization::withCount('evaluations')
            ->get()
            ->map(function ($organization) {
                return [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'logo' => $organization->logo,
                    'evaluations_count' => $organization->evaluations_count,
                    // Indicador para ver el reporte, el frontend usará el id
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
