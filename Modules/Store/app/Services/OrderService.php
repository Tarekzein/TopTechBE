<?php

namespace Modules\Store\Services;

use Modules\Store\Models\Order;
use Modules\Store\Models\OrderItem;
use Modules\Store\Models\Cart;
use Modules\Store\Models\CartItem;
use Modules\Store\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Modules\Store\Models\Product;

class OrderService
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Create a new order from cart items.
     *
     * @param array $data
     * @param Cart $cart
     * @return Order
     */
    public function createFromCart(array $data, Cart $cart): Order
    {
        return DB::transaction(function () use ($data, $cart) {
            try {
                // Load cart items with their products
                $cart->load(['items.product']);

                // Create a map of product prices from meta_data
                $metaPrices = collect($data['meta_data']['cart_items'])->keyBy('product_id')
                    ->map(function ($item) {
                        return [
                            'price' => (float) $item['price'],
                            'quantity' => (int) $item['quantity']
                        ];
                    });

                // Calculate totals using the prices from meta_data
                $cartItems = $cart->items->map(function ($item) use ($metaPrices) {
                    $metaItem = $metaPrices->get($item->product_id);
                    if (!$metaItem) {
                        throw new \Exception(sprintf(
                            'Product ID %d not found in meta_data',
                            $item->product_id
                        ));
                    }

                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $metaItem['quantity'],
                        'price' => $metaItem['price'],
                        'subtotal' => round($metaItem['price'] * $metaItem['quantity'], 2)
                    ];
                });

                $cartSubtotal = $cartItems->sum('subtotal');
                $cartTax = round($cartSubtotal * 0.07, 2); // 7% tax
                $shippingCost = round($data['shipping_cost'] ?? 0, 2);
                $cartTotal = round($cartSubtotal + $cartTax + $shippingCost, 2);

                // Log the values for debugging
                \Log::info('Order totals calculation', [
                    'cart_subtotal' => $cartSubtotal,
                    'provided_subtotal' => $data['subtotal'],
                    'cart_tax' => $cartTax,
                    'provided_tax' => $data['tax'],
                    'shipping_cost' => $shippingCost,
                    'cart_total' => $cartTotal,
                    'provided_total' => $data['total'],
                    'cart_items' => $cartItems->toArray(),
                    'meta_prices' => $metaPrices->toArray()
                ]);

                // Validate totals with a small tolerance for floating point arithmetic
                if (abs($cartSubtotal - $data['subtotal']) > 0.01) {
                    throw new \Exception(sprintf(
                        'Cart subtotal (%.2f) does not match provided subtotal (%.2f). Please refresh the page and try again.',
                        $cartSubtotal,
                        $data['subtotal']
                    ));
                }

                if (abs($cartTax - $data['tax']) > 0.01) {
                    throw new \Exception(sprintf(
                        'Cart tax (%.2f) does not match provided tax (%.2f). Please refresh the page and try again.',
                        $cartTax,
                        $data['tax']
                    ));
                }

                if (abs($cartTotal - $data['total']) > 0.01) {
                    throw new \Exception(sprintf(
                        'Cart total (%.2f) does not match provided total (%.2f). Please refresh the page and try again.',
                        $cartTotal,
                        $data['total']
                    ));
                }

                // Create the order with validated data
                $order = $this->orderRepository->create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $cart->user_id,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => $data['payment_method'],
                    'shipping_method' => $data['shipping_method'],
                    'shipping_cost' => $shippingCost,
                    'subtotal' => $cartSubtotal,
                    'tax' => $cartTax,
                    'total' => $cartTotal,
                    'currency' => $data['currency'] ?? 'USD',
                    'billing_address_id' => $data['billing_address_id'],
                    'shipping_address_id' => $data['shipping_address_id'],
                    'notes' => $data['notes'] ?? null,
                    'meta_data' => [
                        'cart_items' => $cartItems->toArray(),
                        'original_cart' => $cart->toArray(),
                        'meta_prices' => $metaPrices->toArray()
                    ]
                ]);

                // Create order items using the validated cart items
                foreach ($cartItems as $item) {
                    $this->createOrderItem($order, [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'attributes' => $cart->items->firstWhere('product_id', $item['product_id'])->attributes ?? []
                    ]);
                }

                // Clear the cart after successful order creation
                $cart->items()->delete();

                return $order;
            } catch (\Exception $e) {
                \Log::error('Failed to create order', [
                    'cart_id' => $cart->id,
                    'user_id' => $cart->user_id,
                    'data' => $data,
                    'exception' => $e
                ]);
                throw $e;
            }
        });
    }

    /**
     * Create an order item with validated data.
     *
     * @param Order $order
     * @param array $itemData
     * @return OrderItem
     */
    protected function createOrderItem(Order $order, array $itemData): OrderItem
    {
        // Get the product to ensure we have all required information
        $product = Product::findOrFail($itemData['product_id']);

        // Validate required fields
        if (empty($itemData['product_id']) || empty($itemData['quantity']) || empty($itemData['price'])) {
            throw new \Exception('Missing required field for order item: ' . 
                (empty($itemData['product_id']) ? 'product_id' : 
                (empty($itemData['quantity']) ? 'quantity' : 'price')));
        }

        // Calculate item totals
        $subtotal = round($itemData['price'] * $itemData['quantity'], 2);
        $tax = round($subtotal * 0.07, 2); // 7% tax
        $total = round($subtotal + $tax, 2);

        // Create the order item with all required fields
        return $order->items()->create([
            'product_id' => $itemData['product_id'],
            'variation_id' => $itemData['variation_id'] ?? null,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => $itemData['quantity'],
            'price' => $itemData['price'],
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'attributes' => $itemData['attributes'] ?? [],
            'meta_data' => [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'image' => $product->images[0] ?? null
                ],
                'variation' => isset($itemData['variation_id']) ? [
                    'id' => $itemData['variation_id'],
                    'sku' => $product->variations->firstWhere('id', $itemData['variation_id'])?->sku
                ] : null
            ]
        ]);
    }

    /**
     * Update the order status.
     *
     * @param Order $order
     * @param string $status
     * @return Order
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $data = ['status' => $status];

        switch ($status) {
            case 'completed':
                $data['completed_at'] = now();
                break;
            case 'cancelled':
                $data['cancelled_at'] = now();
                break;
            case 'refunded':
                $data['refunded_at'] = now();
                break;
        }

        return $this->orderRepository->update($order, $data);
    }

    /**
     * Update the payment status.
     *
     * @param Order $order
     * @param string $status
     * @param string|null $paymentId
     * @return Order
     */
    public function updatePaymentStatus(Order $order, string $status, ?string $paymentId = null): Order
    {
        $data = ['payment_status' => $status];

        if ($paymentId) {
            $data['payment_id'] = $paymentId;
        }

        if ($status === 'paid') {
            $data['paid_at'] = now();
        }

        return $this->orderRepository->update($order, $data);
    }

    /**
     * Update the shipping information.
     *
     * @param Order $order
     * @param array $data
     * @return Order
     */
    public function updateShippingInfo(Order $order, array $data): Order
    {
        $updateData = [
            'shipping_method' => $data['shipping_method'] ?? $order->shipping_method,
            'shipping_tracking_number' => $data['shipping_tracking_number'] ?? $order->shipping_tracking_number,
            'shipping_tracking_url' => $data['shipping_tracking_url'] ?? $order->shipping_tracking_url,
            'shipping_cost' => $data['shipping_cost'] ?? $order->shipping_cost,
        ];

        // Recalculate total if shipping cost changed
        if (isset($data['shipping_cost'])) {
            $updateData['total'] = $order->subtotal + $order->tax + $data['shipping_cost'] - $order->discount;
        }

        return $this->orderRepository->update($order, $updateData);
    }

    /**
     * Get orders for the authenticated user.
     *
     * @param array $filters
     * @return Collection
     */
    public function getUserOrders(array $filters = []): Collection
    {
        return $this->orderRepository->getForUser(Auth::id(), $filters);
    }

    /**
     * Get a specific order for the authenticated user.
     *
     * @param string $orderNumber
     * @return Order|null
     */
    public function getUserOrder(string $orderNumber): ?Order
    {
        return $this->orderRepository->findByOrderNumberForUser($orderNumber, Auth::id());
    }

    /**
     * Get all orders (admin only).
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllOrders(array $filters = []): Collection
    {
        return $this->orderRepository->getAll($filters);
    }

    /**
     * Get all orders for the authenticated vendor.
     *
     * @param array $filters
     * @return Collection
     */
    public function getVendorOrders(array $filters = []): Collection
    {
        return $this->orderRepository->getForVendor(Auth::user()->vendor->id, $filters);
    }

    /**
     * Get a specific order for the authenticated vendor.
     *
     * @param string $orderNumber
     * @return Order|null
     */
    public function getVendorOrder(string $orderNumber): ?Order
    {
        return $this->orderRepository->findByOrderNumberForVendor($orderNumber, Auth::user()->vendor->id);
    }

    /**
     * Update order status for vendor.
     *
     * @param Order $order
     * @param string $status
     * @return Order
     * @throws \Exception
     */
    public function updateVendorOrderStatus(Order $order, string $status): Order
    {
        $vendorId = Auth::user()->vendor->id;
        
        if (!$this->orderRepository->hasVendorProducts($order, $vendorId)) {
            throw new \Exception('This order does not contain any products from your vendor account.');
        }

        if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
            throw new \Exception('Invalid status for vendor order update.');
        }

        return $this->updateStatus($order, $status);
    }

    /**
     * Update shipping information for vendor.
     *
     * @param Order $order
     * @param array $data
     * @return Order
     * @throws \Exception
     */
    public function updateVendorShippingInfo(Order $order, array $data): Order
    {
        $vendorId = Auth::user()->vendor->id;
        
        if (!$this->orderRepository->hasVendorProducts($order, $vendorId)) {
            throw new \Exception('This order does not contain any products from your vendor account.');
        }

        return $this->updateShippingInfo($order, $data);
    }
} 