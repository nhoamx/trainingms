<?php

namespace App\Jobs;

use App\Models\Evaluation;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fullPath;
    protected $containerName;

    // Puedes aumentar el timeout si se requiere más tiempo (en segundos)
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
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Cache::put('process_status', [
            'status' => 'running',
            'finished' => false,
            'message' => 'El procesamiento ha iniciado'
        ]);

        // 1. Definir el destino fijo en el contenedor y copiar el PDF
        $destinationPath = "/app/input/evaluation.pdf";
        $copyCommand = "docker cp " . escapeshellarg($this->fullPath) . " {$this->containerName}:" . escapeshellarg($destinationPath);
        exec($copyCommand, $copyOutput, $copyReturn);
        Log::info('Job - Comando ejecutado: ' . $copyCommand);
        if ($copyReturn !== 0) {
            Log::error('Job - Error al copiar el archivo al contenedor. Código: ' . $copyReturn . '. Salida: ' . json_encode($copyOutput));
            return;
        }

        // 2. Ejecutar el comando dentro del contenedor (por ejemplo, correr main.py)
        $execCommand = "docker exec {$this->containerName} python /app/main.py";
        exec($execCommand, $execOutput, $execReturn);
        Log::info('Job - Comando ejecutado: ' . $execCommand);
        if ($execReturn !== 0) {
            Log::error('Job - Error al ejecutar el comando en el contenedor. Código: ' . $execReturn . '. Salida: ' . json_encode($execOutput));
            return;
        }

        // 3. Procesar los JSON generados
        // Suponemos que el script Python genera los JSON en una carpeta "output" que está mapeada al host
        $outputFolder = storage_path('app/output');
        $jsonFiles = glob(base_path('docker/output') . '/*.json');

        if (!$jsonFiles) {
            Log::warning('Job - No se encontraron archivos JSON en la carpeta: ' . $outputFolder);
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
            // - document_id: los dos primeros dígitos (o ajusta la lógica según evolucione el requisito)
            $documentId = substr($baseName, 0, 2);
            // - folio: los últimos 4 dígitos
            $folio = substr($baseName, -4);
            // - organization: lo que quede en el medio
            $organizationNumber = substr($baseName, 2, strlen($baseName) - 2 - 4);

            // Buscar la organización según el folio de la organización
            $organization = Organization::where('folio_organization', $organizationNumber)->first();
            if (!$organization) {
                Log::warning("Job - No se encontró organización para el número: " . $organizationNumber);
            }

            // Leer el contenido del archivo JSON
            $jsonContent = file_get_contents($jsonFile);
            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Job - Error al decodificar JSON del archivo: " . $jsonFile);
                continue;
            }

            // Guardar en base de datos
            try {
                Evaluation::create([
                    'document_id'    => $documentId,
                    'folio'          => $folio,
                    'organization_id'=> $organization ? $organization->id : null,
                    'data'           => $data,  // Asumiendo que el campo en la base de datos es de tipo JSON
                ]);
                Log::info("Job - Evaluation guardada para archivo: " . $baseName);
            } catch (\Exception $e) {
                Log::error("Job - Error al guardar evaluation para archivo {$baseName}: " . $e->getMessage());
            }
        }

        Cache::put('process_status', [
            'status' => 'finished',
            'finished' => true,
            'message' => 'El procesamiento ha finalizado'
        ]);
    }
}
