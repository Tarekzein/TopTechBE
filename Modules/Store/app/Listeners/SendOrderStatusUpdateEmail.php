<?php

namespace Modules\Store\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Store\Events\OrderStatusUpdated;
use Modules\Store\Events\PaymentStatusUpdated;
use Modules\Common\Notifications\GenericNotification;

class SendOrderStatusUpdateEmail implements ShouldQueue
{
    public function handle(OrderStatusUpdated $event)
    {
        $order = $event->order;

        // ✅ تحقق: لو الطلب استُلم وطريقة الدفع COD → عدّل الدفع Paid
        if (
            strtolower($event->newStatus) === 'delivered'
            && strtolower($order->payment_method) === 'cash_on_delivery'
            && strtolower($order->payment_status) !== 'paid'
        ) {
            $oldPaymentStatus = $order->payment_status;

            // تحديث حالة الدفع
            $order->update([
                'payment_status' => 'paid',
            ]);

            // 🔔 اطلاق Event جديد
            event(new PaymentStatusUpdated($order, $oldPaymentStatus, 'paid'));
        }

        // رسالة الإشعار
        $content = "Your order #{$order->order_number} status has changed from "
                 . ucfirst($event->oldStatus)
                 . " to "
                 . ucfirst($event->newStatus);

        // إرسال إشعار عام (Mail + FCM + DB)
        $order->user->notify(
            new GenericNotification(
                type: 'order',
                content: $content,
                notificationId: $order->id, // ID الطلب كمرجع
                isAlert: true,
                fcmData: [
                    'order_id' => $order->id,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                ]
            )
        );
    }
}
