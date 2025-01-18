<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
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

        Route::get('/evaluaciones', [DashboardController::class, 'evaluations'])->name('evaluations.index');
        Route::post('/evaluaciones/upload-files', [DashboardController::class, 'uploadFiles'])->name('evaluations.uploadFiles');
        Route::get('/evaluaciones/resultados', [DashboardController::class, 'evaluationResults'])->name('evaluations.results');


        // Listar usuarios
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');

        // Crear usuario
        Route::get('/usuarios/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');

        // Editar usuario
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');

        // Deshabilitar usuario
        Route::post('/usuarios/{user}/disable', [UserController::class, 'disable'])->name('users.disable');

        // Eliminar usuario
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

