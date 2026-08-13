<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only trail of significant user actions (see AuditLogger). Not
 * scoped by BelongsToCompany on purpose: a Superadministrator needs to see
 * entries across every company they oversee, filtered explicitly rather
 * than through the tenant global scope. Only Superadministrators can reach
 * this model at all — see AuditLogPolicy and the 'audit' route group.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property int|null $company_id
 * @property int|null $branch_id
 * @property string $module
 * @property string $action
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property string $description
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $reason
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 */
#[Fillable([
    'user_id', 'user_name', 'company_id', 'branch_id', 'module', 'action',
    'entity_type', 'entity_id', 'description', 'old_values', 'new_values',
    'reason', 'ip_address', 'user_agent', 'created_at',
])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
