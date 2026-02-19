<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'total_amount' => number_format($this->total_amount, 2),
            'currency' => $this->currency,
            'status' => $this->status,
            'internal_reference' => strtoupper($this->internal_reference),
            'payment_reference' => strtoupper($this->payment_reference),
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'failed_at' => $this->failed_at?->toDateTimeString(),

            // Include all items in the order
            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
