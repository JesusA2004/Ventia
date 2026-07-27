<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Branch */
class BranchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'phone' => $this->phone,
            'manager_id' => $this->manager_id,
            'manager_name' => $this->whenLoaded('manager', fn () => $this->manager?->name),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'warehouses_count' => $this->whenCounted('warehouses'),
            'registers_count' => $this->whenCounted('registers'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
