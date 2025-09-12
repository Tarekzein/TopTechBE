<?php

namespace Modules\Store\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Store\Events\RefundProcessed;
use Modules\Common\Notifications\GenericNotification;

class SendRefundNotification implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param \Modules\Store\Events\RefundProcessed $event
     * @return void
     */
    public function handle(RefundProcessed $event): void
    {
        $order = $event->order;

        $content = "A refund of {$event->refundAmount} has been processed "
                 . "for your order #{$order->order_number}.";

        $order->user->notify(
            new GenericNotification(
                type: 'refund',
                content: $content,
                notificationId: $order->id,
                isAlert: true,
                fcmData: [
                    'order_id' => $order->id,
                    'refund_amount' => $event->refundAmount,
                ]
            )
        );
    }
}
