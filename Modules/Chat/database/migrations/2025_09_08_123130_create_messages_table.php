<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                  ->constrained('conversations')
                  ->onDelete('cascade');
            $table->enum('sender_type', ['user', 'admin']);
            $table->unsignedBigInteger('sender_id'); // user_id أو admin_id
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // ممكن نضيف اندكس لتحسين الأداء
            $table->index(['conversation_id', 'sender_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
