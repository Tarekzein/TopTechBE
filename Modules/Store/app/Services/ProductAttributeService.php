<?php

namespace Modules\Store\Services;

use Exception;
use Illuminate\Support\Facades\Validator;
use Modules\Store\Models\ProductAttribute;
use Modules\Store\Models\ProductAttributeValue;

class ProductAttributeService
{
    /**
     * Validate attribute data
     */
    protected function validateAttribute(array $data, ?int $id = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:select,color,image',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'display_order' => 'integer|min:0'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Validate attribute value data
     */
    protected function validateAttributeValue(array $data, ?int $id = null): array
    {
        $rules = [
            'attribute_id' => 'required|exists:product_attributes,id',
            'value' => 'required|string|max:255',
            'color_code' => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
            'image' => 'nullable|string|max:255',
            'display_order' => 'integer|min:0'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Get all attributes
     */
    public function getAllAttributes(int $perPage = 10)
    {
        return ProductAttribute::with('values')
            ->orderBy('display_order')
            ->paginate($perPage);
    }

    /**
     * Get attribute by ID
     */
    public function getAttributeById(int $id)
    {
        $attribute = ProductAttribute::with('values')->find($id);
        if (!$attribute) {
            throw new Exception('Attribute not found');
        }
        return $attribute;
    }

    /**
     * Create new attribute
     */
    public function createAttribute(array $data)
    {
        $validatedData = $this->validateAttribute($data);
        return ProductAttribute::create($validatedData);
    }

    /**
     * Update attribute
     */
    public function updateAttribute(int $id, array $data)
    {
        $validatedData = $this->validateAttribute($data, $id);
        $attribute = ProductAttribute::find($id);
        
        if (!$attribute) {
            throw new Exception('Attribute not found');
        }

        $attribute->update($validatedData);
        return $attribute;
    }

    /**
     * Delete attribute
     */
    public function deleteAttribute(int $id)
    {
        $attribute = ProductAttribute::find($id);
        if (!$attribute) {
            throw new Exception('Attribute not found');
        }
        return $attribute->delete();
    }

    /**
     * Get all values for an attribute
     */
    public function getAttributeValues(int $attributeId, int $perPage = 10)
    {
        return ProductAttributeValue::where('attribute_id', $attributeId)
            ->orderBy('display_order')
            ->paginate($perPage);
    }

    /**
     * Get attribute value by ID
     */
    public function getAttributeValueById(int $id)
    {
        $value = ProductAttributeValue::with('attribute')->find($id);
        if (!$value) {
            throw new Exception('Attribute value not found');
        }
        return $value;
    }

    /**
     * Create new attribute value
     */
    public function createAttributeValue(array $data)
    {
        $validatedData = $this->validateAttributeValue($data);
        return ProductAttributeValue::create($validatedData);
    }

    /**
     * Update attribute value
     */
    public function updateAttributeValue(int $id, array $data)
    {
        $validatedData = $this->validateAttributeValue($data);
        $value = ProductAttributeValue::find($id);
        
        if (!$value) {
            throw new Exception('Attribute value not found');
        }

        $value->update($validatedData);
        return $value;
    }

    /**
     * Delete attribute value
     */
    public function deleteAttributeValue(int $id)
    {
        $value = ProductAttributeValue::find($id);
        if (!$value) {
            throw new Exception('Attribute value not found');
        }
        return $value->delete();
    }
} 