<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Store\Database\Factories\PromoCodeFactory;

class PromoCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code', 'type', 'amount', 'usage_limit', 'usage_limit_per_user', 'used',
        'min_order_total', 'max_discount', 'starts_at', 'expires_at', 'is_active'
    ];

    // protected static function newFactory(): PromoCodeFactory
    // {
    //     // return PromoCodeFactory::new();
    // }

    /**
     * Check if the promocode is currently active and valid for use.
     */
    public function isActive(): bool
    {
        if (!$this->is_active) return false;
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->expires_at && $now->gt($this->expires_at)) return false;
        if ($this->usage_limit !== null && $this->used >= $this->usage_limit) return false;
        return true;
    }

    /**
     * Check if the promocode can be used by a specific user.
     */
    public function canBeUsedBy($userId): bool
    {
        if (!$this->isActive()) return false;
        if ($this->usage_limit_per_user !== null) {
            $userUses = \Modules\Store\Models\PromoCodeUsage::where('user_id', $userId)
                ->where('promocode_id', $this->id)
                ->count();
            if ($userUses >= $this->usage_limit_per_user) return false;
        }
        return true;
    }

    /**
     * Calculate the discount for a given order total.
     */
    public function calculateDiscount($orderTotal): float
    {
        if ($this->type === 'fixed') {
            $discount = min($this->amount, $orderTotal);
        } else { // percent
            $discount = $orderTotal * ($this->amount / 100);
        }
        if ($this->max_discount !== null) {
            $discount = min($discount, $this->max_discount);
        }
        return round($discount, 2);
    }
}
