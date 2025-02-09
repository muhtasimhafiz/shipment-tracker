<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    //
    protected $fillable = ['shipment_id', 'status', 'description', 'occurred_at', 'raw_data', 'address_from', 'address_to', 'location', 'eta'];

    protected $casts = [
        'occurred_at' => 'datetime',
        'raw_data' => 'array',
        'address_from' => 'array',
        'address_to' => 'array',
        'location' => 'array',
        'eta' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
