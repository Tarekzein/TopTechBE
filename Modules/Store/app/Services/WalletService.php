<?php

namespace Modules\Store\Services;

use App\Models\User;
use Modules\Store\Models\Wallet;
use Modules\Store\Models\WalletTransaction;
use Modules\Store\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * Get or create wallet for user
     */
    public function getOrCreateWallet(User $user, string $currency = 'EGP'): Wallet
    {
        return Wallet::getOrCreateForUser($user, $currency);
    }

    /**
     * Add funds to user's wallet
     */
    public function addFunds(User $user, float $amount, ?string $description = null, array $metadata = []): ?WalletTransaction
    {
        try {
            DB::beginTransaction();
            
            $wallet = $this->getOrCreateWallet($user);
            $transaction = $wallet->addFunds($amount, $description, $metadata);
            
            DB::commit();
            
            Log::info('Funds added to wallet', [
                'user_id' => $user->id,
                'amount' => $amount,
                'wallet_id' => $wallet->id,
                'transaction_id' => $transaction->id
            ]);
            
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add funds to wallet', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Deduct funds from user's wallet
     */
    public function deductFunds(User $user, float $amount, ?string $description = null, array $metadata = []): ?WalletTransaction
    {
        try {
            DB::beginTransaction();
            
            $wallet = $this->getOrCreateWallet($user);
            $transaction = $wallet->deductFunds($amount, $description, $metadata);
            
            if (!$transaction) {
                DB::rollBack();
                Log::warning('Insufficient funds in wallet', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'wallet_balance' => $wallet->balance
                ]);
                return null;
            }
            
            DB::commit();
            
            Log::info('Funds deducted from wallet', [
                'user_id' => $user->id,
                'amount' => $amount,
                'wallet_id' => $wallet->id,
                'transaction_id' => $transaction->id
            ]);
            
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to deduct funds from wallet', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Process refund and add to wallet
     */
    public function processRefund(Order $order, ?float $refundAmount = null, ?string $reason = null): ?WalletTransaction
    {
        try {
            DB::beginTransaction();
            
            // If no refund amount specified, refund the full order amount
            $refundAmount = $refundAmount ?? $order->total;
            
            // Validate refund amount
            if ($refundAmount > $order->total) {
                throw new \Exception('Refund amount cannot exceed order total');
            }
            
            // Add refund to user's wallet
            $description = "Refund for order #{$order->order_number}";
            if ($reason) {
                $description .= " - {$reason}";
            }
            
            $metadata = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'refund_reason' => $reason,
                'original_amount' => $order->total,
                'refund_amount' => $refundAmount,
            ];
            
            $transaction = $this->addFunds($order->user, $refundAmount, $description, $metadata);
            
            if (!$transaction) {
                DB::rollBack();
                return null;
            }
            
            // Update order metadata only (status already changed by the caller)
            $order->update([
                'refunded_at' => now(),
                'meta_data' => array_merge($order->meta_data ?? [], [
                    'refund_transaction_id' => $transaction->id,
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $reason,
                    'refund_processed' => true,
                ])
            ]);
            
            DB::commit();
            
            Log::info('Refund processed successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'refund_amount' => $refundAmount,
                'transaction_id' => $transaction->id
            ]);
            
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process refund', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'refund_amount' => $refundAmount,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Process refund manually (without triggering observer)
     */
    public function processRefundManually(Order $order, ?float $refundAmount = null, ?string $reason = null): ?WalletTransaction
    {
        try {
            DB::beginTransaction();
            
            // If no refund amount specified, refund the full order amount
            $refundAmount = $refundAmount ?? $order->total;
            
            // Validate refund amount
            if ($refundAmount > $order->total) {
                throw new \Exception('Refund amount cannot exceed order total');
            }
            
            // Add refund to user's wallet
            $description = "Refund for order #{$order->order_number}";
            if ($reason) {
                $description .= " - {$reason}";
            }
            
            $metadata = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'refund_reason' => $reason,
                'original_amount' => $order->total,
                'refund_amount' => $refundAmount,
            ];
            
            $transaction = $this->addFunds($order->user, $refundAmount, $description, $metadata);
            
            if (!$transaction) {
                DB::rollBack();
                return null;
            }
            
            // Update order status and metadata
            $order->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'meta_data' => array_merge($order->meta_data ?? [], [
                    'refund_transaction_id' => $transaction->id,
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $reason,
                    'refund_processed' => true,
                ])
            ]);
            
            DB::commit();
            
            Log::info('Manual refund processed successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
                'refund_amount' => $refundAmount,
                'transaction_id' => $transaction->id
            ]);
            
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process manual refund', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'refund_amount' => $refundAmount,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get wallet balance for user
     */
    public function getWalletBalance(User $user): float
    {
        $wallet = $this->getOrCreateWallet($user);
        return $wallet->balance;
    }

    /**
     * Check if user has sufficient funds
     */
    public function hasSufficientFunds(User $user, float $amount): bool
    {
        $wallet = $this->getOrCreateWallet($user);
        return $wallet->hasSufficientFunds($amount);
    }

    /**
     * Get wallet transactions for user
     */
    public function getWalletTransactions(User $user, int $limit = 20)
    {
        $wallet = $this->getOrCreateWallet($user);
        return $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get wallet summary for user
     */
    public function getWalletSummary(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user);
        
        $transactions = $wallet->transactions();
        
        return [
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'total_deposits' => $transactions->deposits()->completed()->sum('amount'),
            'total_withdrawals' => $transactions->withdrawals()->completed()->sum('amount'),
            'total_refunds' => $transactions->refunds()->completed()->sum('amount'),
            'transaction_count' => $transactions->count(),
            'last_transaction' => $transactions->latest()->first(),
        ];
    }
}
