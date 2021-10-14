<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
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
            'license_id' => Hashids::encode($this->license_id),
            'role' => $this->role,
            'is_active' => $this->is_active,
            'email' => $this->email,
            'token' => $this->token,
            'user' => new UserResource($this->whenLoaded('user')),
            'privileges' => PrivilegeResource::collection($this->whenLoaded('privileges')),
        ];
    }
}