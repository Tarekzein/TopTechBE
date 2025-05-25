<?php

namespace Modules\Store\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'position',
        'decimal_places',
        'decimal_separator',
        'thousands_separator',
        'is_active',
        'is_default',
        'exchange_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'decimal_places' => 'integer',
        'exchange_rate' => 'decimal:6',
    ];

    /**
     * Format a number according to the currency's settings
     *
     * @param float $amount
     * @return string
     */
    public function format(float $amount): string
    {
        $formatted = number_format(
            $amount,
            $this->decimal_places,
            $this->decimal_separator,
            $this->thousands_separator
        );

        return $this->position === 'before'
            ? $this->symbol . $formatted
            : $formatted . $this->symbol;
    }

    /**
     * Convert amount from this currency to another
     *
     * @param float $amount
     * @param Currency $targetCurrency
     * @return float
     */
    public function convertTo(float $amount, Currency $targetCurrency): float
    {
        if ($this->id === $targetCurrency->id) {
            return $amount;
        }

        // Convert to default currency first, then to target currency
        $amountInDefault = $amount / $this->exchange_rate;
        return $amountInDefault * $targetCurrency->exchange_rate;
    }

    /**
     * Scope a query to only include active currencies
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default currency
     *
     * @return self|null
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }
} 