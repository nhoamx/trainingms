# Importación Masiva de Centros de Trabajo

## Descripción General
Sistema de importación masiva para dar de alta múltiples centros de trabajo (sucursales, plantas, almacenes, oficinas) desde un archivo Excel.

## Acceso a la Funcionalidad
1. Ir a **Organizaciones** → Editar organización
2. Click en pestaña **"Centros de Trabajo"**
3. Click en botón **"Importar desde Excel"** (verde)

## Flujo de Importación

### 1. Descargar Plantilla
- El modal muestra un botón **"Descargar Plantilla Excel"**
- Si la organización ya tiene centros de trabajo secundarios, la plantilla incluirá los datos actuales
- Si no hay centros adicionales, la plantilla estará vacía con solo encabezados

### 2. Campos del Excel

#### Campos Requeridos
- **Nombre**: Nombre del centro de trabajo (max 255 caracteres)
- **Tipo**: Tipo de centro. Valores válidos:
  - `Matriz`
  - `Planta`
  - `Sucursal`
  - `Almacén`
  - `Oficina`
  - `Otro`

#### Campos Opcionales
- **Código**: Identificador de 4 dígitos (ej: 0002, 0003). Si está vacío, se genera automáticamente.
- **Razón Social**: Razón social del centro de trabajo
- **RFC**: Registro Federal de Contribuyentes (max 13 caracteres)
- **Registro Patronal**: Número de registro patronal (max 20 caracteres)
- **Calle y Número**: Dirección física completa
- **Colonia**: Colonia o barrio
- **Código Postal**: CP del domicilio (max 10 caracteres)
- **Municipio**: Municipio o delegación
- **Estado**: Estado de la República
- **Teléfono**: Número de contacto (max 20 caracteres)
- **Email**: Correo electrónico (validación de formato)
- **Notas**: Observaciones o comentarios adicionales

### 3. Validaciones

#### Validación de Tipo
Los valores de la columna **Tipo** son case-insensitive. Valores válidos:
- `matriz` / `Matriz` / `MATRIZ`
- `planta` / `Planta` / `PLANTA`
- `sucursal` / `Sucursal` / `SUCURSAL`
- `almacén` / `Almacén` / `ALMACEN` (con o sin acento)
- `oficina` / `Oficina` / `OFICINA`
- `otro` / `Otro` / `OTRO`

#### Validación de Código
- Si está vacío: Se genera automáticamente el siguiente código disponible (0002, 0003, 0004...)
- Si está lleno: Debe ser único dentro de la organización
- Siempre se convierte a 4 dígitos con relleno de ceros a la izquierda

#### Validación de Email
- Debe tener formato válido de email si se proporciona
- Es opcional

### 4. Lógica de Creación/Actualización

#### Creación (Registro Nuevo)
Cuando el código está vacío o no existe en la base de datos:
- Se crea un nuevo centro de trabajo
- Se genera código automáticamente si está vacío
- Se asignan todos los campos proporcionados

#### Actualización (Registro Existente)
Cuando el código ya existe en la organización:
- Se actualiza el centro de trabajo existente
- Se mantienen los valores anteriores si la celda está vacía
- **IMPORTANTE**: No se puede actualizar el centro primario (código 0001)

### 5. Restricciones del Centro Primario
El centro primario (código 0001) está protegido:
- ❌ No puede ser modificado mediante importación
- ❌ No puede ser eliminado
- ℹ️ Se sincroniza automáticamente con los datos de la organización
- Si se intenta actualizar en el Excel, se omite y se registra en errores

### 6. Resultado de la Importación
Al finalizar, se muestra un mensaje con el resumen:
```
Importación completada: X creados, Y actualizados, Z omitidos.
```

Si hay errores, se muestran los primeros 3:
```
Importación completada: 5 creados, 2 actualizados, 1 omitidos. 
Errores: Fila 3: El tipo inválido 'bodega'. Tipos válidos: matriz, planta, sucursal...
```

## Ejemplos de Uso

### Ejemplo 1: Crear Sucursales
```
Código | Nombre              | Tipo      | Razón Social            | RFC           | Calle y Número
-------|---------------------|-----------|-------------------------|---------------|------------------
       | Sucursal Norte      | Sucursal  | Empresa Norte SA        | ENO123456789  | Av. Norte 123
       | Sucursal Sur        | Sucursal  | Empresa Sur SA          | ESU987654321  | Calle Sur 456
```

### Ejemplo 2: Crear Plantas y Almacenes
```
Código | Nombre              | Tipo      | Municipio    | Estado      | Teléfono
-------|---------------------|-----------|--------------|-------------|-------------
0010   | Planta Monterrey    | Planta    | Monterrey    | Nuevo León  | 8112345678
0020   | Almacén Guadalajara | Almacén   | Guadalajara  | Jalisco     | 3398765432
```

### Ejemplo 3: Actualizar Centros Existentes
```
Código | Nombre              | Email                  | Teléfono
-------|---------------------|------------------------|-------------
0002   | Sucursal Norte      | norte@empresa.com      | 5512345678
0003   | Sucursal Sur        | sur@empresa.com        | 5598765432
```

## Manejo de Errores

### Errores Comunes
1. **Fila X: El nombre del centro de trabajo es requerido**
   - La columna "Nombre" está vacía
   - Solución: Llenar el campo

2. **Fila X: El tipo de centro de trabajo es requerido**
   - La columna "Tipo" está vacía
   - Solución: Especificar un tipo válido

3. **Fila X: Tipo inválido 'bodega'. Tipos válidos: matriz, planta, sucursal, almacen, oficina, otro**
   - El valor en "Tipo" no es válido
   - Solución: Usar uno de los valores listados

4. **Fila X: Ya existe un centro con el código 0002**
   - Código duplicado en el mismo archivo
   - Solución: Verificar códigos únicos o dejar vacío para auto-generación

5. **Fila X: No se puede actualizar el centro primario (código 0001)**
   - Intentando modificar el centro principal
   - Solución: Eliminar esa fila del Excel

6. **Fila X: El email debe tener un formato válido**
   - Email con formato incorrecto
   - Solución: Verificar formato (ejemplo@dominio.com)

## Archivos Relacionados
- **Import**: `app/Imports/WorkCentersImport.php`
- **Export**: `app/Exports/WorkCentersExport.php`
- **Controller**: `app/Http/Controllers/WorkCenterController.php`
- **Routes**: `routes/web.php` (organizations.work-centers.template, organizations.work-centers.import)
- **Frontend**: `resources/js/Pages/Organizations/components/WorkCentersSection.vue`

## Estructura de la Base de Datos
Tabla: `work_centers`
- `id`: UUID
- `organization_id`: UUID (FK)
- `code`: string(4) - Código único por organización
- `name`: string(255)
- `type`: enum (headquarters, plant, branch, warehouse, office, other)
- `is_primary`: boolean
- `legal_name`: string(255) nullable
- `tax_id`: string(13) nullable
- `employer_registration`: string(20) nullable
- `street_address`: string(255) nullable
- `neighborhood`: string(100) nullable
- `postal_code`: string(10) nullable
- `municipality`: string(100) nullable
- `state`: string(100) nullable
- `phone`: string(20) nullable
- `email`: string(255) nullable
- `notes`: text nullable
- `created_at`: timestamp
- `updated_at`: timestamp
