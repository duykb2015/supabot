<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bot_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_bot_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('update_id')->nullable()->index();
            $table->json('payload');
            $table->string('status')->default('received')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bot_updates');
    }
};
