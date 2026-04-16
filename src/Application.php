<?php declare (strict_types=1);

namespace Antares;

use Antares\Container\Container;
use Antares\Hydration\Hydrator;
use Antares\Router\Router;
use Antares\Validation\Validator;

class Application
{
    private array $controllers = [];
    private array $providers = [];
    private string $basePath;
    private Container $container;

    public static function create(string $basePath): static
    {
        $app = new static();
        $app->basePath = $basePath;
        return $app;
    }

    public function controllers(array $controllers): static
    {
        $this->controllers = $controllers;
        return $this;
    }

    public function providers(array $providers): static
    {
        $this->providers = $providers;
        return $this;
    }

    public function run(){
        $this->container = new Container();
        $router = new Router();
        
        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass();
            if (!$provider instanceof ServiceProvider) {
                throw new \RuntimeException("{$providerClass} must implement ServiceProvider");
            }
            $provider->register($this->container);
        }
        
        foreach($this->controllers as $controller)
            {
                $router->register($controller);
            }

        $dispatcher = new Dispatcher($this->container, $router, new Hydrator(new Validator()));

        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $creator = new \Nyholm\Psr7Server\ServerRequestCreator($factory, $factory, $factory, $factory);
        $request = $creator->fromGlobals();

        $errorHandler = new ErrorHandler();

        try {
            $response = $dispatcher->dispatch($request);
        } catch (\Throwable $e) {
            $response = $errorHandler->handle($e);
        }

        $this->emit($response);
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

}