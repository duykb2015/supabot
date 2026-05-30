<?php

namespace Tests\Fixtures\Telegram;

use App\Models\TelegramBot;

class SuccessfulTelegramHandler
{
    public function __invoke(TelegramBot $bot, array $update): void
    {
        $bot->forceFill(['bot_username' => 'handled'])->save();
    }
}
