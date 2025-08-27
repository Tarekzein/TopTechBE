<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\Services\WalletService;
use Modules\Store\Repositories\WalletRepository;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected $walletService;
    protected $walletRepository;

    public function __construct(WalletService $walletService, WalletRepository $walletRepository)
    {
        $this->walletService = $walletService;
        $this->walletRepository = $walletRepository;
    }

    /**
     * Get user's wallet information
     */
    public function getWallet(): JsonResponse
    {
        try {
            $user = Auth::user();
            $wallet = $this->walletService->getOrCreateWallet($user);
            $summary = $this->walletService->getWalletSummary($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'wallet' => $wallet,
                    'summary' => $summary,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get wallet information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet transactions
     */
    public function getTransactions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 20);
            $transactions = $this->walletRepository->getWalletTransactions($user->id, $perPage);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $period = $request->get('period', 'month');
            $statistics = $this->walletService->getWalletSummary($user);

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get wallet statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add funds to wallet (admin only)
     */
    public function addFunds(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:255',
            ]);

            $user = User::findOrFail($request->user_id);
            $transaction = $this->walletService->addFunds(
                $user,
                $request->amount,
                $request->description
            );

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add funds to wallet'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Funds added successfully',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add funds',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Withdraw funds from wallet
     */
    public function withdrawFunds(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:255',
            ]);

            $user = Auth::user();
            $transaction = $this->walletService->deductFunds(
                $user,
                $request->amount,
                $request->description
            );

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient funds or failed to withdraw'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Funds withdrawn successfully',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to withdraw funds',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search transactions
     */
    public function searchTransactions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'required|string|min:2',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $user = Auth::user();
            $perPage = $request->get('per_page', 20);
            $transactions = $this->walletRepository->searchTransactions(
                $user->id,
                $request->search,
                $perPage
            );

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions by date range
     */
    public function getTransactionsByDateRange(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $user = Auth::user();
            $transactions = $this->walletRepository->getTransactionsByDateRange(
                $user->id,
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transactions by date range',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction details
     */
    public function getTransactionDetails(int $transactionId): JsonResponse
    {
        try {
            $user = Auth::user();
            $transaction = $this->walletRepository->getTransactionWithDetails($transactionId);

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
                'message' => 'Failed to get transaction details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
