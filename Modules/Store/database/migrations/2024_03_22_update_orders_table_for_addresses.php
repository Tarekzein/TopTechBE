<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add foreign keys for addresses
            $table->foreignId('billing_address_id')->nullable()->constrained('billing_addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('shipping_addresses')->nullOnDelete();
            
            // Drop the old address columns
            $table->dropColumn([
                'billing_first_name',
                'billing_last_name',
                'billing_email',
                'billing_phone',
                'billing_address',
                'billing_city',
                'billing_state',
                'billing_postcode',
                'billing_country',
                'shipping_first_name',
                'shipping_last_name',
                'shipping_email',
                'shipping_phone',
                'shipping_address',
                'shipping_city',
                'shipping_state',
                'shipping_postcode',
                'shipping_country',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['billing_address_id']);
            $table->dropForeign(['shipping_address_id']);
            $table->dropColumn(['billing_address_id', 'shipping_address_id']);

            // Re-add the old address columns
            $table->string('billing_first_name');
            $table->string('billing_last_name');
            $table->string('billing_email');
            $table->string('billing_phone')->nullable();
            $table->string('billing_address');
            $table->string('billing_city');
            $table->string('billing_state')->nullable();
            $table->string('billing_postcode');
            $table->string('billing_country', 2);
            $table->string('shipping_first_name');
            $table->string('shipping_last_name');
            $table->string('shipping_email');
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postcode');
            $table->string('shipping_country', 2);
        });
    }
}; 