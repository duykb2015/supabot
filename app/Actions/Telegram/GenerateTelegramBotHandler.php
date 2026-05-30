<?php

namespace App\Actions\Telegram;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class GenerateTelegramBotHandler
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * @return class-string
     */
    public function execute(string $name, bool $force = false): string
    {
        $classBase = Str::of($name)
            ->replace(['\\', '/', '-', '_'], ' ')
            ->studly()
            ->finish('Handler')
            ->toString();

        if (! preg_match('/^[A-Z][A-Za-z0-9_]*$/', $classBase)) {
            throw new InvalidArgumentException('Handler name must resolve to a valid PHP class name.');
        }

        $namespace = 'App\\Telegram\\Bots';
        $path = app_path("Telegram/Bots/{$classBase}.php");

        if ($this->files->exists($path) && ! $force) {
            throw new RuntimeException("Handler already exists at [{$path}]. Use --force to overwrite it.");
        }

        $this->files->ensureDirectoryExists(dirname($path));

        $stub = $this->files->get(base_path('stubs/telegram-bot-handler.stub'));
        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $classBase],
            $stub,
        );

        $this->files->put($path, $contents);

        /** @var class-string $handlerClass */
        $handlerClass = "{$namespace}\\{$classBase}";

        return $handlerClass;
    }
}
