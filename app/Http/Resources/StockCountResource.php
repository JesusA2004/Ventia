<?php

namespace App\Http\Resources;

use App\Models\StockCount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockCount */
class StockCountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse->name),
            'started_by_name' => $this->whenLoaded('startedByUser', fn () => $this->startedByUser?->name),
            'completed_by_name' => $this->whenLoaded('completedByUser', fn () => $this->completedByUser?->name),
            'applied_by_name' => $this->whenLoaded('appliedByUser', fn () => $this->appliedByUser?->name),
            'notes' => $this->notes,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'applied_at' => $this->applied_at?->toIso8601String(),
            'items' => StockCountItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
