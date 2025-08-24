<?php

namespace Modules\Store\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Store\Models\Order;

class OrderConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('Order Confirmation - #' . $this->order->order_number)
                    ->view('store::emails.order-confirmation')
                    ->with(['order' => $this->order]);
    }
}