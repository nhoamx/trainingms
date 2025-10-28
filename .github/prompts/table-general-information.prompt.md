---
mode: agent
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'fetch', 'todos']
model: Claude Sonnet 4.5 (copilot)
---


Actualmente la pagina de `resources/js/Pages/Results/Detail.vue` muestra una tabla separada por categoria, dominio, dimensión, items, puntaje. Necesitamos replicar esa tabla en `resources/js/Components/ReportSummaryDashboard.vue` dentro de la tab "Calificación final". 

Deberemos cambiar el orden de las tabs, mostrando el siguiente:
- Calificación final
- Participantes
- Categorias
- Dominios
- Datos demograficos 


Para la tabla que mostraremos, debes analizar que tipo de datos esta obteniendo y replicarlo pero de forma masiva en la nueva del Summary Dashboard. Ya que despues haremos modificaciones a esta tabla. 



