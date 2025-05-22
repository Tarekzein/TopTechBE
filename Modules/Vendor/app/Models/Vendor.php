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
        'corporate_name',
        'tax_number',
        'device_type',
        'with_components',
        'is_active',
        'is_verified',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'corporate_name'
            ]
        ];
    }

    public function settings()
    {
        return $this->hasMany(VendorSetting::class);
    }

    public function getSetting($key)
    {
        return $this->settings()->where('key', $key)->first();
    }

    public function components()
    {
        return $this->hasMany(VendorComponent::class);
    }

    public function getComponent($name)
    {
        return $this->components()->where('name', $name)->first();
    }
}
