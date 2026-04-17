# Antares

API focused framework with attribute based auto-validation, autowiring, and auto doc creation for rapid API development

## Requirements

- PHP 8.2+
- Composer

## Installation

```bash
composer create-project fatjon-lleshi/antares-app my-api
cd my-api
php -S localhost:8080 -t public
```

## Quick Start

Minimal controller with attribute-based routing and automatic DTO validation.
Just type hint your readonly DTO — it gets automatically hydrated, validated, and injected.

### Controller

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Antares\Router\Attributes\Get;
use Antares\Router\Attributes\Post;
use App\DTOs\ExampleRequest;

final class ExampleController
{
    #[Get('/example')]
    public function list(int $page = 1, int $limit = 10): array
    {
        return [
            'page'  => $page,
            'limit' => $limit,
            'data'  => [],
        ];
    }

    #[Post('/example')]
    public function create(ExampleRequest $body): array
    {
        return [
            'name'    => $body->name,
            'message' => 'Created successfully',
        ];
    }
}
```

### Request DTO

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

use Antares\Validation\Attributes\MinLength;
use Antares\Validation\Attributes\MaxLength;

final readonly class ExampleRequest
{
    public function __construct(
        #[MinLength(2), MaxLength(100)]
        public string $name,

        public ?string $description = null,
    ) {}
}
```

The framework automatically:
- Hydrates JSON body → `ExampleRequest` object
- Validates `name` against `MinLength` and `MaxLength`
- Returns RFC 7807 error response if validation fails
- Injects `$page` and `$limit` from query string `?page=1&limit=10`

## Features

- **Attribute-based routing** — define routes with `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]` directly on controller methods
- **Automatic DTO hydration** — type hint a readonly DTO in your controller, JSON body is hydrated automatically
- **Attribute-based validation** — validate DTOs with `#[MinLength]`, `#[Max]`, `#[Email]`, `#[InEnum]` and more
- **Autowiring container** — dependencies resolved automatically via type hints, no manual wiring
- **Query param injection** — scalar method params injected from query string automatically
- **RFC 7807 error responses** — structured JSON error responses out of the box
- **Auto OpenAPI generation** — visit `/docs` for Swagger UI, `/openapi.json` for the spec — zero extra work
- **Middleware pipeline** — global middleware for auth, CORS, rate limiting
- **Multiple routing styles** — attribute, PHP config file, or YAML
- **CLI tools** — `make:controller`, `make:dto` to scaffold files
- **Route caching** — cache routes in production for zero reflection overhead
- **PSR-7 compatible** — works with RoadRunner and Swoole out of the box
- **Modular** — use individual packages standalone without the full framework

## Routing

### Attribute based

```php
#[Get('/example')]
public function list(): array
```

### File based

```php
use App\Controllers\ExampleController;

return [
    ['GET', '/example', ExampleController::class, 'list', 200],
];
```

### YAML based

```yaml
routes:
  - method: GET
    path: /patients
    controller: App\Controllers\PatientController
    action: list
    status: 200
```

## DTOs & Validation

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\WardType;
use Antares\Validation\Attributes\Email;
use Antares\Validation\Attributes\InEnum;
use Antares\Validation\Attributes\Max;
use Antares\Validation\Attributes\MaxLength;
use Antares\Validation\Attributes\Min;
use Antares\Validation\Attributes\MinLength;
use Antares\Validation\Attributes\NotBlank;
use Antares\Validation\Attributes\Pattern;
use Antares\Validation\Attributes\Strict;

#[Strict]
final readonly class CreatePatientRequest
{
    public function __construct(
        #[MinLength(2), MaxLength(100)]
        public string $name,

        #[Min(0), Max(120)]
        public int $age,

        #[InEnum(WardType::class)]
        public string $ward,

        #[Email]
        public string $email,

        #[NotBlank]
        public string $phone,

        #[Pattern('/^\d{5}$/')]
        public string $postalCode,

        public ?string $notes = null,
    ) {}
}
```

The framework automatically hydrates the JSON body into the DTO, runs all validation rules and returns a `422` RFC 7807 response if anything fails.

`#[Strict]` rejects any extra fields sent in the request body that are not declared in the DTO constructor.

### Available validation attributes

| Attribute | Target | Description |
|---|---|---|
| `#[MinLength(2)]` | `string` | Must be at least 2 characters long |
| `#[MaxLength(100)]` | `string` | Must be at most 100 characters long |
| `#[NotBlank]` | `string` | Must not be empty or whitespace only |
| `#[Email]` | `string` | Must be a valid email address |
| `#[Pattern('/^\d{5}$/')]` | `string` | Must match the given regex pattern |
| `#[Min(0)]` | `int\|float` | Must be greater than or equal to 0 |
| `#[Max(120)]` | `int\|float` | Must be less than or equal to 120 |
| `#[InEnum(WardType::class)]` | `string\|int` | Must be a valid case of a backed enum |
| `#[Strict]` | class | Rejects extra fields not declared in the DTO |

### Validation error response

If validation fails the framework returns a `422` RFC 7807 response automatically:

```json
{
    "type": "https://antares.dev/errors",
    "title": "Validation failed",
    "status": 422,
    "errors": {
        "name": ["must be at least 2 characters long"],
        "age": ["must be 0 or greater"],
        "ward": ["must be a valid WardType case"],
        "email": ["must be a valid email address"],
        "phone": ["must not be blank"],
        "postalCode": ["must match /^\\d{5}$/"]
    }
}
```

All errors are collected at once — not one at a time.

## Middleware

Implement `Antares\Middleware\MiddlewareInterface` and register it in your `public/index.php`.

example Middleware
```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ExampleMiddleware implements MiddlewareInterface
{
    public function handle(
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface {
        // Before request
        // e.g. logging, auth, headers

        $response = $next($request);

        // After request
        // e.g. modify response

        return $response;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Antares\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        if ($request->getHeaderLine('Authorization') === '') {
            return response(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
```

Register it globally in `app/Providers/AppServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Antares\Foundation\ServiceProvider;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->addMiddleware(AuthMiddleware::class);
        $this->addMiddleware(CorsMiddleware::class);
    }
}
```

Middleware runs in registration order for every request.

## API Documentation

On application boot, Antares reflects over all registered controllers and routes, collects HTTP methods, paths, query parameters, and DTO shapes, and builds an OpenAPI 3.0 spec array automatically — no annotations or extra configuration required.

Two endpoints are available out of the box:

| Endpoint | Description |
|---|---|
| `/docs` | Swagger UI — interactive browser for your API |
| `/openapi.json` | Raw OpenAPI 3.0 spec as JSON |

Given this controller:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Antares\Router\Attributes\Get;
use Antares\Router\Attributes\Post;
use App\DTOs\CreatePatientRequest;

final class PatientController
{
    #[Get('/patients')]
    public function list(int $page = 1, int $limit = 10): array
    {
        return [];
    }

    #[Post('/patients')]
    public function create(CreatePatientRequest $body): array
    {
        return [];
    }
}
```

The generated spec at `/openapi.json` will include:

```json
{
    "openapi": "3.0.0",
    "paths": {
        "/patients": {
            "get": {
                "parameters": [
                    { "name": "page",  "in": "query", "schema": { "type": "integer" }, "default": 1  },
                    { "name": "limit", "in": "query", "schema": { "type": "integer" }, "default": 10 }
                ]
            },
            "post": {
                "requestBody": {
                    "required": true,
                    "content": {
                        "application/json": {
                            "schema": {
                                "type": "object",
                                "required": ["name", "age", "ward", "email", "phone", "postalCode"],
                                "properties": {
                                    "name":       { "type": "string",  "minLength": 2, "maxLength": 100 },
                                    "age":        { "type": "integer", "minimum": 0,   "maximum": 120  },
                                    "ward":       { "type": "string",  "enum": ["icu", "general"]      },
                                    "email":      { "type": "string",  "format": "email"               },
                                    "phone":      { "type": "string"                                   },
                                    "postalCode": { "type": "string",  "pattern": "^\\d{5}$"          },
                                    "notes":      { "type": "string",  "nullable": true               }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
```

DTO validation attributes (`#[MinLength]`, `#[Max]`, `#[InEnum]`, `#[Pattern]`, etc.) are reflected directly into the OpenAPI schema — the spec always stays in sync with your actual validation rules.

## CLI

Antares ships with a `bin/antares` console for scaffolding.

### Scaffold a controller

```bash
php bin/antares make:controller PatientController
```

Creates `app/Controllers/PatientController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Antares\Router\Attributes\Get;

final class PatientController
{
    #[Get('/patients')]
    public function list(): array
    {
        return [];
    }
}
```

### Scaffold a DTO

```bash
php bin/antares make:dto CreatePatientRequest
```

Creates `app/DTOs/CreatePatientRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class CreatePatientRequest
{
    public function __construct(
        public string $name,
    ) {}
}
```

## Directory Structure

```
my-api/
├── app/
│   ├── Controllers/
│   │   └── ExampleController.php
│   ├── DTOs/
│   │   └── ExampleRequest.php
│   ├── Middleware/
│   │   └── ExampleMiddleware.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── RouteServiceProvider.php
├── config/
│   ├── app.php
│   └── routes.php
├── public/
│   └── index.php
├── storage/
│   └── cache/
├── vendor/
└── composer.json
```

`AppServiceProvider` is where you register middleware and container bindings. `RouteServiceProvider` is where you register your controllers for attribute-based route discovery. `config/routes.php` is only needed when using file-based routing. `storage/cache/` holds the compiled route cache in production.

## Packages

| Package | Description |
|---|---|
| [antares-validation](link) | DTO hydration and validation |
| [antares-container](link) | Autowiring DI container |
| [antares-router](link) | Attribute-based router |

## License

MIT