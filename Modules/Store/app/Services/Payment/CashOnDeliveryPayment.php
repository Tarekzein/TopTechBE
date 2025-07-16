<?php

namespace Modules\Store\Services\Payment;

use Modules\Store\Models\Order;
use Illuminate\Support\Facades\Log;

class CashOnDeliveryPayment extends AbstractPaymentMethod
{
    public function getIdentifier(): string
    {
        return 'cash_on_delivery';
    }

    public function getName(): string
    {
        return 'Cash on Delivery';
    }

    public function getDescription(): string
    {
        return 'Pay with cash upon delivery';
    }

    public function isEnabled(): bool
    {
        try {
            // Force reload configuration to ensure we have latest settings
            $this->loadConfiguration();
            
            $enabled = (bool) ($this->getConfig('enabled', false));
            
            Log::info('Checking Cash on Delivery enabled status:', [
                'enabled' => $enabled,
                'config' => $this->config,
                'raw_enabled_value' => $this->getConfig('enabled')
            ]);
            
            return $enabled;
        } catch (\Exception $e) {
            Log::error('Error checking Cash on Delivery enabled status:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function loadConfiguration(): void
    {
        parent::loadConfiguration();
    }

    public function updateConfiguration(array $config): void {}

    public function getConfigurationFields(): array
    {
        $fields = array_merge(parent::getConfigurationFields(), [
            'enabled' => [
                'type' => 'boolean',
                'label' => 'Enable Cash on Delivery',
                'description' => 'Enable or disable Cash on Delivery payment method',
                'default' => true,
                'required' => true,
            ],
            'minimum_order_amount' => [
                'type' => 'number',
                'label' => 'Minimum Order Amount',
                'description' => 'Minimum order amount required for Cash on Delivery',
                'default' => 0,
                'required' => true,
            ],
            'maximum_order_amount' => [
                'type' => 'number',
                'label' => 'Maximum Order Amount',
                'description' => 'Maximum order amount allowed for Cash on Delivery (leave empty for no limit)',
                'default' => null,
                'required' => false,
            ],
            'available_areas' => [
                'type' => 'array',
                'label' => 'Available Delivery Areas',
                'description' => 'List of areas where Cash on Delivery is available',
                'default' => [],
                'required' => false,
            ],
        ]);

        Log::info('Cash on Delivery configuration fields:', [
            'fields' => $fields
        ]);

        return $fields;
    }

    public function processPayment(Order $order, array $paymentData = []): array
    {
        $this->validatePaymentData($order, $paymentData);

        // For COD, we just need to mark the order as pending payment
        $this->updateOrderStatus($order, 'pending', null);

        return [
            'status' => 'success',
            'message' => 'Order placed successfully. Payment will be collected upon delivery.',
            'order_id' => $order->id,
        ];
    }

    public function handleCallback(array $data): array
    {
        // COD doesn't have callbacks
        return [
            'status' => 'error',
            'message' => 'Callbacks are not supported for Cash on Delivery',
        ];
    }

    protected function validatePaymentData(Order $order, array $paymentData): void
    {
        parent::validatePaymentData($order, $paymentData);

        $minimumAmount = $this->getConfig('minimum_order_amount', 0);
        $maximumAmount = $this->getConfig('maximum_order_amount');

        if ($order->total < $minimumAmount) {
            throw new \InvalidArgumentException(
                "Order amount is below the minimum required amount of {$minimumAmount}"
            );
        }

        if ($maximumAmount && $order->total > $maximumAmount) {
            throw new \InvalidArgumentException(
                "Order amount exceeds the maximum allowed amount of {$maximumAmount}"
            );
        }

        $availableAreas = $this->getConfig('available_areas', []);
        if (!empty($availableAreas) && !in_array($order->shipping_address->area, $availableAreas)) {
            throw new \InvalidArgumentException(
                "Cash on Delivery is not available in your area"
            );
        }
    }
} 