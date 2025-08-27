<?php

namespace Modules\Store\Observers;

use Modules\Store\Models\Order;
use Modules\Store\Services\WalletService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if order status changed to refunded and hasn't been processed yet
        if ($order->wasChanged('status') && 
            $order->status === 'refunded' && 
            !($order->meta_data['refund_processed'] ?? false)) {
            $this->handleOrderRefund($order);
        }
    }

    /**
     * Handle order refund
     */
    protected function handleOrderRefund(Order $order): void
    {
        try {
            // Double-check if refund was already processed (race condition protection)
            if ($order->meta_data && isset($order->meta_data['refund_processed']) && $order->meta_data['refund_processed']) {
                Log::info('Refund already processed for order', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
                return;
            }

            // Process refund to wallet
            $transaction = $this->walletService->processRefund($order);

            if ($transaction) {
                Log::info('Order refund processed successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'refund_amount' => $order->total,
                    'transaction_id' => $transaction->id
                ]);
            } else {
                Log::error('Failed to process order refund', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing order refund', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage()
            ]);
        }
    }
}
