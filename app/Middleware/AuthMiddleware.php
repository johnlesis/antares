<?php

declare(strict_types=1);

namespace App\Middleware;

use Antares\Exceptions\HttpException;
use Antares\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface {
        $token = $request->getHeaderLine('Authorization');

        if (empty($token)) {
            throw new HttpException(401, 'Unauthorized');
        }

        return $next($request);
    }
}