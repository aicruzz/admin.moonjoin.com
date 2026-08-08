<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RI.1: restores the audit_logs table.
     *
     * This migration was applied to the development database but its file was
     * never committed, and it never ran in production at all: the production
     * migrations table has no record of it and the table does not exist there.
     * Because AuditLog::record() catches \Throwable by design so auditing can
     * never break a financial operation, every audit write from phases B.1
     * through B.6 has been silently no-opping in production.
     *
     * The original migration identity is reused deliberately. Production has no
     * record of it, so Laravel will execute it there; development already
     * records it, so Laravel will skip it. Both environments converge without a
     * corrective timestamp and without touching the migration history by hand.
     *
     * The schema below reproduces the verified development table exactly,
     * including index names. No foreign keys and no updated_at: audit rows must
     * survive the deletion of whatever they reference, and are never updated.
     *
     * Historical audit records from B.1-B.6 are unrecoverable and are not
     * backfilled.
     */
    public function up(): void
    {
        // Guards the case where the table was created outside the migration
        // history. It cannot mask a genuinely absent table.
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            // No vendor_employee member: employees are recorded as 'vendor' with
            // the real guard preserved in metadata.actor_guard. Extending this
            // enum is deferred to a later phase.
            $table->enum('actor_type', [
                'admin',
                'merchant',
                'partner_api',
                'system',
                'delivery_man',
                'customer',
                'vendor',
            ]);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_label')->nullable();

            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->json('before')->nullable();
            $table->json('after')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_type', 'actor_id'], 'audit_logs_actor_type_actor_id_index');
            $table->index(['subject_type', 'subject_id'], 'audit_logs_subject_type_subject_id_index');
            $table->index(['action', 'created_at'], 'audit_logs_action_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
