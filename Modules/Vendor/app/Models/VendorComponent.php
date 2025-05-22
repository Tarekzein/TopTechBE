<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Vendor\Database\Factories\VendorComponentFactory;

class VendorComponent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'vendor_id',
        'name',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // protected static function newFactory(): VendorComponentFactory
    // {
    //     // return VendorComponentFactory::new();
    // }
}
