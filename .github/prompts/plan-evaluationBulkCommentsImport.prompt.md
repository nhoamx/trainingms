---
model: Claude Sonnet 4.5 (copilot)
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'problems', 'fetch']
agent: agent
---
## Plan: Importación masiva de comentarios de evaluación por factor

Implementar un sistema para importar comentarios de evaluaciones Likert desde Excel, almacenándolos en una nueva tabla `evaluation_comments` con clasificación por factor, y visualizarlos en los reportes con filtros interactivos.

### Steps

1. **Crear migración y modelo `EvaluationComment`** - Nueva tabla con `paper_evaluation_id`, `factor` (string), `comment` (text). Añadir relación `hasMany` en [`PaperEvaluation`](app/Models/PaperEvaluation.php) y factory para testing.

2. **Crear `EvaluationBulkCommentsImport`** - Nuevo import en [`app/Imports/`](app/Imports/) siguiendo el patrón de [`EvaluationBulkUpdateImport`](app/Imports/EvaluationBulkUpdateImport.php): recibe `organizationId`, busca evaluaciones por `folio_personal`, crea registros de comentarios con su factor.

3. **Crear job `ProcessBulkCommentsImport`** - Nuevo job siguiendo el patrón existente en `ProcessBulkEvaluationImport`, con progreso via broadcasting y manejo de errores.

4. **Añadir rutas y métodos en controlador** - Nuevas rutas en [`web.php`](routes/web.php): template descargable y endpoint de upload para comentarios. Añadir métodos `bulkCommentsTemplate` y `bulkCommentsUpdate` en [`ResultsController`](app/Http/Controllers/ResultsController.php).

5. **Crear `BulkCommentsModal.vue` y añadir a [`List.vue`](resources/js/Pages/PaperEvaluations/List.vue)** - Nuevo modal para upload de comentarios similar a [`BulkUpdateModal.vue`](resources/js/Components/BulkUpdateModal.vue), con drag & drop y selección de archivo. Añadir botón "Cargar Comentarios" en `List.vue`.

6. **Extender [`LikertOrganizationReport.vue`](resources/js/Pages/Reports/LikertOrganizationReport.vue) con filtros por factor** - Añadir prop `factors` desde backend, crear nuevo filtro dropdown de factor, modificar `filteredEvaluations` para aplicar filtro, y mostrar sección de comentarios agrupados por factor.

### Further Considerations

1. **¿Cómo manejar duplicados?** Al reimportar comentarios del mismo folio, ¿reemplazar todos los comentarios existentes o agregar nuevos? Recomiendo reemplazar (delete + insert) por simplicidad.
- Remplazaremos todos los comentarios existentes para el folio al reimportar, como lo hemos manejado en otros procesos similares.

2. **¿Visualización de comentarios en reporte?** ¿Mostrar lista textual de comentarios por factor o solo estadísticas (conteo por factor)? Esto afecta el diseño del componente en el reporte.
- Mostraremos una lista textual de comentarios agrupados por factor para mayor claridad y detalle. Esto es importante para que los usuarios puedan ver el feedback específico asociado a cada factor.

3. **¿Exportar comentarios?** ¿Necesitas descargar los comentarios junto con el reporte o en un Excel separado?
- Inicialmente no implementaremos la exportación de comentarios, pero podemos considerar esta funcionalidad en futuras iteraciones si es requerida por los usuarios.


### Notes
- Utiliza el tool de Laravel Boost. 
- Maneja DRY y SOLID.