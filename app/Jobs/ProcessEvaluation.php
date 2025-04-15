<?php

namespace App\Jobs;

use App\Models\Answer;
use App\Models\Evaluation;
use App\Models\Organization;
use App\Models\Dimension;
use App\Events\EvaluationProcessingStatusChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;

class ProcessEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fullPath;
    protected $containerName;
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param string $fullPath  Ruta completa del PDF almacenado en host
     * @param string $containerName  Nombre o ID del contenedor Docker
     */
    public function __construct($fullPath, $containerName)
    {
        $this->fullPath = $fullPath;
        $this->containerName = $containerName;
    }

    /**
     * Encuentra la dimensión basada en el número de pregunta
     */
    protected function findDimensionForQuestion($questionNumber)
    {
        $dimensions = config('question_dimensions');
        foreach ($dimensions as $domainName => $categories) {
            foreach ($categories as $categoryName => $subcategories) {
                foreach ($subcategories as $dimensionName => $questions) {
                    if (in_array($questionNumber, $questions)) {
                        // Buscar o crear la dimensión en la base de datos
                        return Dimension::firstOrCreate([
                            'name' => $dimensionName
                        ]);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Calcula el score basado en la respuesta
     */
    protected function calculateScore($questionNumber, $answer)
    {
        $answerValues = config('answer_values');

        // Determinar a qué grupo pertenece la pregunta
        $group = in_array($questionNumber, $answerValues['group1']['questions']) ? 'group1' : 'group2';

        // Obtener el valor correspondiente a la respuesta
        return $answerValues[$group]['values'][$answer] ?? null;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Broadcast initial status
        broadcast(new EvaluationProcessingStatusChanged(
            'running',
            'El procesamiento ha iniciado',
            false
        ));

        // 1. Definir el destino fijo en el contenedor y copiar el PDF
        $destinationPath = "/app/input/evaluation.pdf";
        $copyCommand = "docker cp " . escapeshellarg($this->fullPath) . " {$this->containerName}:" . escapeshellarg($destinationPath);
        exec($copyCommand, $copyOutput, $copyReturn);
        Log::info('Job - Comando ejecutado: ' . $copyCommand);
        if ($copyReturn !== 0) {
            Log::error('Job - Error al copiar el archivo al contenedor. Código: ' . $copyReturn . '. Salida: ' . json_encode($copyOutput));
            broadcast(new EvaluationProcessingStatusChanged(
                'error',
                'Error al copiar el archivo al contenedor',
                false
            ));
            return;
        }

        // 2. Ejecutar el comando dentro del contenedor (por ejemplo, correr main.py)
        $execCommand = "docker exec {$this->containerName} python /app/main.py";
        exec($execCommand, $execOutput, $execReturn);
        Log::info('Job - Comando ejecutado: ' . $execCommand);
        if ($execReturn !== 0) {
            Log::error('Job - Error al ejecutar el comando en el contenedor. Código: ' . $execReturn . '. Salida: ' . json_encode($execOutput));
            broadcast(new EvaluationProcessingStatusChanged(
                'error',
                'Error al ejecutar el procesamiento en el contenedor',
                false
            ));
            return;
        }

        // 3. Procesar los JSON generados
        // Suponemos que el script Python genera los JSON en una carpeta "output" que está mapeada al host
        $outputFolder = storage_path('app/output');
        $jsonFiles = glob(base_path('docker/output') . '/*.json');

        if (!$jsonFiles) {
            Log::warning('Job - No se encontraron archivos JSON en la carpeta: ' . $outputFolder);
            broadcast(new EvaluationProcessingStatusChanged(
                'error',
                'No se encontraron archivos JSON para procesar',
                false
            ));
            return;
        }

        foreach ($jsonFiles as $jsonFile) {
            $baseName = basename($jsonFile, '.json'); // Ejemplo: "121470092"
            // Verificar que el nombre tenga la longitud mínima esperada
            if (strlen($baseName) < 6) {
                Log::error("Job - Nombre de archivo JSON inválido: " . $baseName);
                continue;
            }
            // Extraer datos:
            // - document_id: los dos primeros dígitos
            $documentId = substr($baseName, 0, 2);
            // - organization: los dígitos del medio después del document_id
            $organizationNumber = substr($baseName, 2, strlen($baseName) - 2 - 4);
            // - personal_id: los últimos 4 dígitos
            $personalId = substr($baseName, -4);

            // Validar que el personal_id sea numérico y tenga 4 dígitos
            if (!is_numeric($personalId) || strlen($personalId) !== 4) {
                Log::error("Job - personal_id inválido para el archivo: " . $baseName);
                continue;
            }

            Log::info("Job - Procesando archivo con personal_id: " . $personalId);

            // El folio es el número completo del documento
            $folio = $baseName;

            // Buscar la organización según el folio de la organización
            $organization = Organization::where('folio_organization', $organizationNumber)->first();
            if (!$organization) {
                Log::warning("Job - No se encontró organización para el número: " . $organizationNumber . ". Creando una nueva.");
                // Si no existe, crearla
                try {
                    $organization = Organization::create([
                        'name' => 'Organización ' . $organizationNumber, // Usamos el número como nombre
                        'folio_organization' => $organizationNumber,
                        // Puedes añadir valores por defecto para otros campos requeridos aquí
                        // 'field_name' => 'default_value',
                    ]);
                    Log::info("Job - Nueva organización creada con ID: " . $organization->id);
                } catch (\Exception $e) {
                    Log::error("Job - Error al crear la organización para el número {$organizationNumber}: " . $e->getMessage());
                    // Si hay error al crear la organización, podemos decidir si continuar
                    // sin organization_id o detener el procesamiento para este archivo.
                    // Por ahora, continuaremos sin organization_id (se asignará null más adelante).
                    $organization = null; // Aseguramos que organization sea null para el guardado
                }
            }

            // Leer el contenido del archivo JSON
            $jsonContent = file_get_contents($jsonFile);
            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Job - Error al decodificar JSON del archivo: " . $jsonFile);
                continue;
            }

            // Determinar la guía de referencia según el folio
            $referenceGuide = null;
            if (str_starts_with($folio, '12')) {
                $referenceGuide = 'III';
            } elseif (str_starts_with($folio, '17')) {
                $referenceGuide = 'V';
            } elseif (str_starts_with($folio, '13')) {
                $referenceGuide = 'I';
            }

            // Guardar en base de datos
            try {
                $evaluation = Evaluation::create([
                    'document_id'    => $documentId,
                    'folio'          => $folio,
                    'personal_id'    => $personalId,
                    'organization_id'=> $organization ? $organization->id : null,
                    'data'           => $data,
                    'reference_guide'=> $referenceGuide, // Asignar la guía de referencia
                ]);

                Log::info("Job - Evaluation creada con personal_id: " . $evaluation->personal_id);

                foreach ($data as $questionKey => $answer) {
                    // Ignorar respuestas nulas
                    if ($answer === null) {
                        Log::info("Pregunta {$questionKey} sin respuesta, se omite");
                        continue;
                    }

                    $dimensionId = null;
                    $score = null;

                    // Si es una pregunta numérica (01-72), procesamos dimensión y score
                    if (preg_match('/^[0-9]{2}$/', $questionKey) &&
                        intval($questionKey) >= 1 &&
                        intval($questionKey) <= 72) {

                        $questionNumber = intval($questionKey);

                        // Encontrar la dimensión correspondiente
                        $dimension = $this->findDimensionForQuestion($questionNumber);
                        if ($dimension) {
                            $dimensionId = $dimension->id;

                            // Calcular el score
                            $score = $this->calculateScore($questionNumber, $answer);
                        }
                    }

                    // Guardar la respuesta
                    Answer::create([
                        'evaluation_id' => $evaluation->id,
                        'dimension_id' => $dimensionId,
                        'question' => $questionKey,
                        'answer' => $answer,
                        'score' => $score
                    ]);
                }

                Log::info("Job - Evaluation guardada para archivo: " . $baseName);
            } catch (\Exception $e) {
                Log::error("Job - Error al guardar evaluation para archivo {$baseName}: " . $e->getMessage());
            }
        }

        // Una vez procesados todos los archivos JSON, ejecutar el comando para poblar Questions
        try {
            Log::info('Job - Ejecutando comando questions:populate...');
            Artisan::call('questions:populate');
            Log::info('Job - Comando questions:populate finalizado.');
        } catch (\Exception $e) {
            Log::error('Job - Error al ejecutar questions:populate: ' . $e->getMessage());
            // Decidir si el fallo en poblar questions debe marcar el job como fallido
            // Por ahora, solo lo registramos y continuamos para marcar el job principal como exitoso.
            broadcast(new EvaluationProcessingStatusChanged(
                'error',
                'Error al poblar la tabla de preguntas después del procesamiento.',
                false
            ));
            // Podrías querer retornar aquí o lanzar una excepción si este paso es crítico
            // return;
        }

        // Al final del método handle() de tu Job:
        $evaluationsPath = storage_path('app/public/evaluations');
        try {
            $files = File::files($evaluationsPath);
            foreach ($files as $file) {
                File::delete($file);
            }
            Log::info("Job - Carpeta de evaluations limpiada tras el procesamiento.");
        } catch (\Exception $e) {
            Log::error("Job - Error al limpiar carpeta evaluations: " . $e->getMessage());
        }

        // Broadcast completion status
        broadcast(new EvaluationProcessingStatusChanged(
            'finished',
            'El procesamiento ha finalizado exitosamente', // O actualizar mensaje si questions:populate falló
            false
        ));
    }
}
