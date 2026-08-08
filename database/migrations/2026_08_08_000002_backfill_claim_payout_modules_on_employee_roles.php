<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Preserve existing employee capabilities when claim and payout become
     * independent permissions.
     *
     * Before this change, module "order" implicitly granted both Claim Funds and
     * Payout. Introducing the new modules without this backfill would revoke
     * both from every existing employee the moment the code deploys.
     *
     * Rule (approved): a role holding "order" receives "claim" and "payout".
     * A role without "order" receives neither. Effective permissions are
     * therefore identical immediately after deploy, and vendors narrow them
     * deliberately afterwards.
     *
     * Kept separate from the vendor_permissions migration so it can be rolled
     * back on its own. Snapshot employee_roles before deploying: down() removes
     * the two modules from every role, which would also discard any grant a
     * vendor made between deploy and rollback.
     */
    public function up(): void
    {
        DB::table('employee_roles')->orderBy('id')->chunkById(200, function ($roles) {
            foreach ($roles as $role) {
                $modules = json_decode($role->modules ?? '[]', true);
                if (!is_array($modules) || !in_array('order', $modules, true)) {
                    continue;
                }

                $updated = $modules;
                foreach (['claim', 'payout'] as $module) {
                    if (!in_array($module, $updated, true)) {
                        $updated[] = $module;
                    }
                }

                if ($updated !== $modules) {
                    DB::table('employee_roles')
                        ->where('id', $role->id)
                        ->update(['modules' => json_encode(array_values($updated)), 'updated_at' => now()]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('employee_roles')->orderBy('id')->chunkById(200, function ($roles) {
            foreach ($roles as $role) {
                $modules = json_decode($role->modules ?? '[]', true);
                if (!is_array($modules)) {
                    continue;
                }

                $updated = array_values(array_diff($modules, ['claim', 'payout']));

                if ($updated !== $modules) {
                    DB::table('employee_roles')
                        ->where('id', $role->id)
                        ->update(['modules' => json_encode($updated), 'updated_at' => now()]);
                }
            }
        });
    }
};
