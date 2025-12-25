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
        // 1. Notification Templates
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event'); // order_created, order_status_changed, payment_success, payment_failed
            $table->string('channel'); // email, sms, whatsapp, push
            
            $table->json('subject')->nullable(); // Multi-lang: {"en": "Subject", "ar": "..."}
            $table->json('body')->nullable(); // Multi-lang content
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['event', 'channel']);
        });

        // 2. Notification Logs
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('event');
            $table->string('channel');
            $table->string('recipient');
            $table->text('content')->nullable(); // Snapshot of what was sent
            
            $table->string('status'); // sent, failed, queued
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            $table->index(['order_id', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }
};
