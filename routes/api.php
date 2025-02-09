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

  // Create new shipment tracking
  Route::post('/', [ShipmentController::class, 'store'])
    ->name('shipments.store');
});
