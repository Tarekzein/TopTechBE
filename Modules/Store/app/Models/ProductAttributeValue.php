<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Cviebrock\EloquentSluggable\Sluggable;

class ProductAttributeValue extends Model
{
    use SoftDeletes, Sluggable;

    protected $fillable = [
        'attribute_id',
        'value',
        'slug',
        'color_code',
        'image',
        'display_order'
    ];

    protected $casts = [
        'display_order' => 'integer'
    ];

    /**
     * Get the attribute that owns this value.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'value'
            ]
        ];
    }
} 