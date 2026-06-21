<?php

use App\Models\Booking;
use App\Models\Saloon;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('client receives recommended saloons when requested time is already booked', function () {
    $client = User::factory()->create(['role' => 'client']);
    $owner = User::factory()->create(['role' => 'owner']);
    $otherOwner = User::factory()->create(['role' => 'owner']);

    $bookedSaloon = Saloon::create([
        'owner_id' => $owner->id,
        'name' => 'Booked Beauty',
        'location' => 'City Center',
    ]);

    $recommendedSaloon = Saloon::create([
        'owner_id' => $otherOwner->id,
        'name' => 'Available Beauty',
        'location' => 'West Side',
    ]);

    $service = Service::create([
        'saloon_id' => $recommendedSaloon->id,
        'name' => 'Hair Styling',
        'price' => 45,
        'duration' => 60,
    ]);

    Service::create([
        'saloon_id' => $bookedSaloon->id,
        'name' => 'Hair Styling',
        'price' => 50,
        'duration' => 60,
    ]);

    Booking::create([
        'user_id' => $client->id,
        'saloon_id' => $bookedSaloon->id,
        'service_id' => $service->id,
        'booking_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'total_price' => 45,
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($client, 'sanctum')->postJson('/api/bookings', [
        'saloon_id' => $bookedSaloon->id,
        'booking_date' => now()->addDay()->toDateString(),
        'start_time' => '10:30',
        'end_time' => '11:30',
        'service_id' => $service->id,
    ]);

    $response
        ->assertStatus(409)
        ->assertJsonPath('conflict.saloon_id', $bookedSaloon->id)
        ->assertJsonPath('recommended_saloons.0.id', $recommendedSaloon->id)
        ->assertJsonPath('recommended_saloons.0.total_price', 45);
});

test('cancelled bookings do not block a saloon time slot', function () {
    $client = User::factory()->create(['role' => 'client']);
    $owner = User::factory()->create(['role' => 'owner']);

    $saloon = Saloon::create([
        'owner_id' => $owner->id,
        'name' => 'Open Beauty',
        'location' => 'North Side',
    ]);

    $service = Service::create([
        'saloon_id' => $saloon->id,
        'name' => 'Makeup',
        'price' => 70,
        'duration' => 90,
    ]);

    Booking::create([
        'user_id' => $client->id,
        'saloon_id' => $saloon->id,
        'service_id' => $service->id,
        'booking_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'total_price' => 70,
        'status' => 'cancelled',
    ]);

    $response = $this->actingAs($client, 'sanctum')->postJson('/api/bookings', [
        'saloon_id' => $saloon->id,
        'booking_date' => now()->addDay()->toDateString(),
        'start_time' => '10:30',
        'end_time' => '11:30',
        'service_id' => $service->id,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('saloon_id', $saloon->id)
        ->assertJsonPath('service_id', $service->id);
});
