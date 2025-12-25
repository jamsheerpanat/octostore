<?php

namespace App\Services\Content;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ContentService
{
    public function verifyPurchase(int $userId, int $productId): bool
    {
        return Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereHas('items', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();
    }
    
    public function recalculateProductRating(Product $product)
    {
        $avg = $product->reviews()->where('status', 'approved')->avg('rating') ?? 0;
        $count = $product->reviews()->where('status', 'approved')->count();
        
        $product->update([
            'rating_avg' => round($avg, 2),
            'rating_count' => $count
        ]);
        
        // Also clear cache if necessary
        // Cache::forget("product_{$product->id}");
    }
}
