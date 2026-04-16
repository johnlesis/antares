<?php

namespace App\Controllers;

use Antares\Router\Attributes\Get;
use App\DTOs\CreatePatientRequest;
use Antares\Router\Attributes\Post;

class PatientController
{
    #[Get('/patients')]
    public function list(int $page = 1, int $limit = 10): array
    {
        return [
            'page'  => $page,
            'limit' => $limit,
            'data'  => [
                ['id' => 1, 'name' => 'John Doe'],
                ['id' => 2, 'name' => 'Jane Doe'],
            ],
        ];
    }

    #[Get('/patients/{id}')]
    public function show(int $id): array
    {
        return ['id' => $id, 'name' => 'John Doe'];
    }

    #[Post('/patients')]
    public function create(
        CreatePatientRequest $body,    // from JSON body
        ?string $source = null,        // from query string
    ): array
    {
        return [
            'patient' => $body->name,
            'source'  => $source,
        ];
    }
}