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
        return Order::create($data);
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
        $order->fill($data);
        $order->save();
        return $order;
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

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
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
    }
} 