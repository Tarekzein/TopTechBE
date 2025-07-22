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
use Illuminate\Support\Facades\Log;

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
                // Load cart items with their products and product variations
                $cart->load(['items.product', 'items.productVariation']);

                // Debug log to verify loaded cart items and their variations
                Log::info('Cart items for order', [
                    'cart_items' => $cart->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'variation_id' => $item->variation_id ?? null,
                            'productVariation' => $item->productVariation,
                            'product' => $item->product,
                        ];
                    })
                ]);

                // Create a map of product prices from meta_data
                $metaPrices = collect($data['meta_data']['cart_items'])->mapWithKeys(function ($item) {
                    $variationId = $item['variation_id'] ?? null;
                    $key = $item['product_id'] . '-' . ($variationId ?? '0');
                    return [
                        $key => [
                            'price' => (float) $item['price'],
                            'quantity' => (int) $item['quantity'],
                            'variation_id' => $variationId,
                        ]
                    ];
                });

                // Calculate totals using the prices from meta_data
                $cartItems = $cart->items->map(function ($item) use ($metaPrices) {
                    // Use product_variation_id if present, otherwise variation_id, otherwise null
                    $variationId = $item->product_variation_id ?? $item->variation_id ?? null;
                    $key = $item->product_id . '-' . ($variationId ?? '0');
                    $metaItem = $metaPrices->get($key);
                    if (!$metaItem) {
                        Log::error('Meta item not found', [
                            'key' => $key,
                            'metaPrices_keys' => $metaPrices->keys(),
                            'item' => $item,
                        ]);
                        throw new \Exception(sprintf(
                            'Product ID %d (variation %s) not found in meta_data',
                            $item->product_id,
                            $variationId
                        ));
                    }

                    // Use the price from the loaded productVariation if it exists, with sale logic
                    if ($item->productVariation) {
                        $now = now();
                        $saleStart = $item->productVariation->sale_start ? \Carbon\Carbon::parse($item->productVariation->sale_start) : null;
                        $saleEnd = $item->productVariation->sale_end ? \Carbon\Carbon::parse($item->productVariation->sale_end) : null;
                        if (
                            $item->productVariation->sale_price &&
                            $saleStart && $saleEnd &&
                            $now->between($saleStart, $saleEnd)
                        ) {
                            $price = (float) $item->productVariation->sale_price;
                        } else {
                            $price = (float) $item->productVariation->regular_price;
                        }
                    } else {
                        // For simple products, use sale logic if present
                        $now = now();
                        $saleStart = $item->product->sale_start ? \Carbon\Carbon::parse($item->product->sale_start) : null;
                        $saleEnd = $item->product->sale_end ? \Carbon\Carbon::parse($item->product->sale_end) : null;
                        if (
                            $item->product->sale_price &&
                            $saleStart && $saleEnd &&
                            $now->between($saleStart, $saleEnd)
                        ) {
                            $price = (float) $item->product->sale_price;
                        } else {
                            $price = (float) $item->product->regular_price;
                        }
                    }

                    return [
                        'product_id' => $item->product_id,
                        'variation_id' => $variationId,
                        'quantity' => $metaItem['quantity'],
                        'price' => $price,
                        'subtotal' => round($price * $metaItem['quantity'], 2)
                    ];
                });

                $cartSubtotal = $cartItems->sum('subtotal');
                $cartTax = round($cartSubtotal * 0.14, 2); // 14% tax
                $shippingCost = round($data['shipping_cost'] ?? 0, 2);
                $cartTotal = round($cartSubtotal + $cartTax + $shippingCost, 2);

                // Promo code logic
                $promocode = null;
                $discount = 0;
                if (!empty($data['promocode'])) {
                    $promocode = \Modules\Store\Models\PromoCode::where('code', $data['promocode'])->first();
                    if (!$promocode) {
                        throw new \Exception('Promo code not found.');
                    }
                    if (!$promocode->isActive()) {
                        throw new \Exception('Promo code is not active or expired.');
                    }
                    if ($promocode->min_order_total && $cartTotal < $promocode->min_order_total) {
                        throw new \Exception('Order total is less than minimum required for this promo code.');
                    }
                    $userId = $cart->user_id;
                    if ($userId && !$promocode->canBeUsedBy($userId)) {
                        throw new \Exception('Promo code usage limit reached for this user.');
                    }
                    $discount = $promocode->calculateDiscount($cartTotal);
                    $cartTotal = max(0, $cartTotal - $discount);
                }

                // Log the values for debugging
                Log::info('Order totals calculation', [
                    'cart_subtotal' => $cartSubtotal,
                    'provided_subtotal' => $data['subtotal'],
                    'cart_tax' => $cartTax,
                    'provided_tax' => $data['tax'],
                    'shipping_cost' => $shippingCost,
                    'cart_total' => $cartTotal,
                    'provided_total' => $data['total'],
                    'cart_items' => $cartItems->toArray(),
                    'meta_prices' => $metaPrices->toArray(),
                    'discount' => $discount,
                    'promocode' => $promocode ? $promocode->code : null
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
                    'order_number' => $data['order_number'] ?? Order::generateOrderNumber(),
                    'user_id' => $cart->user_id,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => $data['payment_method'],
                    'shipping_method' => $data['shipping_method'],
                    'shipping_cost' => $shippingCost,
                    'subtotal' => $cartSubtotal,
                    'tax' => $cartTax,
                    'discount' => $discount,
                    'total' => $cartTotal,
                    'currency' => $data['currency'] ?? 'USD',
                    'billing_address_id' => $data['billing_address_id'],
                    'shipping_address_id' => $data['shipping_address_id'],
                    'notes' => $data['notes'] ?? null,
                    'promocode_id' => $promocode ? $promocode->id : null,
                    'meta_data' => [
                        'cart_items' => $cartItems->toArray(),
                        'original_cart' => $cart->toArray(),
                        'meta_prices' => $metaPrices->toArray(),
                        'applied_promocode' => $promocode ? $promocode->code : null,
                        'applied_discount' => $discount,
                    ]
                ]);

                // Increment promocode usage if applied
                if ($promocode) {
                    $promocode->increment('used');
                    \Modules\Store\Models\PromoCodeUsage::create([
                        'user_id' => $cart->user_id,
                        'promocode_id' => $promocode->id,
                        'order_id' => $order->id,
                        'used_at' => now(),
                    ]);
                }

                // Create order items using the validated cart items
                foreach ($cartItems as $item) {
                    $this->createOrderItem($order, [
                        'product_id' => $item['product_id'],
                        'variation_id' => $item['variation_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'attributes' => $cart->items->firstWhere(function($cartItem) use ($item) {
                            return $cartItem->product_id == $item['product_id'] && ($cartItem->variation_id ?? null) == ($item['variation_id'] ?? null);
                        })?->attributes ?? []
                    ]);
                }

                // Clear the cart after successful order creation
                $cart->items()->delete();

                return $order;
            } catch (\Exception $e) {
                Log::error('Failed to create order', [
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
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \Exception
     */
    public function getVendorOrders(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        if (!$user->vendor) {
            throw new \Exception('User is not associated with any vendor account.');
        }

        $query = Order::whereHas('items.product', function ($query) use ($user) {
            $query->where('vendor_id', $user->vendor->id);
        })
        ->with(['user', 'items.product', 'items.variation']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        if (isset($filters['sort_column']) && isset($filters['sort_direction'])) {
            $query->orderBy($filters['sort_column'], $filters['sort_direction']);
        } else {
            $query->latest(); // Default sort by created_at desc
        }

        return $query->paginate(
            $filters['per_page'] ?? 10,
            ['*'],
            'page',
            $filters['page'] ?? 1
        );
    }

    /**
     * Get a specific order for the authenticated vendor.
     *
     * @param string $orderNumber
     * @return Order|null
     * @throws \Exception
     */
    public function getVendorOrder(string $orderNumber): ?Order
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        if (!$user->vendor) {
            throw new \Exception('User is not associated with any vendor account.');
        }

        return $this->orderRepository->findByOrderNumberForVendor($orderNumber, $user->vendor->id);
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
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        if (!$user->vendor) {
            throw new \Exception('User is not associated with any vendor account.');
        }

        if (!$this->orderRepository->hasVendorProducts($order, $user->vendor->id)) {
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
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        if (!$user->vendor) {
            throw new \Exception('User is not associated with any vendor account.');
        }

        if (!$this->orderRepository->hasVendorProducts($order, $user->vendor->id)) {
            throw new \Exception('This order does not contain any products from your vendor account.');
        }

        return $this->updateShippingInfo($order, $data);
    }
}
