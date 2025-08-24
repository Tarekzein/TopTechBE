<?php

namespace Modules\Store\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a vendor user
        $this->vendor = User::factory()->create();
        $this->vendor->assignRole('vendor');
    }

    /** @test */
    public function vendor_can_access_dashboard_analytics()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/store/analytics/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'overview',
                    'performance',
                    'recent_orders',
                    'top_products',
                    'sales_trend',
                    'period'
                ]
            ]);
    }

    /** @test */
    public function vendor_can_access_revenue_analytics()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/store/analytics/revenue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'revenue',
                    'sales_trend'
                ]
            ]);
    }

    /** @test */
    public function vendor_can_access_orders_analytics()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/store/analytics/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'orders',
                    'recent_orders'
                ]
            ]);
    }

    /** @test */
    public function vendor_can_access_products_analytics()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/store/analytics/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'products',
                    'top_products'
                ]
            ]);
    }

    /** @test */
    public function vendor_can_access_customers_analytics()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/store/analytics/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'customers'
                ]
            ]);
    }

    /** @test */
    public function vendor_can_access_analytics_summary()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->getJson('/api/store/analytics/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_revenue',
                    'total_orders',
                    'total_customers',
                    'total_products',
                    'average_order_value',
                    'recent_orders_count'
                ]
            ]);
    }

    /** @test */
    public function vendor_can_export_analytics_report()
    {
        $response = $this->actingAs($this->vendor, 'sanctum')
            ->postJson('/api/store/analytics/export', [
                'period' => '30d',
                'format' => 'json'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'vendor_id',
                    'period',
                    'generated_at',
                    'overview',
                    'performance',
                    'recent_orders',
                    'top_products',
                    'sales_trend'
                ]
            ]);
    }

    /** @test */
    public function non_vendor_cannot_access_analytics()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/store/analytics/dashboard');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_analytics()
    {
        $response = $this->getJson('/api/store/analytics/dashboard');

        $response->assertStatus(401);
    }
}
