<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Saloon extends Model
{
    protected $table = 'saloons';

    protected $fillable = ['owner_id', 'name', 'location', 'description', 'image'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'saloon_id');
    }

    public function bridalPackages(): HasMany
    {
        return $this->hasMany(BridalPackage::class, 'saloon_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'saloon_id');
    }
}
