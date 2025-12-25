<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index()
    {
        return response()->json(NotificationTemplate::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event' => 'required|string',
            'channel' => 'required|string|in:email,sms,whatsapp,push',
            'subject' => 'nullable|array',
            'body' => 'required|array',
            'is_active' => 'boolean'
        ]);

        $template = NotificationTemplate::create($validated);
        return response()->json(['data' => $template], 201);
    }

    public function update(Request $request, NotificationTemplate $template)
    {
        $validated = $request->validate([
            'subject' => 'nullable|array',
            'body' => 'sometimes|array',
            'is_active' => 'boolean'
        ]);

        $template->update($validated);
        return response()->json(['data' => $template]);
    }
    
    public function destroy(NotificationTemplate $template)
    {
        $template->delete();
        return response()->noContent();
    }
}
