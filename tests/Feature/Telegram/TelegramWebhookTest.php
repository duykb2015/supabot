<?php

namespace Tests\Feature\Telegram;

use App\Models\TelegramBot;
use App\Models\TelegramBotUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Telegram\SuccessfulTelegramHandler;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_invalid_secret(): void
    {
        $bot = $this->createBot();

        $this->postJson("/telegram/{$bot->slug}/wrong-secret", ['update_id' => 1])
            ->assertForbidden();

        $this->assertDatabaseCount('telegram_bot_updates', 0);
    }

    public function test_rejects_inactive_bot(): void
    {
        $bot = $this->createBot(['is_active' => false]);

        $this->postJson("/telegram/{$bot->slug}/{$bot->webhook_secret}", ['update_id' => 1])
            ->assertNotFound();

        $this->assertDatabaseCount('telegram_bot_updates', 0);
    }

    public function test_missing_handler_marks_update_failed(): void
    {
        $bot = $this->createBot(['handler_class' => 'App\\Telegram\\Bots\\MissingHandler']);

        $this->postJson("/telegram/{$bot->slug}/{$bot->webhook_secret}", ['update_id' => 10])
            ->assertStatus(500)
            ->assertJson(['ok' => false]);

        $this->assertDatabaseHas('telegram_bot_updates', [
            'telegram_bot_id' => $bot->id,
            'update_id' => 10,
            'status' => TelegramBotUpdate::STATUS_FAILED,
        ]);

        $this->assertStringContainsString('does not exist', (string) $bot->refresh()->last_error);
    }

    public function test_invokes_handler_and_marks_update_handled(): void
    {
        $bot = $this->createBot(['handler_class' => SuccessfulTelegramHandler::class]);

        $this->postJson("/telegram/{$bot->slug}/{$bot->webhook_secret}", [
            'update_id' => 55,
            'message' => ['text' => '/start'],
        ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('telegram_bot_updates', [
            'telegram_bot_id' => $bot->id,
            'update_id' => 55,
            'status' => TelegramBotUpdate::STATUS_HANDLED,
        ]);

        $bot->refresh();

        $this->assertSame(55, $bot->last_update_id);
        $this->assertSame('handled', $bot->bot_username);
        $this->assertNull($bot->last_error);
    }

    private function createBot(array $attributes = []): TelegramBot
    {
        return TelegramBot::create(array_merge([
            'name' => 'Support Bot',
            'slug' => 'support-bot',
            'token' => 'test-token',
            'handler_class' => SuccessfulTelegramHandler::class,
        ], $attributes));
    }
}
