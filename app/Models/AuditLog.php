<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Immutable record of a financial or administrative action.
 *
 * The audit_logs table already existed but had no model and no writers. This
 * activates it without altering the schema.
 *
 * Two schema constraints drive the design of record():
 *
 *  - actor_type is an ENUM with no 'vendor_employee' member, and this database
 *    does not run STRICT_TRANS_TABLES, so an out-of-range value would be
 *    silently stored as ''. Actors are therefore mapped onto the existing
 *    members and the precise guard is kept in metadata.actor_guard.
 *  - There is no updated_at column, so Eloquent's default timestamp pair is
 *    disabled and created_at is set explicitly.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $fillable = [
        'actor_type', 'actor_id', 'actor_label', 'action',
        'subject_type', 'subject_id', 'before', 'after',
        'ip', 'user_agent', 'request_id', 'metadata', 'created_at',
    ];

    protected $casts = [
        'actor_id'   => 'integer',
        'subject_id' => 'integer',
        'before'     => 'array',
        'after'      => 'array',
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    /** Values permitted by the actor_type ENUM. */
    public const ACTOR_TYPES = [
        'admin', 'merchant', 'partner_api', 'system',
        'delivery_man', 'customer', 'vendor',
    ];

    /** Actions written in Phase B. Kept explicit so the set stays reviewable. */
    public const ACTION_CLAIM_FUNDS         = 'claim_funds';
    public const ACTION_PAYOUT              = 'payout';
    public const ACTION_ADMIN_WALLET_CREDIT = 'admin_wallet_credit';

    /**
     * Resolve the current actor from whichever guard is authenticated.
     *
     * Returns the ENUM-safe actor_type, the actor id, a human label, and the
     * real guard name. A vendor employee is recorded as actor_type 'vendor'
     * because the ENUM has no member for it; metadata.actor_guard preserves the
     * distinction until the ENUM can be extended in a later phase.
     *
     * @return array{type: string, id: int|null, label: string|null, guard: string}
     */
    public static function resolveActor(): array
    {
        if (auth('admin')->check()) {
            $u = auth('admin')->user();
            return ['type' => 'admin', 'id' => $u->id, 'label' => trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? '')) ?: $u->email, 'guard' => 'admin'];
        }

        if (auth('vendor_employee')->check()) {
            $u = auth('vendor_employee')->user();
            return ['type' => 'vendor', 'id' => $u->id, 'label' => 'Employee: ' . trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? '')), 'guard' => 'vendor_employee'];
        }

        if (auth('vendor')->check()) {
            $u = auth('vendor')->user();
            return ['type' => 'vendor', 'id' => $u->id, 'label' => 'Owner: ' . trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? '')), 'guard' => 'vendor'];
        }

        return ['type' => 'system', 'id' => null, 'label' => null, 'guard' => 'system'];
    }

    /**
     * Write one audit entry.
     *
     * Never throws. A failure to record must not roll back the financial action
     * it describes: the money movement is authoritative and the log observes it.
     * Failures are reported to the application log so they are not invisible.
     */
    public static function record(
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $requestId = null,
        array $metadata = []
    ): void {
        try {
            $actor   = self::resolveActor();
            $request = request();

            // Guard the ENUM explicitly: a value outside the permitted set would
            // be coerced to '' by this database rather than rejected.
            $actorType = in_array($actor['type'], self::ACTOR_TYPES, true) ? $actor['type'] : 'system';

            self::create([
                'actor_type'   => $actorType,
                'actor_id'     => $actor['id'],
                'actor_label'  => $actor['label'] ? mb_substr($actor['label'], 0, 255) : null,
                'action'       => mb_substr($action, 0, 255),
                'subject_type' => $subjectType ? mb_substr($subjectType, 0, 255) : null,
                'subject_id'   => $subjectId,
                'before'       => $before,
                'after'        => $after,
                'ip'           => $request?->ip(),
                // Column is varchar(512) and this database truncates silently.
                'user_agent'   => mb_substr((string) $request?->userAgent(), 0, 512) ?: null,
                'request_id'   => $requestId ? mb_substr($requestId, 0, 64) : null,
                'metadata'     => array_merge(['actor_guard' => $actor['guard']], $metadata),
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('AuditLog::record failed', [
                'action'     => $action,
                'subject_id' => $subjectId,
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
