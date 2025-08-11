# Dashboard PDF Reports Documentation

## Overview

The Dashboard PDF Reports feature allows users to generate and download comprehensive PDF reports based on the dashboard charts and data. This functionality provides different types of reports for various stakeholders.

## Features

### Available Reports

1. **Category Report** (`/dashboard/pdf/category-report`)
   - Category qualifications by risk level
   - Distribution tables and visual charts
   - Statistical summary

2. **Domain Report** (`/dashboard/pdf/domain-report`)
   - Domain qualifications by risk level
   - Risk distribution analysis
   - Statistical summary

3. **Dimension Report** (`/dashboard/pdf/dimension-report`)
   - Dimension risk level distribution
   - Detailed analysis by dimension
   - Statistical overview

4. **Demographic Report** (`/dashboard/pdf/demographic-report`)
   - Demographic distribution analysis
   - Consolidated demographic summary
   - Analysis by demographic field

5. **Complete Dashboard Report** (`/dashboard/pdf/complete-report`)
   - Executive summary
   - All categories, domains, and demographic data
   - Risk analysis and recommendations
   - Comprehensive statistical overview

## Access Control

### User Roles
- **Organization Users**: Can access all reports for their organization
- **Admin Users**: Can access all reports with global data
- **Super Admin Users**: Can access all reports with global data

### Authentication Required
All PDF endpoints require user authentication and appropriate role permissions.

## How to Use

### From the Dashboard UI

1. Navigate to the Dashboard
2. Look for the red "Descargar PDF" (Download PDF) button in the top-right corner
3. Click the button to open the dropdown menu
4. Select the desired report type
5. The PDF will be generated and downloaded automatically

### Direct URL Access

You can also access reports directly via URLs:
- Category Report: `/dashboard/pdf/category-report`
- Domain Report: `/dashboard/pdf/domain-report`
- Dimension Report: `/dashboard/pdf/dimension-report`
- Demographic Report: `/dashboard/pdf/demographic-report`
- Complete Report: `/dashboard/pdf/complete-report`

## PDF Features

### Professional Layout
- Clean, professional design
- Consistent branding and styling
- Page headers and footers
- Proper page breaks

### Data Visualization
- Statistical tables with risk level breakdowns
- Visual progress bars for risk distributions
- Summary cards with key metrics
- Color-coded risk levels

### Content Sections
- **Header**: Report title, organization name, generation date
- **Data Tables**: Detailed breakdowns by category/domain/dimension
- **Visual Charts**: Progress bars and risk level indicators
- **Statistics**: Summary cards with key metrics
- **Risk Analysis**: Recommendations and insights (in complete report)

## Technical Implementation

### Backend Components
- `DashboardPdfController`: Handles PDF generation requests
- `ReportService`: Provides data for reports
- Blade templates in `resources/views/reports/pdf/`
- Routes defined in `routes/web.php`

### Frontend Components
- PDF download buttons in `ReportSummaryDashboard.vue`
- PDF menu in `AdminDashboard.vue`
- Dropdown menus with report options

### Dependencies
- `barryvdh/laravel-dompdf`: PDF generation library
- Laravel Blade templating engine
- Existing report services

## Error Handling

The system includes comprehensive error handling:
- Authentication checks
- Authorization validation
- Service error logging
- Graceful fallbacks for missing data

## Testing

Feature tests are available in `tests/Feature/DashboardPdfTest.php` covering:
- Authentication requirements
- Authorization checks
- PDF generation for all report types
- Route accessibility

## File Structure

```
app/
├── Http/Controllers/
│   └── DashboardPdfController.php
resources/
├── views/reports/pdf/
│   ├── base.blade.php
│   ├── category-report.blade.php
│   ├── domain-report.blade.php
│   ├── dimension-report.blade.php
│   ├── demographic-report.blade.php
│   └── complete-dashboard-report.blade.php
└── js/Components/
    ├── ReportSummaryDashboard.vue
    └── AdminDashboard.vue
tests/
└── Feature/
    └── DashboardPdfTest.php
```

## Future Enhancements

Potential improvements for future versions:
- Custom date ranges for reports
- Organization-specific filtering for admins
- Email delivery of reports
- Scheduled report generation
- Additional chart types and visualizations
- Export to other formats (Excel, CSV)