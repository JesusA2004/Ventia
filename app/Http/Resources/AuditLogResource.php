<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use App\Support\AuditActionLabels;
use App\Support\PermissionLabels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditLog */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name ?? 'Sistema',
            'company_id' => $this->company_id,
            'company_name' => $this->whenLoaded('company', fn () => $this->company?->name),
            'branch_id' => $this->branch_id,
            'branch_name' => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'module' => $this->module,
            'module_label' => PermissionLabels::group($this->module),
            'action' => $this->action,
            'action_label' => AuditActionLabels::label($this->action),
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'description' => $this->description,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'reason' => $this->reason,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
