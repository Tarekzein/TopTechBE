<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // if logged in
            $table->string('session_token')->nullable(); // for guests/local storage
            $table->text('user_message');
            $table->longText('ai_response');
            $table->timestamps();

            $table->index('user_id');
            $table->index('session_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
    }
};
