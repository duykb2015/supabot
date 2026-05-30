<?php

namespace App\Console\Commands;

use App\Actions\Telegram\GenerateTelegramBotHandler;
use Illuminate\Console\Command;
use Throwable;

class MakeTelegramBotHandler extends Command
{
    protected $signature = 'make:telegram-bot-handler {name : Handler name, for example SupportBot} {--force : Overwrite the handler if it already exists}';

    protected $description = 'Create an invokable Telegram bot handler class.';

    public function handle(GenerateTelegramBotHandler $generator): int
    {
        try {
            $handlerClass = $generator->execute(
                (string) $this->argument('name'),
                (bool) $this->option('force'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Telegram bot handler created: {$handlerClass}");

        return self::SUCCESS;
    }
}
