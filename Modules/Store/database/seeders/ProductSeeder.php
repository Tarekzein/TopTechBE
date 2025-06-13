<?php

namespace Modules\Store\Database\Seeders;
use Faker\Factory as Faker;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Store\Models\Product;
use Modules\Store\Models\ProductAttribute;
use Modules\Store\Models\ProductAttributeValue;
use Modules\Store\Models\ProductVariation;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create shared attributes
        $colorAttr = ProductAttribute::firstOrCreate([
            'name' => 'Color',
        ], [
            'type' => 'select',
            'is_required' => true,
            'is_filterable' => true,
            'display_order' => 1,
        ]);

        $sizeAttr = ProductAttribute::firstOrCreate([
            'name' => 'Size',
        ], [
            'type' => 'select',
            'is_required' => true,
            'is_filterable' => true,
            'display_order' => 2,
        ]);

        $colors = collect(['Red', 'Blue', 'Green', 'Black', 'White'])->map(function ($color, $i) use ($colorAttr) {
            return ProductAttributeValue::firstOrCreate([
                'attribute_id' => $colorAttr->id,
                'value' => $color,
            ], [
                'display_order' => $i + 1,
            ]);
        });

        $sizes = collect(['S', 'M', 'L', 'XL'])->map(function ($size, $i) use ($sizeAttr) {
            return ProductAttributeValue::firstOrCreate([
                'attribute_id' => $sizeAttr->id,
                'value' => $size,
            ], [
                'display_order' => $i + 1,
            ]);
        });

        // Create 50 products
        for ($i = 1; $i <= 50; $i++) {
            $isVariable = rand(0, 1) === 1;

            $product = Product::create([
                'name' => 'Product ' . $i,
                'description' => $faker->sentence(12),
                'price' => $isVariable ? 0 : $faker->randomFloat(2, 10, 100),
                'product_type' => $isVariable ? 'variable' : 'simple',
                'regular_price' => $isVariable ? null : $faker->randomFloat(2, 10, 100),
                'sale_price' => $isVariable ? null : $faker->randomFloat(2, 5, 99),
                'sale_start' => now()->subDays(rand(0, 5)),
                'sale_end' => now()->addDays(rand(2, 10)),
                'stock' => $isVariable ? 0 : rand(0, 100),
                'manage_stock' => true,
                'stock_status' => 'instock',
                'allow_backorders' => false,
                'low_stock_threshold' => rand(1, 5),
                'sold_individually' => false,
                'sku' => 'SKU-' . Str::upper(Str::random(8)),
                'images' => [$faker->imageUrl(640, 480, 'technics')],
                'is_active' => true,
                'category_id' => 1,
                'vendor_id' => 1,
                'weight' => $faker->randomFloat(2, 0.2, 2),
                'weight_unit' => 'kg',
                'length' => rand(10, 50),
                'width' => rand(10, 50),
                'height' => rand(1, 10),
                'dimension_unit' => 'cm',
            ]);

            // Add variations if variable product
            if ($isVariable) {
                foreach ($colors as $color) {
                    foreach ($sizes as $size) {
                        ProductVariation::create([
                            'product_id' => $product->id,
                            'sku' => 'VAR-' . Str::upper(Str::random(6)),
                            'regular_price' => $faker->randomFloat(2, 30, 120),
                            'sale_price' => $faker->randomFloat(2, 20, 90),
                            'sale_start' => now()->subDays(rand(0, 3)),
                            'sale_end' => now()->addDays(rand(2, 8)),
                            'manage_stock' => true,
                            'stock' => rand(0, 30),
                            'stock_status' => 'instock',
                            'allow_backorders' => false,
                            'low_stock_threshold' => rand(1, 5),
                            'weight' => $faker->randomFloat(2, 0.3, 1.5),
                            'weight_unit' => 'kg',
                            'length' => rand(10, 30),
                            'width' => rand(10, 30),
                            'height' => rand(1, 10),
                            'dimension_unit' => 'cm',
                            'attributes' => [
                                'attributes' => [
                                    $colorAttr->id => $color->id,
                                    $sizeAttr->id => $size->id,
                                ],
                            ],
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
    }
}
