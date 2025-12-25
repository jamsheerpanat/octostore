<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\DeliveryTimeSlot;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    // Public: Check availability and fee for an Area
    public function checkZone(Request $request) 
    {
        $validated = $request->validate([
            'area_name' => 'required|string',
            'cart_id' => 'nullable|exists:carts,id'
        ]);
        
        // Simple search: Find zone where areas array contains the requested area name
        // (Case insensitive search ideally, JSON search constraints apply)
        $zone = DeliveryZone::where('is_active', true)
            ->whereJsonContains('areas', $validated['area_name']) // Exact match in array
            ->first();
            
        if (!$zone) {
            // Fallback: Default zone? Or Error?
            // Checking for a zone named "Default" or similar if no specific match
            $zone = DeliveryZone::where('is_active', true)->where('name', 'Default')->first();
            
            if (!$zone) {
                return response()->json([
                    'deliverable' => false,
                    'message' => 'Delivery not available in this area.'
                ]);
            }
        }
        
        $response = [
            'deliverable' => true,
            'zone_id' => $zone->id,
            'zone_name' => $zone->name,
            'fee' => (float)$zone->base_fee,
            'currency' => 'USD',
            'cod_allowed' => $zone->cod_allowed,
            'min_order' => (float)$zone->min_order_amount
        ];
        
        // dynamic calc if cart provided
        if ($request->filled('cart_id')) {
            $cart = Cart::find($request->input('cart_id'));
            if ($cart) {
                if ($zone->free_shipping_amount && $cart->items_total >= $zone->free_shipping_amount) {
                    $response['fee'] = 0;
                    $response['is_free'] = true;
                }
                
                if ($cart->items_total < $zone->min_order_amount) {
                     $response['error'] = "Minimum order amount for this area is {$zone->min_order_amount}";
                     $response['deliverable'] = false;
                }
            }
        }
        
        return response()->json($response);
    }
    
    // Public: Get Available Time Slots
    public function getTimeSlots(Request $request)
    {
        // Simple implementation: Return all active slots for today/tomorrow
        // Future: Check 'capacity' against 'orders' count
        
        $slots = DeliveryTimeSlot::where('is_active', true)->get();
        return response()->json(['data' => $slots]);
    }
}
