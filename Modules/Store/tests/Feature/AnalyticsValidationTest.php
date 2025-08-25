<?php

namespace Modules\Store\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use Modules\Store\Models\Order;
use Modules\Store\Models\Product;
use Modules\Store\Models\OrderItem;
use Illuminate\Support\Facades\Log;

class AnalyticsValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $vendor;
    protected $admin;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->vendor = User::factory()->create();
        $this->vendor->assignRole('vendor');
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        
        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
    }

    /** @test */
    public function it_validates_vendor_access_for_analytics()
    {
        // Test customer access (should be denied)
        $response = $this->actingAs($this->customer)
            ->getJson('/api/store/analytics/dashboard');
        
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Vendor ID not found. Please ensure you have vendor access.'
            ]);
    }

    /** @test */
    public function it_validates_period_parameter()
    {
        $response = $this->actingAs($this->vendor)
            ->getJson('/api/store/analytics/dashboard?period=invalid');
        
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid request parameters'
            ]);
    }

    /** @test */
    public function it_accepts_valid_period_parameters()
    {
        $validPeriods = ['7d', '30d', '90d', '1y'];
        
        foreach ($validPeriods as $period) {
            $response = $this->actingAs($this->vendor)
                ->getJson("/api/store/analytics/dashboard?period={$period}");
            
            $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
        }
    }

    /** @test */
    public function it_validates_export_format_parameter()
    {
        $response = $this->actingAs($this->vendor)
            ->postJson('/api/store/analytics/export', [
                'period' => '30d',
                'format' => 'invalid'
            ]);
        
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid request parameters'
            ]);
    }

    /** @test */
    public function it_accepts_valid_export_formats()
    {
        $validFormats = ['json', 'csv'];
        
        foreach ($validFormats as $format) {
            $response = $this->actingAs($this->vendor)
                ->postJson('/api/store/analytics/export', [
                    'period' => '30d',
                    'format' => $format
                ]);
            
            $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
        }
    }

    /** @test */
    public function it_validates_admin_access_for_vendor_analytics()
    {
        // Test vendor trying to access another vendor's analytics
        $response = $this->actingAs($this->vendor)
            ->getJson('/api/store/admin/analytics/vendors/999');
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access. Admin privileges required.'
            ]);
    }

    /** @test */
    public function it_validates_vendor_id_for_admin_analytics()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/store/admin/analytics/vendors/0');
        
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid vendor ID'
            ]);
    }

    /** @test */
    public function it_handles_missing_analytics_data_gracefully()
    {
        // Create vendor with no orders/products
        $newVendor = User::factory()->create();
        $newVendor->assignRole('vendor');
        
        $response = $this->actingAs($newVendor)
            ->getJson('/api/store/analytics/dashboard');
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_revenue' => [
                            'value' => 0,
                            'change' => 0
                        ],
                        'total_orders' => [
                            'value' => 0,
                            'change' => 0
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_logs_analytics_requests()
    {
        Log::shouldReceive('info')
            ->with('Analytics: Fetching dashboard analytics', \Mockery::any())
            ->once();
        
        Log::shouldReceive('info')
            ->with('Analytics: Dashboard analytics fetched successfully', \Mockery::any())
            ->once();
        
        $this->actingAs($this->vendor)
            ->getJson('/api/store/analytics/dashboard');
    }

    /** @test */
    public function it_logs_validation_errors()
    {
        Log::shouldReceive('warning')
            ->with('Analytics: Validation failed for dashboard request', \Mockery::any())
            ->once();
        
        $this->actingAs($this->vendor)
            ->getJson('/api/store/analytics/dashboard?period=invalid');
    }

    /** @test */
    public function it_logs_unauthorized_access_attempts()
    {
        Log::shouldReceive('warning')
            ->with('Analytics: Unauthorized access attempt to vendor analytics', \Mockery::any())
            ->once();
        
        $this->actingAs($this->vendor)
            ->getJson('/api/store/admin/analytics/vendors/999');
    }

    /** @test */
    public function it_returns_proper_error_messages_in_production()
    {
        config(['app.debug' => false]);
        
        $response = $this->actingAs($this->customer)
            ->getJson('/api/store/analytics/dashboard');
        
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Vendor ID not found. Please ensure you have vendor access.'
            ])
            ->assertJsonMissing(['error']);
    }

    /** @test */
    public function it_returns_debug_info_in_development()
    {
        config(['app.debug' => true]);
        
        // Mock an exception to test debug info
        $this->mock(\Modules\Store\Services\AnalyticsService::class, function ($mock) {
            $mock->shouldReceive('getVendorDashboardAnalytics')
                ->andThrow(new \Exception('Test error'));
        });
        
        $response = $this->actingAs($this->vendor)
            ->getJson('/api/store/analytics/dashboard');
        
        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to fetch analytics data. Please try again later.',
                'error' => 'Test error'
            ]);
    }

    /** @test */
    public function it_validates_all_analytics_endpoints()
    {
        $endpoints = [
            '/api/store/analytics/dashboard',
            '/api/store/analytics/revenue',
            '/api/store/analytics/orders',
            '/api/store/analytics/products',
            '/api/store/analytics/customers',
            '/api/store/analytics/summary'
        ];
        
        foreach ($endpoints as $endpoint) {
            // Test invalid period
            $response = $this->actingAs($this->vendor)
                ->getJson("{$endpoint}?period=invalid");
            
            $response->assertStatus(422);
            
            // Test valid period
            $response = $this->actingAs($this->vendor)
                ->getJson("{$endpoint}?period=30d");
            
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function it_handles_database_errors_gracefully()
    {
        // Mock database connection to throw exception
        $this->mock(\Illuminate\Support\Facades\DB::class, function ($mock) {
            $mock->shouldReceive('raw')->andThrow(new \Exception('Database connection failed'));
        });
        
        $response = $this->actingAs($this->vendor)
            ->getJson('/api/store/analytics/dashboard');
        
        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to fetch analytics data. Please try again later.'
            ]);
    }
}
