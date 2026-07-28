<?php

namespace App\Http\Resources;

use App\Models\CashHandover;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CashHandover */
class CashHandoverResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cash_session_id' => $this->cash_session_id,
            'register_name' => $this->whenLoaded('cashSession', fn () => $this->cashSession->register?->name),
            'opening_amount' => $this->whenLoaded('cashSession', fn () => (string) $this->cashSession->opening_amount),
            'branch_name' => $this->whenLoaded('branch', fn () => $this->branch->name),
            'cashier_id' => $this->cashier_id,
            'cashier_name' => $this->whenLoaded('cashier', fn () => $this->cashier->name),
            'approved_by' => $this->approved_by,
            'approver_name' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'expected_cash' => (string) $this->expected_cash,
            'counted_cash' => (string) $this->counted_cash,
            'difference' => (string) $this->difference,
            'denominations' => $this->denominations,
            'cashier_notes' => $this->cashier_notes,
            'supervisor_notes' => $this->supervisor_notes,
            'requested_at' => $this->requested_at->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
