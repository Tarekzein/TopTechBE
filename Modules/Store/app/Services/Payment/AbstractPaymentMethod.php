<?php

namespace Modules\Store\Services\Payment;

use Modules\Store\App\Repositories\SettingRepository;
use Modules\Store\Interfaces\Payment\PaymentMethodInterface;
use Modules\Store\Models\Order;
use Illuminate\Support\Facades\Log;

abstract class AbstractPaymentMethod implements PaymentMethodInterface
{
    protected SettingRepository $settingRepository;
    protected array $config = [];

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
        $this->loadConfiguration();
    }

    /**
     * Load the payment method configuration from settings
     */
    public function loadConfiguration(): void
    {
        $group = 'payment.' . $this->getIdentifier();
        $settings = $this->settingRepository->getByGroup($group);
        
        Log::info('Loading payment method configuration:', [
            'method' => $this->getIdentifier(),
            'group' => $group,
            'settings_count' => $settings->count(),
            'settings' => $settings->toArray()
        ]);

        $this->config = [];
        foreach ($settings as $setting) {
            // Extract the key without the group prefix
            $key = str_replace($group . '.', '', $setting->key);
            $this->config[$key] = $setting->getCastedValue();
        }

        Log::info('Payment method configuration loaded:', [
            'method' => $this->getIdentifier(),
            'config' => $this->config
        ]);
    }

    /**
     * Check if this payment method is enabled
     */
    public function isEnabled(): bool
    {
        $enabled = (bool) ($this->config['enabled'] ?? false);
        
        Log::info('Checking payment method enabled status:', [
            'method' => $this->getIdentifier(),
            'enabled' => $enabled,
            'config' => $this->config
        ]);
        
        return $enabled;
    }

    /**
     * Get a configuration value
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Update the payment method configuration
     */
    public function updateConfiguration(array $config): void
    {
        foreach ($config as $key => $value) {
            $this->settingRepository->updateOrCreate(
                'payment.' . $this->getIdentifier() . '.' . $key,
                $value,
                'Payment Method Configuration'
            );
        }
        $this->loadConfiguration();
    }

    /**
     * Get the configuration fields needed for this payment method
     */
    public function getConfigurationFields(): array
    {
        return [
            'enabled' => [
                'type' => 'boolean',
                'label' => 'Enabled',
                'default' => false,
                'required' => true,
            ],
        ];
    }

    /**
     * Validate the payment data
     */
    protected function validatePaymentData(Order $order, array $paymentData): void
    {
        // Override in child classes to add specific validation
    }

    /**
     * Update order status after payment
     */
    protected function updateOrderStatus(Order $order, string $status, ?string $transactionId = null): void
    {
        $order->update([
            'payment_status' => $status,
            'payment_transaction_id' => $transactionId,
        ]);
    }
}
