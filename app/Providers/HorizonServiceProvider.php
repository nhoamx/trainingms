<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Configurar notificaciones por correo para producción
        Horizon::routeMailNotificationsTo('alfredo@nhoamx.com');
        
        // Configurar tema oscuro para mejor UX durante monitoreo nocturno
        Horizon::night();
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return in_array(optional($user)->email, [
                'alfredo@nhoamx.com', // Administrador principal del sistema
                // Agregar más correos de administradores aquí según sea necesario
            ]);
        });
    }
}
