---
mode: agent
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'fetch', 'todos']
model: Claude Sonnet 4.5 (copilot)
---


el archivo `demographic-report-browsershot.blade.php` es el que se encarga actualmente de manejar el reporte de los datos demograficos para los reportes finales. Actualmente incluye unas secciones. Sin embargo, es necesario cambiarlas por las que establece la norma la cual son las siguientes. 

Las graficas que vamos a incluir seran las siguientes: 
- Distribución Conforme a Género
- Distribución Conforme a Estado Civil
- Distribución Conforme a Rango de Edad
- Distribución Conforme a Nivel de Estudios
- Distribución Conforme al Puesto
- Distribución Conforme a Tipo de Contratación
- Distribución Conforme a Tipo de Personal
- Distribución Conforme al Tipo de Jornada Laboral
- Distribución Conforme a Rotación de Turnos
- Distribución Conforme al Área
- Distribución Conforme a Tiempo en el Puesto Actual (Antigüedad)

Cada grafica debera incluir una tabla basado en sus datos, donde indicaremos el nivel de riesgo a corde a la NOM-035-STPS-2018. 

## Ejemplo de Tablas:
| Género     | Total | Nulo | Bajo | Medio | Alto | Muy | Nu+Ba | Me+Al+MA | CF* |
|-------------|--------|------|------|--------|------|-----|--------|----------|-----|
| Masculino   | 63     | 13   | 22   | 21     | 6    | 1   | 35     | 28       | 72  |
| Femenino    | 65     | 16   | 20   | 23     | 6    | 0   | 36     | 29       | 66  |


| Estado Civil  | Total | Nulo | Bajo | Medio | Alto | Muy | Nu+Ba | Me+Al+MA | CF* |
|----------------|--------|------|------|--------|------|-----|--------|----------|-----|
| Casado         | 36     | 8    | 13   | 10     | 5    | 0   | 21     | 15       | 68  |
| Soltero        | 73     | 15   | 23   | 27     | 7    | 1   | 38     | 35       | 71  |
| Unión Libre    | 13     | 6    | 3    | 4      | 0    | 0   | 9      | 4        | 60  |
| Divorciado     | 3      | 0    | 2    | 1      | 0    | 0   | 2      | 1        | 71  |
| Otro           | 3      | 0    | 1    | 2      | 0    | 0   | 1      | 2        | 81  |


## Datos Extra
- No es necesario hacer documentación de los cambios
- Crea una branch basada en develop utilizando las mejores practicas
- Aplica principios SOLID y buenas practicas de desarrollo
- Sera necesario revisar el controlador para que veas como se generan los datos y puedas adaptarlos a las nuevas necesidades.
- Para saber que estamos trabajando, crearas un markdown con los pasos que haras como checklist. Iras marcando cada una que tengas lista. De esa forma sabremos en que punto vas.