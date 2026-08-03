<?php

namespace App\Http\Controllers;

use App\Mail\ClientBookingConfirmed;
use App\Mail\ClientBookingCreated;
use App\Mail\ClientBookingRejected;
use App\Mail\OwnerBookingCreated;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\BridalPackage;
use App\Models\Saloon;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'saloon_id' => 'required|exists:saloons,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'service_id' => 'nullable|exists:services,id|required_without:bridal_package_id|prohibited_if:bridal_package_id,*',
            'bridal_package_id' => 'nullable|exists:bridal_packages,id|required_without:service_id|prohibited_if:service_id,*',
            'client_notes' => 'nullable|string',
        ]);

        $saloon = Saloon::with(['owner', 'services', 'bridalPackages'])->findOrFail($request->saloon_id);

        if ($request->filled('bridal_package_id')) {
            return $this->storePackageBooking($request, $saloon);
        }

        return $this->storeServiceBooking($request, $saloon);
    }

    public function myBookings()
    {
        return response()->json(
            Booking::with([
                'saloon:id,owner_id,name,location,image',
                'service:id,saloon_id,name,price,duration,image',
                'bridalPackage:id,saloon_id,name,package_price,image',
                'notifications',
            ])
                ->where('user_id', auth()->id())
                ->latest()
                ->get()
        );
    }

    public function saloonBookings(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'completed', 'cancelled'])],
        ]);

        $query = Booking::with(['client:id,name,email', 'saloon', 'service', 'bridalPackage'])
            ->whereHas('saloon', fn ($query) => $query->where('owner_id', auth()->id()))
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'completed', 'cancelled'])],
            'owner_notes' => 'nullable|string',
        ]);

        $booking = Booking::with(['client', 'saloon'])->findOrFail($id);

        if ($booking->saloon->owner_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $wasPending = $booking->status === 'pending';

        $booking->update([
            'status' => $request->status,
            'owner_notes' => $request->owner_notes,
        ]);

        if ($wasPending && $booking->status === 'confirmed') {
            AppNotification::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'title' => 'Booking accepted',
                'message' => "Your booking request at {$booking->saloon->name} has been accepted.",
            ]);

            $this->mailSafe(fn () => Mail::to($booking->client->email)->send(new ClientBookingConfirmed($booking)));
        }

        if ($booking->status === 'cancelled') {
            $this->mailSafe(fn () => Mail::to($booking->client->email)->send(new ClientBookingRejected($booking)));
        }

        return response()->json([
            'message' => 'Booking status updated.',
            'booking' => $booking->load(['client:id,name,email', 'saloon', 'service', 'bridalPackage', 'notifications']),
        ]);
    }

    private function storeServiceBooking(Request $request, Saloon $saloon)
    {
        $service = Service::where('id', $request->service_id)
            ->where('saloon_id', $saloon->id)
            ->first();

        if (! $service) {
            return response()->json([
                'message' => 'The selected service is not offered by this saloon.',
            ], 422);
        }

        if ($this->hasBookingConflict($saloon->id, $request->booking_date, $request->start_time, $request->end_time)) {
            return $this->bookingConflictResponse($request, $saloon, $service->id);
        }

        $booking = DB::transaction(function () use ($request, $saloon, $service) {
            if ($this->hasBookingConflict($saloon->id, $request->booking_date, $request->start_time, $request->end_time)) {
                return $this->bookingConflictResponse($request, $saloon, $service->id);
            }

            $booking = Booking::create([
                'user_id' => auth()->id(),
                'saloon_id' => $saloon->id,
                'service_id' => $service->id,
                'booking_date' => $request->booking_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'total_price' => $service->price,
                'status' => 'pending',
                'client_notes' => $request->client_notes,
            ]);

            $this->notifyOwner($booking, 'New service booking request', 'A client requested one of your saloon services.');

            return $booking;
        });

        $this->mailSafe(fn () => Mail::to($booking->saloon->owner->email)->send(new OwnerBookingCreated($booking)));
        $this->mailSafe(fn () => Mail::to($booking->client->email)->send(new ClientBookingCreated($booking)));

        return response()->json($booking->load(['saloon.owner:id,name,email', 'service', 'notifications']), 201);
    }

    private function storePackageBooking(Request $request, Saloon $saloon)
    {
        $package = BridalPackage::where('id', $request->bridal_package_id)
            ->where('saloon_id', $saloon->id)
            ->first();

        if (! $package) {
            return response()->json([
                'message' => 'This bridal package does not belong to the selected saloon.',
            ], 422);
        }

        if ($this->hasBookingConflict($saloon->id, $request->booking_date, $request->start_time, $request->end_time)) {
            return $this->bookingConflictResponse($request, $saloon, null);
        }

        $booking = DB::transaction(function () use ($request, $saloon, $package) {
            if ($this->hasBookingConflict($saloon->id, $request->booking_date, $request->start_time, $request->end_time)) {
                return $this->bookingConflictResponse($request, $saloon, null);
            }

            $booking = Booking::create([
                'user_id' => auth()->id(),
                'saloon_id' => $saloon->id,
                'bridal_package_id' => $package->id,
                'booking_date' => $request->booking_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'total_price' => $package->package_price,
                'status' => 'pending',
                'client_notes' => $request->client_notes,
            ]);

            $this->notifyOwner($booking, 'New bridal package booking request', 'A client requested one of your bridal packages.');

            return $booking;
        });

        $this->mailSafe(fn () => Mail::to($booking->saloon->owner->email)->send(new OwnerBookingCreated($booking)));
        $this->mailSafe(fn () => Mail::to($booking->client->email)->send(new ClientBookingCreated($booking)));

        return response()->json($booking->load(['saloon.owner:id,name,email', 'bridalPackage', 'notifications']), 201);
    }

    private function notifyOwner(Booking $booking, string $title, string $message): void
    {
        AppNotification::create([
            'user_id' => $booking->saloon->owner_id,
            'booking_id' => $booking->id,
            'title' => $title,
            'message' => $message,
        ]);
    }

    private function mailSafe(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error('Failed to send booking email: '.$e->getMessage());
        }
    }

    private function hasBookingConflict(int $saloonId, string $bookingDate, string $startTime, string $endTime): bool
    {
        return Booking::query()
            ->where('saloon_id', $saloonId)
            ->whereDate('booking_date', $bookingDate)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
    }

    private function bookingConflictResponse(Request $request, Saloon $saloon, ?int $serviceId)
    {
        return response()->json([
            'message' => 'The selected saloon is already booked for this time. Please choose another time or try one of the recommended saloons.',
            'conflict' => [
                'saloon_id' => $saloon->id,
                'saloon_name' => $saloon->name,
                'booking_date' => $request->booking_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ],
            'recommended_saloons' => $this->recommendedSaloons(
                $serviceId,
                $request->booking_date,
                $request->start_time,
                $request->end_time,
                $saloon->id
            ),
        ], 409);
    }

    private function recommendedSaloons(?int $serviceId, string $bookingDate, string $startTime, string $endTime, int $excludedSaloonId)
    {
        if (! $serviceId) {
            return collect();
        }

        return Saloon::query()
            ->with([
                'owner:id,name,email',
                'services' => fn ($query) => $query->where('id', $serviceId),
            ])
            ->whereHas(
                'services',
                fn (Builder $query) => $query->where('id', $serviceId)
            )
            ->where('id', '!=', $excludedSaloonId)
            ->whereDoesntHave('bookings', function (Builder $query) use ($bookingDate, $startTime, $endTime) {
                $query->whereDate('booking_date', $bookingDate)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Saloon $saloon) {
                return [
                    'id' => $saloon->id,
                    'name' => $saloon->name,
                    'location' => $saloon->location,
                    'description' => $saloon->description,
                    'image' => $saloon->image,
                    'owner' => $saloon->owner,
                    'total_price' => $saloon->services->sum(fn (Service $service) => (float) $service->price),
                    'total_duration' => $saloon->services->sum(fn (Service $service) => (int) $service->duration),
                    'services' => $saloon->services,
                ];
            });
    }
}
