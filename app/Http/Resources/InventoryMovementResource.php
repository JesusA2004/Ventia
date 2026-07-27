<?php

namespace App\Http\Resources;

use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryMovement */
class InventoryMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'movement_type' => $this->movement_type->value,
            'movement_type_label' => $this->movement_type->label(),
            'direction' => $this->direction->value,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'previous_stock' => $this->previous_stock,
            'resulting_stock' => $this->resulting_stock,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'performed_by_name' => $this->whenLoaded('performedByUser', fn () => $this->performedByUser?->name),
            'occurred_at' => $this->occurred_at->toIso8601String(),
        ];
    }
}
