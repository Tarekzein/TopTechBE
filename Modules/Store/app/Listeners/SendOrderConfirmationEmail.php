<?php

namespace Modules\Store\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Store\Events\OrderCreated;
use Modules\Common\Notifications\GenericNotification;

class SendOrderConfirmationEmail implements ShouldQueue
{
    public function handle(OrderCreated $event)
    {
        $order = $event->order;

        // نص الإشعار
        $content = "Thank you for your order #{$order->order_number}. "
                 . "We are processing it and will notify you once it’s ready.";

        // إرسال إشعار عام (Mail + FCM + DB)
        $order->user->notify(
            new GenericNotification(
                type: 'order',
                content: $content,
                notificationId: $order->id, // مرجع الطلب
                isAlert: false,
                fcmData: [
                    'order_id' => $order->id,
                    'status'   => $order->status,
                    'total'    => $order->total,
                ]
            )
        );
    }
}
