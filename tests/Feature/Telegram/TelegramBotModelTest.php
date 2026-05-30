<?php

namespace Tests\Feature\Telegram;

use App\Models\TelegramBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelegramBotModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_is_encrypted_and_relationships_work(): void
    {
        $bot = TelegramBot::create([
            'name' => 'Support Bot',
            'slug' => 'support-bot',
            'token' => 'plain-token',
            'handler_class' => 'App\\Telegram\\Bots\\SupportBotHandler',
        ]);

        $bot->updates()->create([
            'update_id' => 123,
            'payload' => ['update_id' => 123],
            'status' => 'received',
        ]);

        $rawToken = DB::table('telegram_bots')->whereKey($bot->id)->value('token');

        $this->assertNotSame('plain-token', $rawToken);
        $this->assertSame('plain-token', $bot->refresh()->token);
        $this->assertCount(1, $bot->updates);
    }
}
