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

### 1. Polling Mechanism

Implementamos un sistema de **polling con retry logic** que espera activamente a que aparezcan los archivos JSON:

```php
protected function processJsonResults(): void
{
    $outputFolder = base_path('docker/output');
    $maxAttempts = 60;      // 60 intentos
    $attemptDelay = 10;     // 10 segundos entre intentos
                            // = 10 minutos máximo de espera
    
    // Loop de polling
    while ($attempt < $maxAttempts) {
        $jsonFiles = glob($outputFolder.'/*.json');
        
        if ($jsonFiles && count($jsonFiles) > 0) {
            // ¡Encontrados! Procesar...
            break;
        }
        
        $attempt++;
        sleep($attemptDelay);  // Esperar 10 segundos antes del siguiente intento
    }
}
```

**Características:**
- ⏱️ Espera hasta **10 minutos** (configurable)
- 🔄 Verifica cada **10 segundos** si aparecieron archivos JSON
- 📊 Logging detallado de cada intento
- 🚨 Timeout con mensaje claro si no se encuentran archivos

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

El usuario recibe actualizaciones **cada 30 segundos** mientras el sistema espera:

```php
// Cada 3 intentos (30 segundos)
if ($attempt % 3 === 0) {
    $waitedTime = $attempt * $attemptDelay;
    broadcast(new EvaluationProcessingStatusChanged(
        'running',
        "Analizando documento... ({$waitedTime}s transcurridos)",
        false,
        $this->initiatorUserId
    ));
}
```

**El usuario ve:**
- "Analizando documento... (30s transcurridos)"
- "Analizando documento... (60s transcurridos)"
- "Analizando documento... (90s transcurridos)"
- etc.

### 4. Logging Mejorado

Agregamos logging exhaustivo para debugging:

```php
Log::info('Waiting for JSON files to be created...', [
    'output_folder' => $outputFolder,
    'max_wait_time' => '600 seconds',
]);

Log::info("No JSON files found yet. Waiting... (Attempt 5/60)");

Log::info('JSON files found', [
    'count' => 3,
    'attempt' => 5,
    'wait_time' => '50 seconds',
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

### Ahora (Robusto)
```
1. Copiar PDF → ✅ Broadcast: "Copiando PDF..."
2. docker exec python → ✅ Broadcast: "Ejecutando análisis OCR..."
3. Polling Loop → 🔄 Broadcast: "Esperando resultados..."
   - Intento 1 (0s): No encontrado
   - Intento 2 (10s): No encontrado
   - Intento 3 (20s): No encontrado → Broadcast: "Analizando documento... (30s transcurridos)"
   - Intento 4 (30s): No encontrado
   - Intento 5 (40s): No encontrado
   - Intento 6 (50s): No encontrado → Broadcast: "Analizando documento... (60s transcurridos)"
   - ...
   - Intento 15 (140s): ✅ ¡JSON encontrado!
4. Procesar JSONs → ✅ Broadcast: "Guardando resultados..."
5. Cleanup → ✅
6. Completado → ✅ Broadcast: "Procesamiento finalizado"
```

## Configuración

### Parámetros Ajustables

```php
// En processJsonResults()
$maxAttempts = 60;      // Máximo de intentos (default: 60)
$attemptDelay = 10;     // Segundos entre intentos (default: 10)
// Tiempo máximo = maxAttempts * attemptDelay = 600 segundos (10 minutos)

// Frecuencia de broadcast al usuario
if ($attempt % 3 === 0) // Cada 3 intentos = 30 segundos
```

**Recomendaciones:**
- Para PDFs pequeños (1-5 páginas): 60 intentos es más que suficiente
- Para PDFs grandes (50+ páginas): Considerar aumentar `$maxAttempts` a 120 (20 minutos)
- `$attemptDelay = 10` segundos es un buen balance entre responsividad y carga del servidor

### Timeout del Job

El timeout del Job debe ser mayor que el polling máximo:

```php
public int $timeout = 1200; // 20 minutos (mayor que 10 min de polling)
```

## Ventajas de Esta Solución

✅ **Robusto:** Maneja correctamente el timing asíncrono del procesamiento OCR  
✅ **Informativo:** El usuario ve el progreso en tiempo real  
✅ **Debuggeable:** Logs detallados en cada paso  
✅ **Configurable:** Parámetros ajustables según necesidades  
✅ **Fail-safe:** Timeout claro si algo falla  
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
# - "Analizando documento... (60s transcurridos)"
# - etc.
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
fix: implement polling mechanism for OCR JSON output

Problem:
- docker exec returns immediately (code 0) before Python script finishes
- Job failed with "No JSON files found" because it checked too early
- OCR processing takes time, JSONs created after command returns

Solution:
- Added polling loop with 60 attempts @ 10s intervals (10 min max wait)
- Progress broadcasts every 30s: "Analyzing document... (Xs elapsed)"
- Detailed logging of each polling attempt
- Capture Python script output for debugging
- Improved status broadcasts at each processing stage

Benefits:
- Robust handling of async OCR processing
- Real-time progress updates for users
- Better debugging with comprehensive logs
- Configurable timeout and retry parameters
- Backwards compatible (no Python changes needed)

Verified with production logs showing timing issue pattern.
```
