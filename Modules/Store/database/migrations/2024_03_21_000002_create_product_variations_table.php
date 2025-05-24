<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->default('simple')->after('sku'); // simple or variable
            $table->decimal('regular_price', 10, 2)->nullable()->after('price');
            $table->decimal('sale_price', 10, 2)->nullable()->after('regular_price');
            $table->timestamp('sale_start')->nullable()->after('sale_price');
            $table->timestamp('sale_end')->nullable()->after('sale_start');
            $table->boolean('manage_stock')->default(true)->after('stock');
            $table->string('stock_status')->default('instock')->after('manage_stock'); // instock, outofstock, onbackorder
            $table->boolean('allow_backorders')->default(false)->after('stock_status');
            $table->integer('low_stock_threshold')->nullable()->after('allow_backorders');
            $table->boolean('sold_individually')->default(false)->after('low_stock_threshold');
            $table->decimal('weight', 10, 2)->nullable()->after('sold_individually');
            $table->string('weight_unit')->default('kg')->after('weight');
            $table->decimal('length', 10, 2)->nullable()->after('weight_unit');
            $table->decimal('width', 10, 2)->nullable()->after('length');
            $table->decimal('height', 10, 2)->nullable()->after('width');
            $table->string('dimension_unit')->default('cm')->after('height');
        });

        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->decimal('regular_price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->timestamp('sale_start')->nullable();
            $table->timestamp('sale_end')->nullable();
            $table->boolean('manage_stock')->default(true);
            $table->integer('stock')->default(0);
            $table->string('stock_status')->default('instock');
            $table->boolean('allow_backorders')->default(false);
            $table->integer('low_stock_threshold')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('weight_unit')->default('kg');
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('dimension_unit')->default('cm');
            $table->json('attributes')->nullable(); // Store selected attribute values
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_variation_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_id')->constrained('product_variations')->onDelete('cascade');
            $table->string('image');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variation_images');
        Schema::dropIfExists('product_variations');
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_type',
                'regular_price',
                'sale_price',
                'sale_start',
                'sale_end',
                'manage_stock',
                'stock_status',
                'allow_backorders',
                'low_stock_threshold',
                'sold_individually',
                'weight',
                'weight_unit',
                'length',
                'width',
                'height',
                'dimension_unit'
            ]);
        });
    }
}; 