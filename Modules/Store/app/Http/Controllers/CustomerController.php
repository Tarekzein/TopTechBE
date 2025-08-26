<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Store\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Get all customers (admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'search' => 'string|max:255',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'min_orders' => 'nullable|integer|min:1',
                'min_spent' => 'nullable|numeric|min:0',
                'sort_by' => 'in:name,email,orders_count,total_spent,created_at',
                'sort_direction' => 'in:asc,desc',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional validation: date_to should be after or equal to date_from if both are provided
            if ($request->filled('date_from') && $request->filled('date_to')) {
                if (strtotime($request->date_to) < strtotime($request->date_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => ['date_to' => ['The date to must be after or equal to date from.']]
                    ], 422);
                }
            }

            $filters = $request->only([
                'page', 'per_page', 'search', 'date_from', 'date_to',
                'min_orders', 'min_spent', 'sort_by', 'sort_direction'
            ]);

            $customers = $this->customerService->getAllCustomers($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'customers' => $customers->items(),
                    'pagination' => [
                        'current_page' => $customers->currentPage(),
                        'last_page' => $customers->lastPage(),
                        'per_page' => $customers->perPage(),
                        'total' => $customers->total(),
                        'from' => $customers->firstItem(),
                        'to' => $customers->lastItem(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required.' ? 403 : 500);
        }
    }

    /**
     * Get customers for authenticated vendor.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function vendorIndex(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'search' => 'string|max:255',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'min_orders' => 'nullable|integer|min:1',
                'min_spent' => 'nullable|numeric|min:0',
                'sort_by' => 'in:name,email,orders_count,total_spent,created_at',
                'sort_direction' => 'in:asc,desc',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional validation: date_to should be after or equal to date_from if both are provided
            if ($request->filled('date_from') && $request->filled('date_to')) {
                if (strtotime($request->date_to) < strtotime($request->date_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => ['date_to' => ['The date to must be after or equal to date from.']]
                    ], 422);
                }
            }

            $filters = $request->only([
                'page', 'per_page', 'search', 'date_from', 'date_to',
                'min_orders', 'min_spent', 'sort_by', 'sort_direction'
            ]);

            $customers = $this->customerService->getVendorCustomers($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'customers' => $customers->items(),
                    'pagination' => [
                        'current_page' => $customers->currentPage(),
                        'last_page' => $customers->lastPage(),
                        'per_page' => $customers->perPage(),
                        'total' => $customers->total(),
                        'from' => $customers->firstItem(),
                        'to' => $customers->lastItem(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customers for specific vendor (admin only).
     *
     * @param Request $request
     * @param int $vendorId
     * @return JsonResponse
     */
    public function adminVendorIndex(Request $request, int $vendorId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'search' => 'string|max:255',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'min_orders' => 'nullable|integer|min:1',
                'min_spent' => 'nullable|numeric|min:0',
                'sort_by' => 'in:name,email,orders_count,total_spent,created_at',
                'sort_direction' => 'in:asc,desc',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional validation: date_to should be after or equal to date_from if both are provided
            if ($request->filled('date_from') && $request->filled('date_to')) {
                if (strtotime($request->date_to) < strtotime($request->date_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => ['date_to' => ['The date to must be after or equal to date from.']]
                    ], 422);
                }
            }

            $filters = $request->only([
                'page', 'per_page', 'search', 'date_from', 'date_to',
                'min_orders', 'min_spent', 'sort_by', 'sort_direction'
            ]);

            $customers = $this->customerService->getVendorCustomersForAdmin($vendorId, $filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'customers' => $customers->items(),
                    'pagination' => [
                        'current_page' => $customers->currentPage(),
                        'last_page' => $customers->lastPage(),
                        'per_page' => $customers->perPage(),
                        'total' => $customers->total(),
                        'from' => $customers->firstItem(),
                        'to' => $customers->lastItem(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required.' ? 403 : 500);
        }
    }

    /**
     * Get customer details (admin only).
     *
     * @param int $customerId
     * @return JsonResponse
     */
    public function show(int $customerId): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomerDetails($customerId);

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required.' ? 403 : 500);
        }
    }

    /**
     * Get customer details for authenticated vendor.
     *
     * @param int $customerId
     * @return JsonResponse
     */
    public function vendorShow(int $customerId): JsonResponse
    {
        try {
            $customer = $this->customerService->getVendorCustomerDetails($customerId);

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found or not associated with your vendor.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer details for specific vendor (admin only).
     *
     * @param int $customerId
     * @param int $vendorId
     * @return JsonResponse
     */
    public function adminVendorShow(int $customerId, int $vendorId): JsonResponse
    {
        try {
            $customer = $this->customerService->getVendorCustomerDetailsForAdmin($customerId, $vendorId);

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found or not associated with the specified vendor.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required.' ? 403 : 500);
        }
    }

    /**
     * Get customers analytics (admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional validation: date_to should be after or equal to date_from if both are provided
            if ($request->filled('date_from') && $request->filled('date_to')) {
                if (strtotime($request->date_to) < strtotime($request->date_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => ['date_to' => ['The date to must be after or equal to date from.']]
                    ], 422);
                }
            }

            $filters = $request->only(['date_from', 'date_to']);
            $analytics = $this->customerService->getCustomersAnalytics($filters);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required.' ? 403 : 500);
        }
    }

    /**
     * Get customers analytics for authenticated vendor.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function vendorAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional validation: date_to should be after or equal to date_from if both are provided
            if ($request->filled('date_from') && $request->filled('date_to')) {
                if (strtotime($request->date_to) < strtotime($request->date_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => ['date_to' => ['The date to must be after or equal to date from.']]
                    ], 422);
                }
            }

            $filters = $request->only(['date_from', 'date_to']);
            $analytics = $this->customerService->getVendorCustomersAnalytics($filters);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customers analytics for specific vendor (admin only).
     *
     * @param Request $request
     * @param int $vendorId
     * @return JsonResponse
     */
    public function adminVendorAnalytics(Request $request, int $vendorId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional validation: date_to should be after or equal to date_from if both are provided
            if ($request->filled('date_from') && $request->filled('date_to')) {
                if (strtotime($request->date_to) < strtotime($request->date_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => ['date_to' => ['The date to must be after or equal to date from.']]
                    ], 422);
                }
            }

            $filters = $request->only(['date_from', 'date_to']);
            $analytics = $this->customerService->getVendorCustomersAnalyticsForAdmin($vendorId, $filters);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required.' ? 403 : 500);
        }
    }

    /**
     * Export customers data.
     *
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'string|max:255',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'min_orders' => 'nullable|integer|min:1',
                'min_spent' => 'nullable|numeric|min:0',
                'format' => 'in:json,csv',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional validation: date_to should be after or equal to date_from if both are provided
            if ($request->filled('date_from') && $request->filled('date_to')) {
                if (strtotime($request->date_to) < strtotime($request->date_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => ['date_to' => ['The date to must be after or equal to date from.']]
                    ], 422);
                }
            }

            $filters = $request->only([
                'search', 'date_from', 'date_to', 'min_orders', 'min_spent'
            ]);

            $exportData = $this->customerService->exportCustomersData($filters);
            $format = $request->input('format', 'json');

            if ($format === 'csv') {
                $filename = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ];

                $callback = function() use ($exportData) {
                    $file = fopen('php://output', 'w');
                    
                    // Add CSV headers
                    if (!empty($exportData)) {
                        fputcsv($file, array_keys($exportData[0]));
                        
                        // Add data rows
                        foreach ($exportData as $row) {
                            fputcsv($file, $row);
                        }
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }

            return response()->json([
                'success' => true,
                'data' => $exportData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Unauthorized: Admin access required.' ? 403 : 500);
        }
    }

    /**
     * Export vendor customers data.
     *
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function vendorExport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'string|max:255',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'min_orders' => 'nullable|integer|min:1',
                'min_spent' => 'nullable|numeric|min:0',
                'format' => 'in:json,csv',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional validation: date_to should be after or equal to date_from if both are provided
            if ($request->filled('date_from') && $request->filled('date_to')) {
                if (strtotime($request->date_to) < strtotime($request->date_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => ['date_to' => ['The date to must be after or equal to date from.']]
                    ], 422);
                }
            }

            $filters = $request->only([
                'search', 'date_from', 'date_to', 'min_orders', 'min_spent'
            ]);

            try {
                $exportData = $this->customerService->exportVendorCustomersData($filters);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export failed: ' . $e->getMessage(),
                    'debug' => [
                        'user' => auth()->user() ? auth()->user()->id : 'No user',
                        'vendor' => auth()->user() && auth()->user()->vendor ? auth()->user()->vendor->id : 'No vendor',
                        'filters' => $filters,
                        'trace' => $e->getTraceAsString()
                    ]
                ], 500);
            }
            $format = $request->input('format', 'json');

            if ($format === 'csv') {
                $filename = 'vendor_customers_' . date('Y-m-d_H-i-s') . '.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ];

                $callback = function() use ($exportData) {
                    $file = fopen('php://output', 'w');
                    
                    // Add CSV headers
                    if (!empty($exportData)) {
                        fputcsv($file, array_keys($exportData[0]));
                        
                        // Add data rows
                        foreach ($exportData as $row) {
                            fputcsv($file, $row);
                        }
                    }
                    
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }

            return response()->json([
                'success' => true,
                'data' => $exportData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer summary statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional validation: date_to should be after or equal to date_from if both are provided
            if ($request->filled('date_from') && $request->filled('date_to')) {
                if (strtotime($request->date_to) < strtotime($request->date_from)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors' => ['date_to' => ['The date to must be after or equal to date from.']]
                    ], 422);
                }
            }

            $filters = $request->only(['date_from', 'date_to']);
            $summary = $this->customerService->getCustomersSummary($filters);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
