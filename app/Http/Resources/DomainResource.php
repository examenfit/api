<?php

namespace App\Http\Resources;

use App\Models\Domain;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
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
            'parent' => new DomainResource($this->whenLoaded('parent')),
            'children' => !isset($this->created_at) && isset($this->resource->toArray()['children'])
                ? DomainResource::collection(Domain::hydrate($this->resource->toArray()['children']))
                : DomainResource::collection($this->whenLoaded('children')),
            'topics_count' => $this->topics_count,
        ];
    }
}
