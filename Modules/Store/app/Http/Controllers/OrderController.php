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
            'billing_first_name' => 'required|string|max:255',
            'billing_last_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_phone' => 'required|string|max:20',
            'billing_address' => 'required|string|max:255',
            'billing_city' => 'required|string|max:255',
            'billing_state' => 'required|string|max:255',
            'billing_postcode' => 'required|string|max:20',
            'billing_country' => 'required|string|max:2',
            'shipping_first_name' => 'nullable|string|max:255',
            'shipping_last_name' => 'nullable|string|max:255',
            'shipping_email' => 'nullable|email|max:255',
            'shipping_phone' => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string|max:255',
            'shipping_city' => 'nullable|string|max:255',
            'shipping_state' => 'nullable|string|max:255',
            'shipping_postcode' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cart = Cart::where('user_id', Auth::id())->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        try {
            $order = $this->orderService->createFromCart($request->all(), $cart);
            return response()->json($order, 201);
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
        $filters = $request->validate([
            'status' => 'sometimes|string|in:pending,processing,completed,cancelled',
            'payment_status' => 'sometimes|string|in:pending,paid,failed,refunded',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'search' => 'sometimes|string|max:255',
        ]);

        $orders = $this->orderService->getVendorOrders($filters);

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Get a specific order for the authenticated vendor.
     *
     * @param string $orderNumber
     * @return JsonResponse
     */
    public function vendorShow(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getVendorOrder($orderNumber);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found or you do not have access to this order.',
            ], 404);
        }

        return response()->json([
            'data' => $order,
        ]);
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
        $data = $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        try {
            $order = $this->orderService->getVendorOrder($orderNumber);

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found or you do not have access to this order.',
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
            ], 400);
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
        $data = $request->validate([
            'tracking_number' => 'required|string|max:255',
            'shipping_carrier' => 'required|string|max:255',
            'shipping_method' => 'required|string|max:255',
            'estimated_delivery' => 'required|date|after:today',
            'notes' => 'sometimes|string|max:1000',
        ]);

        try {
            $order = $this->orderService->getVendorOrder($orderNumber);

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found or you do not have access to this order.',
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
            ], 400);
        }
    }
} 