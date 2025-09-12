<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Laravel Notifications بيحب UUID
            $table->string('type')->nullable(); // نوع الإشعار
            $table->text('content'); // نص الإشعار
            $table->unsignedBigInteger('sender_id')->nullable(); // المرسل
            $table->unsignedBigInteger('receiver_id')->nullable(); // المستقبل (لو موجه لفرد معين)
            $table->boolean('is_alert')->default(false); // هل تنبيه مهم
            $table->boolean('is_admin')->default(false); // لو الإشعار لكل الـ admins
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('sender_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
