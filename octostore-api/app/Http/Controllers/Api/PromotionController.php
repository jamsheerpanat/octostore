<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashDeal;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PromotionController extends Controller
{
    public function getBanners(Request $request)
    {
        $position = $request->input('position', 'main_slider');
        
        // Cache banners by position for 1 hour
        $banners = Cache::remember("banners_{$position}", 3600, function() use ($position) {
            return Promotion::active()
                ->where('position', $position)
                ->orderBy('sort_order')
                ->get();
        });

        return response()->json(['data' => $banners]);
    }

    public function getFlashDeals(Request $request) 
    {
        $deals = Cache::remember('active_flash_deals', 600, function() {
            return FlashDeal::active()->get();
        });
        
        return response()->json(['data' => $deals]);
    }
    
    public function getFlashDealProducts($slug)
    {
        $deal = FlashDeal::active()->where('slug', $slug)->firstOrFail();
        
        // Load products with their pivot data
        $products = $deal->products()->with(['images', 'brand'])->get();
        
        // Map to standard product resource structure with modified price?
        // Ideally we reuse ProductResource but override specific fields.
        // For now returning raw with pivot info.
        
        return response()->json([
            'deal' => $deal,
            'products' => $products
        ]);
    }
}
