<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Banner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'position',
        'title',
        'subtitle',
        'description',
        'button_text',
        'button_url',
        'image_url',
        'background_start_color',
        'background_end_color',
        'text_color',
        'settings',
        'is_active',
        'sort_order',
        'start_date',
        'end_date',
        'target_audience',
        'impressions',
        'clicks',
    ];

    protected $casts = [
        'settings' => 'array',
        'target_audience' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'impressions' => 'integer',
        'clicks' => 'integer',
    ];

    /**
     * Scope for active banners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for banners by position
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope for banners by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for currently valid banners (within date range)
     */
    public function scopeCurrentlyValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('start_date')
              ->orWhere('start_date', '<=', now());
        })->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        });
    }

    /**
     * Scope for banners visible to user role
     */
    public function scopeForUserRole($query, $userRole = null)
    {
        if (!$userRole) {
            return $query->whereNull('target_audience')
                        ->orWhereJsonLength('target_audience', 0);
        }

        return $query->where(function ($q) use ($userRole) {
            $q->whereNull('target_audience')
              ->orWhereJsonLength('target_audience', 0)
              ->orWhereJsonContains('target_audience', $userRole);
        });
    }

    /**
     * Get click-through rate
     */
    public function getClickThroughRateAttribute()
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    /**
     * Check if banner is currently valid
     */
    public function isCurrentlyValid()
    {
        $now = now();
        
        if ($this->start_date && $this->start_date->gt($now)) {
            return false;
        }
        
        if ($this->end_date && $this->end_date->lt($now)) {
            return false;
        }
        
        return true;
    }

    /**
     * Increment impressions
     */
    public function incrementImpressions()
    {
        $this->increment('impressions');
    }

    /**
     * Increment clicks
     */
    public function incrementClicks()
    {
        $this->increment('clicks');
    }

    /**
     * Get background style for gradient banners
     */
    public function getBackgroundStyleAttribute()
    {
        if ($this->background_start_color && $this->background_end_color) {
            return "linear-gradient(135deg, {$this->background_start_color} 0%, {$this->background_end_color} 100%)";
        }
        
        return $this->background_start_color ?: '#ffffff';
    }

    /**
     * Get settings with defaults
     */
    public function getSettingsWithDefaultsAttribute()
    {
        $defaults = [
            'animation' => 'fade',
            'duration' => 5000,
            'auto_play' => true,
            'show_indicators' => true,
            'responsive' => true,
        ];

        return array_merge($defaults, $this->settings ?? []);
    }
}
