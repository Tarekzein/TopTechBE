<?php

namespace Modules\Vendor\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Vendor\Database\Factories\VendorFactory;

class Vendor extends Model
{
    use HasFactory;
    use Sluggable;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'store_name',
        'slug',
        'description',
        'address',
        'logo',
        'banner',
        'is_active',
        'is_verified',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'store_name'
            ]
        ];
    }
}
