<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function stats(Request $request) 
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        // 1. Sales Summary
        $revenue = Order::where('status', '!=', 'cancelled')
            ->where('status', '!=', 'refunded')
            // ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('grand_total');
            
        $ordersCount = Order::count();
        
        // 2. Orders by Status
        $ordersByStatus = Order::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
            
        // 3. Low Stock Products
        $lowStock = ProductVariant::where('stock_quantity', '<=', 5)
            ->with('product:id,name')
            ->take(5)
            ->get();
            
        // 4. Top Products (Simplified)
        $topProducts = DB::table('order_items')
            ->select('product_name', DB::raw('sum(quantity) as total_sold'))
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();
            
        return response()->json([
            'revenue' => $revenue,
            'orders_count' => $ordersCount,
            'orders_by_status' => $ordersByStatus,
            'low_stock' => $lowStock,
            'top_products' => $topProducts
        ]);
    }
}
