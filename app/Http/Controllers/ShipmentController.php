<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShippoTrackingService;
use App\Models\Shipment;

class ShipmentController extends Controller
{
    protected $trackingService;

    public function __construct(ShippoTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    public function show($trackingNumber)
    {
        // Validate tracking number
        if (empty($trackingNumber) || !is_string($trackingNumber)) {
            return response()->json(['error' => 'Invalid tracking number'], 422);
        }

        $shipment = Shipment::where('tracking_number', $trackingNumber)->first();
        if (!$shipment) {
            return response()->json(['error' => 'Tracking number not found'], 404);
        }

        $trackingInfo = $this->trackingService->getTracking($shipment->tracking_number);
        $shipment = $this->trackingService->checkStatus($shipment, $trackingInfo);
        return response()->json([
            'shipment' => $shipment->load('events'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|min:5|max:100|unique:shipments,tracking_number',
            'email' => 'required|email|max:100',
            'carrier' => 'required|string|max:100',
        ]);


        $trackingInfo = $this->trackingService->registerTracking($request->input());
        return response()->json($trackingInfo);
    }
}
