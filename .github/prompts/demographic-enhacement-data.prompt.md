---
mode: agent
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'fetch']
model: Claude Sonnet 4.5 (copilot)
---

Eres un ingeniero experto en análisis y enriquecimiento de datos. Actualmente tenemos nuestro modelo `app/Models/PaperEvaluation.php` que almacena las evaluaciones y además contiene información demografica dentro de un campo json llamado `demographic_data`. Este campo se usa para almacenar datos demograficos de la persona tales como, genero, edad, nivel educativo, etc.

Sin embargo, hemos notado que el generar los reportes con esto es complicado, ya que los datos demograficos no estan normalizados y es dificil hacer consultas complejas sobre ellos.

Por lo que ahora crearemos un modelo nuevo para almacenar los datos demograficos de manera normalizada. El nuevo modelo se llamara `DemographicData` y tendra las siguientes columnas:
- id (primary key uuid)
- paper_evaluation_id (foreign key a PaperEvaluation que es un uuid)
- gender
- age
- estado_civil
- nivel_estudios
- puesto
- area
- tipo_puesto
- tipo_contratacion
- tipo_personal
- tipo_jornada
- rotacion_turnos
- tiempo_puesto_actual
- tiempo_experiencia_laboral
- extra_fields (json para cualquier otro dato adicional, este sera un objeto json el cual puede tener varios, si el valor no existe en las claves, se guardara aquí)


Los datos los manejaremos en inglés en la base de datos, por lo que las columnas deben estar en inglés.

Además de esto, actualmente ya teenemos datos de las guias de referencia V los cuales son los datos demograficos, deberemos migrar esos datos json al nuevo modelo. Esto lo haremos mediante un one time command que se encargara de leer el modelo `PaperEvaluation`, extraer los datos demograficos del campo json `demographic_data` de los tipo `referencia_v` y crear una nueva instancia de `DemographicData` para cada evaluacion, guardando los datos correspondientes en las columnas adecuadas.

Tambien el proceso `app/Jobs/ProcessPaperEvaluation.php` que se encarga de procesar las evaluaciones, debera ser modificado para que en lugar de guardar los datos demograficos en el campo json, los guarde en el nuevo modelo `DemographicData`.

## Datos a considerar
- Crea una branch nueva donde realizaras todos los cambios necesarios.
- Utiliza las mejores practicas de laravel para la creación de los datos y modelos nuevos. 
- Realizaras conventional commits de cada cambio que realices. 