---
mode: agent
model: GPT-5 (Preview)
tools: ['codebase', 'fetch', 'editFiles', 'runCommands']
---
# Prompt para Agente de IA – Implementación de Campos Personalizados en Quizzes

## Objetivo
Diseñar, implementar y validar un sistema de **campos personalizados** para quizzes, siguiendo los requisitos funcionales, criterios de aceptación, arquitectura y plan de tareas provistos. El agente debe producir un sistema completamente funcional, seguro, optimizado y compatible con diferentes tipos de quiz (normal, reducido, Cisneros).

---

## Requisitos Funcionales

1. **Gestión de Campos Personalizados (Admin)**  
   - Crear, editar, reordenar y eliminar campos personalizados de tipo: `text`, `textarea`, `select`, `number`, `date`.  
   - Validar nombre único por quiz, tipo de campo válido y opciones correctas para `select`.  
   - Almacenar campos como JSON en `quizzes` y opciones en tabla relacional.

2. **Visualización y Captura de Datos (Usuario)**  
   - Mostrar campos personalizados en la sección de datos personales de un quiz.  
   - Ocultar sección si no existen campos personalizados.  
   - Validar datos según tipo y obligatoriedad.

3. **Almacenamiento de Respuestas**  
   - Guardar respuestas en `online_answers` con `reference_guide='CUSTOM'` y `question_key` formato `custom_field_{id}`.  
   - Mantener estructura y consistencia con datos existentes.

4. **Compatibilidad y Consistencia**  
   - Soportar todos los tipos de quiz actuales.  
   - Integrar en reportes junto con datos estándar.

5. **Seguridad y Rendimiento**  
   - Sanitizar entradas para evitar XSS/inyección.  
   - Limitar número de campos (máx. 20) y tamaño de respuesta (máx. 500 caracteres).  
   - Optimizar consultas (eager loading, índices).  
   - Asegurar control de acceso: solo administradores gestionan campos.

---

## Arquitectura y Diseño

- **Base de Datos**  
  - Tabla `quiz_custom_fields`: campos de configuración.  
  - Tabla `quiz_custom_field_options`: opciones para `select`.  
  - `online_answers`: guardar respuestas con referencia `CUSTOM`.

- **Modelos**  
  - `QuizCustomField`: relaciones con `Quiz` y `QuizCustomFieldOption`, validaciones y ordenamiento.  
  - `QuizCustomFieldOption`: solo para `select`, orden por `option_order`.  
  - `Quiz`: métodos `customFields()` y `hasCustomFields()`.

- **Backend**  
  - Modificar `QuizController` en métodos `store()`, `update()`, `showTemp()` y `submit()` para gestionar creación, edición, visualización y guardado de campos y respuestas.

- **Frontend**  
  - `CustomFieldsManager`: UI para CRUD de campos y opciones, validación en tiempo real.  
  - `CustomFieldsRenderer`: renderizado dinámico según tipo de campo, validaciones y recolección de datos.  
  - Integrar en `Quiz/Take`, `Quiz/TakeReduced`, `Quiz/TakeCisneros`.

---

## Plan de Implementación (Pasos Clave)

1. Migraciones para tablas `quiz_custom_fields` y `quiz_custom_field_options` con índices y llaves foráneas.  
2. Creación de modelos y relaciones.  
3. Actualización de validaciones en backend para campos personalizados.  
4. Inclusión de campos en la vista previa (`showTemp()`) con *eager loading*.  
5. Procesamiento y almacenamiento de respuestas (`submit()`).  
6. Desarrollo de `CustomFieldsManager` (gestión) y `CustomFieldsRenderer` (renderizado).  
7. Integración en vistas de creación, edición y toma de quiz.  
8. Validaciones frontend y backend completas.  
9. Tests unitarios e integración para modelos, controladores y componentes.  
10. Manejo de errores con mensajes *user-friendly*.  
11. Optimizaciones de rendimiento y límites de uso.  
12. Documentación técnica y guía de usuario.

---

## Estrategia de Testing

- **Unitarios**: modelos, relaciones, validaciones, ordenamiento.  
- **Integración Backend**: creación, edición, renderizado y guardado de respuestas.  
- **Integración Frontend**: CRUD de campos, renderizado de inputs, validaciones, flujo completo.  
- **Escenarios Negativos**: tipos inválidos, nombres duplicados, campos obligatorios sin respuesta.  
- **Rendimiento**: pruebas con carga alta de campos.

---

## Lineamientos para el Agente

- Mantener integridad referencial en BD mediante transacciones.  
- Asegurar compatibilidad con quizzes sin campos personalizados.  
- Respetar limitaciones y reglas de validación estrictas.  
- Producir código documentado, seguro y optimizado.  
- Generar mensajes claros para usuarios y administradores ante errores.  
- Garantizar que los datos recolectados sean procesables en reportes existentes.

---

# Referencias extendidas 
- [design.md](../../.kiro/specs/quiz-custom-fields/design.md)
- [requirements.md](../../.kiro/specs/quiz-custom-fields/requirements.md)
- [tasks.md](../../.kiro/specs/quiz-custom-fields/tasks.md)

---
