<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\Services\WalletTransactionService;
use Modules\Store\Services\WalletService;
use Modules\Store\Models\Order;
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
            $request->validate([
                'status' => 'required|in:pending,completed,failed,cancelled',
                'reason' => 'nullable|string|max:255',
            ]);

            // Check if user is admin
            if (!Auth::user()->hasRole(['admin', 'super-admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update transaction status'
                ], 403);
            }

            $success = $this->transactionService->updateTransactionStatus(
                $transactionId,
                $request->status,
                $request->reason
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update transaction status'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction status',
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
}
