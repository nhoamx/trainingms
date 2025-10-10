---
mode: agent
model: Claude Sonnet 4.5 (Preview) (copilot)
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'fetch']
---

Continuando con las mejoras añadidas en las instrucciones de `.github/prompts/new-detection-store.prompt.md`, ahora vamos a mejorar el frontend para que el usuario tenga una mejor experiencia al momento de visualizar los datos. 

## Contexto
Actualmente tenemos separado dos roles: `admin` y `organization`. El usuario con rol `admin` puede ver todos los datos y el usuario con rol `organization` solo puede ver los datos que él mismo ha subido.

Para esto, tenemos dos dashboard diferentes:
- `resources/js/Components/AdminDashboard.vue` que es donde el administrador ve la lista de todas las organizaciones y puede hacer click en cada una para ver los reportes de esa organización.
- `resources/js/Components/OrganizationDashboard.vue` que es donde el usuario con rol `organization` ve los reportes de su propia organización. En el caso del rol `admin`, cuando hace click en una organización, se le redirige a esta misma vista pero con los datos de la organización seleccionada.

Actualmente se usan los datos del legacy para mostrar los reportes. Queremos migrar esto para que se usen los datos del nuevo modelo `PaperEvaluation` que hemos creado. La información que mostramos por ahora en el dashboard es para examenes presenciales (source: paper), por lo pronto lo mantendremos de esta forma solamente. 

Hay una ruta tambien que no podemos dejar aun lado `organizaciones/{organization_id}/evaluaciones` que es donde mostramos una lista de las evaluaciones, actualmente estan separadas por guia de referencia, pero creo no es necesario. Podemos cambiarlo para agrupar por folio personal y mostrar que guias de referencia tiene cada folio personal.

Luego al darle clic en "Ver Detalles" este nos redirigira a la ruta `organizacion/{organization_id}/resultados/evaluation_id` donde mostraremos los detalles de la evaluación. Este usaba el evaluation_id del modelo legacy pero ahora obtendremos los detalles del nuevo modelo `PaperEvaluation`.

En los detalles vemos diferentes tabs los cuales te describire a continuación:
- **Resumen**: 
 - Calificación  final: es el total de puntos obtenidos en las respuestas de la guia de referencia III.
 - Categorias: un desgloze de las categorias y el puntaje de cada una de ellas. 
 - Dominios: igual que categorias, un desgloze de los dominios y el puntaje de cada uno de ellos.
 - Tabla de Categorias, Dominios, Dimensiones, items(preguntas) y puntaje. 
- **Guia de Referencia I**: 
 - Mostramos las preguntas y respuestas de la guia de referencia I.
- **Guia de Referencia III**:
    - Mostramos las preguntas y respuestas de la guia de referencia III.
    - Si la guia de referencia III tiene preguntas condicionales, mostramos esas preguntas y respuestas en una seccion aparte.
    - Lo mismo para CITSATS-S1. 
- **Guia de referencia V**:
    - Mostramos las preguntas y respuestas de la guia de referencia V los cuales son los datos demograficos.
- **CISNEROS**:
 - Esta no existe, pero podemos añadirla y dejarla en un (En Desarrollo)


Para el puntaje, anteriormente se añadia al modelo `Question` el campo `value`. Pero este puedes encontrarlo en `config/answer_values.php`.

Para Los Dominos, Categorias y Dimensiones, puedes encontrarlos en `config/question_dimensions.php` donde la gerarquia es la siguiente:
- Categoria
 - Dominio
   - Dimension
     - Items (preguntas)



Por ultimo, nos queda la ruta de `organization/{organization_id}/report` en la cual tenemos graficas y tablas con los datos del modelo question y legacy. Pero creo ahora podemos usar los datos del nuevo modelo `PaperEvaluation` para mostrar la misma informacion.

Basado en lo ya explicado:
- Realiza las mejoras a las rutas, controladores y vistas necesarias para que el usuario pueda ver los datos del nuevo modelo `PaperEvaluation`.
- Asegurate de que el usuario con rol `admin` pueda ver los datos de todas las organizaciones y el usuario con rol `organization` solo pueda ver los datos de su propia organización.


## Notas
- Crea una nueva branch para trabajar estos cambios. Utiliza las mejores practicas para el desarrollo de la aplicación. 
- Usa Inertia.js y Vue.js para las vistas.
- Asegurate de que los cambios no rompan la funcionalidad existente.
- Realiza pruebas para asegurarte de que todo funciona correctamente, en el caos de las pruebas cersiorate de usar database transactions en lugar de database refresh.
- Utiliza principios SOLID y mejores prácticas de diseño de software.
- No es necesario documentar los cambios realizados. 