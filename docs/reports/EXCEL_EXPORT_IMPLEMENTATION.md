# Excel Report Download Implementation

## Overview
This implementation adds the ability for administrators to download comprehensive paper evaluation data in Excel format. The export includes all evaluation data from the `PaperEvaluation` model for a specific organization.

## Implementation Details

### 1. Excel Export Class
**File:** `app/Exports/PaperEvaluationsExport.php`

The export class implements several interfaces from the `maatwebsite/excel` package:
- `FromCollection` - Defines the data source
- `WithHeadings` - Defines column headers
- `WithMapping` - Maps model data to Excel rows
- `WithStrictNullComparison` - Handles null values properly
- `WithStyles` - Applies styling to the Excel file

#### Data Exported
The Excel file includes the following sections:

**Basic Information:**
- Folio Completo (Complete folio)
- Código de Compañía (Company code)
- Folio Personal (Personal folio)
- Nombre del Evaluado (Evaluee name)
- Tipo de Evaluación (Evaluation type)
- Estado de Procesamiento (Processing status)
- Fecha de Procesamiento (Processing date)

**Demographic Data (from `referencia_v.php` config):**
- Sexo (Gender)
- Edad (Age)
- Estado Civil (Marital status)
- Nivel de Estudios (Education level)
- Ocupación/Puesto (Occupation/Position)
- Departamento/Sección/Área (Department/Section/Area)
- Tipo de Puesto (Position type)
- Tipo de Contratación (Contract type)
- Tipo de Personal (Personnel type)
- Tipo de Jornada (Work shift type)
- Rotación de Turnos (Shift rotation)
- Tiempo en Puesto Actual (Time in current position)
- Tiempo de Experiencia Laboral (Total work experience)

**Referencia III - General Questions:**
- All questions from `config/referencia_iii.php` (general section)
- Each question has its own column: "Ref III - P{number}"

**Referencia III - Optional/Conditional Questions:**
- All questions from `config/referencia_iii.php` (conditional section)
- Each question has its own column: "Ref III Opcional - P{number}"

**CITSATS-S1 (Guide I - PTSD Assessment):**
- All questions from `config/guide_i_questions.php`
- Each question has its own column: "CITSATS-S1 - P{number}"

**Escala Cisneros (Mobbing/Workplace Violence Scale):**
- All questions from `config/escala_cisneros.php`
- Each question has its own column: "Cisneros - P{number}"

#### Features
- **Styled Headers:** Blue background with white text for easy identification
- **Human-Readable Values:** Status and evaluation types are translated to Spanish
- **Data Filtering:** Only exports completed evaluations for the specified organization
- **Ordered Output:** Results are sorted by folio for easier analysis

### 2. Controller Method
**File:** `app/Http/Controllers/ReportPdfController.php`

Added method: `downloadExcelReport(Request $request, string $organizationId)`

**Features:**
- Validates organization exists
- Checks user permissions using Laravel policies
- Generates filename with organization name and timestamp
- Returns Excel file using Laravel Excel's `download()` method
- Proper error handling with logging

### 3. Route Configuration
**File:** `routes/web.php`

Added route:
```php
Route::get('reportes/excel/{organization}', 
    [\App\Http\Controllers\ReportPdfController::class, 'downloadExcelReport'])
    ->name('reports.excel.download');
```

**Security:**
- Route is within the authenticated middleware group
- Uses Laravel policy authorization in the controller

### 4. Frontend Component
**File:** `resources/js/Components/ReportSummaryDashboard.vue`

#### Added Function
`downloadExcelReport()` - Handles the Excel download process:
- Validates organization is selected
- Prevents multiple simultaneous downloads
- Uses fetch API with proper credentials
- Handles blob response for file download
- Extracts filename from Content-Disposition header
- Provides user feedback during download
- Comprehensive error handling

#### UI Elements
Added a new download section after Word reports with:
- Green-themed design to differentiate from Word reports (blue) and PDF reports
- Icon indicating Excel/spreadsheet format
- Loading state with spinner during download
- Disabled state to prevent multiple clicks
- Descriptive text explaining what data is exported
- Only visible to admin and super admin users
- Only visible when an organization is selected

## Usage

### For Administrators
1. Navigate to the organization report dashboard
2. Select an organization (if not already selected)
3. Scroll to the "Descargar Datos en Excel" section
4. Click the "Exportar Evaluaciones Completas" button
5. Wait for the Excel file to be generated and downloaded
6. The file will be named: `evaluaciones_{OrganizationName}_{DateTime}.xlsx`

### File Content
The downloaded Excel file contains:
- One row per completed paper evaluation
- All demographic data in separate columns
- All questionnaire responses in separate columns
- Headers in Spanish matching NOM-035 terminology
- Properly formatted for analysis in Excel or data processing tools

## Technical Notes

### Dependencies
- **maatwebsite/excel**: Already installed via composer
- Package provides Laravel Excel functionality for imports/exports

### Performance Considerations
- Export is synchronous (no queue) for immediate download
- Suitable for typical organization sizes
- For very large datasets (>10,000 evaluations), consider adding queue support

### Data Privacy
- Only exports data for the specified organization
- Requires admin-level permissions
- Uses Laravel's policy system for authorization
- Follows existing security patterns in the application

### NOM-035 Compliance
The export structure follows NOM-035-STPS-2018 requirements:
- All validated questionnaire responses are included
- Demographic data matches regulatory requirements
- Column naming uses official terminology
- Data is structured for analysis per regulation guidelines

## Future Enhancements

Potential improvements could include:
1. Add filtering options (date range, evaluation type, status)
2. Option to export only specific columns
3. Queue support for very large exports
4. Multiple file format options (CSV, ODS)
5. Scheduled automatic exports
6. Data aggregation/summary sheets within the same workbook

## Testing Recommendations

1. **Authorization:** Verify only admins can download
2. **Data Integrity:** Confirm all fields export correctly
3. **Edge Cases:** Test with organizations having zero evaluations
4. **File Format:** Verify Excel file opens correctly in Excel and LibreOffice
5. **Performance:** Test with organizations having 100+ evaluations
6. **Error Handling:** Test with invalid organization IDs

## Files Modified/Created

### Created:
- `app/Exports/PaperEvaluationsExport.php` - Main export class

### Modified:
- `app/Http/Controllers/ReportPdfController.php` - Added downloadExcelReport method
- `routes/web.php` - Added Excel download route
- `resources/js/Components/ReportSummaryDashboard.vue` - Added UI and download logic

## Configuration Files Used

The export references these configuration files for question structures:
- `config/referencia_iii.php` - Workplace psychosocial factors
- `config/referencia_v.php` - Demographic data structures
- `config/guide_i_questions.php` - PTSD assessment questions
- `config/escala_cisneros.php` - Mobbing/harassment scale

All question texts and structures are loaded dynamically from these configs, ensuring consistency across the application.
