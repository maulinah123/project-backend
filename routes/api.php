<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\SaloonController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/saloons', [SaloonController::class, 'index']);
    Route::get('/saloons/{id}', [SaloonController::class, 'show']);

    Route::middleware('role:client')->group(function () {
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/my-bookings', [BookingController::class, 'myBookings']);
        Route::get('/client/dashboard', [ClientDashboardController::class, 'index']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    });

    Route::middleware('role:owner')->group(function () {
        Route::get('/owner/dashboard', [OwnerDashboardController::class, 'index']);
        Route::post('/saloons', [SaloonController::class, 'store']);
        Route::get('/owner/saloons', [SaloonController::class, 'mySaloons']);
        Route::get('/owner/saloons/{id}/services', [SaloonController::class, 'myServices']);
        Route::post('/saloons/{id}/create-service', [SaloonController::class, 'createAndAddService']);
        Route::post('/packages', [PackageController::class, 'store']);
        Route::get('/owner/packages', [PackageController::class, 'myPackages']);
        Route::get('/saloon/bookings', [BookingController::class, 'saloonBookings']);
        Route::patch('/saloon/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    });
    
});
