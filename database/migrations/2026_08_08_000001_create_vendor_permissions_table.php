<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendor-owner capabilities.
     *
     * Owners have no employee_roles row, so employee_module_permission_check()
     * short-circuits to true for them. This table gives owners their own
     * permission store, structurally parallel to admin_roles.modules and
     * employee_roles.modules so the whole authorization ecosystem reads the same
     * way. Deliberately a separate table rather than columns on vendors or
     * stores: it keeps the core tables untouched and makes rollback a clean drop.
     *
     * Column is named modules, not permissions, for consistency with the two
     * sibling tables. Future vendor-only capabilities (settlement_approval,
     * financial_reports, withdrawal_approval, banking, employee_management,
     * invoice_export, tax_reports) are added as array members, never as columns.
     */
    public function up(): void
    {
        Schema::create('vendor_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->unique();
            $table->json('modules')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });

        // Every vendor must always have a row: the resolver performs no runtime
        // defaulting, so a missing row is a deployment fault rather than a
        // permission decision. Defaults follow the frozen role template --
        // claim granted, payout withheld until the owner grants it.
        $defaults = json_encode(['claim']);
        $now = now();

        DB::table('vendors')->orderBy('id')->chunkById(500, function ($vendors) use ($defaults, $now) {
            $rows = [];
            foreach ($vendors as $vendor) {
                $rows[] = [
                    'vendor_id'  => $vendor->id,
                    'modules'    => $defaults,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows) {
                DB::table('vendor_permissions')->insertOrIgnore($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_permissions');
    }
};
