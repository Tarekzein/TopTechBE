<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('select'); // select, color, image, etc.
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('product_attributes')->onDelete('cascade');
            $table->string('value');
            $table->string('slug')->unique();
            $table->string('color_code')->nullable(); // For color type attributes
            $table->string('image')->nullable(); // For image type attributes
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }
}; 