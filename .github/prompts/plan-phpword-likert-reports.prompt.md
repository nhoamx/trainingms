---
agent: agent
model: Claude Opus 4.5 (Preview) (copilot)
tools: ['edit', 'search', 'laravel-boost/*', 'runCommands', 'fetch']
---


# Plan: PHPWord Native Word Generation for Likert Reports

Implementing native Word document generation using PHPWord for Likert workplace climate reports, including pie charts (as images) and heat map tables (converted to images), eliminating the PDF→Word conversion dependency.

## Steps

1. **Install PHPWord dependency** — Add `phpoffice/phpword` to `composer.json` and run `composer require phpoffice/phpword` to enable native DOCX generation without PDF intermediary

2. **Create chart rendering Blade component** — Build `resources/views/components/likert-pie-chart.blade.php` that renders the Chart.js pie chart using Vue 3 + Inertia.js pattern (similar to existing diagnostic report templates), with clima laboral distribution data and 4 standardized colors (blue-400, green-600, yellow-500, red-600). Use Browsershot to capture the rendered chart as PNG image via `LikertChartImageService`

3. **Create heat map table image generator** — Implement method in `app/Services/LikertChartImageService.php` to render the complete heat map table (folios × questions with color-coded answer values 1-4) as HTML Blade template, then capture as PNG using Browsershot screenshot. Split table into multiple images at 1000px height intervals to maintain quality and prevent rendering issues with large datasets (all 10 dimensions: Entorno Laboral, Seguridad, Compensación, Comunicación, Participación, Reconocimiento, Capacitación, Equilibrio, Avance, Apoyo)

4. **Build Likert report data service method** — Add `getLikertReportWordData(string $organizationId): array` to `app/Services/ReportPdfService.php` that fetches Likert evaluations, uses `LikertScoreService` for calculations, aggregates clima laboral distribution, and prepares demographic filters data structure matching `LikertOrganizationReport.vue` props

5. **Create PHPWord generation job** — Add `'likert'` case to `GenerateWordReport::generateReportHtml()` and implement `generateLikertWordNative(Organization $organization, ReportPdfService $service): string` that uses PHPWord to create DOCX with: organization header, clima laboral summary section with pie chart image, heat map table image, and NOM-035 compliance footer

6. **Update controller routes for Likert Word reports** — Add `downloadLikertReportWord(Request $request, string $organizationId)` method to `ReportPdfController` following existing pattern of creating `ReportGeneration` record with type `'likert'` and dispatching `GenerateWordReport` job

7. **Add frontend download button** — Extend `ReportSummaryDashboard.vue` downloadWordReport function to support `'likert'` report type with route `/reportes/word/likert/{organization}` and implement polling mechanism identical to demographic/diagnostic/executive reports

## Further Considerations

1. **Future migration to PHPWord for all reports** — Current demographic/diagnostic/executive reports use PDF→Word conversion via Docker/Python. After validating Likert PHPWord implementation, consider migrating all report types to native PHPWord generation for consistent editability and to eliminate Docker dependency for Word reports. Not planned for current iteration.

2. **Image quality optimization** — Monitor generated PNG file sizes for charts and heat map tables. If images are too large (>2MB each), consider implementing compression or reducing Browsershot screenshot scale from default 2x to 1.5x while maintaining readability.

3. **Heat map pagination UX** — With table split every 1000px, consider adding page numbers or "continued" labels to split heat map images so users understand the visual continuation when viewing the Word document.


## Notes
- Create a branch to work on this feature
- Ensure all new code is covered by unit and integration tests (Verify the use of Database transaction instead Database refresher)
- Conventional Commits are necessary on every good change
- Run pint for formating
- Use the laravel boost tool to verify the application, make queries and verify the information we are using