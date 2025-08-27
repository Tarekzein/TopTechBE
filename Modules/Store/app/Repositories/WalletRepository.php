<?php

namespace Modules\Store\Repositories;

use App\Models\User;
use Modules\Store\Models\Wallet;
use Modules\Store\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WalletRepository
{
    /**
     * Get wallet by user ID
     */
    public function getWalletByUserId(int $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('type', 'primary')
            ->first();
    }

    /**
     * Get wallet with transactions
     */
    public function getWalletWithTransactions(int $userId, int $limit = 20): ?Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('type', 'primary')
            ->with(['transactions' => function ($query) use ($limit) {
                $query->orderBy('created_at', 'desc')->limit($limit);
            }])
            ->first();
    }

    /**
     * Get wallet transactions with pagination
     */
    public function getWalletTransactions(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        $wallet = $this->getWalletByUserId($userId);
        
        if (!$wallet) {
            return new LengthAwarePaginator([], 0, $perPage);
        }
        
        return $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get wallet transactions by type
     */
    public function getTransactionsByType(int $userId, string $type, int $limit = 20): Collection
    {
        $wallet = $this->getWalletByUserId($userId);
        
        if (!$wallet) {
            return new Collection();
        }
        
        return $wallet->transactions()
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get wallet statistics
     */
    public function getWalletStatistics(int $userId): array
    {
        $wallet = $this->getWalletByUserId($userId);
        
        if (!$wallet) {
            return [
                'balance' => 0,
                'total_deposits' => 0,
                'total_withdrawals' => 0,
                'total_refunds' => 0,
                'transaction_count' => 0,
            ];
        }
        
        $transactions = $wallet->transactions();
        
        return [
            'balance' => $wallet->balance,
            'total_deposits' => $transactions->deposits()->completed()->sum('amount'),
            'total_withdrawals' => $transactions->withdrawals()->completed()->sum('amount'),
            'total_refunds' => $transactions->refunds()->completed()->sum('amount'),
            'transaction_count' => $transactions->count(),
        ];
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions(int $userId, int $limit = 10): Collection
    {
        $wallet = $this->getWalletByUserId($userId);
        
        if (!$wallet) {
            return new Collection();
        }
        
        return $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search transactions
     */
    public function searchTransactions(int $userId, string $search, int $perPage = 20): LengthAwarePaginator
    {
        $wallet = $this->getWalletByUserId($userId);
        
        if (!$wallet) {
            return new LengthAwarePaginator([], 0, $perPage);
        }
        
        return $wallet->transactions()
            ->where(function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get transactions by date range
     */
    public function getTransactionsByDateRange(int $userId, string $startDate, string $endDate): Collection
    {
        $wallet = $this->getWalletByUserId($userId);
        
        if (!$wallet) {
            return new Collection();
        }
        
        return $wallet->transactions()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get transaction by ID
     */
    public function getTransactionById(int $transactionId): ?WalletTransaction
    {
        return WalletTransaction::find($transactionId);
    }

    /**
     * Get transaction with wallet and user
     */
    public function getTransactionWithDetails(int $transactionId): ?WalletTransaction
    {
        return WalletTransaction::with(['wallet.user'])
            ->find($transactionId);
    }
}
