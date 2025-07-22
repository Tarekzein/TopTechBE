<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Store\Database\Factories\PromoCodeUsageFactory;

class PromoCodeUsage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id', 'promocode_id', 'order_id', 'used_at'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
    public function promocode()
    {
        return $this->belongsTo(\Modules\Store\Models\PromoCode::class, 'promocode_id');
    }
    public function order()
    {
        return $this->belongsTo(\Modules\Store\Models\Order::class, 'order_id');
    }

    // protected static function newFactory(): PromoCodeUsageFactory
    // {
    //     // return PromoCodeUsageFactory::new();
    // }
}
