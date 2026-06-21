<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'saloon_id',
        'service_id',
        'bridal_package_id',
        'booking_date',
        'start_time',
        'end_time',
        'total_price',
        'status',
        'client_notes',
        'owner_notes',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function saloon(): BelongsTo
    {
        return $this->belongsTo(Saloon::class, 'saloon_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function bridalPackage(): BelongsTo
    {
        return $this->belongsTo(BridalPackage::class, 'bridal_package_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }
}
