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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'banner', 'popup', 'sidebar', 'inline', 'video'
            $table->string('position'); // 'header', 'footer', 'sidebar', 'content', 'popup'
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('link_url')->nullable();
            $table->string('link_text')->nullable();
            $table->json('dimensions')->nullable(); // width, height
            $table->json('styling')->nullable(); // CSS styles, colors, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('target_audience')->nullable(); // User roles, locations, etc.
            $table->integer('max_impressions')->nullable();
            $table->integer('current_impressions')->default(0);
            $table->integer('max_clicks')->nullable();
            $table->integer('current_clicks')->default(0);
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('spent', 10, 2)->default(0);
            $table->string('advertiser_name')->nullable();
            $table->string('advertiser_email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
