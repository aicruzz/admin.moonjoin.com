<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rental flash sale campaign.
 *
 * Deliberately rental-owned and separate from flash_sales / flash_sale_items. The
 * shared engine resolves everything through App\Models\Item, while a rental listing
 * is a Vehicle priced on three axes (hourly_price, distance_price, day_wise_price).
 * Forcing vehicles through flash_sale_items would mean rewriting the engine that
 * Food, Grocery and Ecommerce depend on.
 *
 * module_id is the isolation boundary: Car Rental and Short Apt Rental are two
 * modules rows that share the rental architecture, so a campaign belongs to exactly
 * one of them and can never leak into the other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_flash_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id');
            $table->string('title', 255);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_publish')->default(0);
            $table->boolean('status')->default(1);

            // Cost split, mirroring flash_sales. trip_details records the applied
            // discount with discount_on_trip_by, so the split drives accounting
            // attribution rather than a second discount.
            $table->decimal('admin_discount_percentage', 23, 8)->default(0);
            $table->decimal('vendor_discount_percentage', 23, 8)->default(0);

            $table->timestamps();

            // The running-campaign lookup on every price calculation.
            $table->index(['module_id', 'is_publish', 'status'], 'rental_flash_sales_lookup_index');
            $table->index(['start_date', 'end_date'], 'rental_flash_sales_window_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_flash_sales');
    }
};
