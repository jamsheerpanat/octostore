<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AI\AiServiceFactory;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function searchAssist(Request $request)
    {
        $validated = $request->validate(['query' => 'required|string']);
        
        $provider = AiServiceFactory::make();
        $filters = $provider->naturalLanguageToFilters($validated['query']);
        
        return response()->json([
            'original_query' => $validated['query'],
            'derived_filters' => $filters
        ]);
    }
    
    public function reviewSummary(Request $request)
    {
         $validated = $request->validate(['product_id' => 'required|exists:products,id']);
         
         $product = Product::find($validated['product_id']);
         // Fetch last ~20 approved reviews
         $reviews = $product->reviews()->where('status', 'approved')->latest()->take(20)->pluck('body')->toArray();
         
         $provider = AiServiceFactory::make();
         $summary = $provider->summarizeReviews($reviews);
         
         return response()->json([
             'product_id' => $product->id,
             'summary' => $summary
         ]);
    }
    
    public function recommendations(Request $request)
    {
        $validated = $request->validate(['intent' => 'required|string']);
        
        $provider = AiServiceFactory::make();
        $result = $provider->recommendByIntent($validated['intent']);
        
        // Convert keywords to potential products if desired, 
        // or let frontend use keywords to search.
        // For here, just return the AI output.
        
        return response()->json($result);
    }
}
