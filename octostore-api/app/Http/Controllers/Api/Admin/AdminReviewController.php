<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\Content\ContentService;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function index(Request $request) 
    {
        $query = Review::with(['user', 'product']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        return response()->json($query->latest()->paginate(20));
    }
    
    public function updateStatus(Request $request, Review $review, ContentService $contentService)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);
        
        $originalStatus = $review->status;
        $review->update($validated);
        
        // If status changed to/from approved, update aggregation
        if ($validated['status'] === 'approved' || ($originalStatus === 'approved' && $validated['status'] === 'rejected')) {
             $contentService->recalculateProductRating($review->product);
        }
        
        return response()->json(['message' => 'Review status updated']);
    }
}
