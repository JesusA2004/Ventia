<?php

namespace App\Http\Resources;

use App\Models\CashMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CashMovement */
class CashMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'is_inflow' => $this->type->isInflow(),
            'amount' => (string) $this->amount,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }
}
