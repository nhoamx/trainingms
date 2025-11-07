# Instrucciones para Reportes en Word

## Implementación Completada

Se ha implementado la funcionalidad de generación de reportes en formato Word (.docx) utilizando un sistema de colas asíncrono para evitar problemas de timeout.

## Cambios Realizados

### 1. Plantillas PDF
- ✅ Removidos márgenes azules de todas las plantillas PDF
- ✅ Cambiados a colores grises neutros (#6b7280, #d1d5db)
- Archivos modificados:
  - `resources/views/pdfs/demographic-report-browsershot.blade.php`
  - `resources/views/pdfs/diagnostic-report-browsershot.blade.php`
  - `resources/views/pdfs/executive-report.blade.php`

### 2. Conversión PDF a Word
- ✅ Script Python usando `pdf2docx` (v0.5.8) en contenedor Docker
- ✅ Ubicación: `docker/pdf_converter/convert_pdf_to_word.py`
- ✅ Librería instalada en contenedor "training-and-ms"

### 3. Sistema de Colas Asíncrono
- ✅ Modelo `ReportGeneration` para rastrear estado de generación
- ✅ Job `GenerateWordReport` para procesamiento en background
- ✅ Controlador con endpoints para iniciar/verificar/descargar
- ✅ Frontend con polling automático cada 2 segundos

### 4. Interfaz de Usuario
- ✅ Botones de descarga Word solo para administradores
- ✅ Indicador visual durante generación: "Generando reporte, por favor espere..."
- ✅ Descarga automática cuando el reporte está listo
- ✅ Manejo de errores con mensajes al usuario

## Cómo Usar

### 1. Iniciar el Worker de Cola

**IMPORTANTE**: Para que los reportes Word se generen, necesitas tener un worker de cola ejecutándose.

Abre una nueva terminal y ejecuta:

```powershell
php artisan queue:work
```

**Nota**: Mantén este proceso corriendo en segundo plano mientras uses la aplicación. Si lo detienes, los reportes quedarán en estado "pending" y no se procesarán.

#### Opciones Avanzadas del Worker

```powershell
# Worker con reinicio automático cada hora (recomendado para producción)
php artisan queue:work --timeout=600 --tries=3 --max-time=3600

# Worker solo para el queue default
php artisan queue:work --queue=default

# Ver trabajos fallidos
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all
```

### 2. Descargar Reportes Word

1. Inicia sesión como **administrador**
2. Ve al Dashboard de Reportes
3. Selecciona una organización
4. Haz clic en uno de los botones de Word:
   - 📄 Descargar Reporte Demográfico (Word)
   - 📄 Descargar Reporte Diagnóstico (Word)
   - 📄 Descargar Reporte Ejecutivo (Word)

5. El sistema mostrará: "Generando reporte, por favor espere..."
6. El reporte se descargará automáticamente cuando esté listo (puede tomar 1-3 minutos)

### 3. Verificar Estado del Sistema

```powershell
# Verificar que el contenedor Docker esté corriendo
docker ps | Select-String "training-and-ms"

# Verificar que pdf2docx esté instalado en el contenedor
docker exec training-and-ms pip show pdf2docx

# Ver logs del worker de cola
php artisan queue:work --verbose

# Verificar trabajos pendientes en la base de datos
php artisan tinker
# Luego ejecuta: App\Models\ReportGeneration::latest()->get()
```

## Arquitectura del Sistema

### Flujo de Generación de Reporte

```
1. Usuario hace clic en "Descargar Word"
   ↓
2. Frontend envía GET a /reportes/word/{tipo}/{organizacion}
   ↓
3. Controller crea registro ReportGeneration y despacha Job
   ↓
4. Frontend inicia polling (cada 2 seg) a /reportes/word/status/{id}
   ↓
5. Worker procesa Job en background:
   - Genera HTML del reporte
   - Convierte HTML a PDF usando Browsershot
   - Convierte PDF a DOCX usando Python/pdf2docx en Docker
   - Actualiza estado a "completed"
   ↓
6. Frontend detecta "completed" y descarga desde /reportes/word/download/{id}
```

### Tiempos de Procesamiento

- **Generación PDF**: 30-60 segundos
- **Conversión PDF→Word**: 30-90 segundos
- **Tiempo Total**: 1-3 minutos (depende del tamaño del reporte)

### Manejo de Errores

El sistema maneja automáticamente:
- ✅ Timeouts del Job (máximo 10 minutos)
- ✅ Errores de Docker/Python
- ✅ Errores de generación PDF
- ✅ Errores de conversión DOCX
- ✅ Organización sin datos

Los errores se registran en:
- Campo `error_message` del modelo `ReportGeneration`
- Logs de Laravel: `storage/logs/laravel.log`
- Tabla de trabajos fallidos: `failed_jobs`

## Solución de Problemas

### El reporte se queda en "Generando..."

1. Verifica que el worker esté corriendo:
   ```powershell
   # Busca el proceso php artisan queue:work
   Get-Process | Where-Object {$_.ProcessName -eq "php"}
   ```

2. Revisa los logs del worker en la terminal donde lo ejecutaste

3. Verifica el estado en la base de datos:
   ```powershell
   php artisan tinker
   # App\Models\ReportGeneration::find({id})
   ```

### Error: "Container training-and-ms not found"

1. Verifica que el contenedor esté corriendo:
   ```powershell
   docker ps
   ```

2. Si no está corriendo, inícialo:
   ```powershell
   cd docker
   docker-compose up -d
   ```

### El archivo Word está corrupto

1. Verifica que pdf2docx esté instalado:
   ```powershell
   docker exec training-and-ms pip show pdf2docx
   ```

2. Si no está instalado:
   ```powershell
   docker exec training-and-ms pip install pdf2docx==0.5.8
   ```

### Worker se detiene después de un error

Inicia el worker con opciones de reintentos:
```powershell
php artisan queue:work --tries=3 --backoff=10
```

## Producción

Para producción, se recomienda:

1. **Usar Supervisor para mantener el worker corriendo**:
   ```ini
   [program:trainingms-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
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

2. **Usar Laravel Horizon** (ya está instalado):
   ```powershell
   php artisan horizon
   ```

3. **Configurar Redis** como driver de cola en `.env`:
   ```env
   QUEUE_CONNECTION=redis
   ```

4. **Programar limpieza de archivos antiguos**:
   ```php
   // app/Console/Kernel.php
   $schedule->command('app:cleanup-old-reports')->daily();
   ```

## Testing

Para probar manualmente:

1. Inicia el worker: `php artisan queue:work --verbose`
2. En otra terminal, despacha un job de prueba:
   ```powershell
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
   ```

3. Observa los logs del worker
4. Verifica que el archivo se crea en `storage/app/reports/word/`

## Próximos Pasos Sugeridos

- [ ] Agregar comando Artisan para limpiar reportes antiguos (>30 días)
- [ ] Implementar notificación por email cuando el reporte esté listo
- [ ] Agregar cola de prioridad para reportes ejecutivos
- [ ] Implementar descarga de reportes en Excel
- [ ] Agregar barra de progreso real (usando broadcasting)
- [ ] Crear pruebas automatizadas para el Job

## Archivos Relevantes

### Backend
- `app/Jobs/GenerateWordReport.php` - Job principal
- `app/Models/ReportGeneration.php` - Modelo de rastreo
- `app/Http/Controllers/ReportPdfController.php` - Endpoints
- `database/migrations/2025_11_07_040813_create_report_generations_table.php`
- `routes/web.php` - Rutas Word

### Frontend
- `resources/js/Components/ReportSummaryDashboard.vue` - UI con polling

### Docker/Python
- `docker/pdf_converter/convert_pdf_to_word.py` - Script de conversión
- `docker/requirements.txt` - Dependencias Python

### Plantillas
- `resources/views/pdfs/demographic-report-browsershot.blade.php`
- `resources/views/pdfs/diagnostic-report-browsershot.blade.php`
- `resources/views/pdfs/executive-report.blade.php`
