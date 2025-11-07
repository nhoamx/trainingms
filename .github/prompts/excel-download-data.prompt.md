---
mode: agent
model: Claude Sonnet 4.5 (copilot)
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'fetch', 'todos']
---

Ahora que ya tenemos hecho el convertir de pdf a word, podemos comenzar a crear un reporte en excel. 

Este reporte de excel incluiremos:
- El folio completo
- folio compañia
- folio personal
- nombre
- datos demograficos
- guia de ref 3 
- guia de ref 3 preguntas opcionales
- CITSAT
- Guia de referencia v 


El modelo que usaremos sera `app/Models/PaperEvaluation.php` aquí es dodne encontraras todos los datos que necesitamos (no uses el campo de raw_data). Toma en cuenta que el reporte es por organización y solo administradores podran ver la opcion

Obvio el excel debe abarcar TODOS los campos de las guias y datos demograficos en columnas diferentes. 

Para esto usaremos laravel excel. 

La opcion estara en `resources/js/Components/ReportSummaryDashboard.vue`
Abajo de los botones para descargar en word. 


Crea una branch nueva llamada `feature/excel-report-download` y realiza los cambios necesarios para implementar esta funcionalidad.
usa las mejores practicas de desarrollo en laravel y vuejs.
Utiliza la documentación de laravel e inertia para guiarte en la implementación.