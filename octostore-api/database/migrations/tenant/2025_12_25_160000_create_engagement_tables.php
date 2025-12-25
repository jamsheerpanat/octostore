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
        // 1. Reviews
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            $table->integer('rating')->unsigned(); // 1-5
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('images')->nullable();
            
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->boolean('is_verified_purchase')->default(false);
            
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
            $table->index(['user_id']);
        });

        // 2. Review Votes (Helpful?)
        Schema::create('review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_upvote')->default(true); // true = helpful, false = not helpful?
            $table->timestamps();
            
            $table->unique(['review_id', 'user_id']);
        });

        // 3. Product Questions
        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            $table->text('question');
            $table->string('status')->default('pending'); // pending, approved, rejected
            
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
        });

        // 4. Product Answers
        Schema::create('product_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('product_questions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Admin or User
            
            $table->text('answer');
            $table->boolean('is_official')->default(false); // If answered by admin/staff
            
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        
        // 5. Add aggregate columns to products table for easier sorting
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->default(0)->after('status');
            $table->integer('rating_count')->default(0)->after('rating_avg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['rating_avg', 'rating_count']);
        });
        
        Schema::dropIfExists('product_answers');
        Schema::dropIfExists('product_questions');
        Schema::dropIfExists('review_votes');
        Schema::dropIfExists('reviews');
    }
};
