---
mode: agent
model: Claude Sonnet 4
tools: ['codebase', 'fetch', 'editFiles', 'runCommands']
---
Eres un experto en desarrollo de laravel 12 con inertiajs y vue.js, manejas codigo limpio, con un sistema modular y buenas practicas. 

## Objetivo
Diseñar, implementar y validar un sistema de campos personalizados para la implementación actual de Quizzes, siguiendo los requisitos funcionales. El sistema debe permitir la creación, edición y visualización de campos personalizados de manera intuitiva y eficiente para los tipos de quizes actuales (Take, TakeReduce, TakeCisneros).

---

## Requisitos Funcionales

1. Como administrador, debo poder gestionar los campos personalizados en el modal de creación de curso. 
2. Como administrador, debo poder editar o eliminar estos campos despues de que los haya creado.
3. Como usuario, devo poder ver estos campos en la seccion de datos personales añadiendo una indicación visual que diga "Datos Adicionales", validando que si el examen no cuenta con ellos, no añadiremos indicación visual de ellos.
4. Como Analista de Datos, necesito que los campos personalizados incorporados en el formulario se guarden de la misma forma que los campos estándar.
5. Como administrador, estos campos deben aparecer en el reporte que mostramos en el dashboard. 

---

## Arquitectura y Diseño

- **Base de Datos**  
  - Tabla `custom_fields`: campos de configuración.  
  - Campos:
   - `id`: identificador único.
   - `name`: string.
   - `type`: enum (text, number, textarea).
   - `quiz_id`: foreign key a la tabla `quizzes`.


---

## Plan de Implementación (Pasos Clave)

1. Crear una branch basado en develop utilizando las mejors practicas
2. Migraciones para la tabla `custom_fields`
3. Creación del modelo y actualización de modelos y relaciones.
4. Implementar la logica de negocio para crear y relacionar un custom field a un quiz 
5. Actualizar flujo de creación de quizzes, convirtiendo el modal en un formulario incrustado en la pagina sobre la tabla de quizes.
6. Implementar sección para visualizar/editar quizz y ver los campos en el formulario
7. Implementar la sección de "Datos Adicionales" en la vista de detalles del quiz, mostrando los campos personalizados si existen.

---

## Reglas

- Mantener integridad referencial en BD mediante transacciones.  
- Asegurar compatibilidad con quizzes sin campos personalizados.  
- Respetar limitaciones y reglas de validación estrictas.  
- Producir código documentado, seguro y optimizado.  
- Generar mensajes claros para usuarios y administradores ante errores.  
- Garantizar que los datos recolectados sean procesables en reportes existentes.
- Utiliza los comandos de laravel para la creación de archivos necesarios.
- Nunca corras `php artisan serve`, `npm run dev` o `npm run build`, no son necesarios.

---
