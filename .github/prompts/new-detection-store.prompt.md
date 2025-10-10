---
mode: agent
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'fetch']
model: Claude Sonnet 4.5 (Preview) (copilot)
---

Vamos a mejorar la forma en que guardamos la información de las evaluaciones. Por lo cual te explicare lo siguiente: 

- Actualmente revisamos 4 evaluaciones: Referencia I, Referencia III, Referencia V y Cisneros. 
- Cada una inicia con un código
 - 01 = Referencia I
 - 02 = Referencia III
 - 03 = Referencia V
 - 04 = Cisneros
- Los codigos son partes de un folio, el cual es unico para cada evaluación. Este se divide de la siguiente forma: 
 - 2 primeros numeros = código de la evaluación
 - 3 siguientes numeros = codigo/id de la empresa
 - 4 siguientes numeros = folio de la persona a evaluar
- De esta forma obtenemos un folio unico para cada evaluación, el cual es de 9 dígitos (e.j. 019530001).
- El codigo para la detección de la evaluación se encuentra en el contenedor docker y puede manejar archivos con multiples hojas y evaluaciones.

El proceso que se sigue es el siguiente: 
- Se sube el archivo PDF con las evaluaciones mediante la vista en la ruta `evaluations.load` y se envia por el metodo post a la ruta `evaluations.store`.
- En el controlador `EvaluationController` en el método `store` se recibe el archivo y se procesa.
- Este archivo se maneja desde un contenedor docker mediante `app/Jobs/ProcessEvaluation.php`
- El docker se encarga de manejar el archivo y extraer la información de las evaluaciones.
- La información se guarda en `docker/output` en archivos json, los cuales tienen nombre de el folio de cada hoja.
- Cuando el contenedor termina de procesar los datos, estos se siguen procesando en el Job para guardarlos en la base de datos.


## Problema
Actualmente la forma de guardar las evaluaciones es un poco desordenada y no sigue una estructura clara y mantenible. Revisa la estructura actual, esta sera ahora una estructura legacy, no vamos a actualizarla, simplemente la dejaremos ahí ya que anteriormente se ha usado en producción. 

Utiliza el ejemplo actual de los archivos en la carpeta `docker/output` para entender como se guardan las evaluaciones actualmente y conozcas la estructura nueva. 

con base en esta estructura, debemos crear una migración que nos permita almacenar evaluaciones de una forma mantenible y entendible, además que nos permita hacer reportes y graficas. 

## Tarea
- Crearas una nueva migración para la información de las evaluaciones con la estructura nueva. 
- Revisaras que el UI/UX de la aplicación este trabajando bien con reverb al momento de que el job esta activo y se estan procesando las evaluaciones.
- La migración nueva debera tener un campo el cual indicara si la evaluación es presencial o en línea. 
- Es probable que necesitemos crear un nuevo job tambien y el actual se convertira en legacy.

## Notas
- Crea una branch nueva basada en develop (revisa que este actualizada) y utiliza las mejores practicas para nombrarla
- Utiliza principios SOLID y buenas practicas de desarrollo
- Recuerda, debemos crear la migración, arreglar y mejorar el UI/UX y probablemente crear un nuevo job.
- No tocaremos nada del codigo de python. 