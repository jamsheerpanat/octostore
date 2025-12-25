<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryTimeSlot;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;

class AdminDeliveryController extends Controller
{
    // --- Zones ---
    public function indexZones()
    {
        return response()->json(DeliveryZone::orderBy('sort_order')->get());
    }

    public function storeZone(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'areas' => 'required|array', // ['Area1', 'Area2']
            'base_fee' => 'required|numeric|min:0',
            'min_order_amount' => 'required|numeric|min:0',
            'cod_allowed' => 'boolean'
        ]);
        
        $zone = DeliveryZone::create($validated);
        return response()->json(['message' => 'Zone created', 'data' => $zone]);
    }
    
    public function updateZone(Request $request, DeliveryZone $zone)
    {
         $zone->update($request->all());
         return response()->json(['message' => 'Zone updated', 'data' => $zone]);
    }
    
    public function deleteZone(DeliveryZone $zone)
    {
        $zone->delete();
         return response()->json(['message' => 'Zone deleted']);
    }

    // --- Time Slots ---
    public function indexSlots()
    {
         return response()->json(DeliveryTimeSlot::all());
    }

    public function storeSlot(Request $request)
    {
         $validated = $request->validate([
            'name' => 'required|string',
            'start_time' => 'required', // H:i
            'end_time' => 'required', // H:i
            'capacity' => 'integer',
        ]);
        
        $slot = DeliveryTimeSlot::create($validated);
        return response()->json(['message' => 'Slot created', 'data' => $slot]);
    }
    
    public function deleteSlot(DeliveryTimeSlot $slot)
    {
        $slot->delete();
        return response()->json(['message' => 'Slot deleted']);
    }
}
