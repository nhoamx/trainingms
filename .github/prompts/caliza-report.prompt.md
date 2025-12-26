---
agent: agent
model: Claude Opus 4.5 (copilot)
tools: ['execute/testFailure', 'execute/getTerminalOutput', 'execute/runInTerminal', 'read/problems', 'read/readFile', 'read/terminalLastCommand', 'edit', 'search', 'web/fetch', 'laravel-boost/*']
---

Vamos a trabajar ahora con la data de CORPORACION INDUSTRIAL DE CALIZA:
- ID: a0315c7c-d7a2-4969-b51e-d126fa6da1af

Los modelos donde obtendremos información sera en: 
- [Paper Evaluations](../../app/Models/PaperEvaluation.php)
- [Demographic Data](../../app/Models/DemographicData.php)

Basado en lo que hicimos para Presentar los reportes de la planta: `a05bc65b-08cd-45d5-8ae1-f4f9d3eb5238` vamos a hacer un reporte igual, esto basado en los datos de CORPORACION INDUSTRIAL DE CALIZA. 


- Debemos tener nuestro dashboard de Caliza.
- Si un usaurio tiene asignada la organización de caliza, al entrar al dashboard debe ver el reporte correspondiente. 
- La distribución va a ser la misma que la que teniamos en [Organization Dashboard](../../resources/js/Pages/Organizations/Dashboard.vue). El problema es que ese dashboad lo hicimos especifico para Jaropamex Planta 1 y 3. 
- Usando el middleware que tenemos [User Dashboard Access](../../app/Http/Middleware/UserDashboardAccess.php). debemos redirigir al nuevo dashboard de Caliza.
- Las opciones actuales son:
 - Datos de la empresa (Con los datos de la organización)
 - Datos demograficos ( Con los datos de demographic data)
 - Resultados (Mensaje de que aun no esta listo)
 - Analisis (Mensaje de que aun no esta listo)
 - Recomendaciones (Mensaje de que aun no esta listo)
 - Informe (Mensaje de que aun no esta listo)
 - Evidencias (Mensaje de que aun no esta listo)
 - FODA (Mensaje de que aun no esta listo)
 - Conclusiones (Mensaje de que aun no esta listo)

## Importante
-  El repore es de la Norma, no de clima laboral. 
