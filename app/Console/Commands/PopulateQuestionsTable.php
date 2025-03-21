<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Dimension;
use App\Models\Domain;
use App\Models\Evaluation;
use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PopulateQuestionsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popula la tabla de preguntas desde las evaluaciones existentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando población de la tabla questions...');

        $evaluationsCount = Evaluation::count();
        if ($evaluationsCount === 0) {
            $this->error('No hay evaluaciones para procesar.');
            return 1;
        }

        $this->info("Procesando {$evaluationsCount} evaluaciones...");

        $bar = $this->output->createProgressBar($evaluationsCount);
        $bar->start();

        DB::beginTransaction();
        try {
            // Procesar cada evaluación
            Evaluation::chunk(100, function ($evaluations) use ($bar) {
                foreach ($evaluations as $evaluation) {
                    $this->processEvaluation($evaluation);
                    $bar->advance();
                }
            });

            DB::commit();
            $bar->finish();
            $this->newLine();
            $this->info('¡Tabla de preguntas poblada exitosamente!');

            // Mostrar estadísticas
            $questionsCount = Question::count();
            $this->info("Total de preguntas creadas: {$questionsCount}");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Error al procesar las evaluaciones: ' . $e->getMessage());
            Log::error('Error al poblar la tabla questions: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Procesa una evaluación individual y guarda sus preguntas.
     */
    private function processEvaluation(Evaluation $evaluation)
    {
        // Verificar si la evaluación tiene datos
        if (!$evaluation->data || !is_array($evaluation->data)) {
            return;
        }

        // Determinar el tipo de procesamiento basado en reference_guide
        switch ($evaluation->reference_guide) {
            case 'I':
                $this->processTypeI($evaluation);
                break;
            case 'III':
                $this->processTypeIII($evaluation);
                break;
            case 'V':
                $this->processTypeV($evaluation);
                break;
            default:
                // Tipo desconocido, tratar como genérico
                $this->processGeneric($evaluation);
                break;
        }
    }

    /**
     * Procesa evaluaciones de tipo I (preguntas formato pregunta_X).
     */
    private function processTypeI(Evaluation $evaluation)
    {
        foreach ($evaluation->data as $questionKey => $answer) {
            // Ignorar si no es una pregunta válida
            if (!is_string($questionKey) || !str_starts_with($questionKey, 'pregunta_')) {
                continue;
            }

            // Crear la pregunta
            Question::create([
                'evaluation_id' => $evaluation->id,
                'question' => $questionKey,
                'answer' => $answer,
                'reference_guide' => $evaluation->reference_guide,
                // Nota: domain_id, dimension_id, category_id se pueden agregar
                // cuando se defina la estructura de categorización
            ]);
        }
    }

    /**
     * Procesa evaluaciones de tipo III (preguntas formato numérico).
     */
    private function processTypeIII(Evaluation $evaluation)
    {
        foreach ($evaluation->data as $questionKey => $answer) {
            // Verificar si es un número (pregunta)
            if (!is_numeric($questionKey) && !preg_match('/^\d+$/', $questionKey)) {
                continue;
            }

            // Obtener información sobre la dimensión/dominio/categoría
            $dimensionInfo = $this->getDimensionInfo($questionKey, $evaluation->reference_guide);

            // Crear la pregunta
            Question::create([
                'evaluation_id' => $evaluation->id,
                'question' => $questionKey,
                'answer' => $answer,
                'domain_id' => $dimensionInfo['domain_id'] ?? null,
                'dimension_id' => $dimensionInfo['dimension_id'] ?? null,
                'category_id' => $dimensionInfo['category_id'] ?? null,
                'value' => $this->getValueForAnswer($answer, $evaluation->reference_guide),
                'reference_guide' => $evaluation->reference_guide,
            ]);
        }
    }

    /**
     * Procesa evaluaciones de tipo V (datos demográficos).
     */
    private function processTypeV(Evaluation $evaluation)
    {
        foreach ($evaluation->data as $questionKey => $answer) {
            if (!is_string($questionKey)) {
                continue;
            }

            // Crear la pregunta
            Question::create([
                'evaluation_id' => $evaluation->id,
                'question' => $questionKey,
                'answer' => $answer,
                'reference_guide' => $evaluation->reference_guide,
            ]);
        }
    }

    /**
     * Procesa evaluaciones de tipo genérico.
     */
    private function processGeneric(Evaluation $evaluation)
    {
        foreach ($evaluation->data as $questionKey => $answer) {
            if (!is_string($questionKey)) {
                continue;
            }

            // Crear la pregunta
            Question::create([
                'evaluation_id' => $evaluation->id,
                'question' => $questionKey,
                'answer' => $answer,
                'reference_guide' => $evaluation->reference_guide,
            ]);
        }
    }

    /**
     * Obtiene información sobre dimensión/dominio/categoría para una pregunta.
     * Este método debería implementarse para mapear las preguntas a sus dimensiones.
     * Nota: Esta es una implementación básica, debe personalizarse según las reglas específicas.
     */
    private function getDimensionInfo($questionNumber, $referenceGuide)
    {
        // Aquí deberías implementar la lógica para mapear las preguntas
        // a sus dimensiones, dominios y categorías basado en la configuración.
        // Por ejemplo, usar config('question_dimensions') como se hace en ProcessEvaluation.

        $dimensions = config('question_dimensions', []);
        foreach ($dimensions as $categoryName => $domains) {
            foreach ($domains as $domainName => $dimensionGroups) {
                foreach ($dimensionGroups as $dimensionName => $questions) {
                    if (in_array($questionNumber, $questions)) {
                        // Buscar las entidades en la base de datos
                        $category = Category::firstWhere('name', $categoryName);
                        if (!$category) continue;

                        $domain = Domain::where('name', $domainName)
                                         ->where('category_id', $category->id)
                                         ->first();
                        if (!$domain) continue;

                        $dimension = Dimension::where('name', $dimensionName)
                                              ->where('domain_id', $domain->id)
                                              ->first();

                        return [
                            'category_id' => $category->id,
                            'domain_id' => $domain->id,
                            'dimension_id' => $dimension->id ?? null,
                        ];
                    }
                }
            }
        }

        return [];
    }

    /**
     * Obtiene el valor numérico para una respuesta.
     */
    private function getValueForAnswer($answer, $referenceGuide)
    {
        // Basado en la referencia guía y la respuesta, determinar el valor
        $answerValues = config('answer_values', []);

        if (isset($answerValues['group1']['values'][$answer])) {
            return $answerValues['group1']['values'][$answer];
        } elseif (isset($answerValues['group2']['values'][$answer])) {
            return $answerValues['group2']['values'][$answer];
        }

        return null;
    }
}
