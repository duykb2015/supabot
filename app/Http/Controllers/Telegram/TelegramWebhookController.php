<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\TelegramBot;
use App\Models\TelegramBotUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBot $bot, string $secret): JsonResponse
    {
        if (! $bot->is_active) {
            abort(404);
        }

        if (! hash_equals($bot->webhook_secret, $secret)) {
            abort(403);
        }

        $payload = $request->json()->all();

        $update = $bot->updates()->create([
            'update_id' => $payload['update_id'] ?? null,
            'payload' => $payload,
            'status' => TelegramBotUpdate::STATUS_RECEIVED,
        ]);

        try {
            if (! class_exists($bot->handler_class)) {
                throw new \RuntimeException("Handler class [{$bot->handler_class}] does not exist.");
            }

            $handler = app($bot->handler_class);

            if (! is_callable($handler)) {
                throw new \RuntimeException("Handler class [{$bot->handler_class}] is not invokable.");
            }

            $handler($bot, $payload);

            $update->forceFill([
                'status' => TelegramBotUpdate::STATUS_HANDLED,
                'handled_at' => now(),
            ])->save();

            $bot->forceFill([
                'last_update_id' => $payload['update_id'] ?? $bot->last_update_id,
                'last_error' => null,
            ])->save();

            return response()->json(['ok' => true]);
        } catch (Throwable $exception) {
            $update->forceFill([
                'status' => TelegramBotUpdate::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'handled_at' => now(),
            ])->save();

            $bot->forceFill([
                'last_update_id' => $payload['update_id'] ?? $bot->last_update_id,
                'last_error' => $exception->getMessage(),
            ])->save();

            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Webhook handler failed.',
            ], 500);
        }
    }
}
