<?php

declare(strict_types=1);

namespace App\Middleware;

use Antares\Exceptions\HttpException;
use Antares\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class AuthMiddleware implements MiddlewareInterface
{
     private array $excluded = [
        '/openapi.json',
        '/docs',
    ];

    public function handle(
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface {
        $path = $request->getUri()->getPath();

        if (in_array($path, $this->excluded)) {
            return $next($request);
        }

        $token = $request->getHeaderLine('Authorization');

        if (empty($token)) {
            throw new HttpException(401, 'Unauthorized');
        }

        return $next($request);
    }
}