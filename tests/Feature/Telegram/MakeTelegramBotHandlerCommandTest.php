<?php

namespace Tests\Feature\Telegram;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeTelegramBotHandlerCommandTest extends TestCase
{
    private string $handlerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handlerPath = app_path('Telegram/Bots/DemoBotHandler.php');
        File::delete($this->handlerPath);
    }

    protected function tearDown(): void
    {
        File::delete($this->handlerPath);

        parent::tearDown();
    }

    public function test_it_creates_handler_from_stub(): void
    {
        $exitCode = Artisan::call('make:telegram-bot-handler', [
            'name' => 'DemoBot',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->handlerPath);
        $this->assertStringContainsString('class DemoBotHandler', File::get($this->handlerPath));
        $this->assertStringContainsString('App\\Telegram\\Bots', File::get($this->handlerPath));
    }

    public function test_it_does_not_overwrite_existing_handler_without_force(): void
    {
        File::ensureDirectoryExists(dirname($this->handlerPath));
        File::put($this->handlerPath, 'existing');

        $exitCode = Artisan::call('make:telegram-bot-handler', [
            'name' => 'DemoBot',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame('existing', File::get($this->handlerPath));
    }
}
