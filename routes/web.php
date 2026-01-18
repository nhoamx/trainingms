<?php

use App\Http\Controllers\CategoryReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemographicReportController;
use App\Http\Controllers\DimensionReportController;
use App\Http\Controllers\DomainReportController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\GlobalResponseController;
use App\Http\Controllers\OMRController;
use App\Http\Controllers\PeopleListController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\UserDashboardAccess;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Ruta pública para acceso a evaluaciones en línea
Route::get('/evaluacion', function () {
    return \Inertia\Inertia::render('OnlineEvaluation/Access', [
        'title' => 'Acceso a Evaluación',
    ]);
})->name('online-evaluation.access');

Route::controller(\App\Http\Controllers\AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('loginPost');

    Route::post('/logout', 'logout')->name('logout');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/organization/{id}/report', function ($id) {
        return \Inertia\Inertia::render('Reports/AdminOrganizationReport', [
            'organizationId' => $id,
            'title' => 'Reporte de Organización',
        ]);
    })->name('organization.report');

    // Likert-only reports route
    Route::get('/organization/{organization}/likert/report', [ResultsController::class, 'showLikertReport'])
        ->name('organization.likert.report');

    // Organization dashboard route (only for organization users)
    Route::get('/organizacion/{organization}/dashboard', [\App\Http\Controllers\OrganizationDashboardController::class, 'show'])
        ->name('organization.dashboard');

    // Specific evaluation type dashboard routes
    Route::get('/organizacion/{organization}/dashboard/clima-laboral', [\App\Http\Controllers\OrganizationDashboardController::class, 'showLikertDashboard'])
        ->name('organization.dashboard.clima-laboral');

    Route::get('/organizacion/{organization}/dashboard/nom-035', [\App\Http\Controllers\OrganizationDashboardController::class, 'showCalizaDashboard'])
        ->name('organization.dashboard.nom-035');

    Route::get('/organizacion/{organization}/dashboard/nom-002', [\App\Http\Controllers\OrganizationDashboardController::class, 'showNom002Dashboard'])
        ->name('organization.dashboard.nom-002');

    // Online results routes
    Route::get('/organization/{id}/online-results', [App\Http\Controllers\OnlineResultsController::class, 'index'])->name('organization.online-results');
    Route::get('/organization/{id}/online-results/report', [App\Http\Controllers\OnlineResultsController::class, 'report'])->name('organization.online-results.report');
    Route::get('/organization/{organizationId}/online-results/{id}', [App\Http\Controllers\OnlineResultsController::class, 'show'])->name('organization.online-results.show');

    // Paper evaluation editing routes
    Route::prefix('paper-evaluations')->middleware(['auth'])->name('paper-evaluations.')->group(function () {
        Route::patch('/{paperEvaluation}', [App\Http\Controllers\PaperEvaluationController::class, 'update'])->name('update');
        Route::patch('/{paperEvaluation}/name', [App\Http\Controllers\PaperEvaluationController::class, 'updateName'])->name('update-name');
        Route::patch('/{paperEvaluation}/folio', [App\Http\Controllers\PaperEvaluationController::class, 'updateFolio'])->name('update-folio');
        Route::patch('/{paperEvaluation}/demographic-data', [App\Http\Controllers\PaperEvaluationController::class, 'updateDemographicData'])->name('update-demographic-data');
        Route::post('/{paperEvaluation}/check-folio', [App\Http\Controllers\PaperEvaluationController::class, 'checkFolioAvailability'])->name('check-folio');
    });

    Route::post('/folio-batches', [App\Http\Controllers\FolioBatchController::class, 'store'])->name('folio-batches.store');
    Route::get('/folio-batches/{batchId}/folios', [App\Http\Controllers\FolioBatchController::class, 'getFolios'])->name('folio-batches.folios');
    Route::delete('/folio-batches/{batchId}', [App\Http\Controllers\FolioBatchController::class, 'destroy'])->name('folio-batches.destroy');

    // Rutas para evaluaciones en línea
    Route::get('/evaluacion-online/{folio}', [App\Http\Controllers\OnlineEvaluationController::class, 'show'])->name('online-evaluation.show');
    Route::post('/evaluacion-online', [App\Http\Controllers\OnlineEvaluationController::class, 'store'])->name('online-evaluation.store');
    Route::get('/evaluacion-online/resultado/{evaluation}', [App\Http\Controllers\OnlineEvaluationController::class, 'result'])->name('online-evaluation.result');

    Route::get('/reports/dimension-report-summary', [\App\Http\Controllers\DimensionItemSummaryController::class, 'byOrganization'])
        ->name('reports.dimension.summary');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(UserDashboardAccess::class)
        ->name('dashboard');

    // API route for raw answer distribution by category
    Route::get('/dashboard/report/category-answer-distribution/{categoryId}', [DashboardController::class, 'getCategoryAnswerDistribution'])
        ->name('dashboard.report.categoryAnswerDistribution');

    Route::get('/dashboard/report/domain-answer-distribution/{domainId}', [DashboardController::class, 'getDomainAnswerDistribution'])
        ->name('dashboard.report.domainAnswerDistribution');

    // API routes for detail charts
    Route::get('/dashboard/report/dimension-answer-distribution/{dimensionId}', [DashboardController::class, 'getDimensionAnswerDistribution'])
        ->name('dashboard.report.dimensionAnswerDistribution');

    // API route for dimension qualifications (loaded when domain is selected)
    Route::get('/dashboard/report/dimension-qualifications/{domainId}', [DashboardController::class, 'getDimensionQualifications'])
        ->name('dashboard.report.dimensionQualifications');

    // Nuevas rutas para los reportes de categoría
    Route::get('/reports/category-distribution', [CategoryReportController::class, 'getCategoryAnswerTypeDistribution'])
        ->name('reports.category.distribution');
    // Rutas para reportes globales de respuestas
    Route::get('/reports/global-response-counts', [GlobalResponseController::class, 'getGlobalResponseCounts'])
        ->name('reports.global.responseCounts');
    Route::get('/reports/category-response-counts', [GlobalResponseController::class, 'getCategoryResponseCounts'])
        ->name('reports.global.categoryResponseCounts');
    Route::get('/reports/global', [GlobalResponseController::class, 'showGlobalResponseReport'])
        ->name('reports.global.show');

    // Rutas para reportes globales de personas (conteo de personas únicas)
    Route::get('/reports/global-person-counts', [GlobalResponseController::class, 'getGlobalPersonCounts'])
        ->name('reports.global.personCounts');
    Route::get('/reports/category-person-counts', [GlobalResponseController::class, 'getPersonCountByCategoryAndResponse'])
        ->name('reports.global.categoryPersonCounts');
    Route::get('/reports/global-person', [GlobalResponseController::class, 'showGlobalPersonReport'])
        ->name('reports.global.person.show');

    // Rutas para reportes por categoría
    Route::get('/reports/category', [CategoryReportController::class, 'showCategoryReport'])
        ->name('reports.category.show');

    // Rutas para reporte de suma total por categoría
    Route::get('/reports/category-total-scores', [CategoryReportController::class, 'getCategoryTotalScores'])
        ->name('reports.category.totalScores');
    Route::get('/reports/category-total-scores-report', [CategoryReportController::class, 'showCategoryTotalScoreReport'])
        ->name('reports.category.totalScores.show');

    // Rutas para reportes de dominio
    Route::get('/reports/domain-distribution', [DomainReportController::class, 'getDomainAnswerTypeDistribution'])
        ->name('reports.domain.distribution');
    Route::get('/reports/domain', [DomainReportController::class, 'showDomainReport'])
        ->name('reports.domain.show');

    // Rutas para reporte de suma total por dominio
    Route::get('/reports/domain-total-scores', [DomainReportController::class, 'getDomainTotalScores'])
        ->name('reports.domain.totalScores');
    Route::get('/reports/domain-total-scores-report', [DomainReportController::class, 'showDomainTotalScoreReport'])
        ->name('reports.domain.totalScores.show');

    // Rutas para reportes de dimensión
    // NUEVO: Distribución por nivel de riesgo y dimensión
    Route::get('/reports/dimension-risk-distribution', [DimensionReportController::class, 'getDimensionRiskLevelDistribution'])
        ->name('reports.dimension.riskDistribution');
    Route::get('/reports/dimension', [DimensionReportController::class, 'showDimensionReport'])
        ->name('reports.dimension.show');

    // Rutas para reporte de suma total por dimensión
    Route::get('/reports/dimension-total-scores', [DimensionReportController::class, 'getDimensionTotalScores'])
        ->name('reports.dimension.totalScores');
    Route::get('/reports/dimension-total-scores-report', [DimensionReportController::class, 'showDimensionTotalScoreReport'])
        ->name('reports.dimension.totalScores.show');

    // Web Route for Category People List Page
    Route::get('/reports/people-list/{categoryId}/{answerKey}', [PeopleListController::class, 'show'])
        ->name('reports.peopleList');

    // NEW Web Route for Domain People List Page
    Route::get('/reports/people-list-domain/{domainId}/{answerKey}', [PeopleListController::class, 'showDomainList'])
        ->name('reports.peopleListDomain');

    // NEW Web Route for Dimension People List Page
    Route::get('/reports/people-list-dimension/{dimensionId}/{answerKey}', [PeopleListController::class, 'showDimensionList'])
        ->name('reports.peopleListDimension');

    // NEW Web Route for Demographic People List Page
    Route::get('/reports/people-list-demographic/{fieldKey}/{identifier}', [PeopleListController::class, 'showDemographicList'])
        ->name('reports.peopleListDemographic');

    // Rutas para reportes demográficos
    Route::get('/reports/demographic-distribution', [DemographicReportController::class, 'getDemographicDistribution'])
        ->name('reports.demographic.distribution');
    Route::get('/reports/demographic-report', [DemographicReportController::class, 'showDemographicReport'])
        ->name('reports.demographic.show');

    // API Route to get organization list for dropdowns
    Route::get('/api/organizations-list', [\App\Http\Controllers\OrganizationController::class, 'listForDropdown'])
        ->name('api.organizations.list');

    // Perfil
    Route::get('/perfil', [UserController::class, 'showProfile'])->name('profile');
    Route::post('/perfil', [UserController::class, 'updateProfile'])->name('profile.update');

    // Company Data routes (for organization users)
    Route::get('/organizacion/{organization}/datos-empresa', [\App\Http\Controllers\CompanyDataController::class, 'edit'])
        ->name('company-data.edit')
        ->middleware('can:view-organization-results,organization');

    Route::post('/organizacion/{organization}/datos-empresa', [\App\Http\Controllers\CompanyDataController::class, 'update'])
        ->name('company-data.update')
        ->middleware('can:view-organization-results,organization');

    // Policy document routes
    Route::post('/organizacion/{organization}/politica/borrador', [\App\Http\Controllers\CompanyDataController::class, 'uploadPolicyDraft'])
        ->name('company-data.policy.upload-draft')
        ->middleware('can:view-organization-results,organization');

    Route::post('/organizacion/{organization}/politica/aprobada', [\App\Http\Controllers\CompanyDataController::class, 'uploadPolicyApproved'])
        ->name('company-data.policy.upload-approved')
        ->middleware(['can:view-organization-results,organization', 'role:admin|super-admin']);

    Route::get('/organizacion/{organization}/politica/borrador/descargar', [\App\Http\Controllers\CompanyDataController::class, 'downloadPolicyDraft'])
        ->name('company-data.policy.download-draft')
        ->middleware('can:view-organization-results,organization');

    Route::get('/organizacion/{organization}/politica/aprobada/descargar', [\App\Http\Controllers\CompanyDataController::class, 'downloadPolicyApproved'])
        ->name('company-data.policy.download-approved')
        ->middleware('can:view-organization-results,organization');

    // Committee Member routes
    Route::post('/organizacion/{organization}/comite/miembros', [\App\Http\Controllers\CommitteeMemberController::class, 'store'])
        ->name('committee-members.store')
        ->middleware('can:view-organization-results,organization');

    Route::delete('/organizacion/{organization}/comite/miembros/{committeeMember}', [\App\Http\Controllers\CommitteeMemberController::class, 'destroy'])
        ->name('committee-members.destroy')
        ->middleware('can:view-organization-results,organization');

    // Rutas accesibles para usuarios de organización y administradores
    Route::get('/organizacion/{organization}/resultados', [ResultsController::class, 'listResults'])
        ->name('organization.results.list')
        ->middleware('can:view-organization-results,organization');

    Route::get('/organizacion/{organization}/resultados/descargar-folios-faltantes', [ResultsController::class, 'downloadGapFolios'])
        ->name('organization.results.download-gap-folios')
        ->middleware('can:view-organization-results,organization');

    Route::get('/organizacion/{organization}/resultados/{personalFolio}', [ResultsController::class, 'showDetailedResults'])
        ->name('organization.results.detail')
        ->middleware('can:view-organization-results,organization');

    Route::get('/organizacion/{organization}/resultados/{personalFolio}/likert', [ResultsController::class, 'showLikertDetails'])
        ->name('organization.results.likert')
        ->middleware('can:view-organization-results,organization');

    Route::post('/organizacion/{organization}/resultados/{personalFolio}/likert/update', [ResultsController::class, 'updateLikertDemographicData'])
        ->name('organization.results.likert.update')
        ->middleware('can:view-organization-results,organization');

    // Update Likert answers (edit responses UI)
    Route::post('/organizacion/{organization}/resultados/{personalFolio}/likert/answers', [ResultsController::class, 'updateLikertAnswers'])
        ->name('organization.results.likert.update-answers')
        ->middleware('can:view-organization-results,organization');

    // Export Likert evaluations by clima laboral level
    Route::post('/organizacion/{organization}/likert/export-by-level', [ResultsController::class, 'exportLikertByClimaLevel'])
        ->name('organization.likert.export-by-level')
        ->middleware('can:view-organization-results,organization');

    // Multi-sheet climate export routes (admin only)
    Route::get('/organizacion/{organization}/clima/export-options', [ResultsController::class, 'getClimaExportOptions'])
        ->name('organization.clima.export-options')
        ->middleware(['can:view-organization-results,organization', 'role:admin|super-admin']);

    Route::post('/organizacion/{organization}/clima/export-multi', [ResultsController::class, 'exportClimaMultiSheet'])
        ->name('organization.clima.export-multi')
        ->middleware(['can:view-organization-results,organization', 'role:admin|super-admin']);

    // Bulk update routes
    Route::get('/organizacion/{organization}/plantilla-actualizacion', [ResultsController::class, 'downloadTemplate'])
        ->name('organization.results.template')
        ->middleware('can:view-organization-results,organization');

    Route::post('/organizacion/{organization}/actualizacion-masiva', [ResultsController::class, 'bulkUpdate'])
        ->name('organization.results.bulk-update')
        ->middleware('can:view-organization-results,organization');

    // Bulk comments import routes
    Route::get('/organizacion/{organization}/plantilla-comentarios', [ResultsController::class, 'bulkCommentsTemplate'])
        ->name('organization.results.comments-template')
        ->middleware('can:view-organization-results,organization');

    Route::post('/organizacion/{organization}/comentarios-masivos', [ResultsController::class, 'bulkCommentsUpdate'])
        ->name('organization.results.bulk-comments')
        ->middleware('can:view-organization-results,organization');

    Route::get('/bulk-import/{bulkImportJob}/status', [ResultsController::class, 'bulkImportStatus'])
        ->name('bulk-import.status');

    Route::get('/{organizationId}/respuestas/{personalId}', [GlobalResponseController::class, 'showPersonResponses'])
        ->name('responses.personal');

    // Rutas para Admin y Super Admin
    Route::middleware(['role:admin|super-admin|organization'])->group(function () {

        // Delete (cancel) paper evaluation
        Route::delete('/organizacion/{organization}/evaluacion/{evaluation}', [ResultsController::class, 'destroyEvaluation'])
            ->name('organization.evaluation.destroy');

        Route::post('/evaluaciones/upload-files', [DashboardController::class, 'uploadFiles'])->name('evaluations.uploadFiles');

        Route::get('/reporte', [DashboardController::class, 'reportByOrganization'])
            ->name('admin.report');

        // PDF Report Routes
        Route::prefix('reportes/pdf')->name('reports.pdf.')->group(function () {
            Route::get('/demografico/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadDemographicReport'])
                ->name('demographic');
            Route::get('/diagnostico/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadDiagnosticReport'])
                ->name('diagnostic');
            Route::get('/ejecutivo/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadExecutiveReport'])
                ->name('executive');
            Route::get('/nom002/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadNom002Report'])
                ->name('nom002');
        });

        // Word Report Routes
        Route::prefix('reportes/word')->name('reports.word.')->group(function () {
            Route::get('/demografico/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadDemographicReportWord'])
                ->name('demographic');
            Route::get('/diagnostico/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadDiagnosticReportWord'])
                ->name('diagnostic');
            Route::get('/ejecutivo/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadExecutiveReportWord'])
                ->name('executive');
            Route::get('/likert/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadLikertReportWord'])
                ->name('likert');
            Route::get('/status/{report}', [\App\Http\Controllers\ReportPdfController::class, 'checkReportStatus'])
                ->name('status');
            Route::get('/download/{report}', [\App\Http\Controllers\ReportPdfController::class, 'downloadCompletedReport'])
                ->name('download');
        });

        // Excel Report Route
        Route::get('reportes/excel/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadExcelReport'])
            ->name('reports.excel.download');

        Route::controller(EvaluationController::class)
            ->prefix('/evaluaciones')
            ->group(function () {
                Route::get('/', 'index')->name('evaluations.index');
                Route::get('/cargar-evaluacion', 'loadEvaluation')->name('evaluations.load');
                Route::post('/store', 'store')->name('evaluations.store');
                Route::get('/{evaluation}', 'show')->name('evaluations.show');
                Route::delete('/{evaluation}', 'destroy')->name('evaluations.destroy');
            });

        // Nueva ruta dedicada para evaluaciones por organización
        Route::get('/organizaciones/{organization}/evaluaciones', [EvaluationController::class, 'organizationEvaluations'])
            ->name('organizations.evaluations');

        // Nueva ruta POST para reasignar evaluaciones
        Route::post('/organizaciones/{organization}/evaluations/reassign', [EvaluationController::class, 'reassignEvaluations'])
            ->name('organizations.evaluations.reassign');

        Route::controller(\App\Http\Controllers\OrganizationController::class)->prefix('/organizaciones')->group(function () {
            Route::get('/', 'index')->name('organizations.index');
            Route::get('/create', 'create')->name('organizations.create');
            Route::post('/store', 'store')->name('organizations.store');
            Route::get('/{organization}/edit', 'edit')->name('organizations.edit')->withTrashed();
            Route::put('/{organization}', 'update')->name('organizations.update')->withTrashed();
            Route::delete('/{organization}', 'destroy')->name('organizations.destroy');
            Route::delete('/{organization}/delete', 'forceDelete')->name('organizations.force-delete')->withTrashed();
            Route::put('/{organization}/restore', 'restore')->name('organizations.restore')->withTrashed();
            Route::get('/{organization}/export-likert-answers', 'exportLikertAnswers')->name('organizations.export-likert-answers');
        });

        // Rutas para extintores (NOM-002)
        Route::prefix('/organizaciones/{organization}/extintores')->name('organizations.assets.')->group(function () {
            Route::get('/', [\App\Http\Controllers\AssetController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\AssetController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\AssetController::class, 'store'])->name('store');
            Route::get('/{asset}/edit', [\App\Http\Controllers\AssetController::class, 'edit'])->name('edit');
            Route::put('/{asset}', [\App\Http\Controllers\AssetController::class, 'update'])->name('update');
            Route::delete('/{asset}', [\App\Http\Controllers\AssetController::class, 'destroy'])->name('destroy');
            Route::get('/{asset}/qr', [\App\Http\Controllers\AssetController::class, 'qrCode'])->name('qr');
            Route::get('/{asset}/qr/download', [\App\Http\Controllers\AssetController::class, 'downloadQr'])->name('qr.download');
            Route::get('/qr/download-all', [\App\Http\Controllers\AssetController::class, 'downloadAllQr'])->name('qr.download-all');
            Route::get('/template', [\App\Http\Controllers\AssetController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [\App\Http\Controllers\AssetController::class, 'importAssets'])->name('import');
        });

        // Rutas para puestos de ocupación
        Route::post('/occupation-positions', [\App\Http\Controllers\OccupationPositionController::class, 'store'])
            ->name('occupation-positions.store');
        Route::delete('/occupation-positions/{occupationPosition}', [\App\Http\Controllers\OccupationPositionController::class, 'destroy'])
            ->name('occupation-positions.destroy');
        Route::get('/occupation-positions/{organization}/template', [\App\Http\Controllers\OccupationPositionController::class, 'downloadTemplate'])
            ->name('occupation-positions.template');
        Route::post('/occupation-positions/{organization}/import', [\App\Http\Controllers\OccupationPositionController::class, 'import'])
            ->name('occupation-positions.import');

        // Rutas para áreas de departamento
        Route::post('/department-areas', [\App\Http\Controllers\DepartmentAreaController::class, 'store'])
            ->name('department-areas.store');
        Route::delete('/department-areas/{departmentArea}', [\App\Http\Controllers\DepartmentAreaController::class, 'destroy'])
            ->name('department-areas.destroy');
        Route::get('/department-areas/{organization}/template', [\App\Http\Controllers\DepartmentAreaController::class, 'downloadTemplate'])
            ->name('department-areas.template');
        Route::post('/department-areas/{organization}/import', [\App\Http\Controllers\DepartmentAreaController::class, 'import'])
            ->name('department-areas.import');

        Route::controller(ResultsController::class)->prefix('/resultados')->group(function () {
            Route::get('/', 'index')->name('results.index');
            Route::get('/{organization}', 'organizationResults')->name('results.organization');
            Route::put('/evaluacion/{evaluation}/guia-v/preguntas/{question}', 'updateGuideVQuestion')->name('results.guide-v.question.update');
            Route::put('/evaluacion/{evaluation}/guia-iii/preguntas/{question}', 'updateGuideIIIQuestion')->name('results.guide-iii.question.update');
        });

        Route::controller(QuizController::class)->prefix('/examenes')->name('quiz.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/{test}/edit', 'edit')->name('edit');
            Route::put('/{test}', 'update')->name('update');
            Route::delete('/{test}', 'destroy')->name('destroy');
        });

        Route::controller(UserController::class)
            ->prefix('/usuarios')
            ->group(function () {
                Route::get('/', 'index')->name('users.index');
                Route::get('/create', 'create')->name('users.create');
                Route::get('/{user}/edit', 'edit')->name('users.edit');
            });

        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');

        // Editar usuario
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');

        // Deshabilitar usuario
        Route::post('/usuarios/{user}/disable', [UserController::class, 'disable'])->name('users.disable');

        // Eliminar usuario
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/api/notifications', function (Request $request) {
            // Supongamos que el estado se guarda en cache o en un modelo.
            // Por ejemplo, podrías retornar algo como:
            $status = cache('process_status', [
                'status' => 'pending',
                'finished' => false,
                'message' => 'Procesando...',
            ]);

            return response()->json($status);
        });
    });

    Route::prefix('quizzes')->group(function () {
        Route::get('/', [QuizController::class, 'index'])->name('quizzes.index');
        Route::post('/', [QuizController::class, 'store'])->name('quizzes.store');
        Route::get('/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
        Route::put('/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
        Route::post('/{quiz}/toggle', [QuizController::class, 'toggle'])->name('quizzes.toggle');
    });

});

// Rutas públicas para acceder al examen temporal (fuera del middleware auth)
Route::get('/q/{tempUrl}', [QuizController::class, 'showTemp'])->name('quiz.temp');
Route::post('/quiz/{quiz}/submit', [QuizController::class, 'submit'])
    ->name('quiz.submit');
// ->middleware(\App\Http\Middleware\RateLimitQuizSubmissions::class);

// Ruta pública para inspección de activos (extintores)
Route::get('/assets/{asset}/inspect', [\App\Http\Controllers\AssetController::class, 'inspect'])->name('assets.inspect');
Route::get('/assets/{asset}/inspections/create', [\App\Http\Controllers\AssetController::class, 'createInspection'])->name('assets.inspections.create');
Route::post('/assets/{asset}/inspections', [\App\Http\Controllers\AssetController::class, 'storeInspection'])->name('assets.inspections.store');

// Rutas públicas para las plantillas OMR de evaluación presencial
Route::prefix('omr')->name('omr.')->group(function () {
    Route::get('/referencia-i', [OMRController::class, 'referenciaI'])->name('referencia-i');
    Route::get('/referencia-iii', [OMRController::class, 'referenciaIII'])->name('referencia-iii');
    Route::get('/referencia-v', [OMRController::class, 'referenciaV'])->name('referencia-v');
    Route::get('/escala-cisneros', [OMRController::class, 'escalaCisneros'])->name('escala-cisneros');
    Route::get('/likert', [OMRController::class, 'likert'])->name('likert');

    // POST route for PDF generation (authenticated)
    Route::post('/generate-pdf', [OMRController::class, 'generatePdf'])
        ->middleware('auth')
        ->name('generate-pdf');
});
