<?php

namespace App\Services\Telegram;

use App\Models\TelegramBot;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramWebhookManager
{
    /**
     * @throws ConnectionException
     */
    public function setWebhook(TelegramBot $bot): string
    {
        $webhookUrl = $bot->webhookEndpoint();

        $response = Http::asJson()
            ->acceptJson()
            ->post($this->telegramApiUrl($bot, 'setWebhook'), [
                'url' => $webhookUrl,
            ]);

        if (! $response->successful() || $response->json('ok') !== true) {
            $description = $response->json('description') ?: $response->body();

            throw new RuntimeException("Telegram setWebhook failed: {$description}");
        }

        $bot->forceFill([
            'webhook_url' => $webhookUrl,
            'last_webhook_set_at' => now(),
            'last_error' => null,
        ])->save();

        return $webhookUrl;
    }

    /**
     * @throws ConnectionException
     */
    public function deleteWebhook(TelegramBot $bot): void
    {
        $response = Http::asJson()
            ->acceptJson()
            ->post($this->telegramApiUrl($bot, 'deleteWebhook'));

        if (! $response->successful() || $response->json('ok') !== true) {
            $description = $response->json('description') ?: $response->body();

            throw new RuntimeException("Telegram deleteWebhook failed: {$description}");
        }

        $bot->forceFill([
            'webhook_url' => null,
            'last_webhook_set_at' => null,
            'last_error' => null,
        ])->save();
    }

    private function telegramApiUrl(TelegramBot $bot, string $method): string
    {
        return sprintf('https://api.telegram.org/bot%s/%s', $bot->token, $method);
    }
}
