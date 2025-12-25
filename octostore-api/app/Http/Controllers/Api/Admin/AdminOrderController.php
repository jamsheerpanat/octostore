<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Jobs\GeneratePackingSlip;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items']);
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('order_number')) {
            $query->where('order_number', 'like', "%{$request->input('order_number')}%");
        }
        
        $orders = $query->latest()->paginate(20);
        
        return response()->json($orders);
    }
    
    public function show($id)
    {
        $order = Order::with(['user', 'items.product', 'items.variant', 'history', 'payments'])->findOrFail($id);
        return response()->json($order);
    }
    
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,packed,out_for_delivery,delivered,cancelled,refunded',
            'comment' => 'nullable|string',
            'notify_customer' => 'boolean'
        ]);
        
        $oldStatus = $order->status;
        $newStatus = $validated['status'];
        
        if ($oldStatus !== $newStatus) {
            $order->status = $newStatus;
            
            if ($newStatus === 'delivered') {
                $order->delivered_at = now();
            } elseif ($newStatus === 'cancelled') {
                $order->cancelled_at = now();
            }
            
            $order->save();
            
            // Log History
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $newStatus,
                'comment' => $validated['comment'] ?? "Status updated to {$newStatus}",
                'changed_by_user_id' => $request->user()->id
            ]);
            
            // Dispatch Event
            event(new OrderStatusUpdated($order, $oldStatus, $newStatus));
            
            // Trigger Jobs
            if ($newStatus === 'confirmed') {
                GeneratePackingSlip::dispatch($order);
            }
        }
        
        return response()->json([
            'message' => 'Order status updated',
            'data' => $order->fresh('history')
        ]);
    }
    
    public function exportCsv(Request $request) 
    {
        // Simple implementation: Return CSV string as download
        // Real world: Use Laravel Excel or stream response
        
        $orders = Order::latest()->limit(1000)->get();
        
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=orders.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];
        
        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Order Number', 'Total', 'Status', 'Date']);
            
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id, 
                    $order->order_number, 
                    $order->grand_total, 
                    $order->status, 
                    $order->created_at
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
