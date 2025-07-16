<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\Services\PaymentService;
use Modules\Store\Models\Order;
use Modules\Store\Repositories\OrderRepository;
use Modules\Store\Services\Payment\GeideaPaymentService;
use Illuminate\Support\Facades\Log;
use Modules\Store\Services\OrderService;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected OrderRepository $orderRepository;
    protected OrderService $orderService;

    public function __construct(
        PaymentService $paymentService,
        OrderRepository $orderRepository,
        OrderService $orderService
    ) {
        $this->paymentService = $paymentService;
        $this->orderRepository = $orderRepository;
        $this->orderService = $orderService;
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
            ])->values();

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

        $order = $this->orderRepository->findByOrderNumber($orderId);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.',
            ], 404);
        }

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

    /**
     * Create Geidea payment session
     */
    public function createGeideaSession(Request $request, GeideaPaymentService $geidea)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'merchantReferenceId' => 'required|string',
            'callbackUrl' => 'required|url',
            // Add more validation as needed
        ]);
        try {
            $sessionId = $geidea->createSession(
                $request->amount,
                $request->currency,
                $request->merchantReferenceId,
                $request->callbackUrl,
                config('services.geidea.api_password'),
                config('services.geidea.public_key'),
                $request->except(['amount', 'currency', 'merchantReferenceId', 'callbackUrl'])
            );
            return response()->json(['status' => 'success', 'sessionId' => $sessionId]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Handle Geidea payment callback
     */
    public function geideaCallback(Request $request)
    {
        Log::info('Geidea payment callback received', $request->all());
        $merchantReferenceId = $request->input('merchantReferenceId');
        $status = $request->input('status') ?? $request->input('paymentStatus') ?? $request->input('responseCode');
        $order = $this->orderRepository->findByOrderNumber($merchantReferenceId);
        if (!$order) {
            Log::error('Order not found for Geidea callback', ['merchantReferenceId' => $merchantReferenceId]);
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }
        // Determine payment status
        $paidStatuses = ['success', 'paid', '000']; // 000 is Geidea success code
        $isPaid = in_array(strtolower($status), $paidStatuses) || $status === '000';
        $newStatus = $isPaid ? 'paid' : 'failed';
        $this->orderService->updatePaymentStatus($order, $newStatus, $request->input('paymentId') ?? null);
        Log::info('Order payment status updated from Geidea callback', [
            'order_number' => $order->order_number,
            'new_status' => $newStatus,
            'callback_status' => $status
        ]);
        return response()->json(['status' => 'success', 'message' => 'Callback received, order payment status updated']);
    }
} 