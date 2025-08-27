<?php

namespace Modules\Vendor\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class VendorFinancialController extends Controller
{
    /**
     * Get vendor financial overview
     */
    public function getFinancialOverview(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor account not found'
                ], 404);
            }

            $vendorId = $user->vendor->id;
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
            $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

            // Convert string dates to Carbon instances
            if (is_string($dateFrom)) {
                $dateFrom = Carbon::parse($dateFrom);
            }
            if (is_string($dateTo)) {
                $dateTo = Carbon::parse($dateTo);
            }

            // Get orders for this vendor
            $orders = DB::table('orders as o')
                ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->where('p.vendor_id', $vendorId)
                ->where('p.deleted_at', null)
                ->where('oi.deleted_at', null)
                ->where('o.deleted_at', null)
                ->whereBetween('o.created_at', [$dateFrom, $dateTo])
                ->select([
                    'o.id',
                    'o.order_number',
                    'o.status',
                    'o.payment_status',
                    'o.total',
                    'o.created_at',
                    'oi.quantity',
                    'oi.price',
                    'oi.subtotal',
                    'oi.total as item_total',
                    'p.name as product_name',
                    'p.sku as product_sku'
                ])
                ->get();

            // Calculate financial metrics
            $totalRevenue = $orders->where('payment_status', 'paid')->sum('total');
            $totalOrders = $orders->count();
            $paidOrders = $orders->where('payment_status', 'paid')->count();
            $pendingOrders = $orders->where('payment_status', 'pending')->count();
            $cancelledOrders = $orders->where('status', 'cancelled')->count();
            
            // Calculate vendor earnings (assuming 80% commission for vendor, 20% for platform)
            $vendorCommission = 0.80; // 80% commission
            $totalEarnings = $totalRevenue * $vendorCommission;
            
            // Calculate average order value
            $averageOrderValue = $paidOrders > 0 ? $totalRevenue / $paidOrders : 0;
            
            // Calculate conversion rate
            $conversionRate = $totalOrders > 0 ? ($paidOrders / $totalOrders) * 100 : 0;

            // Get monthly revenue data for charts
            $monthlyRevenue = DB::table('orders as o')
                ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->where('p.vendor_id', $vendorId)
                ->where('p.deleted_at', null)
                ->where('oi.deleted_at', null)
                ->where('o.deleted_at', null)
                ->where('o.payment_status', 'paid')
                ->whereBetween('o.created_at', [$dateFrom->copy()->subMonths(11), $dateTo])
                ->select(
                    DB::raw('DATE_FORMAT(o.created_at, "%Y-%m") as month'),
                    DB::raw('SUM(o.total) as revenue'),
                    DB::raw('COUNT(DISTINCT o.id) as orders_count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Get top selling products
            $topProducts = DB::table('orders as o')
                ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->where('p.vendor_id', $vendorId)
                ->where('p.deleted_at', null)
                ->where('oi.deleted_at', null)
                ->where('o.deleted_at', null)
                ->where('o.payment_status', 'paid')
                ->whereBetween('o.created_at', [$dateFrom, $dateTo])
                ->select([
                    'p.id',
                    'p.name',
                    'p.sku',
                    DB::raw('SUM(oi.quantity) as total_quantity'),
                    DB::raw('SUM(oi.total) as total_revenue'),
                    DB::raw('COUNT(DISTINCT o.id) as orders_count')
                ])
                ->groupBy('p.id', 'p.name', 'p.sku')
                ->orderByDesc('total_revenue')
                ->limit(10)
                ->get();

            // Get recent transactions
            $recentTransactions = $orders
                ->where('payment_status', 'paid')
                ->sortByDesc('created_at')
                ->take(10)
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'amount' => $order->total,
                        'status' => $order->status,
                        'date' => Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
                        'product_name' => $order->product_name,
                        'quantity' => $order->quantity
                    ];
                });

            $financialOverview = [
                'summary' => [
                    'total_revenue' => round($totalRevenue, 2),
                    'total_earnings' => round($totalEarnings, 2),
                    'total_orders' => $totalOrders,
                    'paid_orders' => $paidOrders,
                    'pending_orders' => $pendingOrders,
                    'cancelled_orders' => $cancelledOrders,
                    'average_order_value' => round($averageOrderValue, 2),
                    'conversion_rate' => round($conversionRate, 2),
                    'vendor_commission_rate' => $vendorCommission * 100 . '%'
                ],
                'monthly_revenue' => $monthlyRevenue,
                'top_products' => $topProducts,
                'recent_transactions' => $recentTransactions,
                'date_range' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $financialOverview
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch financial overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed financial analytics
     */
    public function getFinancialAnalytics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor account not found'
                ], 404);
            }

            $vendorId = $user->vendor->id;
            $period = $request->get('period', 'month'); // day, week, month, year
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
            $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

            if (is_string($dateFrom)) {
                $dateFrom = Carbon::parse($dateFrom);
            }
            if (is_string($dateTo)) {
                $dateTo = Carbon::parse($dateTo);
            }

            // Revenue trends
            $revenueTrends = $this->getRevenueTrends($vendorId, $period, $dateFrom, $dateTo);
            
            // Profit margins
            $profitMargins = $this->getProfitMargins($vendorId, $dateFrom, $dateTo);
            
            // Sales performance
            $salesPerformance = $this->getSalesPerformance($vendorId, $dateFrom, $dateTo);
            
            // Customer insights
            $customerInsights = $this->getCustomerInsights($vendorId, $dateFrom, $dateTo);

            $analytics = [
                'revenue_trends' => $revenueTrends,
                'profit_margins' => $profitMargins,
                'sales_performance' => $salesPerformance,
                'customer_insights' => $customerInsights
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch financial analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue trends
     */
    private function getRevenueTrends($vendorId, $period, $dateFrom, $dateTo)
    {
        $dateFormat = match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m'
        };

        return DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->where('p.vendor_id', $vendorId)
            ->where('p.deleted_at', null)
            ->where('oi.deleted_at', null)
            ->where('o.deleted_at', null)
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$dateFrom, $dateTo])
            ->select(
                DB::raw("DATE_FORMAT(o.created_at, '{$dateFormat}') as period"),
                DB::raw('SUM(o.total) as revenue'),
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('AVG(o.total) as avg_order_value')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * Get profit margins
     */
    private function getProfitMargins($vendorId, $dateFrom, $dateTo)
    {
        // This is a simplified calculation - in a real scenario, you'd need cost data
        $vendorCommission = 0.80;
        
        $revenue = DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->where('p.vendor_id', $vendorId)
            ->where('p.deleted_at', null)
            ->where('oi.deleted_at', null)
            ->where('o.deleted_at', null)
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$dateFrom, $dateTo])
            ->sum('o.total');

        $vendorEarnings = $revenue * $vendorCommission;
        $platformFees = $revenue * (1 - $vendorCommission);
        
        return [
            'total_revenue' => round($revenue, 2),
            'vendor_earnings' => round($vendorEarnings, 2),
            'platform_fees' => round($platformFees, 2),
            'profit_margin_percentage' => $revenue > 0 ? round(($vendorEarnings / $revenue) * 100, 2) : 0,
            'commission_rate' => $vendorCommission * 100
        ];
    }

    /**
     * Get sales performance
     */
    private function getSalesPerformance($vendorId, $dateFrom, $dateTo)
    {
        $currentPeriod = DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->where('p.vendor_id', $vendorId)
            ->where('p.deleted_at', null)
            ->where('oi.deleted_at', null)
            ->where('o.deleted_at', null)
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$dateFrom, $dateTo])
            ->select(
                DB::raw('SUM(o.total) as revenue'),
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('AVG(o.total) as avg_order_value')
            )
            ->first();

        // Previous period for comparison
        $periodLength = $dateFrom->diffInDays($dateTo);
        $previousDateFrom = $dateFrom->copy()->subDays($periodLength);
        $previousDateTo = $dateFrom->copy()->subDay();

        $previousPeriod = DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->where('p.vendor_id', $vendorId)
            ->where('p.deleted_at', null)
            ->where('oi.deleted_at', null)
            ->where('o.deleted_at', null)
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$previousDateFrom, $previousDateTo])
            ->select(
                DB::raw('SUM(o.total) as revenue'),
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('AVG(o.total) as avg_order_value')
            )
            ->first();

        // Calculate growth percentages
        $revenueGrowth = $previousPeriod->revenue > 0 
            ? (($currentPeriod->revenue - $previousPeriod->revenue) / $previousPeriod->revenue) * 100 
            : 0;
        
        $ordersGrowth = $previousPeriod->orders_count > 0 
            ? (($currentPeriod->orders_count - $previousPeriod->orders_count) / $previousPeriod->orders_count) * 100 
            : 0;

        return [
            'current_period' => [
                'revenue' => round($currentPeriod->revenue ?? 0, 2),
                'orders_count' => $currentPeriod->orders_count ?? 0,
                'avg_order_value' => round($currentPeriod->avg_order_value ?? 0, 2)
            ],
            'previous_period' => [
                'revenue' => round($previousPeriod->revenue ?? 0, 2),
                'orders_count' => $previousPeriod->orders_count ?? 0,
                'avg_order_value' => round($previousPeriod->avg_order_value ?? 0, 2)
            ],
            'growth' => [
                'revenue_growth' => round($revenueGrowth, 2),
                'orders_growth' => round($ordersGrowth, 2)
            ]
        ];
    }

    /**
     * Get customer insights
     */
    private function getCustomerInsights($vendorId, $dateFrom, $dateTo)
    {
        $customerData = DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->join('users as u', 'o.user_id', '=', 'u.id')
            ->where('p.vendor_id', $vendorId)
            ->where('p.deleted_at', null)
            ->where('oi.deleted_at', null)
            ->where('o.deleted_at', null)
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$dateFrom, $dateTo])
            ->select([
                'u.id',
                'u.first_name',
                'u.last_name',
                'u.email',
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('SUM(o.total) as total_spent'),
                DB::raw('AVG(o.total) as avg_order_value'),
                DB::raw('MAX(o.created_at) as last_order_date')
            ])
            ->groupBy('u.id', 'u.first_name', 'u.last_name', 'u.email')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        $totalCustomers = $customerData->count();
        $totalRevenue = $customerData->sum('total_spent');
        $avgCustomerValue = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

        return [
            'top_customers' => $customerData,
            'summary' => [
                'total_customers' => $totalCustomers,
                'total_revenue' => round($totalRevenue, 2),
                'avg_customer_value' => round($avgCustomerValue, 2)
            ]
        ];
    }

    /**
     * Get financial reports
     */
    public function getFinancialReports(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor account not found'
                ], 404);
            }

            $vendorId = $user->vendor->id;
            $reportType = $request->get('report_type', 'sales'); // sales, products, customers
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth());
            $dateTo = $request->get('date_to', Carbon::now()->endOfMonth());

            if (is_string($dateFrom)) {
                $dateFrom = Carbon::parse($dateFrom);
            }
            if (is_string($dateTo)) {
                $dateTo = Carbon::parse($dateTo);
            }

            $report = match($reportType) {
                'sales' => $this->getSalesReport($vendorId, $dateFrom, $dateTo),
                'products' => $this->getProductsReport($vendorId, $dateFrom, $dateTo),
                'customers' => $this->getCustomersReport($vendorId, $dateFrom, $dateTo),
                default => $this->getSalesReport($vendorId, $dateFrom, $dateTo)
            };

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch financial reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales report
     */
    private function getSalesReport($vendorId, $dateFrom, $dateTo)
    {
        return DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->where('p.vendor_id', $vendorId)
            ->where('p.deleted_at', null)
            ->where('oi.deleted_at', null)
            ->where('o.deleted_at', null)
            ->whereBetween('o.created_at', [$dateFrom, $dateTo])
            ->select([
                'o.id',
                'o.order_number',
                'o.status',
                'o.payment_status',
                'o.total',
                'o.created_at',
                'oi.quantity',
                'oi.price',
                'oi.total as item_total',
                'p.name as product_name',
                'p.sku as product_sku'
            ])
            ->orderBy('o.created_at', 'desc')
            ->get();
    }

    /**
     * Get products report
     */
    private function getProductsReport($vendorId, $dateFrom, $dateTo)
    {
        return DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->where('p.vendor_id', $vendorId)
            ->where('p.deleted_at', null)
            ->where('oi.deleted_at', null)
            ->where('o.deleted_at', null)
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$dateFrom, $dateTo])
            ->select([
                'p.id',
                'p.name',
                'p.sku',
                DB::raw('SUM(oi.quantity) as total_quantity'),
                DB::raw('SUM(oi.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('AVG(oi.price) as avg_price')
            ])
            ->groupBy('p.id', 'p.name', 'p.sku')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Get customers report
     */
    private function getCustomersReport($vendorId, $dateFrom, $dateTo)
    {
        return DB::table('orders as o')
            ->join('order_items as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->join('users as u', 'o.user_id', '=', 'u.id')
            ->where('p.vendor_id', $vendorId)
            ->where('p.deleted_at', null)
            ->where('oi.deleted_at', null)
            ->where('o.deleted_at', null)
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$dateFrom, $dateTo])
            ->select([
                'u.id',
                'u.first_name',
                'u.last_name',
                'u.email',
                DB::raw('COUNT(DISTINCT o.id) as orders_count'),
                DB::raw('SUM(o.total) as total_spent'),
                DB::raw('AVG(o.total) as avg_order_value'),
                DB::raw('MIN(o.created_at) as first_order_date'),
                DB::raw('MAX(o.created_at) as last_order_date')
            ])
            ->groupBy('u.id', 'u.first_name', 'u.last_name', 'u.email')
            ->orderByDesc('total_spent')
            ->get();
    }
}
