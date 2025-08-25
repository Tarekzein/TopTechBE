<?php

namespace Modules\Store\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\Store\Models\Order;
use Modules\Store\Models\Product;
use Modules\Store\Models\OrderItem;
use App\Models\User;
use InvalidArgumentException;

class AnalyticsRepository
{
    /**
     * Validate vendor ID
     */
    private function validateVendorId(int $vendorId): void
    {
        if ($vendorId <= 0) {
            throw new InvalidArgumentException('Invalid vendor ID provided');
        }
    }

    /**
     * Validate period parameter
     */
    private function validatePeriod(string $period): void
    {
        $validPeriods = ['7d', '30d', '90d', '1y'];
        if (!in_array($period, $validPeriods)) {
            throw new InvalidArgumentException('Invalid period provided. Must be one of: ' . implode(', ', $validPeriods));
        }
    }

    /**
     * Validate date range array
     */
    private function validateDateRange(array $dateRange): void
    {
        if (count($dateRange) !== 2) {
            throw new InvalidArgumentException('Invalid date range format. Must contain start and end dates.');
        }
        
        if (!isset($dateRange[0]) || !isset($dateRange[1])) {
            throw new InvalidArgumentException('Invalid date range. Start and end dates are required.');
        }
    }

    /**
     * Get vendor analytics overview
     */
    public function getVendorAnalytics(int $vendorId, string $period = '30d'): array
    {
        try {
            $this->validateVendorId($vendorId);
            $this->validatePeriod($period);
            
            Log::info('Analytics Repository: Fetching vendor analytics', [
                'vendor_id' => $vendorId,
                'period' => $period
            ]);
            
            $dateRange = $this->getDateRange($period);
            
            $analytics = [
                'revenue' => $this->getRevenueAnalytics($vendorId, $dateRange),
                'orders' => $this->getOrdersAnalytics($vendorId, $dateRange),
                'customers' => $this->getCustomersAnalytics($vendorId, $dateRange),
                'products' => $this->getProductsAnalytics($vendorId, $dateRange),
                'performance' => $this->getPerformanceMetrics($vendorId, $dateRange),
                'recent_orders' => $this->getRecentOrders($vendorId, 5),
                'top_products' => $this->getTopProducts($vendorId, $dateRange, 5),
                'sales_trend' => $this->getSalesTrend($vendorId, $dateRange),
            ];
            
            Log::info('Analytics Repository: Vendor analytics fetched successfully', [
                'vendor_id' => $vendorId,
                'has_data' => !empty($analytics)
            ]);
            
            return $analytics;
            
        } catch (InvalidArgumentException $e) {
            Log::error('Analytics Repository: Invalid argument in getVendorAnalytics', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId,
                'period' => $period
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Analytics Repository: Failed to get vendor analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vendor_id' => $vendorId,
                'period' => $period
            ]);
            throw $e;
        }
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(int $vendorId, array $dateRange): array
    {
        try {
            $this->validateVendorId($vendorId);
            $this->validateDateRange($dateRange);
            
            Log::debug('Analytics Repository: Fetching revenue analytics', [
                'vendor_id' => $vendorId,
                'date_range' => $dateRange
            ]);
            
            $currentRevenue = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->where('orders.payment_status', 'paid')
                ->whereBetween('orders.created_at', $dateRange)
                ->sum(DB::raw('order_items.quantity * order_items.price'));

            $previousRevenue = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->where('orders.payment_status', 'paid')
                ->whereBetween('orders.created_at', $this->getPreviousDateRange($dateRange))
                ->sum(DB::raw('order_items.quantity * order_items.price'));

            $change = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

            $result = [
                'total' => round($currentRevenue, 2),
                'change' => round($change, 1),
                'currency' => 'USD'
            ];
            
            Log::debug('Analytics Repository: Revenue analytics calculated', [
                'vendor_id' => $vendorId,
                'current_revenue' => $currentRevenue,
                'previous_revenue' => $previousRevenue,
                'change_percentage' => $change
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Analytics Repository: Failed to get revenue analytics', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId,
                'date_range' => $dateRange
            ]);
            
            // Return default values on error
            return [
                'total' => 0,
                'change' => 0,
                'currency' => 'USD'
            ];
        }
    }

    /**
     * Get orders analytics
     */
    public function getOrdersAnalytics(int $vendorId, array $dateRange): array
    {
        try {
            $this->validateVendorId($vendorId);
            $this->validateDateRange($dateRange);
            
            $currentOrders = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->whereBetween('orders.created_at', $dateRange)
                ->distinct('orders.id')
                ->count('orders.id');

            $previousOrders = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->whereBetween('orders.created_at', $this->getPreviousDateRange($dateRange))
                ->distinct('orders.id')
                ->count('orders.id');

            $change = $previousOrders > 0 ? (($currentOrders - $previousOrders) / $previousOrders) * 100 : 0;

            $result = [
                'total' => $currentOrders,
                'change' => round($change, 1)
            ];
            
            Log::debug('Analytics Repository: Orders analytics calculated', [
                'vendor_id' => $vendorId,
                'current_orders' => $currentOrders,
                'previous_orders' => $previousOrders,
                'change_percentage' => $change
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Analytics Repository: Failed to get orders analytics', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId,
                'date_range' => $dateRange
            ]);
            
            // Return default values on error
            return [
                'total' => 0,
                'change' => 0
            ];
        }
    }

    /**
     * Get customers analytics
     */
    public function getCustomersAnalytics(int $vendorId, array $dateRange): array
    {
        try {
            $this->validateVendorId($vendorId);
            $this->validateDateRange($dateRange);
            
            $currentCustomers = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->whereBetween('orders.created_at', $dateRange)
                ->distinct('orders.user_id')
                ->count('orders.user_id');

            $previousCustomers = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->whereBetween('orders.created_at', $this->getPreviousDateRange($dateRange))
                ->distinct('orders.user_id')
                ->count('orders.user_id');

            $change = $previousCustomers > 0 ? (($currentCustomers - $previousCustomers) / $previousCustomers) * 100 : 0;

            $result = [
                'total' => $currentCustomers,
                'change' => round($change, 1)
            ];
            
            Log::debug('Analytics Repository: Customers analytics calculated', [
                'vendor_id' => $vendorId,
                'current_customers' => $currentCustomers,
                'previous_customers' => $previousCustomers,
                'change_percentage' => $change
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Analytics Repository: Failed to get customers analytics', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId,
                'date_range' => $dateRange
            ]);
            
            // Return default values on error
            return [
                'total' => 0,
                'change' => 0
            ];
        }
    }

    /**
     * Get products analytics
     */
    public function getProductsAnalytics(int $vendorId, array $dateRange): array
    {
        try {
            $this->validateVendorId($vendorId);
            $this->validateDateRange($dateRange);
            
            $currentProducts = Product::where('vendor_id', $vendorId)
                ->whereBetween('created_at', $dateRange)
                ->count();

            $previousProducts = Product::where('vendor_id', $vendorId)
                ->whereBetween('created_at', $this->getPreviousDateRange($dateRange))
                ->count();

            $change = $previousProducts > 0 ? (($currentProducts - $previousProducts) / $previousProducts) * 100 : 0;

            $result = [
                'total' => $currentProducts,
                'change' => round($change, 1)
            ];
            
            Log::debug('Analytics Repository: Products analytics calculated', [
                'vendor_id' => $vendorId,
                'current_products' => $currentProducts,
                'previous_products' => $previousProducts,
                'change_percentage' => $change
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Analytics Repository: Failed to get products analytics', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId,
                'date_range' => $dateRange
            ]);
            
            // Return default values on error
            return [
                'total' => 0,
                'change' => 0
            ];
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(int $vendorId, array $dateRange): array
    {
        try {
            $this->validateVendorId($vendorId);
            $this->validateDateRange($dateRange);
            
            // Average Order Value
            $totalRevenue = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->where('orders.payment_status', 'paid')
                ->whereBetween('orders.created_at', $dateRange)
                ->sum(DB::raw('order_items.quantity * order_items.price'));

            $totalOrders = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->where('orders.payment_status', 'paid')
                ->whereBetween('orders.created_at', $dateRange)
                ->distinct('orders.id')
                ->count('orders.id');

            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

            // Conversion Rate (simplified - would need visitor data)
            $conversionRate = 3.2; // Mock data - would calculate from actual visitor data

            // Return Rate
            $returnedOrders = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->where('orders.status', 'refunded')
                ->whereBetween('orders.created_at', $dateRange)
                ->distinct('orders.id')
                ->count('orders.id');

            $returnRate = $totalOrders > 0 ? ($returnedOrders / $totalOrders) * 100 : 0;

            // Customer Satisfaction (mock data - would come from reviews)
            $customerSatisfaction = 4.6;

            $result = [
                'average_order_value' => round($averageOrderValue, 2),
                'conversion_rate' => $conversionRate,
                'return_rate' => round($returnRate, 1),
                'customer_satisfaction' => $customerSatisfaction
            ];
            
            Log::debug('Analytics Repository: Performance metrics calculated', [
                'vendor_id' => $vendorId,
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'average_order_value' => $averageOrderValue
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Analytics Repository: Failed to get performance metrics', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId,
                'date_range' => $dateRange
            ]);
            
            // Return default values on error
            return [
                'average_order_value' => 0,
                'conversion_rate' => 0,
                'return_rate' => 0,
                'customer_satisfaction' => 0
            ];
        }
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $vendorId, int $limit = 5): array
    {
        try {
            // Get unique order IDs for this vendor
            $orderIds = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('products.vendor_id', $vendorId)
                ->select('orders.id')
                ->distinct()
                ->orderBy('orders.created_at', 'desc')
                ->limit($limit)
                ->pluck('orders.id')
                ->toArray();
            
            if (empty($orderIds)) {
                return [];
            }
            
            // Get the actual order details
            $orders = Order::join('users', 'orders.user_id', '=', 'users.id')
                ->whereIn('orders.id', $orderIds)
                ->select([
                    'orders.order_number as order_number',
                    'users.first_name as first_name',
                    'users.last_name as last_name',
                    'orders.total as amount',
                    'orders.status',
                    'orders.created_at as date'
                ])
                ->orderBy('orders.created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    // Convert the date string to Carbon instance for proper formatting
                    $date = is_string($order->date) ? Carbon::parse($order->date) : $order->date;
                    
                    return [
                        'id' => $order->order_number,
                        'customer' => $order->first_name . ' ' . $order->last_name,
                        'amount' => round($order->amount, 2),
                        'status' => $order->status,
                        'date' => $date->format('Y-m-d')
                    ];
                })
                ->toArray();
            
            Log::debug('Analytics Repository: Recent orders fetched', [
                'vendor_id' => $vendorId,
                'count' => count($orders)
            ]);
            
            return $orders;
            
        } catch (\Exception $e) {
            Log::error('Analytics Repository: Failed to get recent orders', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId
            ]);
            
            // Return empty array on error
            return [];
        }
    }

    /**
     * Get top performing products
     */
    public function getTopProducts(int $vendorId, array $dateRange, int $limit = 5): array
    {
        try {
            $this->validateVendorId($vendorId);
            $this->validateDateRange($dateRange);
            
            $products = Product::join('order_items', 'products.id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('products.vendor_id', $vendorId)
                ->where('orders.payment_status', 'paid')
                ->whereBetween('orders.created_at', $dateRange)
                ->select([
                    'products.name',
                    DB::raw('SUM(order_items.quantity) as sales'),
                    DB::raw('SUM(order_items.quantity * order_items.price) as revenue')
                ])
                ->groupBy('products.id', 'products.name')
                ->orderBy('sales', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($product, $index) {
                    // Mock growth data - would calculate from previous period
                    $growth = rand(-10, 25);
                    
                    return [
                        'name' => $product->name,
                        'sales' => $product->sales,
                        'revenue' => round($product->revenue, 2),
                        'growth' => $growth
                    ];
                })
                ->toArray();
            
            Log::debug('Analytics Repository: Top products fetched', [
                'vendor_id' => $vendorId,
                'count' => count($products)
            ]);
            
            return $products;
            
        } catch (\Exception $e) {
            Log::error('Analytics Repository: Failed to get top products', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId,
                'date_range' => $dateRange
            ]);
            
            // Return empty array on error
            return [];
        }
    }

    /**
     * Get sales trend data
     */
    public function getSalesTrend(int $vendorId, array $dateRange): array
    {
        try {
            $this->validateVendorId($vendorId);
            $this->validateDateRange($dateRange);
            
            $startDate = Carbon::parse($dateRange[0]);
            $endDate = Carbon::parse($dateRange[1]);
            
            $trend = [];
            $current = $startDate->copy();

            while ($current <= $endDate) {
                $monthStart = $current->copy()->startOfMonth();
                $monthEnd = $current->copy()->endOfMonth();

                $monthlyRevenue = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->where('products.vendor_id', $vendorId)
                    ->where('orders.payment_status', 'paid')
                    ->whereBetween('orders.created_at', [$monthStart, $monthEnd])
                    ->sum(DB::raw('order_items.quantity * order_items.price'));

                $monthlyOrders = Order::join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->where('products.vendor_id', $vendorId)
                    ->where('orders.payment_status', 'paid')
                    ->whereBetween('orders.created_at', [$monthStart, $monthEnd])
                    ->distinct('orders.id')
                    ->count('orders.id');

                $trend[] = [
                    'month' => $current->format('M'),
                    'sales' => round($monthlyRevenue, 2),
                    'orders' => $monthlyOrders
                ];

                $current->addMonth();
            }

            Log::debug('Analytics Repository: Sales trend calculated', [
                'vendor_id' => $vendorId,
                'periods' => count($trend)
            ]);

            return $trend;
            
        } catch (\Exception $e) {
            Log::error('Analytics Repository: Failed to get sales trend', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId,
                'date_range' => $dateRange
            ]);
            
            // Return empty array on error
            return [];
        }
    }

    /**
     * Get date range based on period
     */
    private function getDateRange(string $period): array
    {
        $endDate = Carbon::now();
        
        switch ($period) {
            case '7d':
                $startDate = $endDate->copy()->subDays(7);
                break;
            case '30d':
                $startDate = $endDate->copy()->subDays(30);
                break;
            case '90d':
                $startDate = $endDate->copy()->subDays(90);
                break;
            case '1y':
                $startDate = $endDate->copy()->subYear();
                break;
            default:
                $startDate = $endDate->copy()->subDays(30);
        }

        return [$startDate, $endDate];
    }

    /**
     * Get previous date range for comparison
     */
    private function getPreviousDateRange(array $currentRange): array
    {
        $currentStart = Carbon::parse($currentRange[0]);
        $currentEnd = Carbon::parse($currentRange[1]);
        
        $duration = $currentEnd->diffInDays($currentStart);
        
        $previousEnd = $currentStart->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($duration);

        return [$previousStart, $previousEnd];
    }
}
