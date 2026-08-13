<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Services\ActiveCompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Single write path for the audit trail (see #21/#22 in the UX request this
 * shipped with): every entry gets the acting user, company/branch, a
 * human-readable Spanish description, and an optional before/after diff.
 * Called directly from controllers/actions around the specific operations
 * worth auditing — it is not a generic model-observer that logs everything,
 * since not every field change on every model is worth a row here.
 */
class AuditLogger
{
    private const REDACTED_KEYS = [
        'password', 'password_confirmation', 'token', 'remember_token',
        'current_password', 'supervisor_password', 'api_token',
    ];

    public function __construct(
        private readonly Request $request,
        private readonly ActiveCompanyContext $activeCompany,
    ) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $module,
        string $action,
        string $description,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?int $branchId = null,
    ): AuditLog {
        $user = $this->request->user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'company_id' => $this->activeCompany->companyId(),
            'branch_id' => $branchId,
            'module' => $module,
            'action' => $action,
            'entity_type' => $entity !== null ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'description' => $description,
            'old_values' => $oldValues !== null ? self::redact($oldValues) : null,
            'new_values' => $newValues !== null ? self::redact($newValues) : null,
            'reason' => $reason,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent() !== null ? substr($this->request->userAgent(), 0, 255) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function redact(array $values): array
    {
        return collect($values)->except(self::REDACTED_KEYS)->all();
    }
}
