<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = ['saloon_id', 'name', 'description', 'price', 'duration', 'image'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function saloon(): BelongsTo
    {
        return $this->belongsTo(Saloon::class, 'saloon_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'service_id');
    }
}
