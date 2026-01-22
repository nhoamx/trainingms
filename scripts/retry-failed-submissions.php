<?php

/**
 * Script para reprocesar submissions fallidos
 * Uso: php scripts/retry-failed-submissions.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\ProcessOnlineEvaluation;
use App\Models\SubmissionStatus;

echo "\n=== Reprocesando Submissions Fallidos ===\n\n";

$failedSubmissions = SubmissionStatus::where('status', 'failed')
    ->where('retry_count', '<', 3)
    ->get();

if ($failedSubmissions->isEmpty()) {
    echo "✅ No hay submissions fallidos para reprocesar\n\n";
    exit(0);
}

echo "Encontrados {$failedSubmissions->count()} submissions para reprocesar:\n\n";

foreach ($failedSubmissions as $submission) {
    echo "  • Folio {$submission->folio} - ID {$submission->id}\n";
    echo "    Intentos previos: {$submission->retry_count}\n";

    // Reset status to pending
    $submission->update([
        'status' => 'pending',
        'error_message' => null,
    ]);

    // Dispatch job again
    ProcessOnlineEvaluation::dispatch($submission->id);

    echo "    ✅ Job redespachado\n\n";
}

echo "=== Completado ===\n";
echo "Los jobs se procesarán cuando el queue worker esté activo.\n";
echo "Ejecuta: php artisan queue:work --queue=quiz_processing\n\n";
