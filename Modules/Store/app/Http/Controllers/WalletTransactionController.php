<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\Services\WalletTransactionService;
use Modules\Store\Services\WalletService;
use Modules\Store\Models\Order;
use Modules\Store\Models\WalletTransaction ;
use Illuminate\Support\Facades\Auth;

class WalletTransactionController extends Controller
{
    protected $transactionService;
    protected $walletService;

    public function __construct(WalletTransactionService $transactionService, WalletService $walletService)
    {
        $this->transactionService = $transactionService;
        $this->walletService = $walletService;
    }

    /**
     * Process refund for an order
     */
    public function processRefund(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'refund_amount' => 'nullable|numeric|min:0.01',
                'reason' => 'nullable|string|max:255',
            ]);

            $order = Order::findOrFail($request->order_id);
            
            // Check if user owns this order or is admin
            if ($order->user_id !== Auth::id() && !Auth::user()->hasRole(['admin', 'super-admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to process refund for this order'
                ], 403);
            }

            // Check if order is already refunded
            if ($order->status === 'refunded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already refunded'
                ], 400);
            }

            $refundAmount = $request->refund_amount ?? $order->total;
            
            // Validate refund amount
            if ($refundAmount > $order->total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund amount cannot exceed order total'
                ], 400);
            }

            // Use manual refund to avoid observer loop
            $transaction = $this->walletService->processRefundManually(
                $order,
                $refundAmount,
                $request->reason
            );

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process refund'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => [
                    'transaction' => $transaction,
                    'order' => $order->fresh(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => 'nullable|in:week,month,year',
            ]);

            $user = Auth::user();
            $period = $request->get('period', 'month');
            $statistics = $this->transactionService->getTransactionStatistics($user->id, $period);

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transaction statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update transaction status (admin only)
     */
    public function updateStatus(Request $request, int $transactionId): JsonResponse
{
    try {
        // 🔹 Validate input
        $validator = \Validator::make($request->all(), [
            'status' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(), // هنا يرجع كل الحقول اللي فشلت
            ], 422);
        }

        // 🔹 Check admin role
        if (!Auth::user()->hasRole(['admin', 'super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only admin or super-admin can update transaction status'
            ], 403);
        }

        // 🔹 Update transaction
        $success = $this->transactionService->updateTransactionStatus(
            $transactionId,
            $request->status,
            $request->reason
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction update failed: Invalid transaction ID or status'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction status updated successfully'
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Transaction not found',
            'error' => $e->getMessage()
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Unexpected error while updating transaction status',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Get refund history for user
     */
    public function getRefundHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 20);
            
            $refunds = $user->wallet?->transactions()
                ->where('type', 'refund')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            if (!$refunds) {
                $refunds = collect([])->paginate($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => $refunds
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get refund history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction by ID
     */
    public function getTransaction(int $transactionId): JsonResponse
    {
        try {
            $user = Auth::user();
            $transaction = \Modules\Store\Models\WalletTransaction::with(['wallet.user'])
                ->find($transactionId);

            if (!$transaction || $transaction->wallet->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }
     public function getAllTransactions(Request $request)
    {
        $filters = $request->only(['description','type','status','date_from','date_to','min_amount','max_amount','page','per_page']);
        $transactions = WalletTransaction::query();

        if (!empty($filters['description'])) {
            $transactions->where('description', 'like', '%' . $filters['description'] . '%');
        }

        if ($filters['type'] ?? false) {
            $transactions->where('type', $filters['type']);
        }
        if ($filters['status'] ?? false) {
            $transactions->where('status', $filters['status']);
        }
        if ($filters['date_from'] ?? false) {
            $transactions->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] ?? false) {
            $transactions->whereDate('created_at', '<=', $filters['date_to']);
        }
        if ($filters['min_amount'] ?? false) {
            $transactions->where('amount', '>=', $filters['min_amount']);
        }
        if ($filters['max_amount'] ?? false) {
            $transactions->where('amount', '<=', $filters['max_amount']);
        }

        $perPage = $filters['per_page'] ?? 10;
        $data = $transactions->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // 🟢 Get all refund requests
    public function getAllRefunds(Request $request)
    {
        $filters = $request;
        $refunds = Order::where('status', 'refunded');

        

        $perPage = $filters['per_page'] ?? 20;
        $data = $refunds->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // 🟢 Update transaction details (admin override)
    public function updateTransaction(Request $request, WalletTransaction $transaction)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $transaction->update($request->only(['amount', 'status', 'metadata']));

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    // 🟢 Admin analytics / summary
    public function getAdminAnalytics()
{
    try {
        if (!Auth::user()->hasRole(['admin', 'super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only admin can view analytics'
            ], 403);
        }

        // Join wallets to get currency and group by currency + type
        $analytics = \Modules\Store\Models\WalletTransaction::query()
            ->selectRaw('wallets.currency, wallet_transactions.type, SUM(wallet_transactions.amount) as total_amount')
            ->join('wallets', 'wallet_transactions.wallet_id', '=', 'wallets.id')
            ->groupBy('wallets.currency', 'wallet_transactions.type')
            ->get();

        // Restructure result to be grouped per currency
        $result = [];
        foreach ($analytics as $row) {
            $currency = $row->currency;
            if (!isset($result[$currency])) {
                $result[$currency] = [
                    'currency' => $currency,
                    'total_added' => 0,
                    'total_withdrawn' => 0,
                    'total_refunded' => 0,
                ];
            }

            if ($row->type === 'deposit') {
                $result[$currency]['total_added'] = $row->total_amount;
            } elseif ($row->type === 'withdrawal') {
                $result[$currency]['total_withdrawn'] = $row->total_amount;
            } elseif ($row->type === 'refund') {
                $result[$currency]['total_refunded'] = $row->total_amount;
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_values($result)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get analytics',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
 * 🟢 Get all refund history (admin only)
 */
public function getAllRefundHistory(Request $request): JsonResponse
{
    try {
        if (!Auth::user()->hasRole(['admin', 'super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only admin can view refund history'
            ], 403);
        }

        $perPage = $request->get('per_page', 20);

        $refunds = WalletTransaction::with(['wallet.user'])
            ->where('type', 'refund')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $refunds
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get refund history',
            'error' => $e->getMessage()
        ], 500);
    }
}
/**
 * Get transactions by wallet ID
 */
public function getTransactionsByWallet(Request $request, int $walletId): JsonResponse
{
    try {
        $user = Auth::user();

        // Get wallet with user relation
        $wallet = \Modules\Store\Models\Wallet::with('user')->findOrFail($walletId);

        if ($wallet->user_id !== $user->id && !$user->hasRole(['admin', 'super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view transactions for this wallet'
            ], 403);
        }

        $filters = $request->only(['type', 'status', 'date_from', 'date_to', 'min_amount', 'max_amount']);
        $perPage = $request->get('per_page', 20);

        $transactions = WalletTransaction::where('wallet_id', $walletId);

        // Apply filters
        if ($filters['type'] ?? false) {
            $transactions->where('type', $filters['type']);
        }
        if ($filters['status'] ?? false) {
            $transactions->where('status', $filters['status']);
        }
        if ($filters['date_from'] ?? false) {
            $transactions->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] ?? false) {
            $transactions->whereDate('created_at', '<=', $filters['date_to']);
        }
        if ($filters['min_amount'] ?? false) {
            $transactions->where('amount', '>=', $filters['min_amount']);
        }
        if ($filters['max_amount'] ?? false) {
            $transactions->where('amount', '<=', $filters['max_amount']);
        }

        $data = $transactions->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'wallet' => $wallet,   // ✅ include wallet with user
            'user'   => $wallet->user, // ✅ explicitly return wallet owner
            'transactions' => $data,
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Wallet not found'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get wallet transactions',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
