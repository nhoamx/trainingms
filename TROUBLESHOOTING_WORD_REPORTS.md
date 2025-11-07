# Solución de Problemas: Reportes Word Corrompidos

## Problema Identificado

**Síntoma**: El frontend descarga un archivo Word corrompido antes de que el Job termine de procesarlo. El Job continúa ejecutándose y finalmente falla.

**Causa raíz**: El frontend puede estar intentando descargar el archivo antes de que esté completamente generado, o el Job está fallando en algún punto del proceso de conversión.

## Mejoras Implementadas

### 1. **Validación Mejorada del Estado "Completed"** ✅

El endpoint `/reportes/word/status/{id}` ahora verifica que:
- El status sea `'completed'`
- El archivo realmente exista en el sistema de archivos
- Solo retorna `completed: true` cuando ambas condiciones se cumplan

```php
// app/Http/Controllers/ReportPdfController.php
$fileExists = false;
if ($reportGeneration->isCompleted() && $reportGeneration->file_path) {
    $fileExists = file_exists($reportGeneration->file_path);
}

return response()->json([
    'completed' => $reportGeneration->isCompleted() && $fileExists,
    // ...
]);
```

### 2. **Validación de Archivo en el Job** ✅

El Job ahora verifica que el archivo DOCX:
- Fue creado exitosamente
- No está vacío (> 0 bytes)
- Existe antes de marcar el estado como `'completed'`

```php
// app/Jobs/GenerateWordReport.php
if (!file_exists($docxPath)) {
    throw new \Exception('DOCX file was not created: ' . $docxPath);
}

$fileSize = filesize($docxPath);
if ($fileSize === 0) {
    throw new \Exception('DOCX file is empty (0 bytes)');
}
```

### 3. **Logging Exhaustivo** ✅

Ahora se registra cada paso del proceso:

**En el Job**:
- Inicio del procesamiento con `started_at`
- Tamaño del archivo DOCX generado
- Verificación de existencia del archivo

**En el Controlador**:
- Cada intento de descarga
- Verificaciones de autorización
- Estado del reporte al momento de descarga
- Existencia del archivo

### 4. **Columna `original_filename`** ✅

Agregada la columna faltante a la tabla `report_generations` para almacenar el nombre original del archivo.

## Cómo Diagnosticar el Problema

### Paso 1: Revisar los Logs en Tiempo Real

```powershell
# En una terminal, observa los logs mientras intentas generar un reporte
Get-Content storage\logs\laravel.log -Wait -Tail 50
```

Busca estas entradas:
1. ✅ `Starting Word report generation` - Job inició
2. ✅ `Generating PDF` - PDF en proceso
3. ✅ `Converting PDF to DOCX` - Conversión iniciada
4. ✅ `DOCX file created successfully` - Archivo creado con tamaño
5. ✅ `Word report generation completed successfully` - Todo OK
6. ✅ `Download request received` - Frontend intentando descargar
7. ✅ `Starting file download` - Descarga iniciada

### Paso 2: Verificar el Estado en la Base de Datos

```powershell
php artisan tinker
```

```php
// Ver el último reporte generado
$report = App\Models\ReportGeneration::latest()->first();

// Verificar su estado
echo "Status: " . $report->status . "\n";
echo "File Path: " . $report->file_path . "\n";
echo "File Exists: " . (file_exists($report->file_path) ? 'YES' : 'NO') . "\n";
echo "File Size: " . (file_exists($report->file_path) ? filesize($report->file_path) : 'N/A') . "\n";
echo "Started: " . $report->started_at . "\n";
echo "Completed: " . $report->completed_at . "\n";
echo "Error: " . $report->error_message . "\n";
```

### Paso 3: Verificar Trabajos Fallidos

```powershell
# Ver trabajos fallidos en la cola
php artisan queue:failed

# Ver detalles de un trabajo fallido específico
php artisan queue:failed --id=<UUID>
```

### Paso 4: Verificar Docker y Python

```powershell
# Verificar que el contenedor esté corriendo
docker ps | Select-String "training-and-ms"

# Probar el script de conversión manualmente
docker exec training-and-ms python /app/pdf_converter/convert_pdf_to_word.py --help

# Verificar que pdf2docx esté instalado
docker exec training-and-ms pip show pdf2docx
```

## Posibles Causas y Soluciones

### Causa 1: Worker de Cola No Está Corriendo

**Síntoma**: El reporte se queda en estado `'pending'` indefinidamente.

**Solución**:
```powershell
php artisan queue:work --verbose
```

### Causa 2: Timeout del Job

**Síntoma**: El Job falla después de 10 minutos con un error de timeout.

**Solución 1**: Aumentar el timeout en el Job
```php
// app/Jobs/GenerateWordReport.php
public $timeout = 900; // 15 minutos
```

**Solución 2**: Aumentar el timeout en la configuración de la cola
```env
# .env
QUEUE_TIMEOUT=900
```

### Causa 3: Docker No Responde

**Síntoma**: Error "Container training-and-ms not found" o timeouts al ejecutar comandos Docker.

**Solución**:
```powershell
# Reiniciar el contenedor
cd docker
docker-compose restart

# O iniciarlo si está detenido
docker-compose up -d
```

### Causa 4: Archivo PDF Demasiado Grande

**Síntoma**: El Job falla durante la conversión con "Memory limit exceeded" o "Timeout".

**Solución**: Optimizar el PDF antes de convertir
```php
// En GenerateWordReport.php, agregar compresión
$this->configureBrowsershot($html)
    ->scale(0.9) // Reducir escala
    ->format('A4')
    ->save($pdfPath);
```

### Causa 5: Error en el Script de Python

**Síntoma**: El Job falla con error "Failed to convert PDF to DOCX".

**Diagnóstico**:
```powershell
# Ver logs del contenedor Docker
docker logs training-and-ms

# Ejecutar el script manualmente con un PDF de prueba
docker exec -it training-and-ms bash
cd /app
python pdf_converter/convert_pdf_to_word.py /app/test.pdf /app/test.docx
```

**Posibles Soluciones**:
- Reinstalar pdf2docx: `docker exec training-and-ms pip install --upgrade pdf2docx==0.5.8`
- Verificar permisos de archivos en `/app/temp/`
- Verificar que el PDF no esté corrupto o protegido

### Causa 6: Frontend Descargando Antes de Tiempo

**Síntoma**: Se descarga un archivo Word de 0 bytes o corrompido.

**Solución**: Las mejoras implementadas ya resuelven esto al:
1. Verificar que el archivo exista antes de retornar `completed: true`
2. Validar en el Job que el archivo no esté vacío
3. No permitir descarga si el status no es `'completed'`

## Testing Manual

### Test 1: Verificar Flujo Completo

```powershell
# Terminal 1: Worker con verbose
php artisan queue:work --verbose

# Terminal 2: Logs
Get-Content storage\logs\laravel.log -Wait -Tail 50

# Terminal 3: Generar reporte desde la UI
# O usar tinker:
php artisan tinker
```

```php
$org = App\Models\Organization::first();
$user = App\Models\User::first();

$report = App\Models\ReportGeneration::create([
    'user_id' => $user->id,
    'organization_id' => $org->id,
    'report_type' => 'diagnostic',
    'format' => 'word',
    'status' => 'pending'
]);

App\Jobs\GenerateWordReport::dispatch($report);

// Esperar unos segundos y verificar
$report->refresh();
echo $report->status;
```

### Test 2: Verificar Archivo Generado

```powershell
# Después de que el reporte se complete
php artisan tinker
```

```php
$report = App\Models\ReportGeneration::latest()->first();

if (file_exists($report->file_path)) {
    echo "✅ Archivo existe\n";
    echo "Tamaño: " . filesize($report->file_path) . " bytes\n";
    echo "Path: " . $report->file_path . "\n";
    
    // Intentar leer los primeros bytes (DOCX debe empezar con "PK")
    $handle = fopen($report->file_path, 'rb');
    $magic = fread($handle, 2);
    fclose($handle);
    
    if ($magic === 'PK') {
        echo "✅ Es un archivo ZIP válido (DOCX)\n";
    } else {
        echo "❌ NO es un archivo DOCX válido\n";
        echo "Magic bytes: " . bin2hex($magic) . "\n";
    }
} else {
    echo "❌ Archivo NO existe: " . $report->file_path . "\n";
}
```

## Monitoreo en Producción

### Comando Supervisor (Recomendado)

```ini
[program:trainingms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=900
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

### Script de Limpieza Automática

Crear un comando Artisan para limpiar reportes antiguos:

```powershell
php artisan make:command CleanOldReports
```

```php
// app/Console/Commands/CleanOldReports.php
public function handle()
{
    $daysOld = 30;
    $threshold = now()->subDays($daysOld);
    
    $reports = ReportGeneration::where('created_at', '<', $threshold)->get();
    
    foreach ($reports as $report) {
        if ($report->file_path && file_exists($report->file_path)) {
            unlink($report->file_path);
        }
        $report->delete();
    }
    
    $this->info("Cleaned {$reports->count()} old reports");
}
```

Programar en `app/Console/Kernel.php`:
```php
$schedule->command('app:clean-old-reports')->daily();
```

## Checklist de Verificación

Antes de reportar un bug, verifica:

- [ ] Worker de cola está corriendo (`php artisan queue:work`)
- [ ] Contenedor Docker está corriendo (`docker ps`)
- [ ] pdf2docx está instalado (`docker exec training-and-ms pip show pdf2docx`)
- [ ] Migración `report_generations` tiene todas las columnas (`php artisan migrate:status`)
- [ ] Los logs no muestran errores de PHP (`storage/logs/laravel.log`)
- [ ] Los logs del worker no muestran errores (`terminal donde corre queue:work`)
- [ ] El directorio `storage/app/temp/` existe y tiene permisos de escritura
- [ ] La organización tiene evaluaciones/datos para generar el reporte
- [ ] El usuario que intenta descargar tiene rol de admin o super-admin

## Contacto de Soporte

Si el problema persiste después de seguir esta guía:

1. Recopila los siguientes datos:
   - Logs de Laravel (`storage/logs/laravel.log`)
   - Output del worker (`php artisan queue:work --verbose`)
   - Estado del reporte en la BD (`ReportGeneration::latest()->first()`)
   - Logs de Docker (`docker logs training-and-ms`)
   - Versión de pdf2docx instalada

2. Crea un Issue en el repositorio con toda la información recopilada.
