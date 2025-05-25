<?php

namespace Modules\Store\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'setting_id',
        'value',
        'locale',
    ];

    /**
     * Get the setting that owns this value
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }

    /**
     * Scope a query to only include values for a specific locale
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $locale
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLocale($query, ?string $locale)
    {
        return $query->where('locale', $locale);
    }
} 