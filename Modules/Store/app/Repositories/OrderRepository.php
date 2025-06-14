<?php

namespace Modules\Store\Repositories;

use Modules\Store\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    /**
     * Create a new order.
     *
     * @param array $data
     * @return Order
     */
    public function create(array $data): Order
    {
        // Validate required fields
        $requiredFields = [
            'order_number',
            'user_id',
            'status',
            'payment_status',
            'payment_method',
            'subtotal',
            'tax',
            'shipping_cost',
            'total',
            'currency',
            'shipping_method'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === null) {
                throw new \Exception("Missing required field for order: {$field}");
            }
        }

        // Ensure numeric values are properly formatted
        $numericFields = ['subtotal', 'tax', 'shipping_cost', 'discount', 'total'];
        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = round((float) $data[$field], 2);
            }
        }

        // Ensure meta_data is properly formatted
        if (isset($data['meta_data']) && !is_array($data['meta_data'])) {
            $data['meta_data'] = json_decode($data['meta_data'], true) ?? [];
        }

        try {
            return Order::create($data);
        } catch (\Exception $e) {
            \Log::error('Failed to create order: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e
            ]);
            throw new \Exception('Failed to create order: ' . $e->getMessage());
        }
    }

    /**
     * Find an order by order number.
     *
     * @param string $orderNumber
     * @return Order|null
     */
    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::where('order_number', $orderNumber)->first();
    }

    /**
     * Find an order by order number for a specific user.
     *
     * @param string $orderNumber
     * @param int $userId
     * @return Order|null
     */
    public function findByOrderNumberForUser(string $orderNumber, int $userId): ?Order
    {
        return Order::where('order_number', $orderNumber)
            ->where('user_id', $userId)
            ->with(['items.product', 'items.variation'])
            ->first();
    }

    /**
     * Get orders for a specific user with filters.
     *
     * @param int $userId
     * @param array $filters
     * @return Collection
     */
    public function getForUser(int $userId, array $filters = []): Collection
    {
        $query = Order::where('user_id', $userId)
            ->with(['items.product', 'items.variation']);

        $this->applyFilters($query, $filters);

        return $query->latest()->get();
    }

    /**
     * Get all orders with filters (admin only).
     *
     * @param array $filters
     * @return Collection
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Order::with(['user', 'items.product', 'items.variation']);

        $this->applyFilters($query, $filters);

        return $query->latest()->get();
    }

    /**
     * Get orders for a specific vendor with filters.
     *
     * @param int $vendorId
     * @param array $filters
     * @return Collection
     */
    public function getForVendor(int $vendorId, array $filters = []): Collection
    {
        $query = Order::whereHas('items.product', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })->with(['user', 'items.product', 'items.variation']);

        $this->applyFilters($query, $filters);

        return $query->latest()->get();
    }

    /**
     * Find an order by order number for a specific vendor.
     *
     * @param string $orderNumber
     * @param int $vendorId
     * @return Order|null
     */
    public function findByOrderNumberForVendor(string $orderNumber, int $vendorId): ?Order
    {
        return Order::whereHas('items.product', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })
        ->where('order_number', $orderNumber)
        ->with(['user', 'items.product', 'items.variation'])
        ->first();
    }

    /**
     * Check if an order contains products from a specific vendor.
     *
     * @param Order $order
     * @param int $vendorId
     * @return bool
     */
    public function hasVendorProducts(Order $order, int $vendorId): bool
    {
        return $order->items()
            ->whereHas('product', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })
            ->exists();
    }

    /**
     * Update an order.
     *
     * @param Order $order
     * @param array $data
     * @return Order
     */
    public function update(Order $order, array $data): Order
    {
        // Ensure numeric values are properly formatted
        $numericFields = ['subtotal', 'tax', 'shipping_cost', 'discount', 'total'];
        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = round((float) $data[$field], 2);
            }
        }

        // Ensure meta_data is properly formatted
        if (isset($data['meta_data']) && !is_array($data['meta_data'])) {
            $data['meta_data'] = json_decode($data['meta_data'], true) ?? $order->meta_data;
        }

        try {
            $order->update($data);
            return $order->fresh();
        } catch (\Exception $e) {
            \Log::error('Failed to update order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'data' => $data,
                'exception' => $e
            ]);
            throw new \Exception('Failed to update order: ' . $e->getMessage());
        }
    }

    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters($query, array $filters): void
    {
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

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('billing_email', 'like', "%{$search}%")
                  ->orWhere('billing_first_name', 'like', "%{$search}%")
                  ->orWhere('billing_last_name', 'like', "%{$search}%");
            });
        }

        // Always order by most recent first
        $query->latest();
    }
} 