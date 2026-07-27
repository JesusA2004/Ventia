<?php

namespace App\Http\Resources;

use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockTransfer */
class StockTransferResource extends JsonResource
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
            'is_cancellable' => $this->status->isCancellable(),
            'origin_warehouse_id' => $this->origin_warehouse_id,
            'origin_warehouse_name' => $this->whenLoaded('originWarehouse', fn () => $this->originWarehouse->name),
            'destination_warehouse_id' => $this->destination_warehouse_id,
            'destination_warehouse_name' => $this->whenLoaded('destinationWarehouse', fn () => $this->destinationWarehouse->name),
            'requested_by_name' => $this->whenLoaded('requestedByUser', fn () => $this->requestedByUser?->name),
            'approved_by_name' => $this->whenLoaded('approvedByUser', fn () => $this->approvedByUser?->name),
            'shipped_by_name' => $this->whenLoaded('shippedByUser', fn () => $this->shippedByUser?->name),
            'received_by_name' => $this->whenLoaded('receivedByUser', fn () => $this->receivedByUser?->name),
            'notes' => $this->notes,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'items' => StockTransferItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
