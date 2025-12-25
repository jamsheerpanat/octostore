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
        // 1. Delivery Zones
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Geographic definitions
            $table->json('areas')->nullable(); // List of area names, cities, or zip codes
            $table->json('coordinates')->nullable(); // For future polygon support
            
            // Financial Rules
            $table->decimal('base_fee', 8, 2)->default(0);
            $table->decimal('min_order_amount', 8, 2)->default(0); // If cart < this, cannot deliver? Or just surcharge? Usually "min order for delivery"
            $table->decimal('free_shipping_amount', 8, 2)->nullable(); // If > this, fee is 0
            
            // COD Rules
            $table->boolean('cod_allowed')->default(true);
            $table->decimal('cod_surcharge', 8, 2)->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
        });

        // 2. Delivery Time Slots
        Schema::create('delivery_time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Morning", "10AM - 1PM"
            
            $table->time('start_time');
            $table->time('end_time');
            
            $table->integer('capacity')->default(100); // Max orders per slot
            $table->integer('cutoff_minutes')->default(60); // Must occur X mins before slot starts
            
            $table->json('days_of_week')->nullable(); // [0,1,2,3,4,5,6] (0=Sunday)
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_time_slots');
        Schema::dropIfExists('delivery_zones');
    }
};
