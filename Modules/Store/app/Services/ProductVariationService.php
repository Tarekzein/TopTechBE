<?php

namespace Modules\Store\Services;

use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Modules\Store\Models\Product;
use Modules\Store\Models\ProductVariation;
use Modules\Store\Models\ProductVariationImage;

class ProductVariationService
{
    /**
     * Validate variation data
     */
    protected function validateVariation(array $data, ?int $id = null): array
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
            'sku' => 'required|string|max:50|unique:product_variations,sku' . ($id ? ",$id" : ''),
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start' => 'nullable|date',
            'sale_end' => 'nullable|date|after_or_equal:sale_start',
            'manage_stock' => 'boolean',
            'stock' => 'required_if:manage_stock,true|integer|min:0',
            'allow_backorders' => 'boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'required_with:weight|in:kg,g,lb,oz',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'dimension_unit' => 'required_with:length,width,height|in:cm,m,mm,in,ft',
            'attributes' => 'required|array',
            'attributes.*' => 'required|exists:product_attribute_values,id',
            'is_active' => 'boolean'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Validate variation image data
     */
    protected function validateVariationImage(array $data): array
    {
        $rules = [
            'variation_id' => 'required|exists:product_variations,id',
            'image' => 'required|string|max:255',
            'display_order' => 'integer|min:0'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Get all variations for a product
     */
    public function getProductVariations(int $productId, int $perPage = 10)
    {
        return ProductVariation::where('product_id', $productId)
            ->with(['images'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get variation by ID
     */
    public function getVariationById(int $id)
    {
        $variation = ProductVariation::with(['images', 'product'])->find($id);
        if (!$variation) {
            throw new Exception('Variation not found');
        }
        return $variation;
    }

    /**
     * Create new variation
     */
    public function createVariation(array $data)
    {
        try {
            DB::beginTransaction();

            $validatedData = $this->validateVariation($data);
            $variation = ProductVariation::create($validatedData);

            // Create variation images if provided
            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $index => $image) {
                    ProductVariationImage::create([
                        'variation_id' => $variation->id,
                        'image' => $image,
                        'display_order' => $index
                    ]);
                }
            }

            DB::commit();
            return $variation->load('images');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update variation
     */
    public function updateVariation(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $validatedData = $this->validateVariation($data, $id);
            $variation = ProductVariation::find($id);
            
            if (!$variation) {
                throw new Exception('Variation not found');
            }

            $variation->update($validatedData);

            // Update variation images if provided
            if (isset($data['images']) && is_array($data['images'])) {
                // Delete existing images
                $variation->images()->delete();

                // Create new images
                foreach ($data['images'] as $index => $image) {
                    ProductVariationImage::create([
                        'variation_id' => $variation->id,
                        'image' => $image,
                        'display_order' => $index
                    ]);
                }
            }

            DB::commit();
            return $variation->load('images');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete variation
     */
    public function deleteVariation(int $id)
    {
        try {
            DB::beginTransaction();

            $variation = ProductVariation::find($id);
            if (!$variation) {
                throw new Exception('Variation not found');
            }

            // Delete associated images
            $variation->images()->delete();
            
            // Delete variation
            $variation->delete();

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add image to variation
     */
    public function addVariationImage(array $data)
    {
        $validatedData = $this->validateVariationImage($data);
        return ProductVariationImage::create($validatedData);
    }

    /**
     * Remove image from variation
     */
    public function removeVariationImage(int $id)
    {
        $image = ProductVariationImage::find($id);
        if (!$image) {
            throw new Exception('Variation image not found');
        }
        return $image->delete();
    }

    /**
     * Update variation image order
     */
    public function updateVariationImageOrder(array $imageIds)
    {
        try {
            DB::beginTransaction();

            foreach ($imageIds as $index => $id) {
                ProductVariationImage::where('id', $id)
                    ->update(['display_order' => $index]);
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 