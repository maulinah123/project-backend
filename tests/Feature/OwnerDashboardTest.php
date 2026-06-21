<?php

use App\Models\Booking;
use App\Models\BridalPackage;
use App\Models\Saloon;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can view dashboard totals for their saloons only', function () {
    $owner = User::factory()->create(['name' => 'Frank', 'role' => 'owner']);
    $otherOwner = User::factory()->create(['role' => 'owner']);
    $client = User::factory()->create(['role' => 'client']);

    $firstSaloon = Saloon::create([
        'owner_id' => $owner->id,
        'name' => 'Frank Beauty',
        'location' => 'City Center',
    ]);

    $secondSaloon = Saloon::create([
        'owner_id' => $owner->id,
        'name' => 'Frank Bridal',
        'location' => 'North Side',
    ]);

    $otherSaloon = Saloon::create([
        'owner_id' => $otherOwner->id,
        'name' => 'Other Beauty',
        'location' => 'West Side',
    ]);

    Service::create([
        'saloon_id' => $firstSaloon->id,
        'name' => 'Hair',
        'price' => 50,
        'duration' => 60,
    ]);

    Service::create([
        'saloon_id' => $firstSaloon->id,
        'name' => 'Makeup',
        'price' => 70,
        'duration' => 90,
    ]);

    Service::create([
        'saloon_id' => $secondSaloon->id,
        'name' => 'Hair',
        'price' => 45,
        'duration' => 60,
    ]);

    Service::create([
        'saloon_id' => $otherSaloon->id,
        'name' => 'Nails',
        'price' => 30,
        'duration' => 30,
    ]);

    BridalPackage::create([
        'saloon_id' => $firstSaloon->id,
        'name' => 'Classic Bride',
        'package_price' => 200,
    ]);

    BridalPackage::create([
        'saloon_id' => $secondSaloon->id,
        'name' => 'Premium Bride',
        'package_price' => 350,
    ]);

    BridalPackage::create([
        'saloon_id' => $otherSaloon->id,
        'name' => 'Other Bride',
        'package_price' => 150,
    ]);

    Booking::create([
        'user_id' => $client->id,
        'saloon_id' => $firstSaloon->id,
        'booking_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'total_price' => 50,
    ]);

    Booking::create([
        'user_id' => $client->id,
        'saloon_id' => $secondSaloon->id,
        'booking_date' => now()->addDays(2)->toDateString(),
        'start_time' => '12:00',
        'end_time' => '13:00',
        'total_price' => 45,
    ]);

    Booking::create([
        'user_id' => $client->id,
        'saloon_id' => $otherSaloon->id,
        'booking_date' => now()->addDay()->toDateString(),
        'start_time' => '14:00',
        'end_time' => '15:00',
        'total_price' => 30,
    ]);

    $response = $this->actingAs($owner, 'sanctum')->getJson('/api/owner/dashboard');

    $response
        ->assertOk()
        ->assertJsonPath('owner_name', 'Frank')
        ->assertJsonPath('total_bookings', 2)
        ->assertJsonPath('total_services', 2)
        ->assertJsonPath('total_saloons', 2)
        ->assertJsonPath('total_packages', 2)
        ->assertJsonCount(2, 'recent_bookings');
});
