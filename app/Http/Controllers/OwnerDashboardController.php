<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BridalPackage;
use App\Models\Saloon;
use App\Models\Service;
use Illuminate\Http\Request;

class OwnerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $owner = $request->user();

        $ownerSaloons = fn ($query) => $query->where('owner_id', $owner->id);

        return response()->json([
            'owner_name' => $owner->name,
            'total_bookings' => Booking::whereHas('saloon', $ownerSaloons)->count(),
            'total_services' => Service::whereHas('saloon', $ownerSaloons)->count(),
            'total_saloons' => Saloon::where('owner_id', $owner->id)->count(),
            'total_packages' => BridalPackage::whereHas('saloon', $ownerSaloons)->count(),
            'recent_bookings' => Booking::with([
                'client:id,name,email',
                'saloon:id,owner_id,name,location,image',
                'service:id,saloon_id,name,price,duration,image',
                'bridalPackage:id,saloon_id,name,package_price,image',
            ])
                ->whereHas('saloon', $ownerSaloons)
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
