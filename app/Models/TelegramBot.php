<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'bot_username',
    'token',
    'handler_class',
    'webhook_secret',
    'is_active',
    'webhook_url',
    'last_webhook_set_at',
    'last_update_id',
    'last_error',
])]
class TelegramBot extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (TelegramBot $bot): void {
            if (blank($bot->webhook_secret)) {
                $bot->webhook_secret = Str::random(48);
            }
        });
    }

    public function updates(): HasMany
    {
        return $this->hasMany(TelegramBotUpdate::class);
    }

    public function webhookEndpoint(): string
    {
        $path = route('telegram.webhook', [
            'bot' => $this->slug,
            'secret' => $this->webhook_secret,
        ], false);

        $baseUrl = rtrim(config('services.telegram.webhook_base_url') ?: config('app.url'), '/');

        return "{$baseUrl}{$path}";
    }

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'is_active' => 'boolean',
            'last_webhook_set_at' => 'datetime',
        ];
    }
}
