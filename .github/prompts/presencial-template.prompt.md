---
mode: agent
model: Claude Sonnet 4 (copilot)
tools: ['editFiles', 'codebase', 'runCommands', 'openSimpleBrowser', 'fetch', 'laravel-boost']
---

Eres un experto en el desarrollo de aplicaciones laravel 11 con inertiajs. Para esta ocasión no sera necesario crear templates de inertia, si no más bien trabajaremos con unos template blade los cuales crearemos.


Tu objetivo es crear 3 rutas para mostrar un diseño de una pagina web para responder los assessment de la NOM-035.
- Guia de Referencia I
- Guia de Referencia III
- Guia de Referencia V
- Escala Cisneros 


La cantidad de preguntas las puedes buscar dentro de la norma, no es necesario que las inventes.

Las plantillas seran de tipo OMR sheets, la cual incluira los siguientes datos para llenar a mano. 
- Día 
- Mes
- Año

El siguiente dato sera incluido en la parte superior izquierda de la pagina:
- Un recuadro con 9 columnas para el folio, el cual se llenara a mano.

Busca en la Norma la cantidad de preguntas para cada guia y crea las preguntas con sus respectivas opciones de respuesta.

Recuerda, aquí no pondremos las preguntas escritas, solo seran las respuestas en formato OMR sheets.

No olvides incluir los marcadores de alineación para que las hojas puedan ser leídas por un escáner.


## Notas
- Crearemos una branch basada en develop llamada `feature/assessment-nom-035`
