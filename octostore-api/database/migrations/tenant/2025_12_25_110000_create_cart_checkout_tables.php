<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Carts
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index(); // For guests
            $table->foreignId('user_id')->nullable()->index(); // For logged in users
            $table->string('status')->default('active'); // active, converted, abandoned
            $table->string('currency')->default('USD');
            $table->decimal('items_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['session_id', 'status']);
        });

        // 2. Cart Items
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2); // Price at the moment of adding
            $table->decimal('total_price', 10, 2); // quantity * unit_price
            
            $table->json('attributes')->nullable(); // Snapshot of variant attributes
            $table->timestamps();
        });

        // 3. Addresses
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index(); // Can be null for guest orders attached later? Or strictly for profiles
            $table->string('type')->default('shipping'); // shipping, billing
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code');
            $table->string('country_code', 2); // ISO 2 char
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 4. Shipping Methods
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->json('rules')->nullable(); // e.g., {"min_order_value": 100, "allowed_countries": ["US", "AE"]}
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
