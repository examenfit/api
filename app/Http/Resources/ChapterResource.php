<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class ChapterResource extends JsonResource
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
            'title' => $this->title,
            'methodology' => new MethodologyResource($this->whenLoaded('methodology')),
            'parent' => new ChapterResource($this->whenLoaded('parent')),
            'children' => ChapterResource::collection($this->whenLoaded('children')),
            'topics_count' => $this->topics_count,
        ];
    }
}
