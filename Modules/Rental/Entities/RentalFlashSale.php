<?php

namespace Modules\Rental\Entities;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A rental flash sale campaign, scoped to exactly one module.
 *
 * Car Rental and Short Apt Rental share the rental architecture and are separated
 * only by module_id, so module scoping here is what stops one module's campaign
 * from pricing the other's bookings.
 */
class RentalFlashSale extends Model
{
    use HasFactory;

    protected $table = 'rental_flash_sales';

    protected $fillable = [
        'module_id',
        'title',
        'start_date',
        'end_date',
        'is_publish',
        'status',
        'admin_discount_percentage',
        'vendor_discount_percentage',
    ];

    protected $casts = [
        'module_id' => 'integer',
        'is_publish' => 'integer',
        'status' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'admin_discount_percentage' => 'float',
        'vendor_discount_percentage' => 'float',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(RentalFlashSaleVehicle::class, 'rental_flash_sale_id');
    }

    /** Published and enabled. Mirrors the shared engine's publish lifecycle. */
    public function scopeActive($query)
    {
        return $query->where('is_publish', 1)->where('status', 1);
    }

    /** Inside its configured window right now. Eligibility is booking-time. */
    public function scopeRunning($query)
    {
        $now = now();

        return $query->where('start_date', '<=', $now)->where('end_date', '>=', $now);
    }

    public function scopeModule($query, $module_id)
    {
        return $query->where('module_id', $module_id);
    }
}
