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
            // Create the order
            $orderData = [
                'order_number' => Order::generateOrderNumber(),
                'user_id' => Auth::id(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'] ?? null,
                'subtotal' => $cart->subtotal,
                'tax' => $cart->tax,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'discount' => $cart->discount,
                'total' => $cart->total + ($data['shipping_cost'] ?? 0),
                'currency' => $data['currency'] ?? config('store.currency', 'USD'),
                'shipping_method' => $data['shipping_method'] ?? null,
                'billing_first_name' => $data['billing_first_name'],
                'billing_last_name' => $data['billing_last_name'],
                'billing_email' => $data['billing_email'],
                'billing_phone' => $data['billing_phone'],
                'billing_address' => $data['billing_address'],
                'billing_city' => $data['billing_city'],
                'billing_state' => $data['billing_state'],
                'billing_postcode' => $data['billing_postcode'],
                'billing_country' => $data['billing_country'],
                'shipping_first_name' => $data['shipping_first_name'] ?? $data['billing_first_name'],
                'shipping_last_name' => $data['shipping_last_name'] ?? $data['billing_last_name'],
                'shipping_email' => $data['shipping_email'] ?? $data['billing_email'],
                'shipping_phone' => $data['shipping_phone'] ?? $data['billing_phone'],
                'shipping_address' => $data['shipping_address'] ?? $data['billing_address'],
                'shipping_city' => $data['shipping_city'] ?? $data['billing_city'],
                'shipping_state' => $data['shipping_state'] ?? $data['billing_state'],
                'shipping_postcode' => $data['shipping_postcode'] ?? $data['billing_postcode'],
                'shipping_country' => $data['shipping_country'] ?? $data['billing_country'],
                'notes' => $data['notes'] ?? null,
                'meta_data' => $data['meta_data'] ?? null,
            ];

            $order = $this->orderRepository->create($orderData);

            // Create order items from cart items
            foreach ($cart->items as $cartItem) {
                $this->createOrderItem($order, $cartItem);
            }

            // Clear the cart
            $cart->items()->delete();
            $cart->delete();

            return $order->load('items');
        });
    }

    /**
     * Create an order item from a cart item.
     *
     * @param Order $order
     * @param CartItem $cartItem
     * @return OrderItem
     */
    protected function createOrderItem(Order $order, CartItem $cartItem): OrderItem
    {
        $product = $cartItem->product;
        $variation = $cartItem->variation;

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variation_id' => $variation?->id,
            'name' => $variation ? $variation->name : $product->name,
            'sku' => $variation ? $variation->sku : $product->sku,
            'quantity' => $cartItem->quantity,
            'price' => $cartItem->price,
            'subtotal' => $cartItem->subtotal,
            'tax' => $cartItem->tax,
            'total' => $cartItem->total,
            'attributes' => $cartItem->attributes,
            'meta_data' => $cartItem->meta_data,
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