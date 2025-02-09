<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    //
    protected $fillable = ['shipment_id', 'status', 'description', 'occurred_at', 'raw_data'];
    
    protected $casts = [
        'occurred_at' => 'datetime',
        'raw_data' => 'array'
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
