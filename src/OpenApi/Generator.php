<?php

declare(strict_types=1);

namespace Antares\OpenApi;

use Antares\Router\Router;

final class Generator
{
    private SchemaBuilder $schemaBuilder;
    private PathBuilder $pathBuilder;

    public function __construct(
        private readonly Router $router,
    ) {
        $this->schemaBuilder = new SchemaBuilder();
        $this->pathBuilder   = new PathBuilder();
    }

    public function generate(): array
    {
        $excluded = ['/openapi.json', '/docs'];
        $routes  = $this->router->getRoutes();
        $paths   = [];
        $schemas = [];

        foreach ($routes as $route) {
            if (in_array($route[1], $excluded)) {
                continue;
            }
            $path = $this->pathBuilder->build($route);
            $paths = array_merge_recursive($paths, $path);

            $controller = $route[2];
            $method     = $route[3];
            $reflection = new \ReflectionMethod($controller, $method);

            foreach ($reflection->getParameters() as $parameter) {
                $type = $parameter->getType();
                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    $ref = new \ReflectionClass($typeName);
                    if ($ref->isReadOnly() && !isset($schemas[$typeName])) {
                        $schemas[$typeName] = $this->schemaBuilder->build($typeName);
                    }
                }
            }

            $returnType = $reflection->getReturnType();
            if ($returnType instanceof \ReflectionNamedType && !$returnType->isBuiltin()) {
                $typeName = $returnType->getName();
                if (class_exists($typeName) && !isset($schemas[$typeName])) {
                    $schemas[$typeName] = $this->schemaBuilder->build($typeName);
                }
            }
        }

        return [
            'openapi' => '3.0.0',
            'info'    => [
                'title'   => 'Antares API',
                'version' => '1.0.0',
            ],
            'paths'      => $paths,
            'components' => [
                'schemas' => $schemas,
            ],
        ];
    }
}