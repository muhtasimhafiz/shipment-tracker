<?php

namespace Database\Factories;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
  protected $model = Shipment::class;

  public function definition()
  {
    return [
      'tracking_number' => $this->faker->unique()->regexify('[A-Z0-9]{16}'),
      'status' => $this->faker->randomElement(['PRE_TRANSIT', 'IN_TRANSIT', 'DELIVERED']),
      'email' => $this->faker->email(),
      'created_at' => now(),
      'updated_at' => now(),
    ];
  }

}
