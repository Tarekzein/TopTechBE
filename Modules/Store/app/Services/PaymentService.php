<?php

namespace Modules\Store\Services;

use Modules\Store\App\Repositories\SettingRepository;
use Modules\Store\Interfaces\Payment\PaymentMethodInterface;
use Modules\Store\Models\Order;
use Illuminate\Support\Collection;
use Modules\Store\Services\Payment\CashOnDeliveryPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PaymentService
{
    protected SettingRepository $settingRepository;
    protected array $paymentMethods = [];

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
        
        try {
            Log::info('Initializing PaymentService');
            $this->registerPaymentMethods();
            $this->initializePaymentMethodSettings();
            
            // Debug log registered methods
            Log::info('PaymentService initialization complete:', [
                'methods' => array_keys($this->paymentMethods),
                'count' => count($this->paymentMethods)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize PaymentService:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Initialize default settings for payment methods
     */
    protected function initializePaymentMethodSettings(): void
    {
        Log::info('Initializing payment method settings');
        
        // Clear all settings cache
        Cache::forget('store_settings_all');
        Cache::forget('store_settings_payment');
        
        foreach ($this->paymentMethods as $method) {
            try {
                $identifier = $method->getIdentifier();
                $fields = $method->getConfigurationFields();
                
                Log::info('Processing payment method settings:', [
                    'method' => $identifier,
                    'fields' => $fields
                ]);
                
                // Ensure enabled setting is first
                if (isset($fields['enabled'])) {
                    $enabledField = $fields['enabled'];
                    unset($fields['enabled']);
                    $fields = ['enabled' => $enabledField] + $fields;
                }
                
                foreach ($fields as $key => $field) {
                    $settingKey = "payment.{$identifier}.{$key}";
                    
                    Log::info('Updating setting:', [
                        'key' => $settingKey,
                        'field' => $field
                    ]);
                    
                    // Determine the type based on the field type
                    $fieldType = $field['type'] ?? 'string';
                    $type = match ($fieldType) {
                        'boolean' => 'boolean',
                        'number', 'integer' => 'integer',
                        'array' => 'array',
                        default => 'string',
                    };

                    $label = $field['label'] ?? ucfirst(str_replace('_', ' ', $key));
                    $description = $field['description'] ?? "Configuration for {$label}";

                    try {
                        // For enabled setting, ensure it's properly initialized
                        if ($key === 'enabled') {
                            $defaultValue = $field['default'] ?? false;
                            Log::info('Initializing enabled setting:', [
                                'method' => $identifier,
                                'default' => $defaultValue
                            ]);
                        }

                        $setting = $this->settingRepository->updateOrCreate(
                            $settingKey,
                            $field['default'] ?? null,
                            $label,
                            $description,
                            "payment.{$identifier}",
                            false,
                            $type
                        );
                        
                        // Clear cache for this specific setting
                        Cache::forget('store_settings_' . $settingKey);
                        
                        Log::info('Setting updated successfully:', [
                            'setting' => $setting->toArray(),
                            'value' => $setting->getCastedValue()
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to update setting:', [
                            'key' => $settingKey,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Continue with other settings even if one fails
                        continue;
                    }
                }

                // Verify enabled status after initialization
                if (method_exists($method, 'loadConfiguration')) {
                    $method->loadConfiguration();
                }
                $enabled = $method->isEnabled();
                Log::info('Payment method enabled status after initialization:', [
                    'method' => $identifier,
                    'enabled' => $enabled
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to process payment method settings:', [
                    'method' => $method->getIdentifier(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with other payment methods even if one fails
                continue;
            }
        }
        
        Log::info('Finished initializing payment method settings');
    }

    /**
     * Register all available payment methods
     */
    protected function registerPaymentMethods(): void
    {
        Log::info('Starting payment method registration');
        
        try {
            // Register built-in payment methods
            $cod = new CashOnDeliveryPayment($this->settingRepository);
            $this->registerPaymentMethod($cod);
            
            Log::info('Cash on Delivery payment method registered:', [
                'identifier' => $cod->getIdentifier(),
                'enabled' => $cod->isEnabled(),
                'config' => $cod->getConfigurationFields()
            ]);

            // Additional payment methods can be registered here
            // $this->registerPaymentMethod(new VisaPayment($this->settingRepository));
            // $this->registerPaymentMethod(new WalletPayment($this->settingRepository));
            // $this->registerPaymentMethod(new BankTransferPayment($this->settingRepository));
            
            Log::info('Payment method registration complete');
        } catch (\Exception $e) {
            Log::error('Failed to register payment methods:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Register a payment method
     */
    public function registerPaymentMethod(PaymentMethodInterface $paymentMethod): void
    {
        try {
            $identifier = $paymentMethod->getIdentifier();
            
            if (isset($this->paymentMethods[$identifier])) {
                Log::warning('Payment method already registered:', [
                    'identifier' => $identifier
                ]);
                return;
            }
            
            $this->paymentMethods[$identifier] = $paymentMethod;
            
            Log::info('Payment method registered successfully:', [
                'identifier' => $identifier,
                'name' => $paymentMethod->getName()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to register payment method:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get all available payment methods
     */
    public function getAvailablePaymentMethods(): Collection
    {
        try {
            Log::info('Getting available payment methods');
            
            $methods = collect($this->paymentMethods)
                ->filter(function (PaymentMethodInterface $method) {
                    try {
                        // Force reload configuration before checking if enabled
                        if (method_exists($method, 'loadConfiguration')) {
                            $method->loadConfiguration();
                        }
                        
                        $enabled = $method->isEnabled();
                        
                        // Debug log each method's status
                        Log::info('Payment method status check:', [
                            'method' => $method->getIdentifier(),
                            'enabled' => $enabled,
                            'config' => $method->getConfigurationFields()
                        ]);
                        
                        return $enabled;
                    } catch (\Exception $e) {
                        Log::error('Error checking payment method status:', [
                            'method' => $method->getIdentifier(),
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        return false;
                    }
                });

            // Debug log final available methods
            Log::info('Available payment methods retrieved:', [
                'methods' => $methods->map(fn($m) => [
                    'identifier' => $m->getIdentifier(),
                    'name' => $m->getName(),
                    'enabled' => $m->isEnabled()
                ])->toArray(),
                'count' => $methods->count()
            ]);

            return $methods;
        } catch (\Exception $e) {
            Log::error('Error getting available payment methods:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]);
        }
    }

    /**
     * Get all payment methods (including disabled ones)
     */
    public function getAllPaymentMethods(): Collection
    {
        return collect($this->paymentMethods);
    }

    /**
     * Get a specific payment method
     */
    public function getPaymentMethod(string $identifier): ?PaymentMethodInterface
    {
        return $this->paymentMethods[$identifier] ?? null;
    }

    /**
     * Process payment for an order
     */
    public function processPayment(Order $order, string $methodIdentifier, array $paymentData = []): array
    {
        $method = $this->getPaymentMethod($methodIdentifier);

        if (!$method) {
            throw new \InvalidArgumentException("Payment method '{$methodIdentifier}' not found");
        }

        if (!$method->isEnabled()) {
            throw new \InvalidArgumentException("Payment method '{$methodIdentifier}' is not enabled");
        }

        return $method->processPayment($order, $paymentData);
    }

    /**
     * Handle payment callback
     */
    public function handleCallback(string $methodIdentifier, array $data): array
    {
        $method = $this->getPaymentMethod($methodIdentifier);

        if (!$method) {
            throw new \InvalidArgumentException("Payment method '{$methodIdentifier}' not found");
        }

        return $method->handleCallback($data);
    }

    /**
     * Update payment method configuration
     */
    public function updatePaymentMethodConfig(string $methodIdentifier, array $config): void
    {
        $method = $this->getPaymentMethod($methodIdentifier);

        if (!$method) {
            throw new \InvalidArgumentException("Payment method '{$methodIdentifier}' not found");
        }

        $method->updateConfiguration($config);
    }
}
