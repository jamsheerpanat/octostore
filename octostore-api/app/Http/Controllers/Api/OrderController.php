<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // For authenticated customers to see their orders
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items'])
            ->latest()
            ->paginate(10);
            
        return response()->json($orders);
    }

    public function show(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['items.product', 'history', 'payments'])
            ->firstOrFail();
            
        return response()->json($order);
    }
    
    // Store method typically handled by CheckoutController or Payment Webhook, 
    // but here is a simple internal creation for the pipeline demo
}
