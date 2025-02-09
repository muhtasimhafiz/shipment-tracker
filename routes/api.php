<?php

// routes/api.php
use App\Http\Controllers\ShipmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('shipments')->group(function () {
  // Get tracking information
  Route::get('/{tracking_number}', [ShipmentController::class, 'show'])
    ->where('tracking_number', '[A-Za-z0-9]+')
    ->name('shipments.show');

  // Get shipment events
  Route::get('/{tracking_number}/events', [ShipmentController::class, 'events'])
    ->where('tracking_number', '[A-Za-z0-9]+')
    ->name('shipments.events');

  // Get all shipments
  Route::get('/', [ShipmentController::class, 'index'])
    ->name('shipments.index');

  // Create new shipment tracking
  Route::post('/', [ShipmentController::class, 'store'])
    ->name('shipments.store');

  // Force refresh tracking
  Route::post('/{tracking_number}/refresh', [ShipmentController::class, 'refresh'])
    ->where('tracking_number', '[A-Za-z0-9]+')
    ->name('shipments.refresh');


});
