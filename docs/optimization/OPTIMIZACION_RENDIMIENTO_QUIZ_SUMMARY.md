# Optimización de Rendimiento (Resumen Ejecutivo)

Este resumen sintetiza los puntos críticos del documento "docs/optimization/OPTIMIZACION_RENDIMIENTO_QUIZ.md" y agrega ejemplos mínimos para uso inmediato.

## ¿Qué se logró?
- Procesamiento asíncrono de envíos de evaluaciones (colas) → respuesta al usuario en ~300ms.
- Sistema robusto de reintentos, estados y recuperación ante fallos.
- Límite de tasa por IP/quiz para evitar abuso (3/hora).
- Manejo optimizado de inserciones (batch insert) y memoria.

## Componentes clave
- Job: app/Jobs/ProcessQuizSubmission.php (ShouldQueue, timeout 5m, retries 3)
- Estado: app/Models/SubmissionStatus.php (PENDING → PROCESSING → COMPLETED/FAILED)
- Middleware: app/Http/Middleware/RateLimitQuizSubmissions.php
- Job opcional: app/Jobs/ProcessIneImages.php (archivos INE)

## Flujo mínimo (contrato)
- Input: payload JSON de respuestas (Referencias I/III/V, Cisneros, campos personalizados, imágenes opcionales)
- Output: registros almacenados y SubmissionStatus actualizado
- Errores: marcados en SubmissionStatus (status=FAILED, error_message), reintentos automáticos (máx. 3)
- Éxito: status=COMPLETED, datos insertados en lotes (500)

## Ejemplos

### 1) Controlador (fragmento)
```php
// app/Http/Controllers/QuizController.php
public function submit(Request $request, Quiz $quiz): RedirectResponse
{
    $validated = $request->validate([
        'answers' => ['required'],
        // ... otros campos según el quiz
    ]);

    $submission = SubmissionStatus::create([
        'quiz_id' => $quiz->id,
        'status' => 'PENDING',
        'payload' => $validated, // snapshot completo
    ]);

    ProcessQuizSubmission::dispatch($submission->id)->onQueue('default');

    return back()->with('message', 'Tu evaluación fue recibida y se procesará en segundo plano.');
}
```

### 2) Inserción por lotes (fragmento)
```php
// app/Jobs/ProcessQuizSubmission.php
$chunks = array_chunk($records, 500);
foreach ($chunks as $chunk) {
    OnlineAnswer::insert($chunk);
}
```

### 3) Rate limiting (aplicación middleware)
```php
// bootstrap/app.php (Laravel 11)
->withMiddleware(function ($middleware) {
    $middleware->alias(['quiz.rate' => \App\Http\Middleware\RateLimitQuizSubmissions::class]);
})
// routes/web.php
auth()->group(function () {
    Route::post('/quiz/{quiz}/submit', [QuizController::class, 'submit'])
        ->middleware('quiz.rate');
});
```

### 4) Cola en desarrollo
```bash
php artisan queue:work --tries=3
```

## Edge cases cubiertos
- Payload grande → procesamiento por lotes + memoria acotada.
- Timeouts o fallos intermitentes → reintentos progresivos.
- Envíos duplicados o spam → middleware de tasa por IP/quiz.
- Archivos INE inválidos → validación y manejo de errores específicos.

## Beneficios directos
- UX: respuesta casi instantánea; no bloquea navegación.
- Confiabilidad: snapshot de datos y recuperación post-fallo.
- Escalabilidad: procesamiento paralelo con workers.
- Observabilidad: estados y logs claros por envío.

Para detalles completos, ver: docs/optimization/OPTIMIZACION_RENDIMIENTO_QUIZ.md
