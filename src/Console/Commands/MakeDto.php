<?php

declare(strict_types=1);

namespace Antares\Console\Commands;

final class MakeDto extends GeneratorCommand
{
    protected function getPath(string $name): string
    {
        return getcwd() . "/app/DTOs/{$name}.php";
    }

    protected function getStub(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\DTOs;

use Antares\Validation\Attributes\Email;
use Antares\Validation\Attributes\InEnum;
use Antares\Validation\Attributes\Min;
use Antares\Validation\Attributes\Max;
use Antares\Validation\Attributes\MinLength;
use Antares\Validation\Attributes\MaxLength;
use Antares\Validation\Attributes\NotBlank;
use Antares\Validation\Attributes\Pattern;
use Antares\Validation\Attributes\Strict;

final readonly class {$name}
{
    public function __construct(
    ) {}
}
PHP;
    }
}