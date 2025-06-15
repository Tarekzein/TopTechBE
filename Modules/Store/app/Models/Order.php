<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'payment_status',
        'payment_method',
        'payment_id',
        'subtotal',
        'tax',
        'shipping_cost',
        'discount',
        'total',
        'currency',
        'shipping_method',
        'shipping_tracking_number',
        'shipping_tracking_url',
        'notes',
        'meta_data',
        'billing_address_id',
        'shipping_address_id',
        'paid_at',
        'completed_at',
        'cancelled_at',
        'refunded_at',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'tax' => 'float',
        'shipping_cost' => 'float',
        'discount' => 'float',
        'total' => 'float',
        'meta_data' => 'array',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('YmdHis');
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $timestamp . $random;
    }

    /**
     * Check if the order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if the order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the order is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Get the full name of the billing customer.
     */
    public function getBillingFullNameAttribute(): string
    {
        return $this->billing_first_name . ' ' . $this->billing_last_name;
    }

    /**
     * Get the full name of the shipping customer.
     */
    public function getShippingFullNameAttribute(): string
    {
        return $this->shipping_first_name . ' ' . $this->shipping_last_name;
    }

    /**
     * Get the full billing address.
     */
    public function getBillingFullAddressAttribute(): string
    {
        $address = $this->billing_address;
        if ($this->billing_city) {
            $address .= ', ' . $this->billing_city;
        }
        if ($this->billing_state) {
            $address .= ', ' . $this->billing_state;
        }
        if ($this->billing_postcode) {
            $address .= ' ' . $this->billing_postcode;
        }
        if ($this->billing_country) {
            $address .= ', ' . $this->billing_country;
        }
        return $address;
    }

    /**
     * Get the full shipping address.
     */
    public function getShippingFullAddressAttribute(): string
    {
        $address = $this->shipping_address;
        if ($this->shipping_city) {
            $address .= ', ' . $this->shipping_city;
        }
        if ($this->shipping_state) {
            $address .= ', ' . $this->shipping_state;
        }
        if ($this->shipping_postcode) {
            $address .= ' ' . $this->shipping_postcode;
        }
        if ($this->shipping_country) {
            $address .= ', ' . $this->shipping_country;
        }
        return $address;
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(BillingAddress::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class);
    }
} 