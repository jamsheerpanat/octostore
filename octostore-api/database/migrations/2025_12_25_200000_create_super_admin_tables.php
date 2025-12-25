<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Super Admins
        if (!Schema::hasTable('super_admins')) {
            Schema::create('super_admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // 2. Plans
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->decimal('price', 10, 2);
                $table->integer('duration_days')->default(30);

                // Limits: { "products": 100, "staff": 2, "orders": 1000 }
                $table->json('limits')->nullable();

                // Default Features Enabled: ["coupons", "reviews"]
                $table->json('features')->nullable();

                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Update Tenants
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->after('is_active')->constrained();
            }
            if (!Schema::hasColumn('tenants', 'feature_flags')) {
                $table->json('feature_flags')->nullable()->after('plan_id');
            }
            if (!Schema::hasColumn('tenants', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('feature_flags');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'feature_flags', 'subscription_ends_at']);
        });

        Schema::dropIfExists('plans');
        Schema::dropIfExists('super_admins');
    }
};
