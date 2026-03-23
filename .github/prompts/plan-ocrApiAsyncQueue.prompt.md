## Plan: OCR API Interna Async con Cola y Reintentos

Enfoque validado: `RQ + Redis` y contrato asíncrono (`POST` crea job, `GET` consulta estado).  
Con esto evitamos bloqueos por PDFs pesados y dejamos el flujo listo para producción en Docker desde el inicio.

**Fase 1 - Refactor de Orquestación OMR**
1. Extraer del flujo actual una función reutilizable para procesar un PDF por instrumento y devolver resultado estructurado.
2. Mantener compatibilidad del CLI actual para no romper operación manual.
3. Reusar mapeos actuales de instrumentos y respuestas sin cambiar lógica de negocio.

Referencias actuales:
1. [docker/main.py](docker/main.py)
2. [docker/omr/pipeline.py](docker/omr/pipeline.py)
3. [docker/omr/helpers/omr.py](docker/omr/helpers/omr.py)
4. [docker/annotator/annotations/answers-mapping.json](docker/annotator/annotations/answers-mapping.json)

**Fase 2 - API Flask Asíncrona**
1. Definir `POST /jobs` para recibir PDF + instrument, validar, guardar temporal y encolar.
2. Definir `GET /jobs/{job_id}` con estados normalizados: `queued`, `started`, `finished`, `failed`.
3. Definir `GET /health` para health checks.
4. Respuesta de `POST /jobs`: `202` con `job_id` y URL de status.

**Fase 3 - Worker, Reintentos e Idempotencia**
1. Worker RQ para ejecutar procesamiento OMR fuera del request web.
2. Política de reintentos con backoff para errores transitorios.
3. Detección de duplicados por hash de archivo + instrumento para evitar doble procesamiento.
4. Persistencia mínima de artefactos por job (`result`, `error`, `timestamps`) para trazabilidad.

**Fase 4 - Docker Producción**
1. Separar servicios: `api`, `worker`, `redis`.
2. Agregar health checks y restart policy.
3. Parametrizar por variables de entorno: timeout, tamaño máximo, retries, URL Redis.
4. Asegurar volumen/rutas para staging de PDFs y salida de JSON.

Referencia base:
1. [docker/Dockerfile](docker/Dockerfile)
2. [docker/docker-compose.yml](docker/docker-compose.yml)
3. [docker/requirements.txt](docker/requirements.txt)

**Fase 5 - Integración Laravel**
1. Cambiar consumo OCR desde flujo síncrono a submit+poll.
2. Mantener semántica actual de errores en job de Laravel.
3. Separar reintentos por tipo de fallo: red/timeout (retry) vs dato inválido (fallo terminal).

Referencias:
1. [app/Jobs/ProcessPaperEvaluation.php](app/Jobs/ProcessPaperEvaluation.php)
2. [config/services.php](config/services.php)
3. [docs/OCR_HTTP_MIGRATION.md](docs/OCR_HTTP_MIGRATION.md)

**Verificación**
1. Pruebas Python para ciclo de vida de job y reintentos.
2. Pruebas Laravel para submit+poll y manejo de errores.
3. Prueba manual end-to-end con 1 PDF de cada instrumento (`gri`, `griii`, `grv`).
4. Validar estados terminales y payload de error.

**Alcance**
1. Incluye arquitectura async robusta (cola, retry, status, health, Docker prod).
2. No incluye todavía autenticación fuerte externa ni dashboard de monitoreo (solo base operativa interna).

Si te parece bien este plan, el siguiente paso es implementar Fase 1 y Fase 2 juntas para entregar rápidamente un primer endpoint funcional con `job_id` y polling.
