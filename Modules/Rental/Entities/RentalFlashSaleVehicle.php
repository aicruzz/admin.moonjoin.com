<?php

namespace Modules\Rental\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A vehicle taking part in a rental flash sale campaign.
 *
 * This model owns both halves of the promotion so display pricing and booking
 * pricing cannot disagree: resolveFor() is the single eligibility lookup used by
 * the API payload and by the booking flow, and reserve() is the single atomic
 * redemption used inside the trip transaction.
 *
 * redemption_cap / redeemed are promotional allocation only. Physical availability
 * remains vehicle_identities counted against schedule_at, which this class never
 * reads or writes.
 */
class RentalFlashSaleVehicle extends Model
{
    use HasFactory;

    protected $table = 'rental_flash_sale_vehicles';

    protected $fillable = [
        'rental_flash_sale_id',
        'vehicle_id',
        'discount_type',
        'discount',
        'applies_to',
        'redemption_cap',
        'redeemed',
        'status',
    ];

    protected $casts = [
        'rental_flash_sale_id' => 'integer',
        'vehicle_id' => 'integer',
        'discount' => 'float',
        'redemption_cap' => 'integer',
        'redeemed' => 'integer',
        'status' => 'integer',
    ];

    public const DISCOUNT_TYPES = ['percent', 'amount'];

    public const PRICING_AXES = ['all', 'hourly', 'distance_wise', 'day_wise'];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(RentalFlashSale::class, 'rental_flash_sale_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * The one eligibility lookup.
     *
     * A campaign applies when it is published, enabled, running now, belongs to the
     * booking's module, and covers the rental axis being priced. Eligibility is
     * evaluated at booking time -- the rental schedule itself may fall outside the
     * campaign window.
     *
     * $rental_type is the axis in play ('hourly', 'distance_wise', 'day_wise'); a
     * campaign row set to 'all' covers every axis.
     */
    public static function resolveFor($vehicle_id, $module_id, ?string $rental_type = null): ?self
    {
        if (!$vehicle_id || !$module_id) {
            return null;
        }

        return self::query()
            ->active()
            ->where('vehicle_id', $vehicle_id)
            ->when($rental_type, function ($query) use ($rental_type) {
                $query->whereIn('applies_to', ['all', $rental_type]);
            })
            ->whereHas('flashSale', function ($query) use ($module_id) {
                $query->active()->running()->module($module_id);
            })
            ->first();
    }

    /**
     * Is this vehicle already in another campaign in the same module whose window
     * overlaps the given one?
     *
     * Two overlapping campaigns for one vehicle would leave the pricing engine
     * choosing a winner, so attachment is refused instead. Lives here rather than in
     * the admin controller so the rule is testable and has one definition.
     */
    public static function hasOverlappingCampaign($vehicle_id, RentalFlashSale $flash_sale): bool
    {
        return self::query()
            ->where('vehicle_id', $vehicle_id)
            ->whereHas('flashSale', function ($query) use ($flash_sale) {
                $query->where('id', '!=', $flash_sale->id)
                    ->where('module_id', $flash_sale->module_id)
                    ->where('start_date', '<=', $flash_sale->end_date)
                    ->where('end_date', '>=', $flash_sale->start_date);
            })
            ->exists();
    }

    /** Remaining promotional allocation; null when the campaign is uncapped. */
    public function remainingRedemptions(): ?int
    {
        if ($this->redemption_cap === null) {
            return null;
        }

        return max(0, $this->redemption_cap - $this->redeemed);
    }

    public function isExhausted(): bool
    {
        $remaining = $this->remainingRedemptions();

        return $remaining !== null && $remaining <= 0;
    }

    /**
     * Atomically consume $quantity redemptions.
     *
     * A single conditional UPDATE: the database evaluates the cap while holding the
     * row lock, so two customers competing for the last allocation serialise and the
     * campaign can never be oversold. Read-modify-write would race.
     *
     * Returns true when the allocation was reserved. False means the cap is
     * exhausted (or the quantity is invalid) and the caller must roll the booking
     * back -- the reservation is all-or-nothing, never partial.
     *
     * Callers run inside the trip transaction, so a rollback releases the
     * reservation. A later cancellation does not: see the architecture record.
     */
    public function reserve(int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

        $reserved = self::query()
            ->where('id', $this->id)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('redemption_cap')
                    ->orWhereRaw('redeemed + ? <= redemption_cap', [$quantity]);
            })
            ->update([
                'redeemed' => DB::raw('redeemed + ' . $quantity),
                'updated_at' => now(),
            ]);

        return $reserved > 0;
    }

    /**
     * Discount for a base price, using the same percent/amount semantics as
     * vehicles.discount_type/discount_price and the same guards the provider form
     * enforces: a percentage below 100, and an amount never exceeding the price.
     */
    public function discountFor(float $price): float
    {
        if ($price <= 0 || $this->discount <= 0) {
            return 0.0;
        }

        if ($this->discount_type === 'percent') {
            $percentage = min((float) $this->discount, 99.99);

            return ($price * $percentage) / 100;
        }

        return min((float) $this->discount, $price);
    }
}
