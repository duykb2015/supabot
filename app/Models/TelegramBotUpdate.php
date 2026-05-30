<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'telegram_bot_id',
    'update_id',
    'payload',
    'status',
    'error_message',
    'handled_at',
])]
class TelegramBotUpdate extends Model
{
    use HasFactory;

    public const STATUS_RECEIVED = 'received';

    public const STATUS_HANDLED = 'handled';

    public const STATUS_FAILED = 'failed';

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'handled_at' => 'datetime',
        ];
    }
}
