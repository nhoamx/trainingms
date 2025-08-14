# Documento de Diseño - Campos Personalizados para Quizzes

## Visión General

El sistema de campos personalizados permitirá a los administradores añadir campos adicionales a los quizzes que se mostrarán en la sección de datos personales. Estos campos se almacenarán como JSON en la tabla de quizzes y sus respuestas se guardarán en la tabla online_answers usando el mismo patrón que los datos existentes.

## Arquitectura

### Modificaciones en la Base de Datos

#### Nueva Tabla `quiz_custom_fields`
```sql
- id (bigint, primary key)
- quiz_id (bigint, foreign key to quizzes.id)
- field_name (string, 255)
- field_type (enum: 'text', 'textarea', 'select', 'number', 'date')
- is_required (boolean, default false)
- placeholder (string, nullable)
- field_order (integer, default 0)
- created_at, updated_at (timestamps)
```

#### Nueva Tabla `quiz_custom_field_options`
```sql
- id (bigint, primary key)
- custom_field_id (bigint, foreign key to quiz_custom_fields.id)
- option_value (string, 255)
- option_label (string, 255)
- option_order (integer, default 0)
- created_at, updated_at (timestamps)
```

#### Tabla `online_answers` 
- Usar reference_guide 'CUSTOM' para identificar respuestas de campos personalizados
- question_key contendrá el ID del custom_field: "custom_field_{id}"
- Mantener la estructura existente: question_key, answer_value

### Estructura de Datos

#### Modelo QuizCustomField
- Relación belongsTo con Quiz
- Relación hasMany con QuizCustomFieldOption
- Métodos para validación y renderizado

#### Modelo QuizCustomFieldOption
- Relación belongsTo con QuizCustomField
- Para campos tipo 'select' únicamente

## Componentes y Interfaces

### Backend - Modelos

#### Nuevo Modelo QuizCustomField
```php
class QuizCustomField extends Model
{
    protected $fillable = [
        'quiz_id', 'field_name', 'field_type', 'is_required', 
        'placeholder', 'field_order'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'field_order' => 'integer'
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options()
    {
        return $this->hasMany(QuizCustomFieldOption::class, 'custom_field_id')
                    ->orderBy('option_order');
    }
}
```

#### Nuevo Modelo QuizCustomFieldOption
```php
class QuizCustomFieldOption extends Model
{
    protected $fillable = [
        'custom_field_id', 'option_value', 'option_label', 'option_order'
    ];

    protected $casts = [
        'option_order' => 'integer'
    ];

    public function customField()
    {
        return $this->belongsTo(QuizCustomField::class, 'custom_field_id');
    }
}
```

#### Modificaciones al Modelo Quiz
```php
public function customFields()
{
    return $this->hasMany(QuizCustomField::class)->orderBy('field_order');
}

public function hasCustomFields()
{
    return $this->customFields()->exists();
}
```

### Backend - QuizController

#### Modificaciones en store() y update()
- Validar datos de campos personalizados
- Crear/actualizar registros en quiz_custom_fields y quiz_custom_field_options
- Manejar transacciones para mantener integridad

#### Modificaciones en showTemp()
- Incluir customFields con sus options en los datos enviados al frontend
- Usar eager loading para optimizar queries
- Mantener compatibilidad con quizzes existentes

#### Modificaciones en submit()
- Procesar respuestas de campos personalizados usando custom_field_id
- Almacenar en online_answers con reference_guide 'CUSTOM'
- question_key formato: "custom_field_{id}"

### Frontend - Componentes

#### CustomFieldsManager (Nuevo)
- Componente para gestionar campos personalizados en la creación/edición de quiz
- Permite añadir, editar, reordenar y eliminar campos
- Validación en tiempo real

#### CustomFieldsRenderer (Nuevo)
- Componente para mostrar campos personalizados en el quiz
- Renderiza diferentes tipos de campos según su configuración
- Maneja validación de campos requeridos

#### Modificaciones en Quiz/Take, Quiz/TakeReduced, Quiz/TakeCisneros
- Integrar CustomFieldsRenderer en la sección de datos personales
- Mostrar solo si existen campos personalizados
- Incluir respuestas en el envío del formulario

## Modelos de Datos

### Tipos de Campos Soportados

1. **text**: Campo de texto simple
2. **textarea**: Campo de texto multilínea
3. **select**: Lista desplegable con opciones predefinidas
4. **number**: Campo numérico
5. **date**: Selector de fecha

### Validaciones

#### En el Backend
- Validar tipos de campo permitidos (enum)
- Validar que field_name sea único por quiz
- Validar que los campos requeridos tengan respuesta
- Validar opciones solo para campos tipo 'select'
- Sanitizar datos de entrada

#### En el Frontend
- Validación en tiempo real de campos requeridos
- Validación de formato según tipo de campo
- Prevenir envío si faltan campos obligatorios

## Manejo de Errores

### Escenarios de Error

1. **Tipo de campo no soportado**: Retornar error de validación
2. **Nombre de campo duplicado**: Mostrar error de validación
3. **Campo requerido sin respuesta**: Mostrar error de validación
4. **Error al guardar campos/respuestas**: Rollback de transacción
5. **Opciones inválidas para campo select**: Retornar error de validación

### Estrategias de Recuperación

- Validación exhaustiva antes de guardar
- Transacciones de base de datos para mantener consistencia
- Logs detallados para debugging
- Fallback a comportamiento estándar si custom_fields es null

## Estrategia de Testing

### Tests Unitarios

#### Modelos
- Test de relaciones Quiz -> QuizCustomField -> QuizCustomFieldOption
- Test de métodos helper (hasCustomFields)
- Test de validaciones en QuizCustomField y QuizCustomFieldOption
- Test de ordenamiento por field_order y option_order

#### QuizController
- Test de creación de quiz con campos personalizados
- Test de validación de campos y opciones
- Test de procesamiento de respuestas personalizadas
- Test de almacenamiento en online_answers con reference_guide 'CUSTOM'
- Test de eager loading de customFields con options

### Tests de Integración

#### Flujo Completo
- Test de creación de quiz con campos personalizados
- Test de toma de quiz con campos personalizados
- Test de almacenamiento de respuestas
- Test de compatibilidad con diferentes tipos de quiz

### Tests Frontend

#### Componentes
- Test de CustomFieldsManager (CRUD de campos)
- Test de CustomFieldsRenderer (renderizado y validación)
- Test de integración con formularios existentes

## Consideraciones de Rendimiento

### Optimizaciones

1. **Eager Loading**: Usar with(['customFields.options']) para evitar N+1 queries
2. **Indexing**: Índices en quiz_id, field_order, custom_field_id, option_order
3. **Caching**: Cache de campos para quizzes activos frecuentemente accedidos

### Limitaciones

- Máximo 20 campos personalizados por quiz
- Máximo 500 caracteres por respuesta de texto
- Máximo 10 opciones por campo select

## Migración y Compatibilidad

### Estrategia de Migración

1. Crear tablas quiz_custom_fields y quiz_custom_field_options
2. Quizzes existentes no tendrán registros relacionados
3. Comportamiento backward-compatible

### Compatibilidad

- Quizzes sin campos personalizados funcionarán igual que antes
- Frontend detectará ausencia de campos y no mostrará sección
- Reportes existentes no se verán afectados

## Seguridad

### Validaciones de Seguridad

1. **Sanitización**: Limpiar HTML/JS de respuestas de texto
2. **Validación de Tipos**: Verificar tipos de datos en respuestas
3. **Límites de Tamaño**: Limitar tamaño de respuestas y número de campos
4. **Autorización**: Solo administradores pueden gestionar campos personalizados

### Prevención de Ataques

- Validación estricta de tipos de campo y opciones
- Escape de caracteres especiales en respuestas
- Límites de rate limiting en creación de campos
- Validación de unicidad de nombres de campo por quiz