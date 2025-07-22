<?php

namespace Modules\Store\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Store\Models\PromoCode;
use Carbon\Carbon;

class PromoCodeSeeder extends Seeder
{
    public function run()
    {
        // PromoCode::truncate();
        PromoCode::query()->delete();

        PromoCode::create([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'amount' => 10,
            'usage_limit' => 100,
            'usage_limit_per_user' => 1,
            'min_order_total' => 50,
            'max_discount' => 100,
            'starts_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addMonth(),
            'is_active' => true,
        ]);

        PromoCode::create([
            'code' => 'FIXED20',
            'type' => 'fixed',
            'amount' => 20,
            'usage_limit' => 50,
            'usage_limit_per_user' => null,
            'min_order_total' => 100,
            'max_discount' => null,
            'starts_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addMonth(),
            'is_active' => true,
        ]);

        PromoCode::create([
            'code' => 'SUMMER25',
            'type' => 'percent',
            'amount' => 25,
            'usage_limit' => null, // unlimited global
            'usage_limit_per_user' => 2,
            'min_order_total' => 0,
            'max_discount' => 50,
            'starts_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addMonths(2),
            'is_active' => true,
        ]);

        PromoCode::create([
            'code' => 'UNLIMITED5',
            'type' => 'percent',
            'amount' => 5,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'min_order_total' => 0,
            'max_discount' => null,
            'starts_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addYear(),
            'is_active' => true,
        ]);
    }
}
