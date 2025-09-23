<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\Services\AnalyticsService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AnalyticsController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get vendor ID from authenticated user
     */
    private function getVendorId(Request $request): ?int
    {
        $user = $request->user();
        
        if (!$user) {
            return null;
        }

       $id = $user->vendor?->id ?? $user->id;
       Log::info('Analytics: Vendor ID', ['id' => $id]);

       return $id;
    }

    /**
     * Get vendor dashboard analytics
     */
    public function getVendorDashboard(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $request->validate([
                'period' => 'sometimes|string|in:7d,30d,90d,1y'
            ]);

            $vendorId = $this->getVendorId($request);
            
            if (!$vendorId) {
                Log::warning('Analytics: Vendor ID not found for user', [
                    'user_id' => $request->user()->id,
                    'user_roles' => $request->user()->roles->pluck('name')->toArray()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor ID not found. Please ensure you have vendor access.'
                ], 400);
            }

            Log::info('Analytics: Fetching dashboard analytics', [
                'vendor_id' => $vendorId,
                'period' => $request->get('period', '30d'),
                'user_id' => $request->user()->id
            ]);

            $result = $this->analyticsService->getVendorDashboardAnalytics($vendorId, $request);

            Log::info('Analytics: Dashboard analytics fetched successfully', [
                'vendor_id' => $vendorId,
                'has_data' => !empty($result['data'])
            ]);

            return response()->json($result);

        } catch (ValidationException $e) {
            Log::warning('Analytics: Validation failed for dashboard request', [
                'errors' => $e->errors(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Analytics: Failed to fetch dashboard analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
                'vendor_id' => $vendorId ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics data. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $request->validate([
                'period' => 'sometimes|string|in:7d,30d,90d,1y'
            ]);

            $vendorId = $this->getVendorId($request);
            
            if (!$vendorId) {
                Log::warning('Analytics: Vendor ID not found for revenue analytics', [
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor ID not found. Please ensure you have vendor access.'
                ], 400);
            }

            Log::info('Analytics: Fetching revenue analytics', [
                'vendor_id' => $vendorId,
                'period' => $request->get('period', '30d')
            ]);

            $result = $this->analyticsService->getRevenueAnalytics($vendorId, $request);

            return response()->json($result);

        } catch (ValidationException $e) {
            Log::warning('Analytics: Validation failed for revenue request', [
                'errors' => $e->errors(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Analytics: Failed to fetch revenue analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
                'vendor_id' => $vendorId ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue analytics. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Admin: Daily orders trend between date range (defaults last 30 days)
     */
    public function getDailyOrdersTrend(Request $request): JsonResponse
    {
        try {
            try {
                $from = $request->filled('date_from') ? Carbon::parse($request->get('date_from')) : now()->subDays(30);
            } catch (\Throwable $e) { $from = now()->subDays(30); }
            try {
                $to = $request->filled('date_to') ? Carbon::parse($request->get('date_to')) : now();
            } catch (\Throwable $e) { $to = now(); }
            if ($from->greaterThan($to)) { [$from, $to] = [$to->copy(), $from->copy()]; }

            $rows = DB::table('orders')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error('Analytics: Failed to fetch daily orders trend', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch daily orders trend',
            ], 500);
        }
    }

    /**
     * Get orders analytics
     */
    public function getOrdersAnalytics(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $request->validate([
                'period' => 'sometimes|string|in:7d,30d,90d,1y'
            ]);

            $vendorId = $this->getVendorId($request);
            
            if (!$vendorId) {
                Log::warning('Analytics: Vendor ID not found for orders analytics', [
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor ID not found. Please ensure you have vendor access.'
                ], 400);
            }

            Log::info('Analytics: Fetching orders analytics', [
                'vendor_id' => $vendorId,
                'period' => $request->get('period', '30d')
            ]);

            $result = $this->analyticsService->getOrdersAnalytics($vendorId, $request);

            return response()->json($result);

        } catch (ValidationException $e) {
            Log::warning('Analytics: Validation failed for orders request', [
                'errors' => $e->errors(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Analytics: Failed to fetch orders analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
                'vendor_id' => $vendorId ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders analytics. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get products analytics
     */
    public function getProductsAnalytics(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $request->validate([
                'period' => 'sometimes|string|in:7d,30d,90d,1y'
            ]);

            $vendorId = $this->getVendorId($request);
            
            if (!$vendorId) {
                Log::warning('Analytics: Vendor ID not found for products analytics', [
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor ID not found. Please ensure you have vendor access.'
                ], 400);
            }

            Log::info('Analytics: Fetching products analytics', [
                'vendor_id' => $vendorId,
                'period' => $request->get('period', '30d')
            ]);

            $result = $this->analyticsService->getProductsAnalytics($vendorId, $request);

            return response()->json($result);

        } catch (ValidationException $e) {
            Log::warning('Analytics: Validation failed for products request', [
                'errors' => $e->errors(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Analytics: Failed to fetch products analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
                'vendor_id' => $vendorId ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products analytics. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get customers analytics
     */
    public function getCustomersAnalytics(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $request->validate([
                'period' => 'sometimes|string|in:7d,30d,90d,1y'
            ]);

            $vendorId = $this->getVendorId($request);
            
            if (!$vendorId) {
                Log::warning('Analytics: Vendor ID not found for customers analytics', [
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor ID not found. Please ensure you have vendor access.'
                ], 400);
            }

            Log::info('Analytics: Fetching customers analytics', [
                'vendor_id' => $vendorId,
                'period' => $request->get('period', '30d')
            ]);

            $result = $this->analyticsService->getCustomersAnalytics($vendorId, $request);

            return response()->json($result);

        } catch (ValidationException $e) {
            Log::warning('Analytics: Validation failed for customers request', [
                'errors' => $e->errors(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Analytics: Failed to fetch customers analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
                'vendor_id' => $vendorId ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customers analytics. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export analytics report
     */
    public function exportReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => 'sometimes|string|in:7d,30d,90d,1y',
                'format' => 'sometimes|string|in:json,csv'
            ]);

            $vendorId = $this->getVendorId($request);
            
            if (!$vendorId) {
                Log::warning('Analytics: Vendor ID not found for export report', [
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor ID not found. Please ensure you have vendor access.'
                ], 400);
            }

            Log::info('Analytics: Exporting report', [
                'vendor_id' => $vendorId,
                'period' => $request->get('period', '30d'),
                'format' => $request->get('format', 'json')
            ]);

            $result = $this->analyticsService->exportAnalyticsReport($vendorId, $request);

            Log::info('Analytics: Report exported successfully', [
                'vendor_id' => $vendorId,
                'format' => $request->get('format', 'json')
            ]);

            return response()->json($result);

        } catch (ValidationException $e) {
            Log::warning('Analytics: Validation failed for export request', [
                'errors' => $e->errors(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Analytics: Failed to export report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
                'vendor_id' => $vendorId ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export analytics report. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get analytics summary
     */
    public function getSummary(Request $request): JsonResponse
    {
        try {
            $vendorId = $this->getVendorId($request);
            
            if (!$vendorId) {
                Log::warning('Analytics: Vendor ID not found for summary', [
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor ID not found. Please ensure you have vendor access.'
                ], 400);
            }

            Log::info('Analytics: Fetching summary', [
                'vendor_id' => $vendorId
            ]);

            $result = $this->analyticsService->getAnalyticsSummary($vendorId);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Analytics: Failed to fetch summary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
                'vendor_id' => $vendorId ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics summary. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get analytics for specific vendor (admin only)
     */
    public function getVendorAnalytics(Request $request, int $vendorId): JsonResponse
    {
        try {
            // Validate vendor ID
            if ($vendorId <= 0) {
                Log::warning('Analytics: Invalid vendor ID provided', [
                    'vendor_id' => $vendorId,
                    'user_id' => $request->user()->id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid vendor ID'
                ], 400);
            }

            // Check if user is admin
            if (!$request->user()->hasRole(['admin', 'super-admin'])) {
                Log::warning('Analytics: Unauthorized access attempt to vendor analytics', [
                    'vendor_id' => $vendorId,
                    'user_id' => $request->user()->id,
                    'user_roles' => $request->user()->roles->pluck('name')->toArray()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access. Admin privileges required.'
                ], 403);
            }

            Log::info('Analytics: Admin fetching vendor analytics', [
                'vendor_id' => $vendorId,
                'admin_user_id' => $request->user()->id
            ]);

            $result = $this->analyticsService->getVendorDashboardAnalytics($vendorId, $request);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Analytics: Failed to fetch vendor analytics (admin)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vendor_id' => $vendorId,
                'admin_user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vendor analytics. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get analytics overview for all vendors (admin only)
     */
    public function getAllVendorsAnalytics(Request $request): JsonResponse
    {
        try {
            // Check if user is admin
            if (!$request->user()->hasRole(['admin', 'super-admin'])) {
                Log::warning('Analytics: Unauthorized access attempt to all vendors analytics', [
                    'user_id' => $request->user()->id,
                    'user_roles' => $request->user()->roles->pluck('name')->toArray()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access. Admin privileges required.'
                ], 403);
            }

            // Validate request parameters
            $request->validate([
                'period' => 'sometimes|string|in:7d,30d,90d,1y'
            ]);

            Log::info('Analytics: Admin fetching all vendors analytics', [
                'admin_user_id' => $request->user()->id,
                'period' => $request->get('period', '30d')
            ]);

            $result = $this->analyticsService->getAllVendorsAnalytics($request);

            Log::info('Analytics: All vendors analytics fetched successfully', [
                'admin_user_id' => $request->user()->id,
                'has_data' => !empty($result['data'])
            ]);

            return response()->json($result);

        } catch (ValidationException $e) {
            Log::warning('Analytics: Validation failed for all vendors request', [
                'errors' => $e->errors(),
                'admin_user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Analytics: Failed to fetch all vendors analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch all vendors analytics. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
