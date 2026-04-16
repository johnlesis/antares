<?php
declare(strict_types=1);
namespace Antares;
use Antares\Container\Container;
use Antares\Exceptions\HttpException;
use Antares\Hydration\Hydrator;
use Antares\Router\Router;
use Exception;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
final class Dispatcher
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly Hydrator $hydrator,
    ) {}

    public function dispatch(
        ServerRequestInterface $request
    ): Response {
        $httpMethod = $request->getMethod();
        $uri = $request->getUri()->getPath();
        [$controllerClass, $methodName, $routeParams, $statusCode] = $this->router->match($httpMethod, $uri);
        $controller = $this->container->make($controllerClass);
        $reflection = new \ReflectionMethod($controller, $methodName);
        $parameters = $reflection->getParameters();
        $args = [];
        foreach ($parameters as $parameter) {
            $args[] = $this->resolveParameter($parameter, $routeParams, $request);
        }
        $result = $reflection->invokeArgs($controller, $args);
        return $this->buildResponse($result, $statusCode);
    }

    private function resolveParameter(
        \ReflectionParameter $parameter,
        array $routeParams,
        ServerRequestInterface $request,
    ): mixed {
        $type = $parameter->getType();
        $typeName = $type->getName();
        if (isset($routeParams[$parameter->getName()])) {
            return $this->castRouteParam(
                $routeParams[$parameter->getName()],
                $type
            );
        }
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            if ((new \ReflectionClass($typeName))->isReadOnly()) {

                $rawBody = (string) $request->getBody();

                if (!empty($rawBody) && json_decode($rawBody) === null) {
                    throw new HttpException(400, "Invalid JSON body");
                }

                $data = json_decode($rawBody, true) ?? [];

                return $this->hydrator->hydrate(
                    $typeName,
                    $data
                );
            }
            return $this->container->make($typeName);
        }
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } else {
            throw new Exception('could not resolve parameters');
        }
    }

    private function castRouteParam(string $value, ?\ReflectionNamedType $type): mixed
    {
        return match($type?->getName()) {
            'int'   => filter_var($value, FILTER_VALIDATE_INT) !== false
                            ? (int) $value
                            : throw new HttpException(400, "Route parameter must be a valid integer, got '{$value}'"),
            'float' => filter_var($value, FILTER_VALIDATE_FLOAT) !== false
                            ? (float) $value
                            : throw new HttpException(400, "Route parameter must be a valid float, got '{$value}'"),
            'bool'  => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null
                            ? filter_var($value, FILTER_VALIDATE_BOOLEAN)
                            : throw new HttpException(400, "Route parameter must be a valid boolean, got '{$value}'"),
            default => $value,
        };
    }

    private function buildResponse(mixed $result, int $statusCode): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        $body = match(true) {
            $result === null   => '',
            is_array($result)  => json_encode($result),
            is_object($result) => json_encode(get_object_vars($result)),
            default            => json_encode($result),
        };
        return new Response(
            status: $statusCode,
            headers: ['Content-Type' => 'application/json'],
            body: $body,
        );
    }
}