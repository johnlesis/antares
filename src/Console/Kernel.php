<?php

declare(strict_types=1);

namespace Antares\Console;

use Antares\Console\Commands\CacheClear;
use Antares\Console\Commands\MakeController;
use Antares\Console\Commands\MakeDto;

final class Kernel
{
    private array $commands = [
        'make:controller' => MakeController::class,
        'make:dto'        => MakeDto::class,
        'cache:clear'     => CacheClear::class,
    ];

    public function handle(array $argv): void
    {
        if (!isset($argv[1])) {
            echo "Usage: antares <command> <name>\n";
            echo "\nAvailable commands:\n";
            foreach ($this->commands as $name => $class) {
                echo "  {$name}\n";
            }
            return;
        }

        $command = $argv[1];
        $name    = $argv[2] ?? null;

        if (!isset($this->commands[$command])) {
            echo "Unknown command: {$command}\n";
            echo "Run 'antares' to see available commands.\n";
            return;
        }

        $commandClass = $this->commands[$command];
        (new $commandClass())->handle($name);
    }
}