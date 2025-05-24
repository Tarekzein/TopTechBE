<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained('wishlists')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();

            // Ensure a product can only be added once to a wishlist
            $table->unique(['wishlist_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('wishlist_items');
    }
}; 