<?php

require_once __DIR__ . '/../vendor/autoload.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
use Antares\Application;
use App\Middleware\AuthMiddleware;
use App\Providers\AppServiceProvider;
use App\Providers\RouteServiceProvider;

Application::create(__DIR__ . '/..')
    ->providers([
        AppServiceProvider::class,    // bindings, singletons
        RouteServiceProvider::class,  // controllers
    ])
    // ->middleware([
    //     AuthMiddleware::class,
    // ])
    ->run();