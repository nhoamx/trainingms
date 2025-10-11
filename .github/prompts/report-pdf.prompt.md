---
mode: agent
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'openSimpleBrowser', 'fetch']
model: Claude Sonnet 4.5 (Preview) (copilot)
---

Necesitamos ahora crear los reportes en PDF de las evaluaciones, estos seran 3 tipos de reportes: 
- Informe demografico
 - Este informe mostramos una grafica de barras con la distribución de los datos demograficos por cada una de sus agrupaciones: edad, genero, nivel educativo, ocupacion, estado civil, etc.
 - Debajo de la grafica mostramos una tabla basado en la información de la grafica. Este incluye el total de participantes y el el total de cada una de las agrupaciones.
 - Como ejemplo puedes ver en `resources/js/Components/ReportSummaryDashboard.vue` la tab de `Datos Demograficos`.
- Informe de resultados diagnóstico
 - `storage/app/public/reportes/informe-resultados-diagnostico.md` es un ejemplo del informe de resultados diagnostico. El contenido es opcional pero la inforamción de las graficas y tablas es obligatoria.
- Informe ejecutivo
 - Pendiente por ahora


## Tareas:
- Debes buscar una parte en el Report Summary donde incluir los botones para descargar los pdfs. 
- Debes crear la logica para cada uno de los reportes
- Puedes basarte en la logica del Report Summary Dashboard para entender como puedes contar la informaicón 
- Las rutas no deben ser accesibles más que para administradores
- Recuerda que mostraremos los reportes por organiación 
- Hara falta añadir más información a las organizaciones, por lo que deberemos añadir los campos necesarios. Nullable para no alterar la información ya existente.


## Notas:
- Crea una branch basada en develop utilizando las mejores practicas
- Aplica principios SOLID y buenas practicas de desarrollo
- No es necesario crear documentación de los cambios
- Para saber que estamos trabajando, crearas un markdown con los pasos que haras como checklist. Iras marcando cada una que tengas lista. De esa forma sabremos en que punto vas.



