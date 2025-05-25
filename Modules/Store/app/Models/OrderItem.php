<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'variation_id',
        'name',
        'sku',
        'quantity',
        'price',
        'subtotal',
        'tax',
        'total',
        'attributes',
        'meta_data'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'attributes' => 'array',
        'meta_data' => 'array'
    ];

    /**
     * Get the order that owns the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variation associated with the item.
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    /**
     * Calculate the subtotal for the item.
     */
    public function calculateSubtotal(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Calculate the total for the item including tax.
     */
    public function calculateTotal(): float
    {
        return $this->subtotal + $this->tax;
    }

    /**
     * Get the formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    /**
     * Get the formatted subtotal.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return number_format($this->subtotal, 2);
    }

    /**
     * Get the formatted tax.
     */
    public function getFormattedTaxAttribute(): string
    {
        return number_format($this->tax, 2);
    }

    /**
     * Get the formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2);
    }

    /**
     * Get the attribute values as a string.
     */
    public function getAttributeValuesStringAttribute(): string
    {
        if (empty($this->attributes)) {
            return '';
        }

        $values = [];
        foreach ($this->attributes as $attribute) {
            if (isset($attribute['name']) && isset($attribute['value'])) {
                $values[] = $attribute['name'] . ': ' . $attribute['value'];
            }
        }

        return implode(', ', $values);
    }
} 