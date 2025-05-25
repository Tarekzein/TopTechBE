<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('set null');
            $table->string('name'); // Product name at time of purchase
            $table->string('sku'); // SKU at time of purchase
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // Price per unit at time of purchase
            $table->decimal('subtotal', 10, 2); // Price * quantity
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2); // Subtotal + tax
            $table->json('attributes')->nullable(); // For variable products, store selected attributes
            $table->json('meta_data')->nullable(); // For storing additional data
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}; 