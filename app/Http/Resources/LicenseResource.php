<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->hash_id,
            'type' => $this->type,
            'settings' => $this->settings ?: json_decode('{}'),
            'begin' => $this->begin,
            'end' => $this->end,
            'is_active' => $this->is_active,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'seats' => SeatResource::collection($this->whenLoaded('seats')),
        ];
    }
}
