<?php

namespace App\Providers;

use Antares\ServiceProvider;
use Antares\Container\Container;

final class AppServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // $container->singleton(
        //     DatabaseConnection::class,
        //     fn() => new DatabaseConnection(
        //         host: $_ENV['DB_HOST'],
        //         port: $_ENV['DB_PORT'],
        //         name: $_ENV['DB_NAME'],
        //     )
        // );

        // $container->bind(
        //     PatientRepositoryInterface::class,
        //     PatientRepository::class,
        // );
    }
}