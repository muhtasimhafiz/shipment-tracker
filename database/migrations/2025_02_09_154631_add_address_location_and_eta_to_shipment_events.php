<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipment_events', function (Blueprint $table) {
            $table->json('address_from')->nullable();
            $table->json('address_to')->nullable();
            $table->json('location')->nullable();
            $table->timestamp('eta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_events', function (Blueprint $table) {
            $table->dropColumn(['address_from', 'address_to', 'location', 'eta']);
        });
    }
};
