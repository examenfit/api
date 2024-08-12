<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivilegeResource extends JsonResource
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
            'action' => $this->action,
            'begin' => $this->begin,
            'end' => $this->end,
            'settings' => $this->settings ?: json_decode('{}'),
            'is_active' => $this->is_active,
            'object_id' => Hashids::encode($this->object_id),
            'object_type' => $this->object_type,
            'user' => new UserResource($this->whenLoaded('user')),
            'privileges' => PrivilegeResource::collection($this->whenLoaded('privileges')),
            'ean' => $this->ean,
            'use_group' => $this->use_group,
        ];
    }
}

