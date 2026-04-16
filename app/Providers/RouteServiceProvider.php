<?php

namespace App\Providers;

use Antares\ServiceProvider;
use Antares\Container\Container;
use Antares\Router\Router;
use App\Controllers\PatientController;
use App\Controllers\UserController;

final class RouteServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $router = $container->make(Router::class);
        
        // $router->register(PatientController::class);
        // $router->register(UserController::class);
    }
}