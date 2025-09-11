<?php

namespace Modules\Store\Listeners;

use Modules\Store\Events\PaymentStatusUpdated;
use Modules\Common\Notifications\GenericNotification;

class SendPaymentStatusNotification
{
    /**
     * Handle the event.
     *
     * @param \Modules\Store\Events\PaymentStatusUpdated $event
     * @return void
     */
    public function handle(PaymentStatusUpdated $event): void
    {
        $order = $event->order;

        // 🔍 Log for debugging
        \Log::info("📢 Listener fired 123 for payment status update", [
            
        ]);

        $content = "Your payment status for order #{$order->order_number} has been updated "
                 . "from {$event->oldStatus} to {$event->newStatus}.";

        $order->user->notify(
            new GenericNotification(
                type: 'payment',
                content: $content,
                notificationId: $order->id,
                isAlert: true,
                fcmData: [
                    'order_id'       => $order->id,
                    'payment_status' => $event->newStatus,
                ]
            )
        );
    }
}
