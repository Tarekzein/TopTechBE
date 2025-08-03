<?php

namespace Modules\Authentication\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Authentication\Notifications\WelcomeNotification;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_email_is_sent_on_user_registration()
    {
        Notification::fake();

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);
        
        // Assert that the welcome notification was sent
        Notification::assertSentTo(
            User::where('email', 'john.doe@example.com')->first(),
            WelcomeNotification::class
        );
    }

    public function test_welcome_email_is_sent_on_vendor_registration()
    {
        Notification::fake();

        $vendorData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'corporate_name' => 'Test Corp',
            'tax_number' => '123456789',
            'device_type' => 'web',
            'with_components' => false,
        ];

        $response = $this->postJson('/api/auth/vendor-register', $vendorData);

        $response->assertStatus(201);
        
        // Assert that the welcome notification was sent
        Notification::assertSentTo(
            User::where('email', 'jane.smith@example.com')->first(),
            WelcomeNotification::class
        );
    }
} 