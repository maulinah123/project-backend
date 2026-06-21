<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;

class NotificationController extends Controller
{
    public function index()
    {
        return response()->json(
            AppNotification::with('booking')
                ->where('user_id', auth()->id())
                ->latest()
                ->get()
        );
    }

    public function markAsRead($id)
    {
        $notification = AppNotification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return response()->json($notification);
    }
}
