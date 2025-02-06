<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return \Inertia\Inertia::render('Welcome');
});


Route::controller(\App\Http\Controllers\AuthController::class)->group(function() {
   Route::get('/login', 'showLogin')->name('login');
   Route::post('/login', 'login')->name('loginPost');

   Route::post('/logout', 'logout')->name('logout');
});


Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil
    Route::get('/perfil', [UserController::class, 'showProfile'])->name('profile');
    Route::post('/perfil', [UserController::class, 'updateProfile']);

    // Rutas para Company
    Route::get('/reportes', [DashboardController::class, 'companyReports'])->name('company.reports');

    // Rutas para Admin y Super Admin
    Route::middleware(['role:admin|super-admin'])->group(function () {

        //Route::get('/evaluaciones', [DashboardController::class, 'evaluations'])->name('evaluations.index');
        Route::post('/evaluaciones/upload-files', [DashboardController::class, 'uploadFiles'])->name('evaluations.uploadFiles');
        //Route::get('/evaluaciones/resultados', [DashboardController::class, 'evaluationResults'])->name('evaluations.results');

        Route::controller(EvaluationController::class)
            ->prefix('/evaluaciones')
            ->group(function() {
                Route::get('/', 'index')->name('evaluations.index');
                Route::get('/cargar-evaluacion', 'loadEvaluation')->name('evaluations.load');
                Route::post('/store', 'store')->name('evaluations.store');
            });

        Route::controller(\App\Http\Controllers\OrganizationController::class)->prefix('/organizaciones')->group(function() {
            Route::get('/', 'index')->name('organizations.index');
            Route::get('/create', 'create')->name('organizations.create');
            Route::post('/store', 'store')->name('organizations.store');
            Route::get('/{organization}/edit', 'edit')->name('organizations.edit')->withTrashed();
            Route::put('/{organization}', 'update')->name('organizations.update')->withTrashed();
            Route::delete('/{organization}', 'destroy')->name('organizations.destroy');
            Route::put('/{organization}/restore', 'restore')->name('organizations.restore')->withTrashed();
        });

        Route::controller(UserController::class)
            ->prefix('/usuarios')
            ->group(function() {
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

        Route::get('/process-status', function (Request $request) {
            // Supongamos que el estado se guarda en cache o en un modelo.
            // Por ejemplo, podrías retornar algo como:
            $status = cache('process_status', [
                'status' => 'pending',
                'finished' => false,
                'message' => 'Procesando...'
            ]);

            return response()->json($status);
        });
    });

});

