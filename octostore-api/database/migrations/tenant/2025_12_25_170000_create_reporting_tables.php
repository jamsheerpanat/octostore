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
        // 1. Analytics Events (Lightweight tracking)
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('event_type'); // view_product, add_to_cart, checkout_start, purchase
            
            $table->nullableMorphs('subject'); // e.g. Product id
            $table->json('properties')->nullable(); // e.g. url, referrer, value
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['event_type', 'created_at']);
        });

        // 2. Export Jobs (For large report tracking)
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id'); // Admin
            $table->string('report_type'); // sales, products, customers
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('analytics_events');
    }
};
