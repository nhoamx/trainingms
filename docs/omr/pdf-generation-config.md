# Configuración de Generación de PDFs OMR

## Archivo de Configuración

Los ajustes de generación de PDFs OMR se encuentran centralizados en `config/omr.php`. Estos valores pueden ser sobrescritos mediante variables de entorno en tu archivo `.env`.

## Variables de Configuración Disponibles

### `OMR_PDF_CHUNK_SIZE` (default: 100)

**Descripción:** Número de páginas que se generarán por cada chunk/fragmento durante la creación de PDFs grandes.

**Recomendaciones:**
- **100 páginas**: Configuración conservadora, ideal para servidores con recursos limitados (2-4 GB RAM)
- **200-300 páginas**: Configuración equilibrada para servidores con recursos moderados (4-8 GB RAM)
- **500 páginas**: Configuración agresiva para servidores con buenos recursos (8+ GB RAM)

**Impacto:**
- Valores más altos = generación más rápida pero mayor consumo de memoria
- Valores más bajos = generación más lenta pero más estable en recursos limitados

**Ejemplo en `.env`:**
```env
OMR_PDF_CHUNK_SIZE=500
```

### `OMR_PDF_JOB_THRESHOLD` (default: 100)

**Descripción:** Número mínimo de folios para activar el procesamiento en segundo plano mediante jobs.

**Comportamiento:**
- Si el total de folios **≤ threshold**: generación síncrona (inmediata)
- Si el total de folios **> threshold**: generación asíncrona (background jobs)

**Recomendaciones:**
- Para evaluaciones pequeñas (< 50 folios), ajusta a 50 para mantener sincronía
- Para lotes grandes (> 100 folios), usa el valor por defecto o mayor

**Ejemplo en `.env`:**
```env
OMR_PDF_JOB_THRESHOLD=50
```

### `OMR_PDF_MEMORY_LIMIT` (default: 512)

**Descripción:** Límite de memoria en MB asignado al proceso de generación de PDFs.

**Recomendaciones:**
- **256 MB**: Para chunks pequeños (≤ 100 páginas)
- **512 MB**: Configuración estándar recomendada
- **1024 MB**: Para chunks grandes (≥ 500 páginas) o layouts complejos

**Ejemplo en `.env`:**
```env
OMR_PDF_MEMORY_LIMIT=1024
```

### `OMR_PDF_EXECUTION_TIME` (default: 1800)

**Descripción:** Tiempo máximo de ejecución en segundos (30 minutos por defecto).

**Recomendaciones:**
- **300 segundos (5 min)**: Para lotes pequeños (< 200 folios)
- **1800 segundos (30 min)**: Estándar recomendado
- **3600 segundos (60 min)**: Para lotes muy grandes (> 1000 folios)

**Ejemplo en `.env`:**
```env
OMR_PDF_EXECUTION_TIME=3600
```

### `OMR_PDF_BROWSERSHOT_TIMEOUT` (default: 300)

**Descripción:** Timeout de navegación de Puppeteer/Browsershot por chunk en segundos.

**Recomendaciones:**
- **180 segundos (3 min)**: Para chunks pequeños (≤ 100 páginas)
- **300 segundos (5 min)**: Configuración estándar recomendada
- **600 segundos (10 min)**: Para chunks grandes (≥ 500 páginas)

**Importante:** Este valor debe ser menor que `OMR_PDF_EXECUTION_TIME`.

**Ejemplo en `.env`:**
```env
OMR_PDF_BROWSERSHOT_TIMEOUT=600
```

### `OMR_PDF_SCALE_FACTOR` (default: 0.96)

**Descripción:** Factor de escala para el renderizado del PDF (0.1 a 1.0).

**Uso:**
- Ajuste fino de dimensiones y calidad del PDF
- Valores cercanos a 1.0 = tamaño original
- Valores menores = PDF más compacto

**Ejemplo en `.env`:**
```env
OMR_PDF_SCALE_FACTOR=0.98
```

---

## Escenarios de Configuración Recomendados

### Escenario 1: Servidor con Recursos Limitados
```env
OMR_PDF_CHUNK_SIZE=100
OMR_PDF_JOB_THRESHOLD=50
OMR_PDF_MEMORY_LIMIT=256
OMR_PDF_EXECUTION_TIME=1800
OMR_PDF_BROWSERSHOT_TIMEOUT=300
```

### Escenario 2: Servidor Estándar (Recomendado)
```env
OMR_PDF_CHUNK_SIZE=200
OMR_PDF_JOB_THRESHOLD=100
OMR_PDF_MEMORY_LIMIT=512
OMR_PDF_EXECUTION_TIME=1800
OMR_PDF_BROWSERSHOT_TIMEOUT=300
```

### Escenario 3: Servidor de Alto Rendimiento
```env
OMR_PDF_CHUNK_SIZE=500
OMR_PDF_JOB_THRESHOLD=200
OMR_PDF_MEMORY_LIMIT=1024
OMR_PDF_EXECUTION_TIME=3600
OMR_PDF_BROWSERSHOT_TIMEOUT=600
```

---

## Monitoreo y Logs

Los logs de generación incluyen ahora la información del `chunk_size` configurado:

```
[INFO] Dividiendo en chunks {
  "total_chunks": 4,
  "pages_per_chunk": 500,
  "configured_chunk_size": 500
}
```

Puedes revisar los logs en `storage/logs/laravel.log` para analizar el rendimiento con diferentes configuraciones.

---

## Notas Importantes

1. **Reiniciar workers de cola**: Después de cambiar configuraciones de `.env`, reinicia los workers:
   ```bash
   php artisan queue:restart
   ```

2. **Optimizar configuración**: Ejecuta después de cambiar valores en `.env`:
   ```bash
   php artisan config:cache
   ```

3. **Pruebas de rendimiento**: Realiza pruebas con diferentes `chunk_size` para encontrar el equilibrio óptimo según tu infraestructura.

4. **Memoria del servidor**: Asegúrate que `OMR_PDF_MEMORY_LIMIT * número_de_workers` no exceda la RAM disponible del servidor.
