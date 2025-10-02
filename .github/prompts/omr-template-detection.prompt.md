---
mode: agent
model: Claude Sonnet 4.5 (Preview) (copilot)
tools: ['edit', 'runNotebooks', 'search', 'new', 'runCommands', 'runTasks', 'usages', 'vscodeAPI', 'problems', 'changes', 'testFailure', 'openSimpleBrowser', 'fetch', 'githubRepo', 'extensions', 'laravel-boost']
---


Uno de los procesos del sistema es cuando el usuario carga una evaluación y esta se procesa en un queue, el queue ejecuta un comando dentro de un contenedor docker para obtener el pdf, convertirlo en imagenes, alinear las imagenes mediante detección de marcas de archivos OMR y luego procesar cada imagen alineada para extraer las respuestas y guardarlas en un json. 
El archivo `docker/main.py` se encarga de este proceso.

## Tareas
- Debemos verificar los templates de OMR en la ruta `resources/views/omr`, debemos hacer que todos los templates de OMR que tenemos en el sistema puedan ser detectados y alineados correctamente por el codigo en `docker/main.py`.
- Los 4 marcadores siempre deben estar en las esquinas de la hoja, entonces debemos tener todo estandarizado dentro de los templates de los archivos de referencia.
- Vas a modificar los archivos de OMR en `resources/views/omr` para que puedan ser detectados y alineados correctamente por el codigo en `docker/main.py`.
- Si es necesario, vas a modificar el codigo en `docker/main.py` para que pueda detectar y alinear correctamente los templates de OMR que tenemos en el sistema.
- Vas a crear un comando que nos creara en storage 3 archivos pdf de prueba, uno por cada template de OMR que tenemos en el sistema. El comando debe llamarse `omr:generate-test-pdfs` y debe crear los archivos en `storage/app/omr-test-pdfs`. Los archivos deben tener el nombre del template, por ejemplo: `referencia-i.pdf`, `referencia-iii.pdf`, `referencia-v.pdf`.
- Para los archivos tests deberas incluir folios y llenar burbujas de respuestas, para que el codigo pueda detectar y procesar las respuestas.
- Debemos hacer que el proceso de main.py pueda detectar tambien las respuestas y continuar el proceso. 



Es posible que haya que modificar main.py para que pueda manejar mejor las evaluaciones que tenemos. 


## Notas
- Crea una branch usando las mejores practicas para nombrar branches basada en develop.
- Utiliza las mejores practicas para escribir codigo python.
- Actualmente todos los servicios, queue, docker, reverb estan corriendo, no tienes que preocuparte por levantar alguno. 
- Para verificar cualquier cosa de python es necesario usar `docker exec -it training-and-ms ...` la ruta dentro del contenedor es `/app` ahí es donde estan todos los archivos.
- El contenedor docker solo se usa para el python. 
- No es necesario que escribas pruebas unitarias. 
- No es necesario hacer npm run build|dev o php artisan serve, ya que estamos trabajando con laravel herd, entonces ya esta levantado todo.