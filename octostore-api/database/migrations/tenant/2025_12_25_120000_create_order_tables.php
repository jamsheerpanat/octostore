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
        // 1. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index(); // Registered user
            $table->string('session_id')->nullable(); // Guest tracking
            
            // Addresses (Snapshot to preserve history even if user address changes)
            $table->json('billing_address');
            $table->json('shipping_address');
            
            $table->string('order_number')->unique(); // Tenant-specific format e.g., INV-2025-001
            $table->string('status')->default('pending'); 
            // Pipeline: pending, confirmed, processing, packed, out_for_delivery, delivered, cancelled, refunded
            
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded, partially_refunded
            $table->string('payment_method')->nullable();
            
            $table->string('shipping_method')->nullable();
            $table->string('tracking_number')->nullable();
            
            // Financials
            $table->string('currency')->default('USD');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            
            $table->string('coupon_code')->nullable();
            
            $table->text('customer_note')->nullable();
            $table->text('internal_note')->nullable(); // Admin only
            
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'created_at']);
        });

        // 2. Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); 
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            
            $table->string('product_name'); // Snapshot
            $table->string('variant_name')->nullable(); // Snapshot e.g. "Size: M, Color: Red"
            $table->string('sku')->nullable();
            
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            
            $table->json('attributes')->nullable(); // Variant attributes
            $table->timestamps();
        });

        // 3. Order Status History (Audit trail)
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('comment')->nullable();
            $table->foreignId('changed_by_user_id')->nullable(); // Null = System
            $table->timestamps();
        });

        // 4. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id')->unique(); // Gateway ID
            $table->string('gateway'); // stripe, paypal, cod
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('USD');
            $table->string('status'); // success, failed, pending
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });
        
        // 5. Returns (RMA)
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable();
            $table->string('rma_number')->unique();
            $table->string('status')->default('requested'); // requested, approved, received, refunded, rejected
            $table->text('reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('condition')->nullable(); // new, opened, damaged
            $table->timestamps();
        });
        
        // 6. Refunds
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained();
            $table->string('refund_transaction_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('reason')->nullable();
            $table->string('status')->default('processed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
