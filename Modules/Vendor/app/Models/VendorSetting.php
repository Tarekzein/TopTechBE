<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Vendor\Database\Factories\VendorSettingFactory;

class VendorSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'vendor_id',
        'key',
        'value',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    // protected static function newFactory(): VendorSettingFactory
    // {
    //     // return VendorSettingFactory::new();
    // }
}
