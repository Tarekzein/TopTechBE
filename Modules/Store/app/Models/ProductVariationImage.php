<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariationImage extends Model
{
    protected $fillable = [
        'variation_id',
        'image',
        'display_order'
    ];

    protected $casts = [
        'display_order' => 'integer'
    ];

    /**
     * Get the variation that owns this image.
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }
} 