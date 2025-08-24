<?php

namespace Modules\Store\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Store\Events\OrderStatusUpdated;
use Modules\Store\Emails\OrderStatusUpdateEmail;

class SendOrderStatusUpdateEmail implements ShouldQueue
{
    public function handle(OrderStatusUpdated $event)
    {
        $order = $event->order;
        
        // Send email to customer
        Mail::to($order->user->email)
            ->send(new OrderStatusUpdateEmail(
                $order, 
                $event->oldStatus, 
                $event->newStatus
            ));
    }
}