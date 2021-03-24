<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MethodologyResource extends JsonResource
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
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'topics_count' => $this->topics_count,
        ];
    }
}
