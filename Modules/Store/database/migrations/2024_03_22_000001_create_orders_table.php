<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('pending'); // pending, processing, completed, cancelled, refunded
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->string('payment_method')->nullable(); // credit_card, paypal, etc.
            $table->string('payment_id')->nullable(); // Payment gateway transaction ID
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('shipping_method')->nullable();
            $table->string('shipping_tracking_number')->nullable();
            $table->string('shipping_tracking_url')->nullable();
            
            // Billing Information
            $table->string('billing_first_name');
            $table->string('billing_last_name');
            $table->string('billing_email');
            $table->string('billing_phone')->nullable();
            $table->string('billing_address');
            $table->string('billing_city');
            $table->string('billing_state')->nullable();
            $table->string('billing_postcode');
            $table->string('billing_country');
            
            // Shipping Information
            $table->string('shipping_first_name');
            $table->string('shipping_last_name');
            $table->string('shipping_email');
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postcode');
            $table->string('shipping_country');
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->json('meta_data')->nullable(); // For storing additional data
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}; 