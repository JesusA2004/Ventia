<?php

namespace App\Http\Resources;

use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SaleReturn */
class SaleReturnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'sale_id' => $this->sale_id,
            'sale_folio' => $this->whenLoaded('sale', fn () => $this->sale->folio),
            'status' => $this->status,
            'total_refunded' => (string) $this->total_refunded,
            'reason' => $this->reason,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'processed_at' => $this->processed_at->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'sale_item_id' => $item->sale_item_id,
                'product_name' => $item->saleItem->product_name_snapshot,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'total_refunded' => (string) $item->total_refunded,
                'restocked' => $item->restocked,
            ])),
        ];
    }
}
