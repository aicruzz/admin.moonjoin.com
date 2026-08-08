<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capabilities granted to a vendor owner.
 *
 * Sits alongside admin_roles and employee_roles as the third member of the
 * authorization ecosystem, using the same modules JSON array so callers and
 * future maintainers read all three the same way.
 *
 * Every vendor is guaranteed a row: seeded for existing vendors by migration,
 * created automatically for new vendors by Vendor::booted(). There is no
 * runtime defaulting -- a missing row is a deployment fault, and the resolver
 * surfaces it rather than silently granting or denying a financial permission.
 */
class VendorPermission extends Model
{
    protected $table = 'vendor_permissions';

    protected $fillable = ['vendor_id', 'modules'];

    protected $casts = [
        'vendor_id' => 'integer',
        'modules'   => 'array',
    ];

    /**
     * Capabilities a vendor owner holds when their record is first created.
     * Claim is granted; payout is withheld until the owner grants it.
     */
    public const DEFAULT_MODULES = ['claim'];

    /**
     * Vendor-only capabilities offered in the management UI. Extending the
     * platform means adding a member here plus a checkbox -- no migration,
     * no new column, no resolver change.
     */
    public const AVAILABLE_MODULES = ['claim', 'payout'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function grants(string $module): bool
    {
        return in_array($module, (array) $this->modules, true);
    }
}
