# Shippo Tracking Service

A Laravel service that integrates with the Shippo API to handle shipment tracking and status updates.

## Overview

This service provides functionality to:

-   Register new shipment tracking
-   Check and update shipment statuses
-   Create tracking events
-   Handle status notifications (e.g., lost shipment emails)

## Features

-   Shipment status tracking
-   Status mapping between Shippo and internal system
-   Event logging for shipment status changes
-   Automatic email notifications for lost shipments
-   Address tracking for shipment origin and destination

## Database

SQLite is used as the database.

## Requirements

-   PHP 8.1 or higher
-   Laravel 10.x
-   Composer
-   Node.js & NPM

## Installation

1. composer install
2. npm install && npm run build
3. php artisan migrate
4. php artisan serve
5. In env file, set the Shippo API key to variable SHIPPO_KEY (TEST API KEY: shippo_test_f5e442b5facf39c6f9061c62aa0fef4a76620d0a)

## Testing commands

1. php artisan test

## Implementation

To test the API O followed this documentation: https://docs.goshippo.com/docs/tracking/tracking/#testing-tracking

I used the following endpoint to create a new shipment:

```
POST {{url}}/api/shipments
```

I used the following endpoint to get the tracking events:

```
GET {{url}}/api/shipments/{{tracking_number}}
```

## Postman Collection

The "Shippo Tracking API" Postman collection is available [here](https://github.com/muhtasimhafiz/shipment-tracker/blob/main/Shippo.postman_collection.json)

## Assumptions

To similuate the live tracking events, I am calling the shippo api https://api.goshippo.com/tracks/

Therefore whenever a user wants to track a shipment, I am generating the tracking information(randomly) from the shippo api asumming that this would be the response in live api. I am storing the response in the database.

Intial event for shipment is assumed as CREATED.

## Supported Statuses

The service supports the following shipment statuses:

-   `CREATED`
-   `DELIVERED`
-   `LOST`
-   `IN_TRANSIT`
-   `CANCELLED`

## Status Mapping

The service automatically maps Shippo statuses to internal status codes:

| Shippo Status | Internal Status |
| ------------- | --------------- |
| PRE_TRANSIT   | CREATED         |
| UNKNOWN       | LOST            |
| DELIVERED     | DELIVERED       |
| TRANSIT       | IN_TRANSIT      |
| RETURNED      | CANCELLED       |

## Events

The service creates events for:

-   Initial shipment registration
-   Status updates

Each event includes:

-   Status
-   Description
-   Location data
-   Address information
-   Raw tracking history
-   ETA

## Error Handling

The service includes error handling for:

-   Failed status updates
-   Failed shipment registration
-   Invalid tracking information

## Development Notes

For testing purposes, the service includes:

-   Random status generation
-   Default transit status for new shipments
-   Mock address data
