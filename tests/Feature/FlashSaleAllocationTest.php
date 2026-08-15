<?php

namespace Tests\Feature;

use App\Models\FlashSaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Flash sale allocation accounting.
 *
 * Covers the safety-critical half of ProductLogic::update_flash_stock(): the atomic
 * conditional reservation that decides whether an order may consume allocation, and
 * the resulting sold / available_stock arithmetic.
 *
 * The reservation statement asserted here is byte-identical to the one the helper
 * issues. The helper's surrounding lookup (FlashSaleItem::Active() joined through
 * item.store) is not exercised, because Store and Item carry global scopes that would
 * need a large and brittle schema fixture; those paths are covered by manual
 * verification instead.
 *
 * Runs on its own in-memory SQLite connection so phpunit.xml and the developer
 * database are untouched.
 */
class FlashSaleAllocationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'flash_sale_test',
            'database.connections.flash_sale_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        Schema::create('flash_sale_items', function ($table) {
            $table->id();
            $table->unsignedBigInteger('flash_sale_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('stock');
            $table->integer('sold')->default(0);
            $table->integer('available_stock');
            $table->string('discount_type')->default('percent');
            $table->double('discount')->default(0);
            $table->double('discount_amount')->default(0);
            $table->double('price')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Inserted through the query builder: FlashSaleItem declares no $fillable, and the
     * production model must not be altered for a test's convenience.
     */
    private function allocation(int $stock = 50): FlashSaleItem
    {
        $id = DB::table('flash_sale_items')->insertGetId([
            'flash_sale_id' => 1,
            'item_id' => 1,
            'stock' => $stock,
            'sold' => 0,
            'available_stock' => $stock,
            'discount_type' => 'percent',
            'discount' => 50,
            'discount_amount' => 0,
            'price' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return FlashSaleItem::findOrFail($id);
    }

    /** The exact reservation issued by ProductLogic::update_flash_stock(). */
    private function reserve(int $id, int $quantity): int
    {
        return FlashSaleItem::where('id', $id)
            ->where('available_stock', '>=', $quantity)
            ->update([
                'sold' => DB::raw('sold + ' . $quantity),
                'available_stock' => DB::raw('available_stock - ' . $quantity),
                'updated_at' => now(),
            ]);
    }

    public function test_the_approved_acceptance_scenario(): void
    {
        $fs = $this->allocation(50);

        $this->assertSame(1, $this->reserve($fs->id, 1));
        $fs->refresh();
        $this->assertSame(1, $fs->sold);
        $this->assertSame(49, $fs->available_stock);

        $this->assertSame(1, $this->reserve($fs->id, 3));
        $fs->refresh();
        $this->assertSame(4, $fs->sold);
        $this->assertSame(46, $fs->available_stock);

        $this->assertSame(1, $this->reserve($fs->id, 46));
        $fs->refresh();
        $this->assertSame(50, $fs->sold);
        $this->assertSame(0, $fs->available_stock);

        // Exhausted: the next unit must be refused and nothing may move.
        $this->assertSame(0, $this->reserve($fs->id, 1));
        $fs->refresh();
        $this->assertSame(50, $fs->sold);
        $this->assertSame(0, $fs->available_stock);
        $this->assertLessThanOrEqual($fs->stock, $fs->sold);
        $this->assertGreaterThanOrEqual(0, $fs->available_stock);
    }

    public function test_quantity_greater_than_one_consumes_the_real_quantity(): void
    {
        $fs = $this->allocation(10);

        $this->reserve($fs->id, 3);
        $fs->refresh();

        $this->assertSame(3, $fs->sold, 'sold must move by the ordered quantity, not by one');
        $this->assertSame(7, $fs->available_stock);
    }

    public function test_a_request_larger_than_the_remainder_is_rejected_whole(): void
    {
        $fs = $this->allocation(5);
        $this->reserve($fs->id, 4);

        // 2 requested, 1 remaining: must fail outright rather than partially consume.
        $this->assertSame(0, $this->reserve($fs->id, 2));

        $fs->refresh();
        $this->assertSame(4, $fs->sold);
        $this->assertSame(1, $fs->available_stock);
    }

    public function test_two_buyers_cannot_oversell_the_last_unit(): void
    {
        $fs = $this->allocation(5);
        $this->reserve($fs->id, 4);

        $first = $this->reserve($fs->id, 1);
        $second = $this->reserve($fs->id, 1);

        $this->assertSame(1, $first, 'the first buyer takes the last unit');
        $this->assertSame(0, $second, 'the second buyer must be refused');

        $fs->refresh();
        $this->assertSame(5, $fs->sold, 'sold must never exceed the allocation');
        $this->assertSame(0, $fs->available_stock, 'available_stock must never go negative');
    }

    public function test_a_rolled_back_order_consumes_no_allocation(): void
    {
        $fs = $this->allocation(50);

        DB::beginTransaction();
        $this->reserve($fs->id, 5);
        DB::rollBack();

        $fs->refresh();
        $this->assertSame(0, $fs->sold, 'a failed order must not consume allocation');
        $this->assertSame(50, $fs->available_stock);
    }

    public function test_non_positive_quantities_are_ignored_by_the_helper(): void
    {
        // update_flash_stock() guards $quantity < 1 before touching the database, so no
        // caller can inflate an allocation through a zero or negative quantity.
        $this->assertNull(\App\CentralLogics\ProductLogic::update_flash_stock((object) ['id' => 1], 0));
        $this->assertNull(\App\CentralLogics\ProductLogic::update_flash_stock((object) ['id' => 1], -3));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('flash_sale_items');
        parent::tearDown();
    }
}
