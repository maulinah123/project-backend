<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Booking;

class ClientDashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $bookings = Booking::with([
            'saloon:id,owner_id,name,location,image',
            'service:id,saloon_id,name,price,duration,image',
            'bridalPackage:id,saloon_id,name,package_price,image',
        ])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $notifications = AppNotification::with('booking:id,user_id,saloon_id,service_id,bridal_package_id,booking_date,start_time,end_time,total_price,status,client_notes,owner_notes')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'total_bookings' => $bookings->count(),
            'pending_bookings' => $bookings->where('status', 'pending')->count(),
            'confirmed_bookings' => $bookings->where('status', 'confirmed')->count(),
            'completed_bookings' => $bookings->where('status', 'completed')->count(),
            'cancelled_bookings' => $bookings->where('status', 'cancelled')->count(),
            'unread_notifications' => $notifications->whereNull('read_at')->count(),
            'recent_bookings' => $bookings->take(5)->values(),
            'notifications' => $notifications,
        ]);
    }
}
