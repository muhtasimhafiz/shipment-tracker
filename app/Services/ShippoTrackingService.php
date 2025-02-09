<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\Shipment;

use Shippo_Track;

class ShippoTrackingService
{
  private $statuses = [
    'SHIPPO_UNKNOWN' => 'LOST',
    'SHIPPO_DELIVERED' => 'DELIVERED',
    'SHIPPO_TRANSIT' => 'TRANSIT',
    'SHIPPO_RETURNED' => 'CANCELLED',
    // 'SHIPPO_CANCELLED' => 'CANCELLED',
  ];

  private $statusMapper = [
    'UNKNOWN' => 'LOST',
    'DELIVERED' => 'DELIVERED',
    'TRANSIT' => 'IN_TRANSIT',
    'RETURNED' => 'CANCELLED',
  ];

  public function __construct() {}

  public function getTracking($trackingNumber)
  {
    $track = Shippo_Track::get_status([
      'id' => "SHIPPO_UNKNOWN",
      'carrier' => 'shippo'
    ]);

    return $track;
  }


  public function checkStatus($shipment)
  {
    $trackingInfo = $this->getTracking($shipment->tracking_number);
    $tracked_status = $this->statusMapper[$trackingInfo->tracking_status->status] ?? $trackingInfo->tracking_status->status;
    if ($tracked_status !== $shipment->status) {
      try {
        $shipment->status = $tracked_status;
        $shipment->save();
        $this->createEvent($shipment, $trackingInfo);
      } catch (\Exception $e) {
        $shipment->refresh();
        throw new \Exception('error updating shipment status');
      }

      if ($tracked_status === LOST) {
        $shipment->sendLostEmail();
      }
    }

    return $shipment;
  }


  public function registerTracking($data)
  {
    $trackingInfo = $this->getTracking($data['tracking_number']);
    try {
      $shipment = Shipment::create([
        'tracking_number' => $data['tracking_number'],
        'carrier' => $data['carrier'],
        'status' => $this->statusMapper[$trackingInfo->tracking_status->status] ?? $trackingInfo->tracking_status->status,
        'email' => $data['email']
      ]);

      $this->createEvent($shipment, $trackingInfo);
    } catch (\Exception $e) {
      if ($shipment ?? false) {
        $shipment->delete();
      }
      throw $e;
    }

    return $shipment;
  }

  public function createEvent($shipment, $trackingInfo)
  {
    $trackingHistory = array_map(function ($historyItem) {
      return $historyItem->__toArray();
    }, $trackingInfo->tracking_history);

    $event = $shipment->events()->create([
      'status' => $trackingInfo->tracking_status->status,
      'description' => $trackingInfo->tracking_status->status_details,
      'occurred_at' => now(),
      'raw_data' => $trackingHistory
    ]);

    return $event;
  }
}
