---
mode: agent
model: Claude Sonnet 4.5 (Preview) (copilot)
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'fetch']
---

Siguiendo las mejoras mencionoadas en: `.github/prompts/improve-frontend-with-new-data.prompt.md` y `.github/prompts/new-detection-store.prompt.md`, ahora vamos a actualizar el reporte tradicional que se encuentra en el Dashboard del Admin y de la Organización para que utilice los datos del nuevo modelo `PaperEvaluation`.

Recuerda que este reporte tradicional es solo para examenes presenciales (source: paper), y que el usuario con rol `admin` puede ver los datos de todas las organizaciones, mientras que el usuario con rol `organization` solo puede ver los datos de su propia organización, por lo que es redirigido automaticamente a su dashboard.

El archivo `resources/js/Components/ReportSummaryDashboard.vue` es la vista principal del reporte tradicional, y actualmente utiliza los datos del modelo legacy. Queremos migrar esto para que utilice los datos del nuevo modelo `PaperEvaluation`.

Es importante respetar las graficas actuales y la estructura del reporte, pero adaptandolo para que utilice los datos del nuevo modelo `PaperEvaluation`.

Es probable que el archivo del Report Summary necesite ser dividido en componentes más pequeños para mejorar la mantenibilidad y la claridad del código además de reusabilidad de algunas cosas. No vendria mal tambien migrar types, interfaces, props, etc. para seguir las buenas practicas. 

No olvides revisar la logica actual ya que anteriormente habia muchos modelos y relaciones que ahora pueden ser simplificadas con el nuevo modelo `PaperEvaluation` y con algunos archivos config para obtener los datos del puntaje por categorias, dominios, etc.

## Tareas
- Refactoriza el archivo `resources/js/Components/ReportSummaryDashboard.vue` para simplificar su estructura y mejorar la claridad del código.
- Actualiza el reporte tradicional para que utilice los datos del nuevo modelo `PaperEvaluation`.


## Notas
- Crea una nueva branch para trabajar estos cambios. Utiliza las mejores practicas para el desarrollo de la aplicación.
- Usa Inertia.js y Vue.js para las vistas.
- Utiliza principios SOLID y patrones de diseño donde sea apropiado.