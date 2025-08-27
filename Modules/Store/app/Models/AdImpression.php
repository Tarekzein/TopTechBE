<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class AdImpression extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_id',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'referrer',
        'page_url',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the ad that owns the impression
     */
    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Get the user that made the impression
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for impressions by action
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for impressions by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for impressions by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for impressions by session
     */
    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
