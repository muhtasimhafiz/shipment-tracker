<?php

use App\Mail\LostMail;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Services\ShippoTrackingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    Http::fake();

    cache()->flush();
});

// Valid Tracking Number Tests
test('can fetch tracking info for valid tracking number', function () {

    // replace with a valid tracking number
    // $trackingNumber = '901231231231231232d';
    $shipment = Shipment::factory()->create([
        'tracking_number' => '9012' . uniqid(), // Generate unique tracking number
        'status' => 'CREATED',
        'email' => 'test@test.com',
        'carrier' => 'shippo'
    ]);
    $response = $this->getJson("/api/shipments/{$shipment->tracking_number}");

    $response->assertOk()
        ->assertJsonStructure([
            'shipment' => [
                'tracking_number',
                'status',
                'created_at',
                'updated_at',
                'email',
                'events' => []
            ]
        ]);
});

// Invalid Tracking Number Tests
test('returns error for invalid tracking number', function () {
    // Mock the Shippo API response for invalid tracking number
    Http::fake([
        '*' => Http::response([
            'message' => 'Invalid tracking number'
        ], 404)
    ]);

    $trackingNumber = 'INVALID123';
    $response = $this->getJson("/api/shipments/$trackingNumber");

    $response->assertNotFound()
        ->assertJson(['error' => 'Tracking number not found']);
});


test('tracking events are properly logged in database', function () {
    // Create a test shipment
    $shipment = Shipment::factory()->create([
        'tracking_number' => '9012' . uniqid(), // Generate unique tracking number
        'status' => 'CREATED',
        'email' => 'test@test.com',
        'carrier' => 'shippo'
    ]);


    // Make the API request
    // 2 request are made to create 2 shipment events
    $response = $this->getJson("/api/shipments/{$shipment->tracking_number}");
    $response = $this->getJson("/api/shipments/{$shipment->tracking_number}");

    // Assert response is successful
    $response->assertOk();
    $response = $response->json();
    // Assert all tracking events were logged
    $this->assertDatabaseHas('shipment_events', [
        'shipment_id' => $shipment->id,
        'status' => $response['shipment']['status'],
    ]);

    // Assert events count
    $this->assertGreaterThanOrEqual(1, ShipmentEvent::where('shipment_id', $shipment->id)->count());
});

test('shipment events updating correctly', function () {
    //test shipment
    $shipment = Shipment::factory()->create([
        'tracking_number' => '9012' . uniqid(),
        'status' => 'CREATED',
        'email' => 'test@test.com',
        'carrier' => 'shippo'
    ]);

    // response object
    $mockResponse = (object)[
        'tracking_number' => $shipment->tracking_number,
        'tracking_status' => (object)[
            'status' => 'DELIVERED',
            'status_details' => 'Your shipment has departed from the origin.',
            'status_date' => '2024-03-20T10:00:00Z',
            'location' => (object)[
                'city' => 'Los Angeles',
                'state' => 'CA',
                'zip' => '90001',
                'country' => 'US'
            ]
        ],
        'address_from' => (object)[
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip' => '94103',
            'country' => 'US'
        ],
        'address_to' => (object)[
            'city' => 'Chicago',
            'state' => 'IL',
            'zip' => '60611',
            'country' => 'US'
        ],
        'eta' => '2025-02-09T15:18:33.985Z',
        'tracking_history' => []
    ];

    $ShippoTrackingService = new ShippoTrackingService();
    $ShippoTrackingService->checkStatus($shipment, $mockResponse);

    $this->assertDatabaseHas('shipments', [
        'tracking_number' => $shipment->tracking_number,
        'status' => 'DELIVERED',
    ]);
});

test('notification for lost shipment', function () {
    //test shipment
    $shipment = Shipment::factory()->create([
        'tracking_number' => '9012' . uniqid(),
        'status' => 'CREATED',
        'email' => 'test@test.com',
        'carrier' => 'shippo'
    ]);

    // response object
    $mockResponse = (object)[
        'tracking_number' => $shipment->tracking_number,
        'tracking_status' => (object)[
            'status' => 'UNKNOWN', // this is a lost shipment
            'status_details' => 'The carrier has received the electronic shipment information.',
            'status_date' => '2024-03-20T10:00:00Z',
            'location' => (object)[
                'city' => 'Los Angeles',
                'state' => 'CA',
                'zip' => '90001',
                'country' => 'US'
            ]
        ],
        'address_from' => (object)[
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip' => '94103',
            'country' => 'US'
        ],
        'address_to' => (object)[
            'city' => 'Chicago',
            'state' => 'IL',
            'zip' => '60611',
            'country' => 'US'
        ],
        'eta' => '2025-02-09T15:18:33.985Z',
        'tracking_history' => []
    ];

    $ShippoTrackingService = new ShippoTrackingService();
    $ShippoTrackingService->checkStatus($shipment, $mockResponse);

    Mail::assertSent(LostMail::class, function ($mail) use ($shipment) {
        return $mail->hasTo($shipment->email);
    });

    $this->assertDatabaseHas('shipments', [
        'tracking_number' => $shipment->tracking_number,
        'status' => 'LOST',
    ]);
});
