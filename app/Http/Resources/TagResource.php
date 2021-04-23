<?php

namespace App\Http\Resources;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
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
            'level_id' => Hashids::encode($this->level_id),
            'name' => $this->name,
            'level' => new LevelResource($this->whenLoaded('level')),
            'children' => TagResource::collection($this->whenLoaded('children')),
            'topics_count' => $this->when($this->topics_count !== null, $this->topics_count),
            'question_count' => $this->when($this->question_count !== null, $this->question_count),
        ];
    }
}
