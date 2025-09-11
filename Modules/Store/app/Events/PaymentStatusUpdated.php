<?php

namespace Modules\Store\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Store\Models\Order;

class PaymentStatusUpdated
{
    use SerializesModels;

    public $order;
    public $oldStatus;
    public $newStatus;

    public function __construct(Order $order, string $oldStatus, string $newStatus)
    {
        \Log::info("📢 event fired", [
        
    ]);
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
