---
mode: agent
model: Claude Sonnet 4.5 (copilot)
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'changes', 'fetch', 'todos']
---

Tenemos el archivo `resources/views/pdfs/diagnostic-report-browsershot.blade.php` El cual actualmente muestra algunos resultados pero el formato final no es el esperado. A continuación te proporcionare el contenido que debemos lleva. 

## Indice
I.- Datos del centro de trabajo 
II.- Objetivo - Referencia: `docs/reports/files/resultado-diagnostico/objetivo.md`
III.- Introducción - Referencia: `docs/reports/files/resultado-diagnostico/introducción.md`
3.1.- Marco jurídico 
IV.- Método utilizado - Referencia: `docs/reports/files/resultado-diagnostico/metodo-utilizado.md`
V.- Resultados - Referencia: `docs/reports/files/resultado-diagnostico/resultados.md`
5.1.- Participantes 
5.2.- Trabajadores que fueron sujetos a acontecimientos traumáticos severos 
5.3.- Violencia laboral 
5.4.- Entorno organizacional 
5.5.- Factores de riesgo psicosocial y entorno organizacional favorable
5.5.1.- Calificación final 
5.5.2.- Cuantificación por categoría 
5.5.3.- Cuantificación por dominio 
5.5.4.- Cuantificación por dimensión 
5.5.5.- Cuantificación por opciones de respuesta 
5.5.6.- Cuantificación por bloque de preguntas 
VI.- Conclusiones - Referencia: `docs/reports/files/resultado-diagnostico/conclusiones.md`
VII.- Recomendaciones y acciones de intervención - Referencia: `docs/reports/files/resultado-diagnostico/recomendaciones.md`



Te he añadido una referencia para cada sección, esta referencia incluye textos que debemos incluir en los reportes en cada una d elas partes. Es necesario que respetemos los textos por la norma. Sin embargo, los datos de la compañia, resultados y todas esas cosas deben ser dinamicas y sacadas de las paper_evaluations y demas entidades de la base de datos.



## Instrucciones
- Crearas un componente blade por cada una de las secciones del indice.
- Añadiras ese componente al archivo `diagnostic-report-browsershot.blade.php` en el orden del indice.
- Debes incluir el texto de las referencias en cada una de las secciones.
- Los datos dinamicos deben ser obtenidos de la base de datos, revisa el controlador para ver como se obtienen los datos.


Para evitar un problema de ambiguedad, confución o perdida de contexto, solo trabajaras con un archivo a la vez. Es decir, primero crearas el componente para la seccion II, luego añadiras el texto de la referencia, luego los datos (tablas, graficas, etc) y por ultimo añadiras el componente a `diagnostic-report-browsershot.blade.php`. Una vez terminado ese archivo, pasas al siguiente. No olvides marcar en el checklist lo que vayas haciendo.

## Notas
- No es necesario hacer documentación de los cambios
- Crea una branch basada en develop utilizando las mejores practicas
- Aplica principios SOLID y buenas practicas de desarrollo
- Sera necesario revisar el controlador para que veas como se generan los datos y puedas adaptarlos a las nuevas necesidades.
- Para saber que estamos trabajando, crearas un markdown con los pasos que haras como checklist. Iras marcando cada una que tengas lista. De esa forma sabremos en que punto vas.
- Los datos en las referencias son ejemplos, debes obtener los datos reales de la base de datos.
