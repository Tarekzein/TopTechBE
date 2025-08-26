<?php

namespace Modules\Store\Repositories;

use App\Models\User;
use Modules\Store\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    /**
     * Get all customers who have made purchases (admin view).
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllCustomers(array $filters = []): LengthAwarePaginator
    {
        $query = User::whereHas('orders')
            ->with(['orders' => function ($query) {
                $query->latest()->take(5);
            }])
            ->withCount([
                'orders',
                'orders as completed_orders_count' => function ($query) {
                    $query->where('status', 'completed');
                }
            ])
            ->withSum('orders', 'total');

        $this->applyFilters($query, $filters);

        return $query->paginate(
            $filters['per_page'] ?? 15,
            ['*'],
            'page',
            $filters['page'] ?? 1
        );
    }

    /**
     * Get customers for a specific vendor.
     *
     * @param int $vendorId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getVendorCustomers(int $vendorId, array $filters = []): LengthAwarePaginator
    {
        $query = User::whereHas('orders.items.product', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })
        ->with(['orders' => function ($query) use ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })->latest()->take(5);
        }])
        ->withCount([
            'orders as vendor_orders_count' => function ($query) use ($vendorId) {
                $query->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                });
            },
            'orders as vendor_completed_orders_count' => function ($query) use ($vendorId) {
                $query->where('status', 'completed')
                    ->whereHas('items.product', function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    });
            }
        ]);

        // Calculate total spent with this vendor
        $query->addSelect([
            'vendor_total_spent' => Order::selectRaw('COALESCE(SUM(total), 0)')
                ->whereColumn('user_id', 'users.id')
                ->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                })
        ]);

        $this->applyVendorFilters($query, $filters, $vendorId);

        return $query->paginate(
            $filters['per_page'] ?? 15,
            ['*'],
            'page',
            $filters['page'] ?? 1
        );
    }

    /**
     * Get customer details by ID for admin.
     *
     * @param int $customerId
     * @return User|null
     */
    public function getCustomerDetails(int $customerId): ?User
    {
        return User::whereHas('orders')
            ->with([
                'orders' => function ($query) {
                    $query->with(['items.product'])->latest();
                }
            ])
            ->withCount([
                'orders',
                'orders as completed_orders_count' => function ($query) {
                    $query->where('status', 'completed');
                },
                'orders as pending_orders_count' => function ($query) {
                    $query->where('status', 'pending');
                },
                'orders as cancelled_orders_count' => function ($query) {
                    $query->where('status', 'cancelled');
                }
            ])
            ->withSum('orders', 'total')
            ->withAvg('orders', 'total')
            ->find($customerId);
    }

    /**
     * Get customer details by ID for specific vendor.
     *
     * @param int $customerId
     * @param int $vendorId
     * @return User|null
     */
    public function getVendorCustomerDetails(int $customerId, int $vendorId): ?User
    {
        return User::whereHas('orders.items.product', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })
        ->with([
            'orders' => function ($query) use ($vendorId) {
                $query->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                })->with(['items.product'])->latest();
            }
        ])
        ->withCount([
            'orders as vendor_orders_count' => function ($query) use ($vendorId) {
                $query->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                });
            },
            'orders as vendor_completed_orders_count' => function ($query) use ($vendorId) {
                $query->where('status', 'completed')
                    ->whereHas('items.product', function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    });
            },
            'orders as vendor_pending_orders_count' => function ($query) use ($vendorId) {
                $query->where('status', 'pending')
                    ->whereHas('items.product', function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    });
            },
            'orders as vendor_cancelled_orders_count' => function ($query) use ($vendorId) {
                $query->where('status', 'cancelled')
                    ->whereHas('items.product', function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    });
            }
        ])
        ->addSelect([
            'vendor_total_spent' => Order::selectRaw('COALESCE(SUM(total), 0)')
                ->whereColumn('user_id', 'users.id')
                ->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                }),
            'vendor_avg_order_value' => Order::selectRaw('COALESCE(AVG(total), 0)')
                ->whereColumn('user_id', 'users.id')
                ->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                })
        ])
        ->find($customerId);
    }

    /**
     * Get customers analytics data for admin.
     *
     * @param array $filters
     * @return array
     */
    public function getCustomersAnalytics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();

        // Total customers
        $totalCustomers = User::whereHas('orders')->count();

        // New customers in period
        $newCustomers = User::whereHas('orders', function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        })->count();

        // Active customers (customers who made orders in the period)
        $activeCustomers = User::whereHas('orders', function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        })->count();

        // Top spending customers
        $topCustomers = User::whereHas('orders')
            ->withSum('orders', 'total')
            ->orderBy('orders_sum_total', 'desc')
            ->take(10)
            ->get();

        // Customer growth over time
        $customerGrowth = User::whereHas('orders')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Average order value per customer
        $avgOrderValue = Order::whereBetween('created_at', [$dateFrom, $dateTo])
            ->avg('total');

        // Customer retention rate (customers who made more than one order)
        $returningCustomers = User::whereHas('orders', function ($query) {
            $query->havingRaw('COUNT(*) > 1');
        })->count();

        $retentionRate = $totalCustomers > 0 ? ($returningCustomers / $totalCustomers) * 100 : 0;

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'active_customers' => $activeCustomers,
            'returning_customers' => $returningCustomers,
            'retention_rate' => round($retentionRate, 2),
            'avg_order_value' => round($avgOrderValue, 2),
            'top_customers' => $topCustomers,
            'customer_growth' => $customerGrowth,
        ];
    }

    /**
     * Get customers analytics data for specific vendor.
     *
     * @param int $vendorId
     * @param array $filters
     * @return array
     */
    public function getVendorCustomersAnalytics(int $vendorId, array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();

        // Total customers for this vendor
        $totalCustomers = User::whereHas('orders.items.product', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })->count();

        // New customers in period for this vendor
        $newCustomers = User::whereHas('orders.items.product', function ($query) use ($vendorId, $dateFrom, $dateTo) {
            $query->where('vendor_id', $vendorId);
        })
        ->whereHas('orders', function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        })->count();

        // Active customers (customers who made orders in the period)
        $activeCustomers = User::whereHas('orders', function ($query) use ($vendorId, $dateFrom, $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                });
        })->count();

        // Top spending customers for this vendor
        $topCustomers = User::whereHas('orders.items.product', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })
        ->addSelect([
            'vendor_total_spent' => Order::selectRaw('COALESCE(SUM(total), 0)')
                ->whereColumn('user_id', 'users.id')
                ->whereHas('items.product', function ($q) use ($vendorId) {
                    $q->where('vendor_id', $vendorId);
                })
        ])
        ->orderBy('vendor_total_spent', 'desc')
        ->take(10)
        ->get();

        // Customer growth over time for this vendor
        $customerGrowth = User::whereHas('orders.items.product', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })
        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Average order value per customer for this vendor
        $avgOrderValue = Order::whereHas('items.product', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->avg('total');

        // Customer retention rate for this vendor
        $returningCustomers = User::whereHas('orders.items.product', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })
        ->whereHas('orders', function ($query) use ($vendorId) {
            $query->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });
        }, '>', 1)
        ->count();

        $retentionRate = $totalCustomers > 0 ? ($returningCustomers / $totalCustomers) * 100 : 0;

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'active_customers' => $activeCustomers,
            'returning_customers' => $returningCustomers,
            'retention_rate' => round($retentionRate, 2),
            'avg_order_value' => round($avgOrderValue, 2),
            'top_customers' => $topCustomers,
            'customer_growth' => $customerGrowth,
        ];
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
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereHas('orders', function ($q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['date_from']);
            });
        }

        if (isset($filters['date_to'])) {
            $query->whereHas('orders', function ($q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['date_to']);
            });
        }

        if (isset($filters['min_orders'])) {
            $query->has('orders', '>=', $filters['min_orders']);
        }

        if (isset($filters['min_spent'])) {
            $query->whereHas('orders', function ($q) use ($filters) {
                $q->havingRaw('SUM(total) >= ?', [$filters['min_spent']]);
            });
        }

        // Default sorting
        if (isset($filters['sort_by'])) {
            $sortDirection = $filters['sort_direction'] ?? 'desc';
            
            switch ($filters['sort_by']) {
                case 'name':
                    $query->orderBy('first_name', $sortDirection);
                    break;
                case 'email':
                    $query->orderBy('email', $sortDirection);
                    break;
                case 'orders_count':
                    $query->orderBy('orders_count', $sortDirection);
                    break;
                case 'total_spent':
                    $query->orderBy('orders_sum_total', $sortDirection);
                    break;
                case 'created_at':
                default:
                    $query->orderBy('created_at', $sortDirection);
                    break;
            }
        } else {
            $query->latest();
        }
    }

    /**
     * Apply filters to the vendor query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @param int $vendorId
     * @return void
     */
    protected function applyVendorFilters($query, array $filters, int $vendorId): void
    {
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereHas('orders', function ($q) use ($filters, $vendorId) {
                $q->whereDate('created_at', '>=', $filters['date_from'])
                  ->whereHas('items.product', function ($subQ) use ($vendorId) {
                      $subQ->where('vendor_id', $vendorId);
                  });
            });
        }

        if (isset($filters['date_to'])) {
            $query->whereHas('orders', function ($q) use ($filters, $vendorId) {
                $q->whereDate('created_at', '<=', $filters['date_to'])
                  ->whereHas('items.product', function ($subQ) use ($vendorId) {
                      $subQ->where('vendor_id', $vendorId);
                  });
            });
        }

        if (isset($filters['min_orders'])) {
            $query->whereHas('orders', function ($q) use ($filters, $vendorId) {
                $q->whereHas('items.product', function ($subQ) use ($vendorId) {
                    $subQ->where('vendor_id', $vendorId);
                });
            }, '>=', $filters['min_orders']);
        }

        if (isset($filters['min_spent'])) {
            $query->whereHas('orders', function ($q) use ($filters, $vendorId) {
                $q->whereHas('items.product', function ($subQ) use ($vendorId) {
                    $subQ->where('vendor_id', $vendorId);
                })
                ->havingRaw('SUM(total) >= ?', [$filters['min_spent']]);
            });
        }

        // Default sorting with vendor-specific column names
        if (isset($filters['sort_by'])) {
            $sortDirection = $filters['sort_direction'] ?? 'desc';
            
            switch ($filters['sort_by']) {
                case 'name':
                    $query->orderBy('first_name', $sortDirection);
                    break;
                case 'email':
                    $query->orderBy('email', $sortDirection);
                    break;
                case 'orders_count':
                    $query->orderBy('vendor_orders_count', $sortDirection);
                    break;
                case 'total_spent':
                    $query->orderBy('vendor_total_spent', $sortDirection);
                    break;
                case 'created_at':
                default:
                    $query->orderBy('created_at', $sortDirection);
                    break;
            }
        } else {
            $query->latest();
        }
    }
}
