<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Modules\Store\Models\WalletTransaction;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'status',
        'type',
        'description',
        'reference',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get or create a wallet for a user
     */
    public static function getOrCreateForUser(User $user, string $currency = 'EGP'): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id, 'type' => 'primary'],
            [
                'balance' => 0,
                'currency' => $currency,
                'status' => 'active',
                'description' => 'Primary wallet for user',
            ]
        );
    }

    /**
     * Add funds to wallet
     */
    public function addFunds(float $amount, ?string $description = null, array $metadata = []): WalletTransaction
    {
        $this->increment('balance', $amount);
        
        return $this->transactions()->create([
            'amount' => $amount,
            'type' => 'deposit',
            'status' => 'completed',
            'description' => $description ?? 'Funds added to wallet',
            'reference' => 'WAL_' . time() . '_' . uniqid(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Deduct funds from wallet
     */
    public function deductFunds(float $amount, ?string $description = null, array $metadata = []): ?WalletTransaction
    {
        if ($this->balance < $amount) {
            return null; // Insufficient funds
        }

        $this->decrement('balance', $amount);
        
        return $this->transactions()->create([
            'amount' => $amount,
            'type' => 'withdrawal',
            'status' => 'completed',
            'description' => $description ?? 'Funds deducted from wallet',
            'reference' => 'WAL_' . time() . '_' . uniqid(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Check if wallet has sufficient funds
     */
    public function hasSufficientFunds(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2) . ' ' . $this->currency;
    }
}
