<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ServiceRequest
 */
class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'location'    => $this->location,
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            'quoted_price' => $this->quoted_price,
            'notes'       => $this->notes,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),

            'customer' => $this->whenLoaded('customer', fn () => [
                'id'         => $this->customer->id,
                'first_name' => $this->customer->first_name,
                'last_name'  => $this->customer->last_name,
                'phone'      => $this->customer->phone,
            ]),

            'service_type' => $this->whenLoaded('catalogItem', fn () => [
                'id'   => $this->catalogItem->id,
                'name' => $this->catalogItem->name,
            ]),

            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'year'  => $this->vehicle->year,
                'make'  => $this->vehicle->make,
                'model' => $this->vehicle->model,
                'color' => $this->vehicle->color,
            ]),

            'location_shared_at' => $this->location_shared_at?->toIso8601String(),
        ];
    }
}
