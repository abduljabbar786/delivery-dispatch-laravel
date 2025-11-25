<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rider extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'phone',
        'status',
        'last_seen_at',
        'latest_lat',
        'latest_lng',
        'latest_pos',
        'battery',
    ];

    protected $hidden = [
        'latest_pos',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'battery' => 'integer',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(RiderLocation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'assigned_rider_id');
    }

    public function currentOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'assigned_rider_id')
            ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'OUT_FOR_DELIVERY'])
            ->latestOfMany();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
