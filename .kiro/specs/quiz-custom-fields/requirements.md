# Documento de Requisitos - Campos Personalizados para Quizzes

## Introducción

Esta funcionalidad permitirá añadir campos personalizados (custom fields) a los quizzes de forma manual, que se mostrarán en la sección de datos personales durante la evaluación. Los campos se validarán para mostrar solo las secciones que contengan datos, y se almacenarán en la base de datos de la misma forma que los datos existentes.

## Requisitos

### Requisito 1

**Historia de Usuario:** Como administrador del sistema, quiero poder añadir campos personalizados a un quiz, para que pueda recopilar información específica adicional según las necesidades de cada organización.

#### Criterios de Aceptación

1. CUANDO un administrador cree o edite un quiz ENTONCES el sistema DEBERÁ permitir añadir campos personalizados de diferentes tipos (texto, selección múltiple, fecha, número)
2. CUANDO se añadan campos personalizados ENTONCES el sistema DEBERÁ validar que cada campo tenga un nombre único y un tipo válido
3. CUANDO se guarden los campos personalizados ENTONCES el sistema DEBERÁ almacenarlos en formato JSON en la tabla de quizzes

### Requisito 2

**Historia de Usuario:** Como usuario que toma un quiz, quiero ver los campos personalizados en la sección de datos personales, para que pueda proporcionar la información adicional requerida.

#### Criterios de Aceptación

1. CUANDO un usuario acceda a un quiz con campos personalizados ENTONCES el sistema DEBERÁ mostrar estos campos en la sección de datos personales
2. CUANDO no existan campos personalizados para un quiz ENTONCES el sistema NO DEBERÁ mostrar una sección vacía de campos personalizados
3. CUANDO se muestren los campos personalizados ENTONCES el sistema DEBERÁ aplicar las validaciones correspondientes según el tipo de campo

### Requisito 3

**Historia de Usuario:** Como administrador del sistema, quiero que las respuestas de los campos personalizados se almacenen de la misma forma que los datos existentes, para que pueda generar reportes y análisis consistentes.

#### Criterios de Aceptación

1. CUANDO un usuario complete un quiz con campos personalizados ENTONCES el sistema DEBERÁ almacenar las respuestas en la tabla online_answers con reference_guide 'CUSTOM'
2. CUANDO se almacenen las respuestas personalizadas ENTONCES el sistema DEBERÁ usar el mismo formato que las respuestas existentes (question_key y answer_value)
3. CUANDO se procesen las respuestas ENTONCES el sistema DEBERÁ mantener la integridad referencial con el quiz y la organización

### Requisito 4

**Historia de Usuario:** Como administrador del sistema, quiero poder gestionar los campos personalizados existentes, para que pueda modificar, eliminar o reordenar los campos según sea necesario.

#### Criterios de Aceptación

1. CUANDO un administrador edite un quiz ENTONCES el sistema DEBERÁ mostrar los campos personalizados existentes para su modificación
2. CUANDO se modifiquen campos personalizados ENTONCES el sistema DEBERÁ permitir cambiar el orden, editar propiedades o eliminar campos
3. CUANDO se eliminen campos personalizados ENTONCES el sistema DEBERÁ advertir sobre el impacto en datos existentes

### Requisito 5

**Historia de Usuario:** Como desarrollador del sistema, quiero que los campos personalizados sean compatibles con los diferentes tipos de quiz existentes, para que la funcionalidad sea consistente en todo el sistema.

#### Criterios de Aceptación

1. CUANDO se añadan campos personalizados ENTONCES el sistema DEBERÁ soportarlos en quizzes normales, reducidos y de tipo Cisneros
2. CUANDO se procesen diferentes tipos de quiz ENTONCES el sistema DEBERÁ manejar los campos personalizados de forma consistente
3. CUANDO se generen reportes ENTONCES el sistema DEBERÁ incluir los datos de campos personalizados junto con los datos estándar