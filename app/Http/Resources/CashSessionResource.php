<?php

namespace App\Http\Resources;

use App\Models\CashSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CashSession */
class CashSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'register_id' => $this->register_id,
            'register_name' => $this->whenLoaded('register', fn () => $this->register->name),
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'opened_at' => $this->opened_at->toIso8601String(),
            'opening_amount' => (string) $this->opening_amount,
            'expected_cash' => $this->expected_cash !== null ? (string) $this->expected_cash : null,
            'counted_cash' => $this->counted_cash !== null ? (string) $this->counted_cash : null,
            'difference' => $this->difference !== null ? (string) $this->difference : null,
            'closed_at' => $this->closed_at?->toIso8601String(),
            'closed_by_name' => $this->whenLoaded('closedByUser', fn () => $this->closedByUser?->name),
            'opening_notes' => $this->opening_notes,
            'closing_notes' => $this->closing_notes,
        ];
    }
}
