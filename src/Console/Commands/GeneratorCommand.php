<?php

declare(strict_types=1);

namespace Antares\Console\Commands;

abstract class GeneratorCommand
{
    abstract protected function getStub(string $name): string;
    abstract protected function getPath(string $name): string;

    public function handle(?string $name): void
    {
        if ($name === null) {
            echo "Please provide a name.\n";
            return;
        }

        $path = $this->getPath($name);
        $stub = $this->getStub($name);

        if (file_exists($path)) {
            echo "File already exists: {$path}\n";
            return;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $stub);
        echo "Created: {$path}\n";
    }
}