---
mode: agent
model: Auto (copilot)
tools: ['edit', 'search', 'runCommands', 'fetch']
---

Tenemos el problema de que hay una infinidad de archivos de documentacion en el repositorio, unos son incluso inutiles o inecesarios, otros se duplican y caen en la redundancia. 

Tu trabajo sera el siguiente
- Crearas un plan para organizar toda la documentacion del repositorio
- Crearás un esquema de carpetas para organizar la documentacion
- Moveras los archivos a las carpetas correspondientes
- Todo esto dentro de la carpeta docs 
- Leeras un archivo de documentación y haras un resumen importante, si vez necesario añadir codigo u ejemplos de ahí, hazlo.
- Si ves que un archivo repite la información o usa el mismo ejemplo que otro archivo, lo eliminaras y dejaras solo uno de ellos.
- Si ves que un archivo no aporta nada, lo eliminaras.

Esto con el fin de mantener una documentacion limpia, organizada y util para los desarrolladores.

Recuerda que debes seguir los siguientes pasos:
- Crea una branch nueva para hacer los cambios
- haras conventional commits para cada cambio que hagas
- Haras un pull request al final con todos los cambios realizados