<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\Content\ContentService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index($productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->with('user:id,name') // Only expose name
            ->latest()
            ->paginate(10);
            
        return response()->json($reviews);
    }

    public function store(Request $request, ContentService $contentService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string',
            'images' => 'nullable|array'
        ]);
        
        $userId = $request->user()->id;
        
        // Verify Purchase
        $isVerified = $contentService->verifyPurchase($userId, $validated['product_id']);
         
        // If system requires purchase to review, enforce here. 
        // For now, we allow unverified but mark them as such, or enforce if needed.
        // "Post review only after verified purchase" -> Enforce it
        if (!$isVerified) {
             return response()->json(['message' => 'You can only review products you have purchased and received.'], 403);
        }
        
        $review = Review::create([
            'user_id' => $userId,
            'product_id' => $validated['product_id'],
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'body' => $validated['body'], // Simple spam check could go here
            'images' => $validated['images'],
            'is_verified_purchase' => true,
            'status' => 'pending' // Moderation queue
        ]);
        
        return response()->json(['message' => 'Review submitted for approval', 'data' => $review], 201);
    }
}
