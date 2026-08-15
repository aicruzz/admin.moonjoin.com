<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Rental\Entities\RentalFlashSale;
use Modules\Rental\Entities\RentalFlashSaleVehicle;
use Tests\TestCase;

/**
 * Rental flash sale campaign eligibility, pricing and promotional allocation.
 *
 * Exercises the two rental-owned models that TripController::tripDetails() calls:
 * RentalFlashSaleVehicle::resolveFor() decides eligibility for both the API payload
 * and the booking, and reserve() consumes the allocation atomically inside the trip
 * transaction.
 *
 * The surrounding booking flow (carts, providers, vehicle_identities, trips) is not
 * scaffolded here: those models carry global scopes and a large relational fixture.
 * Their integration is verified by reading the call sites and by manual booking
 * verification.
 *
 * Runs on its own in-memory SQLite connection; phpunit.xml is untouched.
 */
class RentalFlashSaleTest extends TestCase
{
    private const CAR_RENTAL = 3;
    private const SHORT_APT_RENTAL = 4;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'rental_flash_test',
            'database.connections.rental_flash_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        Schema::create('rental_flash_sales', function ($table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_publish')->default(0);
            $table->boolean('status')->default(1);
            $table->decimal('admin_discount_percentage', 23, 8)->default(0);
            $table->decimal('vendor_discount_percentage', 23, 8)->default(0);
            $table->timestamps();
        });

        Schema::create('rental_flash_sale_vehicles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('rental_flash_sale_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->string('discount_type')->default('percent');
            $table->decimal('discount', 23, 8)->default(0);
            $table->string('applies_to')->default('all');
            $table->integer('redemption_cap')->nullable();
            $table->integer('redeemed')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    private function campaign(array $overrides = []): RentalFlashSale
    {
        return RentalFlashSale::create(array_merge([
            'module_id' => self::CAR_RENTAL,
            'title' => 'Weekend Rental Deal',
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
            'is_publish' => 1,
            'status' => 1,
            'admin_discount_percentage' => 100,
            'vendor_discount_percentage' => 0,
        ], $overrides));
    }

    private function attach(RentalFlashSale $campaign, array $overrides = []): RentalFlashSaleVehicle
    {
        return RentalFlashSaleVehicle::create(array_merge([
            'rental_flash_sale_id' => $campaign->id,
            'vehicle_id' => 77,
            'discount_type' => 'percent',
            'discount' => 50,
            'applies_to' => 'all',
            'redemption_cap' => 20,
            'redeemed' => 0,
            'status' => 1,
        ], $overrides));
    }

    // ---------------------------------------------------------------- eligibility

    public function test_a_running_published_campaign_applies(): void
    {
        $this->attach($this->campaign());

        $this->assertNotNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'hourly'));
    }

    public function test_an_expired_campaign_does_not_apply(): void
    {
        $this->attach($this->campaign([
            'start_date' => now()->subDays(3),
            'end_date' => now()->subDay(),
        ]));

        $this->assertNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'hourly'));
    }

    public function test_a_future_campaign_does_not_apply(): void
    {
        $this->attach($this->campaign([
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(3),
        ]));

        $this->assertNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'hourly'));
    }

    public function test_an_unpublished_campaign_does_not_apply(): void
    {
        $this->attach($this->campaign(['is_publish' => 0]));

        $this->assertNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'hourly'));
    }

    public function test_a_disabled_vehicle_row_does_not_apply(): void
    {
        $this->attach($this->campaign(), ['status' => 0]);

        $this->assertNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'hourly'));
    }

    public function test_car_rental_campaign_cannot_leak_into_short_apt_rental(): void
    {
        $this->attach($this->campaign(['module_id' => self::CAR_RENTAL]));

        $this->assertNotNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'hourly'));
        $this->assertNull(
            RentalFlashSaleVehicle::resolveFor(77, self::SHORT_APT_RENTAL, 'hourly'),
            'a Car Rental campaign must never price a Short Apt Rental booking'
        );
    }

    public function test_short_apt_rental_campaign_cannot_leak_into_car_rental(): void
    {
        $this->attach($this->campaign(['module_id' => self::SHORT_APT_RENTAL]));

        $this->assertNotNull(RentalFlashSaleVehicle::resolveFor(77, self::SHORT_APT_RENTAL, 'hourly'));
        $this->assertNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'hourly'));
    }

    public function test_pricing_axis_is_respected(): void
    {
        $this->attach($this->campaign(), ['applies_to' => 'day_wise']);

        $this->assertNotNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'day_wise'));
        $this->assertNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'hourly'));
        $this->assertNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, 'distance_wise'));
    }

    public function test_applies_to_all_covers_every_axis(): void
    {
        $this->attach($this->campaign(), ['applies_to' => 'all']);

        foreach (['hourly', 'distance_wise', 'day_wise'] as $axis) {
            $this->assertNotNull(RentalFlashSaleVehicle::resolveFor(77, self::CAR_RENTAL, $axis), $axis);
        }
    }

    // ------------------------------------------------------------------- pricing

    public function test_percent_discount_matches_rental_semantics(): void
    {
        $row = $this->attach($this->campaign(), ['discount_type' => 'percent', 'discount' => 50]);

        // hourly_price 1000 x 4 hours
        $this->assertSame(2000.0, $row->discountFor(4000.0));
    }

    public function test_amount_discount_never_exceeds_the_price(): void
    {
        $row = $this->attach($this->campaign(), ['discount_type' => 'amount', 'discount' => 9000]);

        $this->assertSame(3000.0, $row->discountFor(3000.0), 'an amount discount is capped at the price');
    }

    public function test_a_percentage_can_never_reach_one_hundred(): void
    {
        $row = $this->attach($this->campaign(), ['discount_type' => 'percent', 'discount' => 150]);

        $this->assertLessThan(4000.0, $row->discountFor(4000.0), 'the rental never becomes free');
    }

    public function test_zero_discount_and_zero_price_are_safe(): void
    {
        $row = $this->attach($this->campaign(), ['discount' => 0]);

        $this->assertSame(0.0, $row->discountFor(5000.0));
        $this->assertSame(0.0, $row->discountFor(0.0));
    }

    // ---------------------------------------------------------------- redemption

    public function test_quantity_one_consumes_one_redemption(): void
    {
        $row = $this->attach($this->campaign(), ['redemption_cap' => 20, 'redeemed' => 0]);

        $this->assertTrue($row->reserve(1));
        $this->assertSame(1, $row->fresh()->redeemed);
    }

    public function test_quantity_three_consumes_three_redemptions(): void
    {
        $row = $this->attach($this->campaign(), ['redemption_cap' => 20, 'redeemed' => 17]);

        $this->assertTrue($row->reserve(3), '17 + 3 exactly fills a cap of 20');
        $this->assertSame(20, $row->fresh()->redeemed);
    }

    public function test_a_quantity_larger_than_the_remainder_is_rejected_whole(): void
    {
        $row = $this->attach($this->campaign(), ['redemption_cap' => 20, 'redeemed' => 17]);

        $this->assertFalse($row->reserve(4), '17 + 4 exceeds a cap of 20');
        $this->assertSame(17, $row->fresh()->redeemed, 'a rejected booking consumes nothing at all');
    }

    public function test_two_bookings_cannot_oversell_the_final_allocation(): void
    {
        $row = $this->attach($this->campaign(), ['redemption_cap' => 5, 'redeemed' => 4]);

        $first = $row->reserve(1);
        $second = $row->reserve(1);

        $this->assertTrue($first, 'the first booking takes the last redemption');
        $this->assertFalse($second, 'the second booking must be refused');
        $this->assertSame(5, $row->fresh()->redeemed, 'redeemed must never exceed the cap');
    }

    public function test_an_uncapped_campaign_never_exhausts(): void
    {
        $row = $this->attach($this->campaign(), ['redemption_cap' => null, 'redeemed' => 0]);

        $this->assertTrue($row->reserve(500));
        $this->assertTrue($row->reserve(500));
        $this->assertSame(1000, $row->fresh()->redeemed);
        $this->assertNull($row->fresh()->remainingRedemptions());
        $this->assertFalse($row->fresh()->isExhausted());
    }

    public function test_a_rolled_back_booking_consumes_no_allocation(): void
    {
        $row = $this->attach($this->campaign(), ['redemption_cap' => 20, 'redeemed' => 0]);

        DB::beginTransaction();
        $this->assertTrue($row->reserve(3));
        DB::rollBack();

        $this->assertSame(0, $row->fresh()->redeemed, 'a failed booking must release the reservation');
    }

    public function test_non_positive_quantities_cannot_inflate_an_allocation(): void
    {
        $row = $this->attach($this->campaign(), ['redemption_cap' => 20, 'redeemed' => 5]);

        $this->assertFalse($row->reserve(0));
        $this->assertFalse($row->reserve(-3));
        $this->assertSame(5, $row->fresh()->redeemed);
    }

    public function test_remaining_and_exhausted_report_correctly(): void
    {
        $row = $this->attach($this->campaign(), ['redemption_cap' => 10, 'redeemed' => 10]);

        $this->assertSame(0, $row->remainingRedemptions());
        $this->assertTrue($row->isExhausted());
        $this->assertFalse($row->reserve(1), 'an exhausted campaign refuses further redemptions');
    }

    // --------------------------------------------- API representation vs booking

    /**
     * A Vehicle whose provider relation is stubbed, so the accessor can be exercised
     * without a vehicles/stores fixture (both carry global scopes).
     */
    private function vehicleFor(int $module_id): \Modules\Rental\Entities\Vehicle
    {
        $store = new \App\Models\Store();
        $store->module_id = $module_id;

        $vehicle = new \Modules\Rental\Entities\Vehicle();
        $vehicle->id = 77;
        $vehicle->hourly_price = 1000;
        $vehicle->distance_price = 200;
        $vehicle->day_wise_price = 8000;
        $vehicle->setRelation('provider', $store);

        return $vehicle;
    }

    public function test_a_percent_campaign_publishes_an_exact_per_unit_flash_price(): void
    {
        $this->attach($this->campaign(), ['discount_type' => 'percent', 'discount' => 50]);

        $payload = $this->vehicleFor(self::CAR_RENTAL)->flash_sale;

        $this->assertSame('unit_price', $payload['discount_applies_to']);
        $this->assertSame(1000.0, $payload['prices']['hourly']['original_price']);
        $this->assertSame(500.0, $payload['prices']['hourly']['flash_price']);
        $this->assertSame(500.0, $payload['prices']['hourly']['discount_amount']);
    }

    public function test_a_percent_flash_price_agrees_with_what_booking_charges(): void
    {
        $row = $this->attach($this->campaign(), ['discount_type' => 'percent', 'discount' => 50]);
        $payload = $this->vehicleFor(self::CAR_RENTAL)->flash_sale;

        // TripController: hourly_price * hours, then the campaign discount.
        $hours = 4;
        $booking_total = 1000 * $hours;
        $charged = $booking_total - $row->discountFor($booking_total);

        // The advertised per-hour flash price, multiplied out, must equal the charge.
        $implied = $payload['prices']['hourly']['flash_price'] * $hours;

        $this->assertSame(2000.0, $charged);
        $this->assertSame($charged, $implied, 'a percentage scales, so per-unit advertising is exact');
    }

    public function test_an_amount_campaign_does_not_publish_a_misleading_per_unit_price(): void
    {
        $this->attach($this->campaign(), ['discount_type' => 'amount', 'discount' => 500]);

        $payload = $this->vehicleFor(self::CAR_RENTAL)->flash_sale;

        $this->assertSame('booking_total', $payload['discount_applies_to']);
        $this->assertSame('amount', $payload['discount_type']);
        $this->assertSame(500.0, $payload['discount'], 'the flat amount is still published');

        // The per-unit price is real; a per-unit flash price is not.
        $this->assertSame(1000.0, $payload['prices']['hourly']['original_price']);
        $this->assertNull($payload['prices']['hourly']['flash_price']);
        $this->assertNull($payload['prices']['hourly']['discount_amount']);
    }

    public function test_the_reported_defect_scenario_can_no_longer_occur(): void
    {
        // hourly_price 1000, 4-hour booking, flat 500 off.
        $row = $this->attach($this->campaign(), ['discount_type' => 'amount', 'discount' => 500]);
        $payload = $this->vehicleFor(self::CAR_RENTAL)->flash_sale;

        $hours = 4;
        $charged = (1000 * $hours) - $row->discountFor(1000 * $hours);

        $this->assertSame(3500.0, $charged, 'the flat amount comes off the total exactly once');
        $this->assertNull(
            $payload['prices']['hourly']['flash_price'],
            'the API must not advertise 500/hour, which would imply 2000 for four hours'
        );
    }

    public function test_amount_campaigns_still_respect_applies_to(): void
    {
        $this->attach($this->campaign(), [
            'discount_type' => 'amount',
            'discount' => 500,
            'applies_to' => 'day_wise',
        ]);

        $payload = $this->vehicleFor(self::CAR_RENTAL)->flash_sale;

        $this->assertSame(['day_wise'], array_keys($payload['prices']));
        $this->assertSame(8000.0, $payload['prices']['day_wise']['original_price']);
        $this->assertNull($payload['prices']['day_wise']['flash_price']);
    }

    public function test_applies_to_all_exposes_every_axis(): void
    {
        $this->attach($this->campaign(), ['discount_type' => 'percent', 'discount' => 25, 'applies_to' => 'all']);

        $payload = $this->vehicleFor(self::CAR_RENTAL)->flash_sale;

        $this->assertSame(['hourly', 'distance_wise', 'day_wise'], array_keys($payload['prices']));
        $this->assertSame(150.0, $payload['prices']['distance_wise']['flash_price']);
        $this->assertSame(6000.0, $payload['prices']['day_wise']['flash_price']);
    }

    public function test_the_api_hides_a_campaign_from_the_wrong_module(): void
    {
        $this->attach($this->campaign(['module_id' => self::CAR_RENTAL]));

        $this->assertNotNull($this->vehicleFor(self::CAR_RENTAL)->flash_sale);
        $this->assertNull($this->vehicleFor(self::SHORT_APT_RENTAL)->flash_sale);
    }

    public function test_the_api_hides_an_exhausted_campaign(): void
    {
        $this->attach($this->campaign(), ['redemption_cap' => 5, 'redeemed' => 5]);

        $this->assertNull(
            $this->vehicleFor(self::CAR_RENTAL)->flash_sale,
            'an exhausted campaign must stop advertising a flash price'
        );
    }

    public function test_the_api_returns_null_when_no_campaign_runs(): void
    {
        $this->assertNull($this->vehicleFor(self::CAR_RENTAL)->flash_sale);
    }

    // ------------------------------------------------------- overlapping campaigns

    public function test_an_overlapping_campaign_for_the_same_vehicle_is_detected(): void
    {
        $existing = $this->campaign(['start_date' => now()->subDay(), 'end_date' => now()->addDays(5)]);
        $this->attach($existing, ['vehicle_id' => 77]);

        $proposed = $this->campaign(['start_date' => now()->addDays(2), 'end_date' => now()->addDays(9)]);

        $this->assertTrue(
            RentalFlashSaleVehicle::hasOverlappingCampaign(77, $proposed),
            'the pricing engine must never have to choose between two campaigns'
        );
    }

    public function test_a_non_overlapping_campaign_is_allowed(): void
    {
        $existing = $this->campaign(['start_date' => now()->subDays(10), 'end_date' => now()->subDays(5)]);
        $this->attach($existing, ['vehicle_id' => 77]);

        $proposed = $this->campaign(['start_date' => now()->addDay(), 'end_date' => now()->addDays(3)]);

        $this->assertFalse(RentalFlashSaleVehicle::hasOverlappingCampaign(77, $proposed));
    }

    public function test_overlap_is_scoped_to_the_module(): void
    {
        $car = $this->campaign(['module_id' => self::CAR_RENTAL, 'start_date' => now(), 'end_date' => now()->addDays(5)]);
        $this->attach($car, ['vehicle_id' => 77]);

        $apt = $this->campaign(['module_id' => self::SHORT_APT_RENTAL, 'start_date' => now(), 'end_date' => now()->addDays(5)]);

        $this->assertFalse(
            RentalFlashSaleVehicle::hasOverlappingCampaign(77, $apt),
            'campaigns in different modules never compete'
        );
    }

    public function test_a_campaign_does_not_overlap_itself(): void
    {
        $campaign = $this->campaign();
        $this->attach($campaign, ['vehicle_id' => 77]);

        $this->assertFalse(RentalFlashSaleVehicle::hasOverlappingCampaign(77, $campaign));
    }

    public function test_a_different_vehicle_is_unaffected_by_an_overlap(): void
    {
        $existing = $this->campaign(['start_date' => now(), 'end_date' => now()->addDays(5)]);
        $this->attach($existing, ['vehicle_id' => 77]);

        $proposed = $this->campaign(['start_date' => now(), 'end_date' => now()->addDays(5)]);

        $this->assertFalse(RentalFlashSaleVehicle::hasOverlappingCampaign(99, $proposed));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('rental_flash_sale_vehicles');
        Schema::dropIfExists('rental_flash_sales');
        parent::tearDown();
    }
}
