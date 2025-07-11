<?php

namespace App\Console\Commands;

use App\Models\Evaluation;
use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateEdadFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:edad-fields';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra los campos edad_d1 y edad_d2 existentes a un campo combinado edad';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando migración de campos edad_d1 y edad_d2 a edad...');

        // Obtener todas las evaluaciones de tipo V
        $evaluations = Evaluation::where('reference_guide', 'V')->get();
        
        if ($evaluations->count() === 0) {
            $this->info('No se encontraron evaluaciones de tipo V para procesar.');
            return 0;
        }

        $this->info("Procesando {$evaluations->count()} evaluaciones de tipo V...");

        $bar = $this->output->createProgressBar($evaluations->count());
        $bar->start();

        $processed = 0;
        $created = 0;

        DB::beginTransaction();
        try {
            foreach ($evaluations as $evaluation) {
                // Buscar los registros de edad_d1 y edad_d2 en la tabla questions
                $edadD1 = Question::where('evaluation_id', $evaluation->id)
                    ->where('question', 'edad_d1')
                    ->first();
                    
                $edadD2 = Question::where('evaluation_id', $evaluation->id)
                    ->where('question', 'edad_d2')
                    ->first();

                // Si ambos campos existen, crear el campo combinado
                if ($edadD1 && $edadD2) {
                    $edadCombinada = $edadD1->answer . '' . $edadD2->answer;

                    // Verificar si ya existe el registro de 'edad'
                    $existingEdad = Question::where('evaluation_id', $evaluation->id)
                        ->where('question', 'edad')
                        ->first();

                    if (!$existingEdad) {
                        // Crear el nuevo registro para 'edad'
                        Question::create([
                            'evaluation_id' => $evaluation->id,
                            'personal_id' => $edadD1->personal_id,
                            'question' => 'edad',
                            'answer' => $edadCombinada,
                            'reference_guide' => $evaluation->reference_guide,
                        ]);
                        
                        $created++;
                    }
                    
                    $processed++;
                }

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine();
            
            $this->info("¡Migración completada exitosamente!");
            $this->info("Evaluaciones procesadas: {$processed}");
            $this->info("Registros 'edad' creados: {$created}");
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Error durante la migración: ' . $e->getMessage());
            Log::error('Error al migrar campos edad: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}
