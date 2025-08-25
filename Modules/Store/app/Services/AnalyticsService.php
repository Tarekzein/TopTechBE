<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\AnalyticsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AnalyticsService
{
    protected $analyticsRepository;

    public function __construct(AnalyticsRepository $analyticsRepository)
    {
        $this->analyticsRepository = $analyticsRepository;
    }

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
     * Get empty overview data structure
     */
    private function getEmptyOverviewData(): array
    {
        return [
            'total_revenue' => [
                'value' => 0,
                'change' => 0,
                'currency' => 'USD'
            ],
            'total_orders' => [
                'value' => 0,
                'change' => 0
            ],
            'total_customers' => [
                'value' => 0,
                'change' => 0
            ],
            'total_products' => [
                'value' => 0,
                'change' => 0
            ]
        ];
    }

    /**
     * Get empty performance data structure
     */
    private function getEmptyPerformanceData(): array
    {
        return [
            'average_order_value' => [
                'value' => 0,
                'currency' => 'USD'
            ],
            'conversion_rate' => [
                'value' => 0,
                'unit' => '%'
            ],
            'return_rate' => [
                'value' => 0,
                'unit' => '%'
            ],
            'customer_satisfaction' => [
                'value' => 0,
                'unit' => '/5'
            ]
        ];
    }

    /**
     * Get vendor dashboard analytics
     */
    public function getVendorDashboardAnalytics(int $vendorId, Request $request): array
    {
        try {
            $this->validateVendorId($vendorId);
            
            $period = $request->get('period', '30d');
            $this->validatePeriod($period);
            
            Log::info('Analytics Service: Fetching vendor dashboard analytics', [
                'vendor_id' => $vendorId,
                'period' => $period
            ]);
            
            $analytics = $this->analyticsRepository->getVendorAnalytics($vendorId, $period);
            
            if (empty($analytics)) {
                Log::warning('Analytics Service: No analytics data found', [
                    'vendor_id' => $vendorId,
                    'period' => $period
                ]);
                
                return [
                    'success' => true,
                    'data' => [
                        'overview' => $this->getEmptyOverviewData(),
                        'performance' => $this->getEmptyPerformanceData(),
                        'recent_orders' => [],
                        'top_products' => [],
                        'sales_trend' => [],
                        'period' => $period
                    ]
                ];
            }

            $result = [
                'success' => true,
                'data' => [
                    'overview' => $this->formatOverviewData($analytics),
                    'performance' => $this->formatPerformanceData($analytics['performance']),
                    'recent_orders' => $analytics['recent_orders'],
                    'top_products' => $analytics['top_products'],
                    'sales_trend' => $analytics['sales_trend'],
                    'period' => $period
                ]
            ];
            
            Log::info('Analytics Service: Dashboard analytics processed successfully', [
                'vendor_id' => $vendorId,
                'has_data' => !empty($analytics)
            ]);
            
            return $result;
            
        } catch (InvalidArgumentException $e) {
            Log::error('Analytics Service: Invalid argument in dashboard analytics', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendorId
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Analytics Service: Failed to get dashboard analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vendor_id' => $vendorId
            ]);
            throw $e;
        }
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(int $vendorId, Request $request): array
    {
        $period = $request->get('period', '30d');
        $analytics = $this->analyticsRepository->getVendorAnalytics($vendorId, $period);

        return [
            'success' => true,
            'data' => [
                'revenue' => $analytics['revenue'],
                'sales_trend' => $analytics['sales_trend']
            ]
        ];
    }

    /**
     * Get orders analytics
     */
    public function getOrdersAnalytics(int $vendorId, Request $request): array
    {
        $period = $request->get('period', '30d');
        $analytics = $this->analyticsRepository->getVendorAnalytics($vendorId, $period);

        return [
            'success' => true,
            'data' => [
                'orders' => $analytics['orders'],
                'recent_orders' => $analytics['recent_orders']
            ]
        ];
    }

    /**
     * Get products analytics
     */
    public function getProductsAnalytics(int $vendorId, Request $request): array
    {
        $period = $request->get('period', '30d');
        $analytics = $this->analyticsRepository->getVendorAnalytics($vendorId, $period);

        return [
            'success' => true,
            'data' => [
                'products' => $analytics['products'],
                'top_products' => $analytics['top_products']
            ]
        ];
    }

    /**
     * Get customers analytics
     */
    public function getCustomersAnalytics(int $vendorId, Request $request): array
    {
        $period = $request->get('period', '30d');
        $analytics = $this->analyticsRepository->getVendorAnalytics($vendorId, $period);

        return [
            'success' => true,
            'data' => [
                'customers' => $analytics['customers']
            ]
        ];
    }

    /**
     * Export analytics report
     */
    public function exportAnalyticsReport(int $vendorId, Request $request): array
    {
        $period = $request->get('period', '30d');
        $format = $request->get('format', 'json');
        
        $analytics = $this->analyticsRepository->getVendorAnalytics($vendorId, $period);

        $report = [
            'vendor_id' => $vendorId,
            'period' => $period,
            'generated_at' => now()->toISOString(),
            'overview' => $this->formatOverviewData($analytics),
            'performance' => $this->formatPerformanceData($analytics['performance']),
            'recent_orders' => $analytics['recent_orders'],
            'top_products' => $analytics['top_products'],
            'sales_trend' => $analytics['sales_trend']
        ];

        if ($format === 'csv') {
            return $this->generateCsvReport($report);
        }

        return [
            'success' => true,
            'data' => $report
        ];
    }

    /**
     * Format overview data for frontend
     */
    private function formatOverviewData(array $analytics): array
    {
        return [
            'total_revenue' => [
                'value' => $analytics['revenue']['total'],
                'change' => $analytics['revenue']['change'],
                'currency' => $analytics['revenue']['currency']
            ],
            'total_orders' => [
                'value' => $analytics['orders']['total'],
                'change' => $analytics['orders']['change']
            ],
            'total_customers' => [
                'value' => $analytics['customers']['total'],
                'change' => $analytics['customers']['change']
            ],
            'total_products' => [
                'value' => $analytics['products']['total'],
                'change' => $analytics['products']['change']
            ]
        ];
    }

    /**
     * Format performance data for frontend
     */
    private function formatPerformanceData(array $performance): array
    {
        return [
            'average_order_value' => [
                'value' => $performance['average_order_value'],
                'currency' => 'USD'
            ],
            'conversion_rate' => [
                'value' => $performance['conversion_rate'],
                'unit' => '%'
            ],
            'return_rate' => [
                'value' => $performance['return_rate'],
                'unit' => '%'
            ],
            'customer_satisfaction' => [
                'value' => $performance['customer_satisfaction'],
                'unit' => '/5'
            ]
        ];
    }

    /**
     * Generate CSV report
     */
    private function generateCsvReport(array $report): array
    {
        $filename = "vendor_analytics_{$report['vendor_id']}_{$report['period']}_" . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $csvData = [
            ['Metric', 'Value', 'Change', 'Unit'],
            ['Total Revenue', $report['overview']['total_revenue']['value'], $report['overview']['total_revenue']['change'] . '%', 'USD'],
            ['Total Orders', $report['overview']['total_orders']['value'], $report['overview']['total_orders']['change'] . '%', ''],
            ['Total Customers', $report['overview']['total_customers']['value'], $report['overview']['total_customers']['change'] . '%', ''],
            ['Total Products', $report['overview']['total_products']['value'], $report['overview']['total_products']['change'] . '%', ''],
            ['Average Order Value', $report['performance']['average_order_value']['value'], '', 'USD'],
            ['Conversion Rate', $report['performance']['conversion_rate']['value'], '', '%'],
            ['Return Rate', $report['performance']['return_rate']['value'], '', '%'],
            ['Customer Satisfaction', $report['performance']['customer_satisfaction']['value'], '', '/5'],
        ];

        // Add sales trend data
        $csvData[] = ['', '', '', ''];
        $csvData[] = ['Sales Trend', '', '', ''];
        $csvData[] = ['Month', 'Sales', 'Orders', ''];
        
        foreach ($report['sales_trend'] as $trend) {
            $csvData[] = [$trend['month'], $trend['sales'], $trend['orders'], ''];
        }

        // Add top products data
        $csvData[] = ['', '', '', ''];
        $csvData[] = ['Top Products', '', '', ''];
        $csvData[] = ['Product Name', 'Sales', 'Revenue', 'Growth %'];
        
        foreach ($report['top_products'] as $product) {
            $csvData[] = [$product['name'], $product['sales'], $product['revenue'], $product['growth']];
        }

        return [
            'success' => true,
            'data' => [
                'filename' => $filename,
                'csv_data' => $csvData,
                'download_url' => null // Would be generated if file storage is implemented
            ]
        ];
    }

    /**
     * Get analytics summary for quick overview
     */
    public function getAnalyticsSummary(int $vendorId): array
    {
        $analytics = $this->analyticsRepository->getVendorAnalytics($vendorId, '30d');

        return [
            'success' => true,
            'data' => [
                'total_revenue' => $analytics['revenue']['total'],
                'total_orders' => $analytics['orders']['total'],
                'total_customers' => $analytics['customers']['total'],
                'total_products' => $analytics['products']['total'],
                'average_order_value' => $analytics['performance']['average_order_value'],
                'recent_orders_count' => count($analytics['recent_orders'])
            ]
        ];
    }
}
