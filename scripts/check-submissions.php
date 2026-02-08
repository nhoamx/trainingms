<?php

/**
 * Script para verificar el estado de los submissions
 * Uso: php scripts/check-submissions.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SubmissionStatus;

echo "\n=== Estado de Submissions ===\n\n";

// Pending
$pending = SubmissionStatus::where('status', 'pending')->count();
echo "⏳ Pending (esperando procesamiento): {$pending}\n";

// Processing
$processing = SubmissionStatus::where('status', 'processing')->count();
echo "⚙️  Processing (procesando ahora): {$processing}\n";

// Completed
$completed = SubmissionStatus::where('status', 'completed')->count();
echo "✅ Completed (completados): {$completed}\n";

// Failed
$failed = SubmissionStatus::where('status', 'failed')->count();
echo "❌ Failed (fallidos): {$failed}\n";

echo "\n=== Total: ".($pending + $processing + $completed + $failed)." submissions ===\n";

// Mostrar últimos 5 pending si hay
if ($pending > 0) {
    echo "\n=== Últimos 5 Pending ===\n";
    $lastPending = SubmissionStatus::where('status', 'pending')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'folio', 'created_at']);

    foreach ($lastPending as $sub) {
        $age = now()->diffForHumans($sub->created_at);
        echo "  • Folio {$sub->folio} - ID {$sub->id} - Creado {$age}\n";
    }
}

// Mostrar últimos 5 failed si hay
if ($failed > 0) {
    echo "\n=== Últimos 5 Failed ===\n";
    $lastFailed = SubmissionStatus::where('status', 'failed')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'folio', 'error_message', 'created_at']);

    foreach ($lastFailed as $sub) {
        echo "  • Folio {$sub->folio} - ID {$sub->id}\n";
        echo '    Error: '.substr($sub->error_message, 0, 100)."...\n";
    }
}

echo "\n";
