<?php

namespace Modules\Store\Interfaces\Payment;

use Modules\Store\Models\Order;

interface PaymentMethodInterface
{
    /**
     * Get the unique identifier for this payment method
     */
    public function getIdentifier(): string;

    /**
     * Get the display name for this payment method
     */
    public function getName(): string;

    /**
     * Get the description for this payment method
     */
    public function getDescription(): string;

    /**
     * Check if this payment method is enabled
     */
    public function isEnabled(): bool;

    /**
     * Process the payment
     * 
     * @param Order $order The order to process payment for
     * @param array $paymentData Additional payment data
     * @return array Payment result with status and any additional data
     */
    public function processPayment(Order $order, array $paymentData = []): array;

    /**
     * Handle payment callback/webhook
     * 
     * @param array $data The callback data
     * @return array Processing result
     */
    public function handleCallback(array $data): array;

    /**
     * Get the configuration fields needed for this payment method
     * 
     * @return array Array of configuration field definitions
     */
    public function getConfigurationFields(): array;
} 