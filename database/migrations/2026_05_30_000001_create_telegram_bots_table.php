<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('bot_username')->nullable();
            $table->text('token');
            $table->string('handler_class');
            $table->string('webhook_secret')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('webhook_url')->nullable();
            $table->timestamp('last_webhook_set_at')->nullable();
            $table->unsignedBigInteger('last_update_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bots');
    }
};
