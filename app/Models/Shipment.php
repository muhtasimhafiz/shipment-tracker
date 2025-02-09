<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\LostMail;

class Shipment extends Model
{
    protected $fillable = ['tracking_number', 'carrier', 'status', 'email'];

    public function events()
    {
        return $this->hasMany(ShipmentEvent::class);
    }

    public function sendLostEmail()
    {
        Mail::to($this->email)->send(new LostMail($this));
    }
}
