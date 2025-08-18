<?php

namespace Modules\Store\Services\Payment;

use Modules\Store\Services\Payment\AbstractPaymentMethod;
use Modules\Store\Models\Order;
use Illuminate\Support\Facades\Log;

class CreditCardPayment extends AbstractPaymentMethod
{
    protected $geideaService;

    public function __construct($settingRepository, ?GeideaPaymentService $geideaService = null)
    {
        parent::__construct($settingRepository);
        $this->geideaService = $geideaService ?? app(GeideaPaymentService::class);
    }

    public function getIdentifier(): string
    {
        return 'credit_card';
    }

    public function getName(): string
    {
        return 'Credit Card';
    }

    public function getDescription(): string
    {
        return 'Pay securely using your credit or debit card.';
    }

    public function isEnabled(): bool
    {
        try {
            $this->loadConfiguration();
            $enabled = (bool)($this->getConfig('enabled', true));
            Log::info('Checking Geidea enabled status:', [
                'enabled' => $enabled,
                'config' => $this->config,
                'raw_enabled_value' => $this->getConfig('enabled')
            ]);
            return $enabled;
        } catch (\Exception $e) {
            Log::error('Error checking Geidea enabled status:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function updateConfiguration(array $config): void
    {
        parent::updateConfiguration($config);
    }

    public function getConfigurationFields(): array
    {
        $fields = array_merge(parent::getConfigurationFields(), [
            'enabled' => [
                'type' => 'boolean',
                'label' => 'Enable Geidea',
                'description' => 'Enable or disable Geidea payment method',
                'default' => true,
                'required' => true,
            ],
        ]);
        Log::info('Geidea configuration fields:', [
            'fields' => $fields
        ]);
        return $fields;
    }

    public function processPayment($order, array $paymentData = []): array
    {
        try {
            // Support both Order model and array/stdClass for pre-order payment session
            if (is_object($order)) {
                $merchantReferenceId = $order->order_number ?? $order->id;
                $amount = $order->total;
                $currency = strtoupper($order->currency ?? 'EGP');
            } elseif (is_array($order)) {
                $merchantReferenceId = $order['order_number'] ?? null;
                $amount = $order['total'] ?? null;
                $currency = strtoupper($order['currency'] ?? 'EGP');
            } else {
                throw new \InvalidArgumentException('Invalid order data for payment');
            }
            
            // Log the payment data for debugging
            Log::info('Geidea payment processing:', [
                'orderType' => is_object($order) ? get_class($order) : gettype($order),
                'merchantReferenceId' => $merchantReferenceId,
                'amount' => $amount,
                'amountType' => gettype($amount),
                'currency' => $currency,
                'currencyType' => gettype($currency),
                'paymentData' => $paymentData
            ]);
            
            $callbackUrl = config('app.url') . '/api/store/payments/geidea/callback';
            
            // Log the API credentials (without exposing sensitive data)
            Log::info('Geidea API credentials check:', [
                'hasApiPassword' => !empty(config('services.geidea.api_password')),
                'hasPublicKey' => !empty(config('services.geidea.public_key')),
                'callbackUrl' => $callbackUrl
            ]);
            
            $sessionId = $this->geideaService->createSession(
                $amount,
                $currency,
                $merchantReferenceId,
                $callbackUrl,
                config('services.geidea.api_password'),
                config('services.geidea.public_key'),
                $paymentData // pass any extra params (customer, order, etc)
            );
            // If we have an Order model, store the sessionId in meta for callback lookup fallback
            if ($order instanceof Order && !empty($sessionId)) {
                try {
                    app(\Modules\Store\Repositories\OrderRepository::class)->mergeMeta($order, [
                        'geidea_session_id' => $sessionId,
                    ]);
                } catch (\Throwable $t) {
                    Log::warning('Failed to store geidea_session_id on order meta', [
                        'order_number' => $order->order_number ?? null,
                        'error' => $t->getMessage(),
                    ]);
                }
            }
            return [
                'status' => 'success',
                'sessionId' => $sessionId,
                'message' => 'Geidea session created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Geidea session creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function handleCallback(array $data): array
    {
        Log::info('Geidea callback received', $data);
        // You can add more logic here to update order/payment status
        return [
            'status' => 'success',
            'message' => 'Callback handled'
        ];
    }

    protected function validatePaymentData(Order $order, array $paymentData): void
    {
        // Add validation if needed
    }
} 