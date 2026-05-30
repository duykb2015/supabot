<?php

namespace Tests\Feature\Telegram;

use App\Models\TelegramBot;
use App\Services\Telegram\TelegramWebhookManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_webhook_calls_telegram_and_persists_url(): void
    {
        config(['services.telegram.webhook_base_url' => 'https://example.test']);
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $bot = TelegramBot::create([
            'name' => 'Support Bot',
            'slug' => 'support-bot',
            'token' => 'test-token',
            'handler_class' => 'App\\Telegram\\Bots\\SupportBotHandler',
        ]);

        $url = app(TelegramWebhookManager::class)->setWebhook($bot);

        $this->assertSame("https://example.test/telegram/{$bot->slug}/{$bot->webhook_secret}", $url);
        $this->assertSame($url, $bot->refresh()->webhook_url);
        $this->assertNotNull($bot->last_webhook_set_at);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-token/setWebhook'
            && $request['url'] === $url);
    }

    public function test_delete_webhook_calls_telegram_and_clears_url(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $bot = TelegramBot::create([
            'name' => 'Support Bot',
            'slug' => 'support-bot',
            'token' => 'test-token',
            'handler_class' => 'App\\Telegram\\Bots\\SupportBotHandler',
            'webhook_url' => 'https://example.test/telegram/support-bot/secret',
            'last_webhook_set_at' => now(),
        ]);

        app(TelegramWebhookManager::class)->deleteWebhook($bot);

        $this->assertNull($bot->refresh()->webhook_url);
        $this->assertNull($bot->last_webhook_set_at);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-token/deleteWebhook');
    }
}
