<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Cviebrock\EloquentSluggable\Sluggable;

class Product extends Model
{
    use SoftDeletes, Sluggable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'product_type',
        'regular_price',
        'sale_price',
        'sale_start',
        'sale_end',
        'stock',
        'manage_stock',
        'stock_status',
        'allow_backorders',
        'low_stock_threshold',
        'sold_individually',
        'sku',
        'images',
        'is_active',
        'category_id',
        'vendor_id',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'sale_start' => 'datetime',
        'sale_end' => 'datetime',
        'stock' => 'integer',
        'manage_stock' => 'boolean',
        'allow_backorders' => 'boolean',
        'low_stock_threshold' => 'integer',
        'sold_individually' => 'boolean',
        'images' => 'array',
        'is_active' => 'boolean',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2'
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the vendor that owns the product.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the variations for this product.
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Get the current price of the product.
     */
    public function getCurrentPriceAttribute()
    {
        if ($this->product_type === 'variable') {
            return $this->variations->min('current_price');
        }

        if ($this->sale_price && 
            (!$this->sale_start || now()->gte($this->sale_start)) && 
            (!$this->sale_end || now()->lte($this->sale_end))) {
            return $this->sale_price;
        }
        return $this->regular_price ?? $this->price;
    }

    /**
     * Check if the product is on sale.
     */
    public function getIsOnSaleAttribute()
    {
        if ($this->product_type === 'variable') {
            return $this->variations->contains('is_on_sale', true);
        }

        return $this->sale_price && 
            (!$this->sale_start || now()->gte($this->sale_start)) && 
            (!$this->sale_end || now()->lte($this->sale_end));
    }

    /**
     * Get the stock status of the product.
     */
    public function getStockStatusAttribute($value)
    {
        if (!$this->manage_stock) {
            return $value;
        }

        if ($this->product_type === 'variable') {
            if ($this->variations->every('stock_status', 'outofstock')) {
                return 'outofstock';
            }
            if ($this->variations->contains('stock_status', 'onbackorder')) {
                return 'onbackorder';
            }
            return 'instock';
        }

        if ($this->stock <= 0) {
            if ($this->allow_backorders) {
                return 'onbackorder';
            }
            return 'outofstock';
        }

        if ($this->low_stock_threshold && $this->stock <= $this->low_stock_threshold) {
            return 'lowstock';
        }

        return 'instock';
    }

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }
} 