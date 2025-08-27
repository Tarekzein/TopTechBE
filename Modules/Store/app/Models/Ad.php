<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Carbon\Carbon;

class Ad extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'position',
        'title',
        'content',
        'image_url',
        'video_url',
        'link_url',
        'link_text',
        'dimensions',
        'styling',
        'is_active',
        'sort_order',
        'start_date',
        'end_date',
        'target_audience',
        'max_impressions',
        'current_impressions',
        'max_clicks',
        'current_clicks',
        'budget',
        'spent',
        'advertiser_name',
        'advertiser_email',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'styling' => 'array',
        'target_audience' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'current_impressions' => 'integer',
        'max_impressions' => 'integer',
        'current_clicks' => 'integer',
        'max_clicks' => 'integer',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
    ];

    /**
     * Get impressions relationship
     */
    public function impressions()
    {
        return $this->hasMany(AdImpression::class);
    }

    /**
     * Scope for active ads
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ads by position
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope for ads by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for currently valid ads (within date range)
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
     * Scope for ads visible to user role
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
     * Scope for ads with available impressions
     */
    public function scopeWithAvailableImpressions($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('max_impressions')
              ->orWhere('current_impressions', '<', $q->raw('max_impressions'));
        });
    }

    /**
     * Scope for ads with available clicks
     */
    public function scopeWithAvailableClicks($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('max_clicks')
              ->orWhere('current_clicks', '<', $q->raw('max_clicks'));
        });
    }

    /**
     * Scope for ads within budget
     */
    public function scopeWithinBudget($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('budget')
              ->orWhere('spent', '<', $q->raw('budget'));
        });
    }

    /**
     * Get click-through rate
     */
    public function getClickThroughRateAttribute()
    {
        if ($this->current_impressions === 0) {
            return 0;
        }

        return round(($this->current_clicks / $this->current_impressions) * 100, 2);
    }

    /**
     * Check if ad is currently valid
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
     * Check if ad has available impressions
     */
    public function hasAvailableImpressions()
    {
        return !$this->max_impressions || $this->current_impressions < $this->max_impressions;
    }

    /**
     * Check if ad has available clicks
     */
    public function hasAvailableClicks()
    {
        return !$this->max_clicks || $this->current_clicks < $this->max_clicks;
    }

    /**
     * Check if ad is within budget
     */
    public function isWithinBudget()
    {
        return !$this->budget || $this->spent < $this->budget;
    }

    /**
     * Record an impression
     */
    public function recordImpression(User $user = null, $sessionId = null, $request = null)
    {
        if (!$this->hasAvailableImpressions()) {
            return false;
        }

        $this->increment('current_impressions');

        // Record detailed impression
        $this->impressions()->create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'referrer' => $request?->header('referer'),
            'page_url' => $request?->fullUrl(),
            'action' => 'impression',
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'user_agent_parsed' => $this->parseUserAgent($request?->userAgent()),
            ],
        ]);

        return true;
    }

    /**
     * Record a click
     */
    public function recordClick(User $user = null, $sessionId = null, $request = null)
    {
        if (!$this->hasAvailableClicks()) {
            return false;
        }

        $this->increment('current_clicks');

        // Record detailed click
        $this->impressions()->create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'referrer' => $request?->header('referer'),
            'page_url' => $request?->fullUrl(),
            'action' => 'click',
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'user_agent_parsed' => $this->parseUserAgent($request?->userAgent()),
            ],
        ]);

        return true;
    }

    /**
     * Get dimensions as CSS string
     */
    public function getDimensionsCssAttribute()
    {
        if (!$this->dimensions) {
            return '';
        }

        $css = [];
        
        if (isset($this->dimensions['width'])) {
            $css[] = "width: {$this->dimensions['width']}px";
        }
        
        if (isset($this->dimensions['height'])) {
            $css[] = "height: {$this->dimensions['height']}px";
        }

        return implode('; ', $css);
    }

    /**
     * Get styling as CSS string
     */
    public function getStylingCssAttribute()
    {
        if (!$this->styling) {
            return '';
        }

        $css = [];
        
        foreach ($this->styling as $property => $value) {
            $css[] = "{$property}: {$value}";
        }

        return implode('; ', $css);
    }

    /**
     * Parse user agent for metadata
     */
    private function parseUserAgent($userAgent)
    {
        if (!$userAgent) {
            return null;
        }

        // Basic parsing - you can use a proper library like jenssegers/agent
        $isMobile = preg_match('/(android|iphone|ipad|mobile)/i', $userAgent);
        $isTablet = preg_match('/(ipad|tablet)/i', $userAgent);
        
        return [
            'is_mobile' => $isMobile,
            'is_tablet' => $isTablet,
            'is_desktop' => !$isMobile && !$isTablet,
        ];
    }
}
