<?php

namespace Modules\Store\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Store\Models\Order;

class ShippingUpdated
{
    use SerializesModels;

    public $order;
    public $shippingData;

    /**
     * Create a new event instance.
     *
     * @param \Modules\Store\Models\Order $order
     * @param array $shippingData
     */
    public function __construct(Order $order, array $shippingData)
    {
        $this->order = $order;
        $this->shippingData = $shippingData;
    }
}
