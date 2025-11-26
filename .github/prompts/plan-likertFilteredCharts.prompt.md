---
agent: agent
model: Claude Opus 4.5 (Preview) (copilot)
tools: ['edit', 'search', 'laravel-boost/*', 'runCommands', 'fetch', 'runTests']
---


# Plan: Gráficas Filtradas y Mapas de Calor para Reporte Likert Word

Implementar múltiples secciones en el reporte Word con gráficas de pastel, barras horizontales y mapas de calor. Orden: **Total → Turno Matutino → Turno Nocturno → Por cada Área con evaluaciones**. Cada sección incluye Clima Laboral + 10 dimensiones, con mapas de calor por dimensión individual.

## Steps

1. **Agregar método `generateBarChartImage()` a [`LikertChartImageService`](../../app/Services/LikertChartImageService.php)** — Crear gráfica de barras horizontales con Chart.js mostrando conteo y porcentaje por nivel. Incluir título dentro de la imagen. Colores: azul/verde/amarillo/rojo.

2. **Agregar método `generateHeatMapImage()` a [`LikertChartImageService`](../../app/Services/LikertChartImageService.php)** — Generar mapa de calor como imagen PNG con título incluido. Paginación automática (~40 filas por imagen). Soportar tanto "todas las preguntas" como por dimensión individual.

3. **Agregar método `filterEvaluationsAndRecalculate()` a [`ReportPdfService`](../../app/Services/ReportPdfService.php)** — Filtrar evaluaciones por turno o área, recalcular distribuciones de Clima Laboral y cada dimensión. Retornar estructura completa con evaluaciones filtradas.

4. **Crear método `addDimensionSection()` en [`GenerateWordReport`](../../app/Jobs/GenerateWordReport.php)** — Generar sección para una dimensión: título en texto, gráfica pie (imagen con título), gráfica barras horizontal (imagen con título), mapa de calor de la dimensión (imagen paginada + tabla nativa).

5. **Crear método `addFilteredReportSection()` en [`GenerateWordReport`](../../app/Jobs/GenerateWordReport.php)** — Generar sección completa para un filtro: page break, título grande del filtro, sección Clima Laboral (pie + barras + mapa calor total), luego llamar `addDimensionSection()` para cada una de las 10 dimensiones.

6. **Refactorizar `generateLikertWordNative()` en [`GenerateWordReport`](../../app/Jobs/GenerateWordReport.php)** — Aumentar `$timeout` a 1200s (20 min). Orquestar: Total → Matutino → Nocturno → cada Área con evaluaciones. Limpiar todas las imágenes temporales al final.

## Configuration

- **Timeout**: 1200 segundos (20 minutos)
- **Gráfica de barras**: Horizontal, mostrando conteo y porcentaje
- **Colores estandarizados**:
  - Totalmente de Acuerdo: `#60a5fa` (azul)
  - De Acuerdo: `#16a34a` (verde)
  - Desacuerdo: `#eab308` (amarillo)
  - Totalmente Desacuerdo: `#dc2626` (rojo)
- **Mapas de calor**: Imagen + tabla nativa, paginados a ~40 filas por página
- **Filtros a generar**: Solo áreas con evaluaciones

## Dimensiones (11 tipos)

1. Clima Laboral (total)
2. Entorno Laboral Seguro
3. Seguridad Laboral
4. Compensación Justa
5. Comunicación Abierta
6. Participación de los Empleados
7. Reconocimiento y Recompensa
8. Capacitación y Desarrollo
9. Equilibrio entre Vida Laboral y Personal
10. Avance Profesional
11. Apoyo al Empleado

## Estructura del Documento

Para cada filtro (Total, Matutino, Nocturno, Áreas...):
- Page break + Título grande del filtro
- **Clima Laboral**:
  - Gráfica pie (imagen con título)
  - Gráfica barras horizontal (imagen con título)
  - Mapa de calor todas las preguntas (imagen paginada + tabla nativa)
- **Por cada dimensión (10)**:
  - Título en texto
  - Gráfica pie (imagen con título)
  - Gráfica barras horizontal (imagen con título)
  - Mapa de calor de la dimensión (imagen paginada + tabla nativa)

## Further Considerations

1. **Tamaño del documento**: Cada sección de filtro tendrá ~22 gráficas (11 tipos × 2 gráficas) + ~11 mapas de calor. Con 4+ filtros = documento extenso (~100+ páginas).

2. **Formato del título del filtro**: Decidir entre "TURNO MATUTINO", "Filtro: Turno Matutino", o "Personal del Turno Matutino (X personas)".
