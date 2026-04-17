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
        
        // Option 1 — attribute based (default, recommended)
        $router->register(PatientController::class);

        // Option 2 — PHP config file
        $router->registerFromConfig(require 'config/routes.php');

        // Option 3 — YAML (requires composer require symfony/yaml)
        // $router->registerFromYaml('config/routes.yaml');
    }
}