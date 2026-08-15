<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A vehicle participating in a rental flash sale campaign.
 *
 * redemption_cap / redeemed are a PROMOTIONAL ALLOCATION, never physical
 * availability. Rental already models availability as vehicle_identities counted
 * against the requested schedule_at (TripController::tripDetails), and that check
 * stays untouched and authoritative. A booking must satisfy both gates: physical
 * identities available, and promotional redemptions remaining.
 *
 * redeemed is consumed by the booked quantity, because trip_details.quantity
 * genuinely multiplies price in the existing booking flow.
 *
 * No availability, stock, calendar, occupancy or apartment column belongs here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_flash_sale_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_flash_sale_id');
            $table->foreignId('vehicle_id');

            // Same vocabulary and validation semantics as vehicles.discount_type /
            // discount_price, so providers and admins reason about one concept.
            $table->string('discount_type', 100)->default('percent');
            $table->decimal('discount', 23, 8)->default(0);

            // Which rental pricing axis the campaign discounts. Mirrors the
            // rental_type enum used by trips/trip_details, plus 'all'.
            $table->enum('applies_to', ['all', 'hourly', 'distance_wise', 'day_wise'])->default('all');

            // NULL cap = uncapped promotion.
            $table->integer('redemption_cap')->nullable();
            $table->integer('redeemed')->default(0);

            $table->boolean('status')->default(1);
            $table->timestamps();

            // A vehicle may be attached to a campaign only once.
            $table->unique(['rental_flash_sale_id', 'vehicle_id'], 'rental_flash_sale_vehicle_unique');
            // Per-vehicle running-campaign lookup during pricing.
            $table->index(['vehicle_id', 'status'], 'rental_flash_sale_vehicle_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_flash_sale_vehicles');
    }
};
