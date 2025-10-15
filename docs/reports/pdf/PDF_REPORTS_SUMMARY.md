# Implementación de Reportes PDF - Resumen

## Fecha de Implementación
10 de Octubre de 2025

## Branch
`feature/pdf-reports` (basado en `develop`)

## Descripción General
Se implementó la funcionalidad para generar y descargar reportes en PDF para evaluaciones de riesgo psicosocial conforme a la NOM-035-STPS-2018. Se crearon tres tipos de reportes:

1. **Informe Demográfico** - Distribución de datos demográficos con análisis por nivel de riesgo
2. **Informe de Resultados Diagnóstico** - Análisis completo de factores de riesgo psicosocial
3. **Informe Ejecutivo** - Placeholder para implementación futura

## Cambios en Base de Datos

### Migración: `2025_10_11_031343_add_report_fields_to_organizations_table.php`
Se añadieron los siguientes campos a la tabla `organizations`:

**Datos de Identificación:**
- `razon_social` - Razón social de la empresa
- `rfc` - RFC de la organización
- `registro_patronal` - Número de registro patronal
- `actividad_principal` - Descripción de la actividad principal

**Datos de Domicilio:**
- `calle_numero` - Dirección (calle y número)
- `colonia` - Colonia
- `codigo_postal` - Código postal
- `municipio` - Municipio
- `estado` - Estado

**Datos de Contacto:**
- `contacto_nombre` - Nombre del contacto principal
- `contacto_puesto` - Puesto del contacto
- `contacto_email` - Email del contacto
- `contacto_movil` - Teléfono móvil del contacto

**Datos del Responsable:**
- `responsable_nombre` - Nombre del responsable de la norma
- `responsable_puesto` - Puesto del responsable
- `responsable_email` - Email del responsable
- `responsable_movil` - Teléfono del responsable

**Datos Estadísticos:**
- `total_trabajadores` - Total de trabajadores en la organización
- `total_hombres` - Total de trabajadores hombres
- `total_mujeres` - Total de trabajadores mujeres
- `muestra_aplicada` - Número de trabajadores evaluados
- `muestra_hombres` - Hombres evaluados
- `muestra_mujeres` - Mujeres evaluadas
- `comite_integrantes` - Integrantes del comité de atención
- `comite_hombres` - Hombres en el comité
- `comite_mujeres` - Mujeres en el comité

**Datos Operacionales:**
- `fecha_aplicacion` - Fecha de aplicación de las evaluaciones
- `justificacion_muestra` - Justificación de la muestra seleccionada

Todos los campos son `nullable` para no afectar datos existentes.

## Backend

### Modelo Actualizado
**`app/Models/Organization.php`**
- Añadidos todos los nuevos campos al array `$fillable`
- Configurados casts para campos de fecha y numéricos en el método `casts()`

### Nuevo Servicio
**`app/Services/ReportPdfService.php`**

Métodos principales:
- `getDemographicDistributionData(string $organizationId): array` - Obtiene datos demográficos agrupados por categoría y nivel de riesgo
- `getDiagnosticResultsData(string $organizationId): array` - Calcula distribución de riesgos por categorías, dominios y dimensiones
- `processDemographicField()` - Procesa campos demográficos individuales
- `formatDemographicsForPdf()` - Formatea datos para visualización en PDF
- `calculateFinalRiskDistribution()` - Calcula distribución de riesgo final
- `calculateCategoryDistribution()` - Calcula distribución por categorías
- `calculateDomainDistribution()` - Calcula distribución por dominios
- `calculateDimensionDistribution()` - Calcula distribución por dimensiones

### Nuevo Controlador
**`app/Http/Controllers/ReportPdfController.php`**

Métodos implementados:
- `downloadDemographicReport()` - Genera y descarga informe demográfico
- `downloadDiagnosticReport()` - Genera y descarga informe de diagnóstico
- `downloadExecutiveReport()` - Placeholder para informe ejecutivo

Todos los métodos incluyen:
- Validación de permisos (solo admin y super-admin)
- Manejo de errores con logging
- Generación de PDFs con DomPDF
- Nombres de archivo descriptivos con fecha

### Rutas Añadidas
**`routes/web.php`**

Dentro del grupo middleware `['role:admin|super-admin']`:
```php
Route::prefix('reportes/pdf')->name('reports.pdf.')->group(function () {
    Route::get('/demografico/{organization}', [ReportPdfController::class, 'downloadDemographicReport'])
        ->name('demographic');
    Route::get('/diagnostico/{organization}', [ReportPdfController::class, 'downloadDiagnosticReport'])
        ->name('diagnostic');
    Route::get('/ejecutivo/{organization}', [ReportPdfController::class, 'downloadExecutiveReport'])
        ->name('executive');
});
```

## Frontend

### Componente Actualizado
**`resources/js/Components/ReportSummaryDashboard.vue`**

Cambios realizados:
1. Añadida función `downloadPdfReport()` para manejar descargas
2. Agregada sección de botones de descarga en el template
3. Botones solo visibles para administradores con organización seleccionada
4. Tres botones con estilos diferenciados:
   - Verde: Informe Demográfico
   - Azul: Informe de Diagnóstico
   - Púrpura (deshabilitado): Informe Ejecutivo

## Vistas PDF

### Vista de Informe Demográfico
**`resources/views/pdfs/demographic-report.blade.php`**

Características:
- Encabezado con información de la organización
- Datos del centro de trabajo (si disponibles)
- Tablas por cada categoría demográfica:
  - Género, Edad, Estado Civil
  - Nivel de Estudios, Ocupación
  - Tipo de Puesto, Contratación, Personal
  - Tipo de Jornada, Rotación de Turnos
  - Experiencia Actual y Laboral
- Columnas de niveles de riesgo con códigos de color
- Columnas calculadas (Nu+Ba, Me+Al+MA)
- Footer con información confidencial

### Vista de Informe de Diagnóstico
**`resources/views/pdfs/diagnostic-report.blade.php`**

Características:
- Datos completos del centro de trabajo
- Tabla de colaboradores (total, hombres, mujeres)
- Sección de objetivos según NOM-035
- Calificación final con distribución de riesgos
- Cuantificación por categoría
- Cuantificación por dominio
- Cuantificación por dimensión
- Tablas con niveles de riesgo coloreados
- Diseño multi-página con saltos de página
- Footer informativo

## Estilos CSS para PDFs

Ambas vistas incluyen:
- Diseño responsivo adaptado para impresión
- Esquema de colores consistente con la aplicación
- Códigos de color para niveles de riesgo:
  - Nulo: #00CED1 (Turquesa)
  - Bajo: #28A745 (Verde)
  - Medio: #FFFF00 (Amarillo)
  - Alto: #FFA500 (Naranja)
  - Muy Alto: #FF0000 (Rojo)
- Tablas con bordes y alternancia de colores
- Encabezados y footers profesionales

## Seguridad

- Todas las rutas protegidas con middleware `role:admin|super-admin`
- Validación de permisos en cada método del controlador
- Verificación de existencia de organización con `findOrFail()`
- Manejo de errores con mensajes apropiados
- Logging de errores para debugging

## Librerías Utilizadas

- **barryvdh/laravel-dompdf** (v3.1) - Ya instalado en el proyecto
- Genera PDFs desde HTML/Blade templates
- Soporte para estilos CSS
- Configuración de tamaño de papel y orientación

## Campos Demográficos Procesados

El servicio procesa los siguientes campos del modelo Evaluation:
- genero
- edad
- estado_civil
- nivel_estudios
- ocupacion
- tipo_puesto
- tipo_contratacion
- tipo_personal
- tipo_jornada
- rotacion_turnos
- experiencia_actual
- experiencia_laboral

## Próximos Pasos Sugeridos

1. **Informe Ejecutivo**: Implementar contenido del informe ejecutivo
2. **Gráficas en PDF**: Añadir gráficas visuales (usando librerías como Chart.js + Image export)
3. **Personalización**: Permitir a organizaciones personalizar algunos campos
4. **Plantillas**: Crear plantillas personalizables para reportes
5. **Programación**: Permitir generación automática y envío por email
6. **Histórico**: Guardar versiones de reportes generados

## Testing Pendiente

- [ ] Probar generación con organización sin datos
- [ ] Probar generación con organización con datos completos
- [ ] Verificar permisos (intentar acceso sin rol admin)
- [ ] Probar con diferentes navegadores
- [ ] Validar formato y contenido de PDFs generados
- [ ] Verificar que los nombres de archivo sean correctos
- [ ] Probar descarga de múltiples reportes seguidos

## Notas Técnicas

- Los PDFs se generan on-the-fly, no se guardan en servidor
- El tamaño de papel está configurado como 'letter' en orientación 'portrait'
- Los datos se obtienen directamente del modelo Evaluation->data (campo JSON)
- Se utiliza el mismo servicio de cálculo que el dashboard para consistencia
- Los campos de Organization son opcionales para no romper funcionalidad existente

## Comandos Ejecutados

```bash
# Crear branch
git checkout -b feature/pdf-reports

# Crear migración
php artisan make:migration add_report_fields_to_organizations_table --no-interaction

# Crear controlador
php artisan make:controller ReportPdfController --no-interaction

# Crear servicio
php artisan make:class Services/ReportPdfService --no-interaction

# Ejecutar migración
php artisan migrate

# Formatear código
vendor/bin/pint --dirty

# Compilar assets
npm run build
```

## Archivos Creados/Modificados

**Creados:**
- `database/migrations/2025_10_11_031343_add_report_fields_to_organizations_table.php`
- `app/Http/Controllers/ReportPdfController.php`
- `app/Services/ReportPdfService.php`
- `resources/views/pdfs/demographic-report.blade.php`
- `resources/views/pdfs/diagnostic-report.blade.php`
- `REPORT_PDF_IMPLEMENTATION.md`
- `PDF_REPORTS_SUMMARY.md`

**Modificados:**
- `app/Models/Organization.php`
- `routes/web.php`
- `resources/js/Components/ReportSummaryDashboard.vue`

## Conclusión

La implementación está completa y lista para testing. Los reportes PDF proporcionan una forma profesional de documentar y compartir los resultados de las evaluaciones de riesgo psicosocial conforme a la NOM-035-STPS-2018.

Los administradores pueden ahora generar reportes demográficos y de diagnóstico directamente desde el dashboard de reportes, facilitando el cumplimiento normativo y la toma de decisiones.
