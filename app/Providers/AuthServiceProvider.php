<?php

namespace App\Providers;

use App\Helpers\Utilidades;
use App\Models\Usuario;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('check-authorization', function ($usuario, $slug) {
            //  dd($slug->toArray());
            return Utilidades::havePermision($usuario, $slug);
        });

        Gate::define('check-rol-admin', function (Usuario $usuario, $rol) {
            return Utilidades::checkRolAdmin($usuario, $rol);
        });
    }
}
