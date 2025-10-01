---
mode: agent
model: Claude Sonnet 4.5 (Preview) (copilot)
tools: ['edit', 'search', 'runCommands', 'fetch', 'laravel-boost']
---

Actualmente nuestras vistas de templates de OMR añaden solo el folio de la persona, estos comienzan de izquierda a derecha. Sin embargo haremos un ajuste para al momento de escanear sabeer que documento es, de que organización y de que persona es. 

Para eso asignaremos algunas claves: 

## Tipo de template
- Referencia I: 01
- Referencia III: 02
- Referencia V: 03
- Escala Cisneros: 04

## Organización
La organización tiene asignada un folio interno, este lo puedes buscar en la tabla folio_organization, consta de 3 numeros solamente, por ejemplo 001, 002, 003, etc.

## Persona
La persona tiene un folio interno, este ya se esta añadiendo en las vistas, consta de 4 numeros solamente, por ejemplo 0001, 0002, 0003, etc.

## Ejemplo
Si tenemos un template de Referencia I (01), de la organización con folio 002 y de la persona con folio 0001, el folio completo que se añadirá en la vista será: 010020001

Debemos además de marcar las burbujas, poder añadir el numero en el recuadro que viene en la parte superior del recuadro del folio. 

## Extra:
- Actualmente para generar un template usamos un boton dentro de `resources/js/Pages/Organizations/components/Folios.vue` el cual abre la ruta de los templates con query strings para generar el template. Puedes ver el codigo en `app/Http/Controllers/OMRController.php` por ejemplo `referenciaI(Request $request)`. Creo que es una mala practica ya que si, queremos imprimir 100 templates se hara una querystring superlarga. 
- Evalua la forma más eficiente y con buenas practicas para hacer esto. Por lo que tendras que refactorizar un poco el codigo para recibir los parametros necesarios para generar el template y la vista para saber como debe enviar los parametros adecuados. 

## Notas:
- Realiza una branch que siga la convención de nombres, basada en develop (verifica que este actualizada con lo ultimo).
- Los commits seran conventional commits, estos deberan ser claros y descriptivos. 
- Si vas a trabajar con tests, procura usar database transactions para que no queden datos residuales.
- Al finalizar la tarea, realiza un PR a develop, con una descripción clara de los cambios realizados y el motivo de estos.