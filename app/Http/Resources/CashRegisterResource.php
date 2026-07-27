<?php

namespace App\Http\Resources;

use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CashRegister */
class CashRegisterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->whenLoaded('branch', fn () => $this->branch->name),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse?->name),
            'name' => $this->name,
            'code' => $this->code,
            'printer_name' => $this->printer_name,
            'has_cash_drawer' => $this->has_cash_drawer,
            'assigned_user_id' => $this->assigned_user_id,
            'assigned_user_name' => $this->whenLoaded('assignedUser', fn () => $this->assignedUser?->name),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
