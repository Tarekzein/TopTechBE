<?php

namespace Modules\Store\Services;

use App\Models\User;
use Modules\Store\Models\WalletTransaction;
use Modules\Store\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletTransactionService
{
    /**
     * Create a refund transaction
     */
    public function createRefundTransaction(Order $order, float $amount, ?string $reason = null): ?WalletTransaction
    {
        try {
            DB::beginTransaction();
            
            $wallet = $order->user->wallet;
            if (!$wallet) {
                // Create wallet if it doesn't exist
                $wallet = \Modules\Store\Models\Wallet::getOrCreateForUser($order->user);
            }
            
            $transaction = $wallet->transactions()->create([
                'amount' => $amount,
                'type' => 'refund',
                'status' => 'completed',
                'description' => "Refund for order #{$order->order_number}" . ($reason ? " - {$reason}" : ''),
                'reference' => 'REF_' . time() . '_' . uniqid(),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'refund_reason' => $reason,
                    'original_amount' => $order->total,
                    'refund_amount' => $amount,
                ],
            ]);
            
            // Update wallet balance
            $wallet->increment('balance', $amount);
            
            DB::commit();
            
            Log::info('Refund transaction created', [
                'transaction_id' => $transaction->id,
                'order_id' => $order->id,
                'amount' => $amount,
                'wallet_id' => $wallet->id,
            ]);
            
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create refund transaction', [
                'order_id' => $order->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
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
     * Create a deposit transaction
     */
    public function createDepositTransaction(User $user, float $amount, ?string $description = null, array $metadata = []): ?WalletTransaction
    {
        try {
            DB::beginTransaction();
            
            $wallet = $user->wallet;
            if (!$wallet) {
                $wallet = \Modules\Store\Models\Wallet::getOrCreateForUser($user);
            }
            
            $transaction = $wallet->transactions()->create([
                'amount' => $amount,
                'type' => 'deposit',
                'status' => 'completed',
                'description' => $description ?? 'Funds deposited',
                'reference' => 'DEP_' . time() . '_' . uniqid(),
                'metadata' => $metadata,
            ]);
            
            // Update wallet balance
            $wallet->increment('balance', $amount);
            
            DB::commit();
            
            Log::info('Deposit transaction created', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'wallet_id' => $wallet->id,
            ]);
            
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create deposit transaction', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create a withdrawal transaction
     */
    public function createWithdrawalTransaction(User $user, float $amount, ?string $description = null, array $metadata = []): ?WalletTransaction
    {
        try {
            DB::beginTransaction();
            
            $wallet = $user->wallet;
            if (!$wallet) {
                $wallet = \Modules\Store\Models\Wallet::getOrCreateForUser($user);
            }
            
            // Check if user has sufficient funds
            if ($wallet->balance < $amount) {
                DB::rollBack();
                Log::warning('Insufficient funds for withdrawal', [
                    'user_id' => $user->id,
                    'requested_amount' => $amount,
                    'wallet_balance' => $wallet->balance,
                ]);
                return null;
            }
            
            $transaction = $wallet->transactions()->create([
                'amount' => $amount,
                'type' => 'withdrawal',
                'status' => 'completed',
                'description' => $description ?? 'Funds withdrawn',
                'reference' => 'WTH_' . time() . '_' . uniqid(),
                'metadata' => $metadata,
            ]);
            
            // Update wallet balance
            $wallet->decrement('balance', $amount);
            
            DB::commit();
            
            Log::info('Withdrawal transaction created', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'wallet_id' => $wallet->id,
            ]);
            
            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create withdrawal transaction', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus(int $transactionId, string $status, ?string $reason = null): bool
    {
        try {
            $transaction = WalletTransaction::find($transactionId);
            
            if (!$transaction) {
                return false;
            }
            
            $transaction->update([
                'status' => $status,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'status_update_reason' => $reason,
                    'status_updated_at' => now(),
                ]),
            ]);
            
            Log::info('Transaction status updated', [
                'transaction_id' => $transactionId,
                'new_status' => $status,
                'reason' => $reason,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update transaction status', [
                'transaction_id' => $transactionId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStatistics(int $userId, string $period = 'month'): array
    {
        $wallet = \App\Models\User::find($userId)->wallet;
        
        if (!$wallet) {
            return [
                'total_transactions' => 0,
                'total_amount' => 0,
                'deposits_count' => 0,
                'withdrawals_count' => 0,
                'refunds_count' => 0,
                'deposits_amount' => 0,
                'withdrawals_amount' => 0,
                'refunds_amount' => 0,
            ];
        }
        
        $query = $wallet->transactions()->completed();
        
        // Filter by period
        switch ($period) {
            case 'week':
                $query->where('created_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->subMonth());
                break;
            case 'year':
                $query->where('created_at', '>=', now()->subYear());
                break;
        }
        
        $transactions = $query->get();
        
        return [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'deposits_count' => $transactions->where('type', 'deposit')->count(),
            'withdrawals_count' => $transactions->where('type', 'withdrawal')->count(),
            'refunds_count' => $transactions->where('type', 'refund')->count(),
            'deposits_amount' => $transactions->where('type', 'deposit')->sum('amount'),
            'withdrawals_amount' => $transactions->where('type', 'withdrawal')->sum('amount'),
            'refunds_amount' => $transactions->where('type', 'refund')->sum('amount'),
        ];
    }
}
