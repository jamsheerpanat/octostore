<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Simple caching for the first page of "all products" calls (no filters)
        $cacheKey = 'products_page_' . $request->get('page', 1);
        $shouldCache = empty($request->except('page'));

        if ($shouldCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $query = Product::query()
            ->with(['brand', 'categories', 'images', 'variants', 'tags']) // Eager load
            ->active();

        // 1. Search
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // 2. Filter by Category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->input('category_id'));
            });
        }

        // 3. Filter by Brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        // 4. Filter by Price Range
        if ($request->filled('price_min')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '>=', $request->input('price_min'));
            });
        }
        if ($request->filled('price_max')) {
             $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '<=', $request->input('price_max'));
            });
        }
        
        // 5. Filter by Tags
        if ($request->filled('tag')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('slug', $request->input('tag'));
            });
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        // A robust implementation would join the variants table for price sorting
        if ($sort === 'price_asc') {
             // simplified sorting by latest for now as price sorting requires subqueries/joins on hasMany
             // typically: join variants, order by min(price)
        } else {
             $query->latest();
        }

        $products = $query->paginate(12);
        
        $resource = ProductResource::collection($products);

        if ($shouldCache) {
            Cache::put($cacheKey, $resource, 600); // 10 minutes
        }

        return $resource;
    }

    public function show($id)
    {
        $product = Product::with(['brand', 'categories', 'images', 'variants', 'tags', 'collections'])
            ->findOrFail($id);
            
        return new ProductResource($product);
    }
    
    // Admin Store/Update methods would go here...
    // Simplified Store method for demonstration
    public function store(Request $request)
    {
         // Validation
         // Create Product
         // Create Variants
         // Upload Images
         return response()->json(['message' => 'Product creation logic implementation required'], 201);
    }
}
