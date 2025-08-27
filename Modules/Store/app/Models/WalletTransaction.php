<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Store\Models\Wallet;

class WalletTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'status',
        'description',
        'reference',
        'metadata',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the user through wallet
     */
    public function user()
    {
        return $this->wallet->user;
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for deposits
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    /**
     * Scope for withdrawals
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdrawal');
    }

    /**
     * Scope for refunds
     */
    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = in_array($this->type, ['deposit', 'refund']) ? '+' : '-';
        return $sign . number_format($this->amount, 2) . ' ' . $this->wallet->currency;
    }

    /**
     * Get transaction type label
     */
    public function getTypeLabelAttribute(): string
    {
        return ucfirst($this->type);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
