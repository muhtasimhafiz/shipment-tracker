<?php

namespace App\Services;

use App\Models\Shipment;

use Shippo_Track;

enum ShipmentStatus: string
{
  case DELIVERED = 'DELIVERED';
  case LOST = 'LOST';

  case IN_TRANSIT = 'IN_TRANSIT';
  case CANCELLED = 'CANCELLED';
}

class ShippoTrackingService
{
  private $statuses = [
    'SHIPPO_UNKNOWN' => 'LOST',
    'SHIPPO_DELIVERED' => 'DELIVERED',
    'SHIPPO_TRANSIT' => 'IN_TRANSIT',
    'SHIPPO_RETURNED' => 'CANCELLED',
    // 'SHIPPO_CANCELLED' => 'CANCELLED',
  ];

  private $statusMapper = [
    'UNKNOWN' => ShipmentStatus::LOST->value,
    'DELIVERED' => ShipmentStatus::DELIVERED->value,
    'TRANSIT' => ShipmentStatus::IN_TRANSIT->value,
    'RETURNED' => ShipmentStatus::CANCELLED->value,
    // 'LOST' => ShipmentStatus::LOST->value,
  ];

  public function __construct() {}

  public function getTracking($trackingNumber)
  {
    $track = Shippo_Track::get_status([
      'id' => array_rand($this->statuses), // for testing, randomly generating status for status update event
      // 'id' => $trackingNumber, // for live tracking
      'carrier' => 'shippo'
    ]);

    return $track;
  }


  public function checkStatus($shipment, $trackingInfo)
  {

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

      if ($tracked_status === ShipmentStatus::LOST->value) {
        $shipment->sendLostEmail();
      }
    }

    return $shipment;
  }


  public function registerTracking($data)
  {
    // in live api
    // $trackingInfo = Shippo_Track::create([
    //   'tracking_number' => $data['tracking_number'],
    //   'carrier' => $data['carrier'],
    // ])

    try {
      $shipment = Shipment::create([
        'tracking_number' => $data['tracking_number'],
        'carrier' => $data['carrier'],
        'status' => ShipmentStatus::IN_TRANSIT->value, // default status for testing, in live api the status will be taken form $trackingInfo
        'email' => $data['email']
      ]);

      // I am creating an event for the shipment when it is registered because according to the docs, when the tracking number is registered, it returns a tracking event
      // $this->createEvent($shipment, $trackingInfo); //for live api
      $this->createEvent($shipment);
    } catch (\Exception $e) {
      if ($shipment ?? false) {
        $shipment->delete();
      }
      throw $e;
    }

    return $shipment;
  }

  private function toArray($object)
  {
    if (is_object($object) && method_exists($object, '__toArray')) {
      return $object->__toArray();
    }
    return (array) $object;
  }

  public function createEvent($shipment, $trackingInfo = null)
  {

    // default event for the shipment when it is registered
    $trackingHistory = [];
    $event_data = [
      'status' => ShipmentStatus::IN_TRANSIT->value,
      'description' => "Shipment is in transit",
      'raw_data' => $trackingHistory,
      'occurred_at' => now(),
      'address_from' => [
        'city' => 'San Francisco',
        'state' => 'CA',
        'zip' => '94103',
        'country' => 'US'
      ],
      'address_to' => [
        'city' => 'Chicago',
        'state' => 'IL',
        'zip' => '60611',
        'country' => 'US'
      ],
      'location' => [
        'city' => 'San Francisco',
        'state' => 'CA',
        'zip' => '94103',
        'country' => 'US'
      ],
      'eta' => now(),
    ];

    // if tracking info is provided, update the event with the latest tracking info
    if ($trackingInfo) {
      $trackingHistory = array_map(function ($historyItem) {
        return $this->toArray($historyItem);
      }, $trackingInfo->tracking_history);

      $event_data['status'] = $this->statusMapper[$trackingInfo->tracking_status->status] ?? $trackingInfo->tracking_status->status;
      $event_data['description'] = $trackingInfo->tracking_status->status_details;
      $event_data['raw_data'] = $trackingHistory;
      $event_data['address_from'] = $this->toArray($trackingInfo->address_from);
      $event_data['address_to'] = $this->toArray($trackingInfo->address_to);
      $event_data['location'] = $this->toArray($trackingInfo->tracking_status->location);
      $event_data['eta'] = $trackingInfo->eta;
    }

    $event = $shipment->events()->create($event_data);

    return $event;
  }
}
