<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => number_format($this->price_paid, 2),
                'seller_id' => $this->seller_id,
            ],
            'price_paid' => number_format($this->price_paid, 2),
            'seller' => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
            ],
        ];
    }
}
