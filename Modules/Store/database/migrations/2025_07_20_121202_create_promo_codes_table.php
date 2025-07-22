<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['fixed', 'percent']);
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('usage_limit')->nullable(); // global usage limit
            $table->unsignedInteger('usage_limit_per_user')->nullable(); // per user limit
            $table->unsignedInteger('used')->default(0); // total used
            $table->decimal('min_order_total', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
