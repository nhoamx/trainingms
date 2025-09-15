---
mode: agent
model: Claude Sonnet 4 (copilot)
tools: ['editFiles', 'codebase', 'runCommands', 'fetch', 'laravel-boost']
---


Actualmente tenemos unas rutas para las guias de referencia I, III, V y escala Cisneros. Estas debemos poderlas convertir en PDF para descargarlas cuando creamos los folios en el sistema dentro de la ruta `organizations.edit`. 

Las rutas de las vistas son las siguientes: 
- `referencia-i`
- `referencia-iii`
- `referencia-v`
- `escala-cisneros`

Estas caracteristicas debera tener el archivo PDF: 
- Tamaño Carta
- Margenes de 10mm
- Debemos crear una hoja por cada folio, es decir, si el usuario selecciona 3 folios, se deben generar 3 hojas en el PDF.
- Cada hoja debera tener llenado el folio .


Realiza los ajustes necesarios en las vistas para que se vean bien en el PDF y crea la funcionalidad para generar el PDF con las caracteristicas mencionadas.

## Notas
- Crea una branch basada en develop usando las mejores practicas 
- El paquete instalado es `barryvdh/laravel-dompdf`