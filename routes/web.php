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

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
    Route::post('/perfil', [UserController::class, 'updateProfile']);

    // Rutas accesibles para usuarios de organización y administradores
    Route::get('/organizacion/{organization}/resultados', [ResultsController::class, 'listResults'])
        ->name('organization.results.list')
        ->middleware('can:view-organization-results,organization');

    Route::get('/organizacion/{organization}/resultados/{personalFolio}', [ResultsController::class, 'showDetailedResults'])
        ->name('organization.results.detail')
        ->middleware('can:view-organization-results,organization');

    Route::get('/{organizationId}/respuestas/{personalId}', [GlobalResponseController::class, 'showPersonResponses'])
        ->name('responses.personal');

    // Rutas para Admin y Super Admin
    Route::middleware(['role:admin|super-admin'])->group(function () {

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
        });

        // Word Report Routes
        Route::prefix('reportes/word')->name('reports.word.')->group(function () {
            Route::get('/demografico/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadDemographicReportWord'])
                ->name('demographic');
            Route::get('/diagnostico/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadDiagnosticReportWord'])
                ->name('diagnostic');
            Route::get('/ejecutivo/{organization}', [\App\Http\Controllers\ReportPdfController::class, 'downloadExecutiveReportWord'])
                ->name('executive');
            Route::get('/status/{report}', [\App\Http\Controllers\ReportPdfController::class, 'checkReportStatus'])
                ->name('status');
            Route::get('/download/{report}', [\App\Http\Controllers\ReportPdfController::class, 'downloadCompletedReport'])
                ->name('download');
        });

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
        });

        // Rutas para puestos de ocupación
        Route::post('/occupation-positions', [\App\Http\Controllers\OccupationPositionController::class, 'store'])
            ->name('occupation-positions.store');
        Route::delete('/occupation-positions/{occupationPosition}', [\App\Http\Controllers\OccupationPositionController::class, 'destroy'])
            ->name('occupation-positions.destroy');

        // Rutas para áreas de departamento
        Route::post('/department-areas', [\App\Http\Controllers\DepartmentAreaController::class, 'store'])
            ->name('department-areas.store');
        Route::delete('/department-areas/{departmentArea}', [\App\Http\Controllers\DepartmentAreaController::class, 'destroy'])
            ->name('department-areas.destroy');

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

// Rutas públicas para las plantillas OMR de evaluación presencial
Route::prefix('omr')->name('omr.')->group(function () {
    Route::get('/referencia-i', [OMRController::class, 'referenciaI'])->name('referencia-i');
    Route::get('/referencia-iii', [OMRController::class, 'referenciaIII'])->name('referencia-iii');
    Route::get('/referencia-v', [OMRController::class, 'referenciaV'])->name('referencia-v');
    Route::get('/escala-cisneros', [OMRController::class, 'escalaCisneros'])->name('escala-cisneros');

    // POST route for PDF generation (authenticated)
    Route::post('/generate-pdf', [OMRController::class, 'generatePdf'])
        ->middleware('auth')
        ->name('generate-pdf');
});
