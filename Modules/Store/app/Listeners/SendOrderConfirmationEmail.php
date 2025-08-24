<?php

namespace Modules\Store\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Store\Events\OrderCreated;
use Modules\Store\Emails\OrderConfirmationEmail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    public function handle(OrderCreated $event)
    {
        $order = $event->order;
        
        // Send email to customer
        Mail::to($order->user->email)
            ->send(new OrderConfirmationEmail($order));
    }
}