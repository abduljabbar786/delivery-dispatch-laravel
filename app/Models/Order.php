<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'code',
        'branch_id',
        'customer_name',
        'customer_phone',
        'address',
        'lat',
        'lng',
        'dest_pos',
        'status',
        'picked_up_at',
        'assigned_rider_id',
        'notes',
    ];

    protected $hidden = [
        'dest_pos',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'picked_up_at' => 'datetime',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, 'assigned_rider_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
