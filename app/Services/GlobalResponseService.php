<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GlobalResponseService
{
    /**
     * Obtiene el conteo global de respuestas por opción (A, B, C, D, E)
     * Implementa el Query #8 de la guía III: Conteo global de respuestas por opción
     */
    public function getGlobalResponseCounts(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía especificada para consistencia
            $guidePersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guidePersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");

                return collect();
            }

            // Ejecutamos la consulta que obtiene el conteo global de respuestas por opción
            $results = Question::where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.answer')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'questions.answer',
                    DB::raw('COUNT(*) as total_responses')
                )
                ->groupBy('questions.answer')
                ->orderBy('questions.answer')
                ->get();

            // Aseguramos que todas las opciones de respuesta (A-E) estén representadas
            $responseMap = [
                'A' => ['label' => 'Siempre', 'value' => 0, 'color' => '#F44336'], // Rojo - Muy Alto
                'B' => ['label' => 'Casi siempre', 'value' => 0, 'color' => '#FFB300'], // Naranja - Alto
                'C' => ['label' => 'Algunas veces', 'value' => 0, 'color' => '#FFEB3B'], // Amarillo - Medio
                'D' => ['label' => 'Casi nunca', 'value' => 0, 'color' => '#8BC34A'], // Verde - Bajo
                'E' => ['label' => 'Nunca', 'value' => 0, 'color' => '#4DD0C6'], // Turquesa - Nulo
            ];

            // Llenamos el mapa con los resultados obtenidos
            foreach ($results as $result) {
                if (isset($responseMap[$result->answer])) {
                    $responseMap[$result->answer]['value'] = $result->total_responses;
                }
            }

            // Calculamos el total de respuestas
            $totalResponses = array_sum(array_column($responseMap, 'value'));

            // Añadimos porcentajes
            foreach ($responseMap as $key => $data) {
                $responseMap[$key]['percentage'] = $totalResponses > 0 ? ($data['value'] / $totalResponses) * 100 : 0;
            }

            return collect([
                'total_responses' => $totalResponses,
                'response_counts' => $responseMap,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener el conteo global de respuestas: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Obtiene el conteo de respuestas por categoría y opción
     * Implementa el Query #9 de la guía III: Conteo de respuestas por categoría y opción
     */
    public function getCategoryResponseCounts(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía especificada
            $guidePersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guidePersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");

                return collect();
            }

            // Ejecutamos la consulta que obtiene el conteo de respuestas por categoría y opción
            $results = Question::join('categories', 'questions.category_id', '=', 'categories.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.answer')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'categories.id as category_id',
                    'categories.name as category_name',
                    'questions.answer',
                    DB::raw('COUNT(*) as total_responses')
                )
                ->groupBy('categories.id', 'categories.name', 'questions.answer')
                ->orderBy('categories.name')
                ->orderBy('questions.answer')
                ->get();

            // Procesamos los resultados para crear un formato más útil para la visualización
            $categoryData = $this->processCategoryResults($results);

            return $categoryData;

        } catch (\Exception $e) {
            Log::error('Error al obtener el conteo de respuestas por categoría y opción: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Procesa los resultados de la consulta para estructurarlos por categoría
     */
    private function processCategoryResults(Collection $results): Collection
    {
        // Agrupamos los resultados por categoría
        $groupedResults = $results->groupBy('category_id');
        $categoryData = collect();

        foreach ($groupedResults as $categoryId => $categoryResponses) {
            $categoryName = $categoryResponses->first()->category_name;

            // Inicializamos contadores para cada tipo de respuesta
            $responsesByType = [
                'A' => 0, // Siempre
                'B' => 0, // Casi siempre
                'C' => 0, // Algunas veces
                'D' => 0, // Casi nunca
                'E' => 0, // Nunca
            ];

            // Sumamos las respuestas por tipo
            foreach ($categoryResponses as $response) {
                if (isset($responsesByType[$response->answer])) {
                    $responsesByType[$response->answer] = $response->total_responses;
                }
            }

            // Calculamos el total de respuestas para esta categoría
            $totalResponses = array_sum($responsesByType);

            // Calculamos porcentajes
            $percentages = [];
            foreach ($responsesByType as $type => $count) {
                $percentages[$type] = $totalResponses > 0 ? ($count / $totalResponses) * 100 : 0;
            }

            // Añadimos al resultado
            $categoryData->push([
                'id' => $categoryId,
                'name' => $categoryName,
                'responses' => $responsesByType,
                'percentages' => $percentages,
                'total' => $totalResponses,
            ]);
        }

        return $categoryData;
    }

    /**
     * Obtiene el conteo de personas únicas por categoría y opción de respuesta
     * Similar al Query #9 pero contando personas únicas en lugar de respuestas
     */
    public function getPersonCountByCategoryAndResponse(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía especificada
            $guidePersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guidePersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");

                return collect();
            }

            // Ejecutamos la consulta que obtiene el conteo de personas únicas por categoría y opción
            $results = Question::join('categories', 'questions.category_id', '=', 'categories.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.answer')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'categories.id as category_id',
                    'categories.name as category_name',
                    'questions.answer',
                    DB::raw('COUNT(DISTINCT questions.personal_id) as total_persons')
                )
                ->groupBy('categories.id', 'categories.name', 'questions.answer')
                ->orderBy('categories.name')
                ->orderBy('questions.answer')
                ->get();

            // Procesamos los resultados para crear un formato más útil para la visualización
            $categoryData = $this->processPersonResults($results);

            return $categoryData;

        } catch (\Exception $e) {
            Log::error('Error al obtener el conteo de personas por categoría y opción: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Procesa los resultados de la consulta para estructurarlos por categoría (versión personas)
     */
    private function processPersonResults(Collection $results): Collection
    {
        // Agrupamos los resultados por categoría
        $groupedResults = $results->groupBy('category_id');
        $categoryData = collect();

        foreach ($groupedResults as $categoryId => $categoryResponses) {
            $categoryName = $categoryResponses->first()->category_name;

            // Inicializamos contadores para cada tipo de respuesta
            $responsesByType = [
                'A' => 0, // Siempre
                'B' => 0, // Casi siempre
                'C' => 0, // Algunas veces
                'D' => 0, // Casi nunca
                'E' => 0, // Nunca
            ];

            // Sumamos las personas por tipo de respuesta
            foreach ($categoryResponses as $response) {
                if (isset($responsesByType[$response->answer])) {
                    $responsesByType[$response->answer] = $response->total_persons;
                }
            }

            // Calculamos el total de personas para esta categoría
            // Nota: No podemos simplemente sumar las personas de cada opción, ya que una persona puede contestar
            // diferentes opciones en diferentes preguntas de la misma categoría
            $totalUniquePersons = Question::join('categories', 'questions.category_id', '=', 'categories.id')
                ->where('categories.id', $categoryId)
                ->where('questions.reference_guide', 'III')
                ->whereNotNull('questions.answer')
                ->select(DB::raw('COUNT(DISTINCT questions.personal_id) as total_persons'))
                ->first()->total_persons;

            // Calculamos porcentajes
            $percentages = [];
            foreach ($responsesByType as $type => $count) {
                $percentages[$type] = $totalUniquePersons > 0 ? ($count / $totalUniquePersons) * 100 : 0;
            }

            // Añadimos al resultado
            $categoryData->push([
                'id' => $categoryId,
                'name' => $categoryName,
                'responses' => $responsesByType,
                'percentages' => $percentages,
                'total' => $totalUniquePersons,
            ]);
        }

        return $categoryData;
    }

    /**
     * Obtiene el conteo global de personas únicas por opción (A, B, C, D, E)
     * Similar al Query #8 pero contando personas únicas en lugar de respuestas
     */
    public function getGlobalPersonCounts(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía especificada para consistencia
            $guidePersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guidePersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");

                return collect();
            }

            // Ejecutamos la consulta que obtiene el conteo global de personas únicas por opción
            $results = Question::where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.answer')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'questions.answer',
                    DB::raw('COUNT(DISTINCT questions.personal_id) as total_persons')
                )
                ->groupBy('questions.answer')
                ->orderBy('questions.answer')
                ->get();

            // Aseguramos que todas las opciones de respuesta (A-E) estén representadas
            $responseMap = [
                'A' => ['label' => 'Siempre', 'value' => 0, 'color' => '#F44336'], // Rojo - Muy Alto
                'B' => ['label' => 'Casi siempre', 'value' => 0, 'color' => '#FFB300'], // Naranja - Alto
                'C' => ['label' => 'Algunas veces', 'value' => 0, 'color' => '#FFEB3B'], // Amarillo - Medio
                'D' => ['label' => 'Casi nunca', 'value' => 0, 'color' => '#8BC34A'], // Verde - Bajo
                'E' => ['label' => 'Nunca', 'value' => 0, 'color' => '#4DD0C6'], // Turquesa - Nulo
            ];

            // Llenamos el mapa con los resultados obtenidos
            foreach ($results as $result) {
                if (isset($responseMap[$result->answer])) {
                    $responseMap[$result->answer]['value'] = $result->total_persons;
                }
            }

            // Calculamos el total de personas únicas que han respondido a cualquier pregunta
            $totalUniquePersons = Question::where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.answer')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(DB::raw('COUNT(DISTINCT questions.personal_id) as total_persons'))
                ->first()->total_persons ?? 0;

            // Añadimos porcentajes
            foreach ($responseMap as $key => $data) {
                $responseMap[$key]['percentage'] = $totalUniquePersons > 0 ? ($data['value'] / $totalUniquePersons) * 100 : 0;
            }

            return collect([
                'total_persons' => $totalUniquePersons,
                'person_counts' => $responseMap,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener el conteo global de personas: '.$e->getMessage());

            return collect();
        }
    }
}
