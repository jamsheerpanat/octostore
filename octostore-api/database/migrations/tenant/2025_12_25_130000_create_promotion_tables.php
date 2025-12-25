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
        // 1. Coupons
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            
            $table->string('type')->default('fixed'); // fixed, percent
            $table->decimal('value', 10, 2); // The amount or percentage
            
            $table->decimal('min_cart_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable(); // Cap for % discounts
            
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->integer('usage_limit_per_coupon')->nullable(); // Global limit
            $table->integer('usage_limit_per_user')->nullable(); // Per customer limit
            
            $table->json('rules')->nullable(); // JSON for allowed_products, allowed_categories, excluded_ids, etc.
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('code');
            $table->index(['is_active', 'expires_at']);
        });

        // 2. Coupon Usage Tracking
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->index(); // Registered user tracking
            $table->foreignId('order_id')->constrained()->cascadeOnDelete(); // Confirmed usage
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
        });

        // 3. Promotions (Banners/Sliders)
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path');
            $table->string('link_type')->nullable(); // category, product, url
            $table->string('link_value')->nullable(); // ID or URL
            $table->string('position')->default('main_slider'); // main_slider, home_banner_1, sidebar, etc.
            $table->integer('sort_order')->default(0);
            
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Flash Deals
        Schema::create('flash_deals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('banner_image')->nullable();
            $table->string('background_color')->nullable(); // Aesthetic
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        // 5. Flash Deal Products
        Schema::create('flash_deal_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            $table->string('discount_type')->default('percent'); // percent, fixed
            $table->decimal('discount_value', 10, 2); 
            
            $table->timestamps();
            
            $table->unique(['flash_deal_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_deal_products');
        Schema::dropIfExists('flash_deals');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
