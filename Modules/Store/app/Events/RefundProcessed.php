<?php

namespace Modules\Store\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Store\Models\Order;

class RefundProcessed
{
    use SerializesModels;

    public $order;
    public $refundAmount;

    /**
     * Create a new event instance.
     *
     * @param \Modules\Store\Models\Order $order
     * @param float $refundAmount
     */
    public function __construct(Order $order, float $refundAmount)
    {
        $this->order = $order;
        $this->refundAmount = $refundAmount;
    }
}
