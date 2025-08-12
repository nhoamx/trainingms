# Plan de Implementación - Campos Personalizados para Quizzes

- [ ] 1. Crear migraciones para tablas de campos personalizados
  - Crear migración para tabla `quiz_custom_fields` con columnas: quiz_id, field_name, field_type, is_required, placeholder, field_order
  - Crear migración para tabla `quiz_custom_field_options` con columnas: custom_field_id, option_value, option_label, option_order
  - Añadir índices apropiados y foreign keys
  - Ejecutar migraciones en entorno de desarrollo
  - _Requisitos: 1.3, 3.2_

- [ ] 2. Crear modelos QuizCustomField y QuizCustomFieldOption
  - Crear modelo QuizCustomField con fillable, casts y relaciones
  - Crear modelo QuizCustomFieldOption con fillable, casts y relaciones
  - Añadir relación customFields() al modelo Quiz
  - Implementar método hasCustomFields() en modelo Quiz
  - Escribir tests unitarios para los nuevos modelos y relaciones
  - _Requisitos: 1.1, 1.2_

- [ ] 3. Actualizar validaciones en QuizController para campos personalizados
  - Modificar método store() para validar y crear campos personalizados
  - Modificar método update() para validar y actualizar campos personalizados
  - Crear reglas de validación para tipos de campo y opciones
  - Implementar validación de unicidad de nombres de campo por quiz
  - Escribir tests para validaciones de campos personalizados
  - _Requisitos: 1.1, 1.2, 4.2_

- [ ] 4. Modificar QuizController::showTemp() para incluir campos personalizados
  - Usar eager loading para cargar customFields con sus options
  - Incluir campos personalizados en datos enviados a las vistas Take, TakeReduced y TakeCisneros
  - Mantener compatibilidad con quizzes sin campos personalizados
  - Escribir tests para verificar inclusión de campos personalizados
  - _Requisitos: 2.1, 5.1_

- [ ] 5. Actualizar QuizController::submit() para procesar respuestas personalizadas
  - Modificar validación para incluir campos personalizados dinámicos basados en custom_field_id
  - Procesar respuestas de campos personalizados en storeOnlineAnswers()
  - Almacenar respuestas con reference_guide 'CUSTOM' y question_key formato "custom_field_{id}"
  - Escribir tests para procesamiento de respuestas personalizadas
  - _Requisitos: 3.1, 3.2, 3.3_

- [ ] 6. Crear componente CustomFieldsManager para gestión de campos
  - Implementar componente React/Vue para añadir campos personalizados
  - Incluir funcionalidad para crear, editar, reordenar y eliminar campos
  - Implementar validación en tiempo real de configuración de campos
  - Añadir soporte para diferentes tipos de campo (text, select, number, date, textarea)
  - _Requisitos: 1.1, 4.1, 4.2_

- [ ] 7. Crear componente CustomFieldsRenderer para mostrar campos en quiz
  - Implementar componente para renderizar campos personalizados según su tipo
  - Añadir validación de campos requeridos
  - Implementar manejo de diferentes tipos de input (text, select, number, date, textarea)
  - Escribir tests para renderizado de diferentes tipos de campo
  - _Requisitos: 2.1, 2.2_

- [ ] 8. Integrar CustomFieldsManager en vistas de creación/edición de quiz
  - Modificar vista Quiz/Index para incluir gestión de campos personalizados
  - Actualizar formularios de creación y edición de quiz
  - Implementar interfaz para gestionar campos existentes
  - Escribir tests de integración para gestión de campos
  - _Requisitos: 1.1, 4.1, 4.2, 4.3_

- [ ] 9. Integrar CustomFieldsRenderer en vistas de toma de quiz
  - Modificar Quiz/Take para mostrar campos personalizados en datos personales
  - Modificar Quiz/TakeReduced para mostrar campos personalizados
  - Modificar Quiz/TakeCisneros para mostrar campos personalizados
  - Implementar lógica para mostrar sección solo si existen campos
  - _Requisitos: 2.1, 2.2, 5.1, 5.2_

- [ ] 10. Implementar validaciones frontend para campos personalizados
  - Añadir validación de campos requeridos antes del envío
  - Implementar validación de formato según tipo de campo
  - Mostrar mensajes de error apropiados para cada tipo de validación
  - Escribir tests para validaciones frontend
  - _Requisitos: 2.3_

- [ ] 11. Actualizar procesamiento de respuestas en frontend
  - Modificar envío de formularios para incluir respuestas de campos personalizados
  - Asegurar compatibilidad con estructura de datos existente
  - Implementar serialización correcta de respuestas personalizadas
  - Escribir tests para envío de respuestas personalizadas
  - _Requisitos: 3.1, 3.2_

- [ ] 12. Crear tests de integración para flujo completo
  - Test de creación de quiz con campos personalizados
  - Test de toma de quiz con campos personalizados
  - Test de almacenamiento correcto en base de datos
  - Test de compatibilidad con diferentes tipos de quiz
  - _Requisitos: 5.1, 5.2, 5.3_

- [ ] 13. Implementar manejo de errores y logging
  - Añadir manejo de errores para validaciones de campos y opciones
  - Implementar logging para debugging de campos personalizados
  - Crear mensajes de error user-friendly para validaciones
  - Manejar errores de integridad referencial en transacciones
  - Escribir tests para escenarios de error
  - _Requisitos: 1.2, 2.3, 3.3_

- [ ] 14. Optimizar rendimiento y añadir limitaciones
  - Implementar límite máximo de campos personalizados por quiz
  - Añadir validación de tamaño máximo de respuestas
  - Optimizar queries para incluir custom_fields solo cuando sea necesario
  - Escribir tests de rendimiento para casos con muchos campos
  - _Requisitos: 1.2, 4.2_

- [ ] 15. Documentar funcionalidad y crear guía de usuario
  - Crear documentación técnica de la implementación relacional
  - Escribir guía de usuario para gestión de campos personalizados
  - Documentar estructura de tablas quiz_custom_fields y quiz_custom_field_options
  - Crear ejemplos de uso para diferentes tipos de campo
  - _Requisitos: 1.1, 4.1_