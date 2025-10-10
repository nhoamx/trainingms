# Fix: OCR Processing Timing Issue - Polling Implementation

## Problema Identificado

En producción, el Job `ProcessPaperEvaluation` estaba fallando con el error:
```
"No se encontraron archivos JSON para procesar"
```

### Análisis del Problema

Revisando los logs de producción se identificó el siguiente patrón:

1. ✅ PDF se copia exitosamente al contenedor Docker
2. ✅ `docker exec python /app/main.py` retorna código 0
3. ✅ Log: "OCR processing completed successfully"
4. ❌ **Inmediatamente** busca archivos JSON → No los encuentra
5. ❌ Job falla y se reintenta (hasta exceder max attempts)

**Root Cause:** El comando `docker exec` retorna **inmediatamente** (código 0) incluso cuando el script Python todavía está procesando. El Job asumía que el código 0 significaba "procesamiento completo", pero en realidad solo significa "comando ejecutado correctamente".

El análisis OCR puede tomar varios minutos dependiendo del tamaño y complejidad del PDF. Los archivos JSON se crean **después** que el comando `docker exec` retorna.

## Solución Implementada

### 1. Polling Mechanism Incremental

Implementamos un sistema de **polling incremental** que procesa archivos JSON conforme van apareciendo:

```php
protected function processJsonResults(): void
{
    $outputFolder = base_path('docker/output');
    $maxAttempts = 120;     // 120 intentos (matches job timeout)
    $attemptDelay = 10;     // 10 segundos entre intentos
                            // = 20 minutos máximo de espera (igual al job timeout)
    
    $processedFiles = [];   // Track archivos ya procesados
    $noNewFilesCount = 0;   // Track tiempo sin nuevos archivos
    $maxNoNewFilesAttempts = 12; // Exit si no hay nuevos por 2 minutos
    
    // Loop de polling incremental
    while ($attempt < $maxAttempts) {
        $jsonFiles = glob($outputFolder.'/*.json');
        
        // Solo procesar archivos NUEVOS (no procesados aún)
        $newFiles = array_diff($jsonFiles ?: [], $processedFiles);
        
        if (!empty($newFiles)) {
            // Procesar inmediatamente los nuevos archivos
            foreach ($newFiles as $jsonFile) {
                $this->processJsonFile($jsonFile);
                $processedFiles[] = $jsonFile; // Marcar como procesado
            }
            
            $noNewFilesCount = 0; // Reset contador
            
            broadcast("Procesados ".count($processedFiles)." formularios...");
        } else {
            $noNewFilesCount++;
            
            // Si ya procesamos al menos 1 y no hay nuevos por 2 min, terminar
            if (count($processedFiles) > 0 && $noNewFilesCount >= 12) {
                break; // Procesamiento completo
            }
        }
        
        sleep(10); // Esperar 10 segundos
    }
}
```

**Características Mejoradas:**

1. **Procesamiento Incremental:** 
   - Procesa archivos JSON conforme aparecen (1 por 1)
   - No espera a que todos terminen
   - Usa `array_diff()` para detectar solo archivos nuevos

2. **No Duplicados:**
   - `$processedFiles` rastrea archivos ya procesados
   - `updateOrCreate()` en `processJsonFile()` previene duplicados en DB
   - Cada archivo se procesa exactamente una vez

3. **Early Exit Inteligente:**
   - Si ya procesó archivos Y no aparecen nuevos por 2 minutos → Sale exitosamente
   - Evita esperar innecesariamente los 20 minutos completos

4. **Timeout Sincronizado:**
   - Job timeout: 20 minutos (`public int $timeout = 1200`)
   - Polling max: 20 minutos (`120 attempts × 10s = 1200s`)
   - Perfectamente alineados

**Características:**
- ⏱️ Espera hasta **20 minutos** (igual al timeout del Job)
- 🔄 Verifica cada **10 segundos** si aparecieron archivos JSON nuevos
- ✅ Procesa archivos **incrementalmente** (conforme aparecen)
- 🚫 Evita **duplicados** - rastrea archivos ya procesados
- 🏁 **Exit automático** si no hay nuevos archivos por 2 minutos
- 📊 Logging detallado de cada archivo procesado
- 🚨 Timeout con mensaje claro si no se encuentra ningún archivo

### 2. Progress Broadcasts Mejorados

Agregamos broadcasts informativos en cada etapa del proceso:

```php
public function handle(): void
{
    broadcast('Copiando PDF al contenedor...');
    $this->copyPdfToContainer();
    
    broadcast('Ejecutando análisis OCR...');
    $this->executeOcrProcessing();
    
    broadcast('Esperando resultados del análisis...');
    $this->processJsonResults();  // ← Aquí ocurre el polling
    
    broadcast('Guardando resultados en la base de datos...');
    $this->cleanupFiles();
    
    broadcast('El procesamiento ha finalizado exitosamente');
}
```

### 3. Updates Durante el Polling

El usuario recibe actualizaciones **cada 30 segundos** con el progreso real:

```php
// Cada 3 intentos (30 segundos)
if ($attempt % 3 === 0) {
    $waitedTime = $attempt * $attemptDelay;
    $message = count($processedFiles) > 0 
        ? "Analizando documento... (".count($processedFiles)." procesados, {$waitedTime}s transcurridos)"
        : "Analizando documento... ({$waitedTime}s transcurridos)";
    
    broadcast(new EvaluationProcessingStatusChanged(
        'running',
        $message,
        false,
        $this->initiatorUserId
    ));
}
```

**El usuario ve:**
- "Analizando documento... (30s transcurridos)" ← Sin archivos aún
- "Analizando documento... (1 procesados, 60s transcurridos)" ← Primer archivo
- "Analizando documento... (3 procesados, 90s transcurridos)" ← Tres archivos
- "Procesados 5 formularios..." ← Actualización inmediata al procesar cada nuevo archivo
- etc.

### 4. Logging Mejorado

Agregamos logging exhaustivo para debugging, incluyendo archivos procesados:

```php
Log::info('Waiting for JSON files to be created...', [
    'output_folder' => $outputFolder,
    'max_wait_time' => '1200 seconds',
]);

Log::info('New JSON files found', [
    'new_count' => 2,
    'total_processed' => 3,
    'attempt' => 15,
]);

Log::info('Processed file', [
    'file' => 'ABC-001-REF3-00001.json',
    'total_processed' => 4,
]);

Log::info('No new files detected for 2 minutes. Assuming processing complete.', [
    'total_processed' => 5,
    'idle_time' => '120 seconds',
]);

Log::info('JSON processing completed', [
    'total_processed' => 5,
    'total_wait_time' => '350 seconds',
]);
```

### 5. Captura de Output del Script Python

Agregamos `2>&1` al comando y logging del output:

```php
$execCommand = "docker exec {$this->containerName} python /app/main.py 2>&1";

exec($execCommand, $execOutput, $execReturn);

if (!empty($execOutput)) {
    Log::info('Python script output:', ['output' => implode("\n", $execOutput)]);
}
```

Esto permite ver cualquier error o warning del script Python directamente en los logs de Laravel.

## Flujo Mejorado

### Antes (Fallaba)
```
1. Copiar PDF → ✅
2. docker exec python → ✅ (retorna inmediatamente)
3. Buscar JSONs → ❌ (no existen aún)
4. FALLO
```

### Primera Versión (Polling Básico)
```
1. Copiar PDF → ✅
2. docker exec python → ✅
3. Polling Loop → 🔄 Esperar todos los JSONs
   - Espera 0s-600s hasta que TODOS los archivos estén listos
4. Procesar todos los JSONs de golpe
5. PROBLEMA: Intentaba procesar los mismos archivos múltiples veces
```

### Versión Final (Polling Incremental) ✅
```
1. Copiar PDF → ✅ Broadcast: "Copiando PDF..."
2. docker exec python → ✅ Broadcast: "Ejecutando análisis OCR..."
3. Polling Loop Incremental → 🔄 Broadcast: "Esperando resultados..."
   - Intento 1 (0s): No hay archivos
   - Intento 2 (10s): No hay archivos
   - Intento 3 (20s): No hay archivos → Broadcast: "Analizando documento... (30s transcurridos)"
   - Intento 5 (40s): ¡Aparece ABC-001-REF3-00001.json!
     → Procesar inmediatamente
     → Marcar como procesado
     → Broadcast: "Procesados 1 formularios..."
   - Intento 6 (50s): No hay nuevos (solo el procesado)
   - Intento 8 (70s): ¡Aparece ABC-001-REF3-00002.json!
     → Procesar inmediatamente (sin reprocesar 00001)
     → Broadcast: "Procesados 2 formularios..."
   - Intento 9-20: Sin nuevos archivos
   - Después de 2 min sin nuevos → Exit exitoso
4. Completado → ✅ Broadcast: "Procesamiento finalizado"
```

## Configuración

### Parámetros Ajustables

```php
// En processJsonResults()
$maxAttempts = 120;     // Máximo de intentos (matches job timeout)
$attemptDelay = 10;     // Segundos entre intentos
// Tiempo máximo = 120 * 10 = 1200 segundos = 20 minutos

$maxNoNewFilesAttempts = 12; // Exit si no hay nuevos por 2 minutos
// Idle timeout = 12 * 10 = 120 segundos = 2 minutos

// Frecuencia de broadcast al usuario
if ($attempt % 3 === 0) // Cada 3 intentos = 30 segundos
```

**Recomendaciones:**
- **maxAttempts:** Debe igualar el job timeout (120 attempts = 20 min = 1200s)
- **attemptDelay:** 10 segundos es óptimo (balance entre responsividad y carga)
- **maxNoNewFilesAttempts:** 12 intentos (2 min) es suficiente para detectar fin de procesamiento
- Para PDFs muy grandes (100+ páginas): Aumentar timeout del Job y maxAttempts proporcionalmente

### Timeout del Job

El timeout del Job debe igualar el polling máximo:

```php
public int $timeout = 1200; // 20 minutos
// = maxAttempts (120) * attemptDelay (10)
```

**Importante:** Estos valores deben estar sincronizados para evitar que el Job se mate antes de que el polling termine.

### Prevención de Duplicados

El sistema previene duplicados en múltiples niveles:

1. **Polling Incremental:**
   ```php
   $processedFiles = []; // Track archivos procesados
   $newFiles = array_diff($jsonFiles, $processedFiles);
   ```

2. **Database-level:**
   ```php
   PaperEvaluation::updateOrCreate(
       ['folio' => $folio], // Unique constraint
       [...]
   );
   ```

3. **Early Exit:**
   ```php
   if (count($processedFiles) > 0 && $noNewFilesCount >= 12) {
       break; // No esperar innecesariamente
   }
   ```

## Ventajas de Esta Solución

✅ **Robusto:** Maneja correctamente el timing asíncrono del procesamiento OCR  
✅ **Incremental:** Procesa archivos conforme aparecen (no espera a todos)  
✅ **Sin Duplicados:** `array_diff()` + `updateOrCreate()` previenen reprocesamiento  
✅ **Eficiente:** Exit automático cuando no hay nuevos archivos por 2 minutos  
✅ **Informativo:** El usuario ve el progreso en tiempo real con contadores  
✅ **Debuggeable:** Logs detallados de cada archivo procesado  
✅ **Configurable:** Parámetros ajustables según necesidades  
✅ **Fail-safe:** Timeout claro sincronizado con Job timeout  
✅ **Backwards Compatible:** No requiere cambios en el script Python  

## Testing

### En Desarrollo
```bash
# 1. Subir PDF de prueba
# 2. Monitorear logs en tiempo real
tail -f storage/logs/laravel.log

# Deberías ver:
# "Waiting for JSON files to be created..."
# "No JSON files found yet. Waiting... (Attempt 1/60)"
# "No JSON files found yet. Waiting... (Attempt 2/60)"
# ...
# "JSON files found" con count y wait_time
```

### En Producción
```bash
# Monitorear Horizon para ver el progreso del Job
# Las notificaciones persistentes mostrarán:
# - "Copiando PDF al contenedor..."
# - "Ejecutando análisis OCR..."
# - "Esperando resultados del análisis..."
# - "Analizando documento... (30s transcurridos)"
# - "Procesados 1 formularios..." ← Primer archivo detectado
# - "Analizando documento... (1 procesados, 60s transcurridos)"
# - "Procesados 2 formularios..." ← Segundo archivo
# - "Procesados 3 formularios..."
# - etc.

# Los logs mostrarán:
tail -f storage/logs/laravel.log

# Ejemplo de output esperado:
# [INFO] Waiting for JSON files...
# [INFO] No JSON files found yet. Waiting... (Attempt 3/120)
# [INFO] New JSON files found (new_count: 1, total_processed: 0)
# [INFO] Processed file (file: ABC-001-REF3-00001.json, total_processed: 1)
# [INFO] Waiting for more files... (Processed: 1, No new files: 3/12)
# [INFO] New JSON files found (new_count: 1, total_processed: 1)
# [INFO] Processed file (file: ABC-001-REF3-00002.json, total_processed: 2)
# [INFO] No new files detected for 2 minutes. Assuming processing complete.
# [INFO] JSON processing completed (total_processed: 2, total_wait_time: 180 seconds)
```

## Archivos Modificados

- `app/Jobs/ProcessPaperEvaluation.php`
  - Agregado polling loop en `processJsonResults()`
  - Mejorados broadcasts con mensajes descriptivos
  - Agregado logging exhaustivo
  - Captura de output del script Python

## Próximos Pasos (Opcional)

Si el polling sigue fallando, considerar:

1. **Investigar por qué `docker exec` retorna temprano:**
   - ¿El script Python se está haciendo fork?
   - ¿Hay un `&` al final del comando que lo pone en background?

2. **Alternativa: Usar un archivo de señal:**
   ```python
   # Al final de main.py
   with open('/app/output/.processing_complete', 'w') as f:
       f.write('done')
   ```
   
   ```php
   // En PHP esperar ese archivo en lugar de JSONs
   while (!file_exists($outputFolder.'/.processing_complete')) {
       sleep(10);
   }
   ```

3. **Alternativa: Webhook callback:**
   - Python hace HTTP POST a Laravel cuando termina
   - Laravel actualiza un flag en Redis/DB
   - Job polling verifica ese flag

Por ahora, el polling de JSONs debería ser suficiente y es la solución más simple y robusta.

## Commit Message

```
fix: improve OCR polling with incremental processing and duplicate prevention

Problem:
- Previous polling waited for all JSONs, then processed all at once
- Python creates JSONs one by one over time (not all at once)
- Risk of processing same file multiple times
- Job timeout (20 min) didn't match polling timeout (10 min)

Solution:
- Incremental processing: process each JSON as it appears
- Track processed files with $processedFiles array
- Use array_diff() to detect only NEW files
- Early exit after 2 min idle (no new files)
- Sync timeouts: 120 attempts * 10s = 1200s = 20 min (matches job timeout)
- Real-time progress: "Procesados X formularios..."

Benefits:
- No duplicate processing (array_diff + updateOrCreate)
- Faster completion (doesn't wait full 20 min if done early)
- Better UX with real-time file count updates
- Proper timeout synchronization
- Handles one-by-one JSON generation correctly

Updated: docs/OCR_POLLING_FIX.md with incremental processing details
```
