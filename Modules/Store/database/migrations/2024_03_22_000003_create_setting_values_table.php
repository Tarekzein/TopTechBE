<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('setting_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained('settings')->onDelete('cascade');
            $table->string('value')->nullable();
            $table->string('locale')->nullable(); // For multilingual settings
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate settings for the same locale
            $table->unique(['setting_id', 'locale']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('setting_values');
    }
}; 