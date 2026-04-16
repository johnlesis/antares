<?php

namespace App\DTOs;

use Antares\Validation\Attributes\MinLength;
use Antares\Validation\Attributes\Min;

final readonly class CreatePatientRequest
{
    public function __construct(
        #[MinLength(2)]
        public string $name,

        #[Min(0)]
        public int $age,
    ) {}
}