<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Store\Models\Order;
use Modules\Store\Models\Cart;
use Modules\Store\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Store\Models\BillingAddress;
use Modules\Store\Models\ShippingAddress;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Create a new order from cart.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string',
            'shipping_method' => 'required|string',
            'shipping_cost' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'billing_address_id' => 'required|exists:billing_addresses,id',
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
            'notes' => 'nullable|string',
            'meta_data' => 'nullable|array',
            'meta_data.cart_items' => 'required|array',
            'meta_data.cart_items.*.product_id' => 'required|integer',
            'meta_data.cart_items.*.quantity' => 'required|integer|min:1',
            'meta_data.cart_items.*.price' => 'required|numeric|min:0',
            'meta_data.cart_items.*.variation_id' => 'nullable|integer',
            'meta_data.cart_items.*.attributes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify that the addresses belong to the authenticated user
        $billingAddress = BillingAddress::where('id', $request->billing_address_id)
            ->where('user_id', Auth::id())
            ->first();

        $shippingAddress = ShippingAddress::where('id', $request->shipping_address_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$billingAddress) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['billing_address_id' => ['The selected billing address is invalid.']]
            ], 422);
        }

        if (!$shippingAddress) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['shipping_address_id' => ['The selected shipping address is invalid.']]
            ], 422);
        }

        $cart = Cart::where('user_id', Auth::id())->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Validate that the totals match
        $calculatedSubtotal = collect($request->meta_data['cart_items'])->sum(function ($item) {
            // Use sale logic for variable and simple products
            $now = \Carbon\Carbon::now('UTC');
            $saleStart = isset($item['sale_start']) ? \Carbon\Carbon::parse($item['sale_start'])->timezone('UTC') : null;
            $saleEnd = isset($item['sale_end']) ? \Carbon\Carbon::parse($item['sale_end'])->timezone('UTC') : null;
            $useSale = (
                isset($item['sale_price']) && $item['sale_price'] &&
                $saleStart && $saleEnd &&
                $now->between($saleStart, $saleEnd)
            );
            $price = $useSale
                ? (float) $item['sale_price']
                : (isset($item['regular_price']) ? (float) $item['regular_price'] : (float) $item['price']);
            Log::info('OrderController subtotal debug', [
                'item' => $item,
                'used_price' => $price,
                'quantity' => $item['quantity'],
                'line_total' => $price * $item['quantity'],
                'now' => $now->toIso8601String(),
                'saleStart' => $saleStart ? $saleStart->toIso8601String() : null,
                'saleEnd' => $saleEnd ? $saleEnd->toIso8601String() : null,
            ]);
            return $price * $item['quantity'];
        });
        
        if (abs($calculatedSubtotal - $request->subtotal) > 0.01) {
            return response()->json([
                'message' => 'Subtotal does not match cart items total',
                'calculated' => $calculatedSubtotal,
                'provided' => $request->subtotal
            ], 422);
        }

        $paymentMethod = $request->input('payment_method');
        $paymentService = app(\Modules\Store\Services\PaymentService::class);
        $orderNumber = \Modules\Store\Models\Order::generateOrderNumber();
        try {
            $paymentResult = null;
            if ($paymentMethod === 'credit_card') {
                $paymentData = array_merge($request->all(), [
                    'merchantReferenceId' => $orderNumber
                ]);
                // Use a fake order object for payment session creation
                $fakeOrder = (object)[
                    'order_number' => $orderNumber,
                    'total' => $request->total,
                    'currency' => $request->currency ?? 'EGP',
                ];
                $paymentResult = $paymentService->processPayment(
                    $fakeOrder,
                    $paymentMethod,
                    $paymentData
                );
                if ($paymentResult['status'] !== 'success') {
                    return response()->json(['message' => $paymentResult['message'] ?? 'Payment failed'], 400);
                }
            }
            // Only create the order if payment succeeded or for COD
            $order = $this->orderService->createFromCart(
                array_merge($request->all(), ['order_number' => $orderNumber]),
                $cart
            );
            return response()->json([
                'order' => $order,
                'payment' => $paymentResult,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get orders for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'payment_status', 'date_from', 'date_to']);
        $orders = $this->orderService->getUserOrders($filters);
        return response()->json($orders);
    }

    /**
     * Get a specific order for the authenticated user.
     *
     * @param string $orderNumber
     * @return JsonResponse
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getUserOrder($orderNumber);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    /**
     * Update order status (admin only).
     *
     * @param Request $request
     * @param string $orderNumber
     * @return JsonResponse
     */
    public function updateStatus(Request $request, string $orderNumber): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,completed,cancelled,refunded',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            $order = $this->orderService->updateStatus($order, $request->status);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update order status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update payment status (admin only).
     *
     * @param Request $request
     * @param string $orderNumber
     * @return JsonResponse
     */
    public function updatePaymentStatus(Request $request, string $orderNumber): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,paid,failed,refunded',
            'payment_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            $order = $this->orderService->updatePaymentStatus($order, $request->status, $request->payment_id);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update payment status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update shipping information (admin only).
     *
     * @param Request $request
     * @param string $orderNumber
     * @return JsonResponse
     */
    public function updateShippingInfo(Request $request, string $orderNumber): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipping_method' => 'nullable|string',
            'shipping_tracking_number' => 'nullable|string',
            'shipping_tracking_url' => 'nullable|url',
            'shipping_cost' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            $order = $this->orderService->updateShippingInfo($order, $request->all());
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update shipping information: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all orders (admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'payment_status',
            'user_id',
            'date_from',
            'date_to',
            'search'
        ]);

        $orders = $this->orderService->getAllOrders($filters);
        return response()->json($orders);
    }

    /**
     * Get all orders for the authenticated vendor.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function vendorIndex(Request $request): JsonResponse
    {
        try {
            $filters = $request->validate([
                'status' => 'nullable|string|in:pending,processing,completed,cancelled,refunded',
                'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'search' => 'nullable|string|max:255',
                'sort' => 'nullable|string|in:created_at.desc,created_at.asc,total.desc,total.asc',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            // Set default values if not provided
            $filters['page'] = $filters['page'] ?? 1;
            $filters['per_page'] = $filters['per_page'] ?? 10;

            // Handle sort parameter
            if (isset($filters['sort'])) {
                [$column, $direction] = explode('.', $filters['sort']);
                $filters['sort_column'] = $column;
                $filters['sort_direction'] = $direction;
                unset($filters['sort']);
            }

            $orders = $this->orderService->getVendorOrders($filters);

            return response()->json([
                'data' => $orders->items(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Get a specific order for the authenticated vendor.
     *
     * @param string $orderNumber
     * @return JsonResponse
     */
    public function vendorShow(string $orderNumber): JsonResponse
    {
        try {
            $order = $this->orderService->getVendorOrder($orderNumber);

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found.',
                ], 404);
            }

            return response()->json([
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update order status for vendor.
     *
     * @param Request $request
     * @param string $orderNumber
     * @return JsonResponse
     */
    public function vendorUpdateStatus(Request $request, string $orderNumber): JsonResponse
    {
        try {
            $data = $request->validate([
                'status' => 'required|string|in:pending,processing,completed,cancelled',
            ]);

            $order = $this->orderService->getVendorOrder($orderNumber);

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found.',
                ], 404);
            }

            $order = $this->orderService->updateVendorOrderStatus($order, $data['status']);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update shipping information for vendor.
     *
     * @param Request $request
     * @param string $orderNumber
     * @return JsonResponse
     */
    public function vendorUpdateShippingInfo(Request $request, string $orderNumber): JsonResponse
    {
        try {
            $data = $request->validate([
                'tracking_number' => 'required|string|max:255',
                'shipping_carrier' => 'required|string|max:255',
                'shipping_method' => 'required|string|max:255',
                'estimated_delivery' => 'required|date|after:today',
                'notes' => 'sometimes|string|max:1000',
            ]);

            $order = $this->orderService->getVendorOrder($orderNumber);

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found.',
                ], 404);
            }

            $order = $this->orderService->updateVendorShippingInfo($order, $data);

            return response()->json([
                'message' => 'Shipping information updated successfully.',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
} 