<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function track(Request $request)
    {
        $validated = $request->validate([
            'event_type' => 'required|string',
            'subject_type' => 'nullable|string',
            'subject_id' => 'nullable|integer',
            'properties' => 'nullable|array'
        ]);

        // Async dispatch or simple insert
        AnalyticsEvent::create([
            'session_id' => $request->header('X-Session-ID'),
            'user_id' => $request->user()?->id,
            'event_type' => $validated['event_type'],
            'subject_type' => $validated['subject_type'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'properties' => $validated['properties']
        ]);
        
        return response()->noContent();
    }
}
