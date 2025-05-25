<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\Services\PaymentService;
use Modules\Store\Models\Order;
use Modules\Store\Repositories\OrderRepository;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected OrderRepository $orderRepository;

    public function __construct(
        PaymentService $paymentService,
        OrderRepository $orderRepository
    ) {
        $this->paymentService = $paymentService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get all available payment methods
     */
    public function getAvailableMethods(): JsonResponse
    {
        $methods = $this->paymentService->getAvailablePaymentMethods()
            ->map(fn ($method) => [
                'identifier' => $method->getIdentifier(),
                'name' => $method->getName(),
                'description' => $method->getDescription(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $methods,
        ]);
    }

    /**
     * Process payment for an order
     */
    public function processPayment(Request $request, string $orderId): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
            'payment_data' => 'array',
        ]);

        $order = $this->orderRepository->findOrFail($orderId);

        try {
            $result = $this->paymentService->processPayment(
                $order,
                $request->input('payment_method'),
                $request->input('payment_data', [])
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Handle payment callback
     */
    public function handleCallback(Request $request, string $method): JsonResponse
    {
        try {
            $result = $this->paymentService->handleCallback(
                $method,
                $request->all()
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get payment method configuration
     */
    public function getMethodConfig(string $method): JsonResponse
    {
        $paymentMethod = $this->paymentService->getPaymentMethod($method);
        
        if (!$paymentMethod) {
            return response()->json([
                'status' => 'error',
                'message' => "Payment method '{$method}' not found",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'identifier' => $paymentMethod->getIdentifier(),
                'name' => $paymentMethod->getName(),
                'description' => $paymentMethod->getDescription(),
                'enabled' => $paymentMethod->isEnabled(),
                'configuration_fields' => $paymentMethod->getConfigurationFields(),
            ],
        ]);
    }

    /**
     * Update payment method configuration
     */
    public function updateMethodConfig(Request $request, string $method): JsonResponse
    {
        $paymentMethod = $this->paymentService->getPaymentMethod($method);
        
        if (!$paymentMethod) {
            return response()->json([
                'status' => 'error',
                'message' => "Payment method '{$method}' not found",
            ], 404);
        }

        try {
            $this->paymentService->updatePaymentMethodConfig(
                $method,
                $request->all()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Payment method configuration updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
} 