<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderLocation extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'rider_id',
        'order_id',
        'lat',
        'lng',
        'pos',
        'speed',
        'heading',
        'accuracy',
        'battery',
        'recorded_at',
    ];

    protected $hidden = [
        'pos',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'speed' => 'float',
        'heading' => 'float',
        'accuracy' => 'float',
        'battery' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
