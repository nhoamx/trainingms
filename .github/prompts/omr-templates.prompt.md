---
mode: agent
model: Claude Sonnet 4.5 (Preview) (copilot)
tools: ['edit', 'search', 'runCommands', 'fetch', 'laravel-boost']
---


Revisa los templates de OMR en la ruta de views/omr. Estos estan escritos en blade y son usados para generar las hojas de respuestas de los exámenes.

Despues estamos creando un pdf con dompdf y necesitamos que las hojas de respuestas se vean bien en el pdf. Esta funcionalidad la estamos usando en `resources/js/Pages/Organizations/Edit.vue` dentro de una tab llamada `Folios` el cual abre muestra otro componente en `resources/js/Pages/Organizations/components/Folios.vue`. Este componente tiene el boton que ayuda a genera el pdf dependiendo del tipo de evaluación que vamos a usar. 


Como estamos usando dompdf pero hemos tenido problemas con las limitaciones que tiene, se ha decidido usar otro paquete para generar el pdf. El paquete nuevo es `spatie/browsershot` el cual usa headless chrome para generar el pdf. El paquete ya esta instalado en el proyecto.

Tu trabajo sera el siguiente:
- Verificar que los templates de OMR se vean bien en el pdf generado con browsershot
- Verificar que los templates de OMR se vean bien en el navegador
- Verificar que los templates de OMR tengan las marcas de referencia bien ajustadas y distribuidas.
- Todos los templates deben caber en una hoja tamaño carta (A4) y deben tener las marcas de referencia bien ajustadas y distribuidas para alinear la hoja al momento de realizar la detección óptica de marcas.
