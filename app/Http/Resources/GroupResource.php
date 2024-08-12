<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
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
            'name' => $this->name,
            'is_active' => $this->is_active,
            'stream_id' => Hashids::encode($this->stream_id),
            //'license_id' => $this->license_id,
            'license' => new LicenseResource($this->whenLoaded('license')),
            'seats' => SeatResource::collection($this->whenLoaded('seats')),
        ];
    }
}
