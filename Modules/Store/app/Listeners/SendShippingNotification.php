<?php

namespace Modules\Store\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Store\Events\ShippingUpdated;
use Modules\Common\Notifications\GenericNotification;

class SendShippingNotification implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param \Modules\Store\Events\ShippingUpdated $event
     * @return void
     */
    public function handle(ShippingUpdated $event): void
    {
        $order = $event->order;
        $data = $event->shippingData;

        $content = "Your order #{$order->order_number} shipping information has been updated. "
                 . (!empty($data['shipping_tracking_number'])
                    ? "Tracking Number: {$data['shipping_tracking_number']}"
                    : "");

        $order->user->notify(
            new GenericNotification(
                type: 'shipping',
                content: $content,
                notificationId: $order->id,
                isAlert: true,
                fcmData: array_merge([
                    'order_id' => $order->id,
                ], $data)
            )
        );
    }
}
