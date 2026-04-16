<?php

namespace App\Controllers;

use Antares\Router\Attributes\Get;
use App\DTOs\CreatePatientRequest;
use Antares\Router\Attributes\Post;

class PatientController
{
    #[Get('/patients')]
    public function list(): array
    {
        return [
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Doe'],
        ];
    }

    #[Get('/patients/{id}')]
    public function show(int $id): array
    {
        return ['id' => $id, 'name' => 'John Doe'];
    }

    #[Post('/patients', statusCode: 201)]
    public function create(CreatePatientRequest $body): array
    {
        return [
            'name' => $body->name,
            'age'  => $body->age,
        ];
    }
}