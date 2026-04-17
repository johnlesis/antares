<?php

declare(strict_types=1);

namespace Antares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Antares\Container\Container;
use Antares\Hydration\Hydrator;
use Antares\Middleware\Pipeline;
use Antares\OpenApi\Generator;
use Antares\OpenApi\OpenApiController;
use Antares\Router\Router;
use Antares\Validation\Validator;

class Application
{
    private array $providers = [];
    private string $basePath;
    private Container $container;
    private Dispatcher $dispatcher;
    private ErrorHandler $errorHandler;
    private array $middleware = [];

    public static function create(string $basePath): static
    {
        $app = new static();
        $app->basePath = $basePath;
        return $app;
    }

    public function providers(array $providers): static
    {
        $this->providers = $providers;
        return $this;
    }

    public function middleware(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function run(): void
    {
        $this->boot();

        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $creator = new \Nyholm\Psr7Server\ServerRequestCreator($factory, $factory, $factory, $factory);
        $request = $creator->fromGlobals();

        $response = $this->handle($request);
        $this->emit($response);
    }

    public function boot(): void
    {
        $this->container = new Container();
        $this->container->singleton(Router::class, fn() => new Router());
        $this->container->singleton(
            Generator::class,
            fn() => new Generator($this->container->make(Router::class))
        );

        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass();
            if (!$provider instanceof ServiceProvider) {
                throw new \RuntimeException("{$providerClass} must implement ServiceProvider");
            }
            $provider->register($this->container);
        }
        $router = $this->container->make(Router::class);
        $router->register(OpenApiController::class);
        $this->dispatcher = new Dispatcher($this->container, $router, new Hydrator(new Validator()));
        $this->errorHandler = new ErrorHandler();
    }

    private function emit(\Psr\Http\Message\ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }
        echo $response->getBody();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $pipeline = new Pipeline(
            $this->middleware,
            fn($request) => $this->dispatcher->dispatch($request),
        );

        try {
            return $pipeline->run($request);
        } catch (\Throwable $e) {
            return $this->errorHandler->handle($e);
        }
    }
}
