<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class ProductVariation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'regular_price',
        'sale_price',
        'sale_start',
        'sale_end',
        'manage_stock',
        'stock',
        'stock_status',
        'allow_backorders',
        'low_stock_threshold',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'attributes',
        'is_active'
    ];

    protected $casts = [
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'sale_start' => 'datetime',
        'sale_end' => 'datetime',
        'manage_stock' => 'boolean',
        'stock' => 'integer',
        'allow_backorders' => 'boolean',
        'low_stock_threshold' => 'integer',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'attributes' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the product that owns this variation.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the images for this variation.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductVariationImage::class, 'variation_id');
    }

    /**
     * Get the current price of the variation.
     */
    public function getCurrentPriceAttribute()
    {
        if ($this->sale_price && 
            (!$this->sale_start || now()->gte($this->sale_start)) && 
            (!$this->sale_end || now()->lte($this->sale_end))) {
            return $this->sale_price;
        }
        return $this->regular_price;
    }

    /**
     * Check if the variation is on sale.
     */
    public function getIsOnSaleAttribute()
    {
        return $this->sale_price && 
            (!$this->sale_start || now()->gte($this->sale_start)) && 
            (!$this->sale_end || now()->lte($this->sale_end));
    }

    /**
     * Get the formatted attributes for display.
     */
    public function getFormattedAttributesAttribute()
    {
        if (empty($this->attributes['attributes'])) {
            return ['attributes' => []];
        }

        $formatted = ['attributes' => []];
        // Ensure we're working with an array
        $attributes = is_string($this->attributes['attributes']) 
            ? json_decode($this->attributes['attributes'], true) 
            : $this->attributes['attributes'];

        if (!is_array($attributes)) {
            return [];
        }

        foreach ($attributes['attributes'] as $attributeId => $valueId) {
            $attribute = ProductAttribute::with('values')->find($attributeId);
            if (!$attribute) {
                continue;
            }
            Log::info($attribute);
            $value = $attribute->values->firstWhere('id', $valueId);
            if ($value) {
                $formatted['attributes'][$attribute->name] = $value->value;
            }
            Log::info($value);
        }
        Log::info($formatted);
        return $formatted;
    }
} 